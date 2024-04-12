<?php
namespace Otpclasses\Otpclasses;

use Drupal\Core;

class SimpleLogging
{
    //
    // Этот класс содержит чистый PHP без использования битрикс АПИ, для отладки в таких местах,
    // как - cron_events.php
    //
    public $IsStarting;
    public $IsFinish;
    public $IsLogging;
    public $log_date_format;
    public $showTimeEachRow;
    public $ObjectName;
    public $timeZone;
    public $log_name;
    public $log_folder;
    public $ext_logging;
    public $loggingFunction;
    public $documentRoot;
    public $documentRootCheck;
    public $eol;

    public $fullFolderName;
    public $fullNameLog;
    public $fullNameDebugLog;
    public $debugPrefix;
    public $boxLoggingIp;
    public $ipClient;
    public $lang;
    public $codeLang;
    public $sitesBox; // контейнер с сайтами, в котором первый сайт - всегда текущий!
    public $domain;   // текущий домен!

    function __construct( $logName = '/simple_logging.log' )
    {
      $this->timeZone = 'Europe/Kiev';
      $this->sitesBox = [];


      try {
          $langManager = \Drupal::languageManager();
          $this->lang = $langManager->getCurrentLanguage();
          $this->codeLang = $this->lang->getId();
        } catch ( \Exception $e) {
          // Drupal активирован не из под апач ...
          $this->codeLang = 'uk';
        }
        //
        // если запуск не из под апача, а из под крона, то это только одно место в DRUPAL:
        //
        if( ! empty( DRUPAL_ROOT ) ) {

          $this->documentRoot = DRUPAL_ROOT;
          $this->documentRootCheck = realpath( dirname( __FILE__ ) . "/../../../.." );

        } elseif( ! empty( $_SERVER["DOCUMENT_ROOT"] ) ) {

            $this->documentRoot     =  $_SERVER["DOCUMENT_ROOT"];

        } else {

            $this->documentRoot     =  realpath( dirname( __FILE__ ) . "/../../../../../web" );
        }
        $sitesPath = $this->documentRoot . '/sites/sites.php';
        if( is_file( $sitesPath ) )
            include( $sitesPath );  // массив $sites загружается из настроек ДРУПАЛ-а

        if( ! empty( $sites ) ) {

          foreach ($sites as $url => $folder) {

            $langCaption = mb_substr($folder, 1);
            $language = ($langCaption == 'ua') ? 'uk' : $langCaption;

            if ($this->codeLang == $language) {
              // определили текущий язык, а значит и текущий сайт
              $this->domain = $url;
              $this->sitesBox[] = ['folder'=>$folder, 'domain'=>$url, 'lang'=>$language];  // всегда первым в массиве!
              break;
            }
          }

          foreach ($sites as $url => $folder) {

            $langCaption = mb_substr($folder, 1);
            $language = ($langCaption == 'ua') ? 'uk' : $langCaption;

            if ($this->domain != $url) {
              // этого домена в массиве еще нет - добавдяем
              $this->domain = $url;
              $this->sitesBox[] = ['folder'=>$folder, 'domain'=>$url, 'lang'=>$language];
            }
          }
        }
        //
        $this->IsStarting       = false;
        $this->IsFinish         = false;
        $this->SetLogOn();
        $this->showTimeEachRow  = false;
        $this->ObjectName       = '';
        $this->ext_logging      = false;
        $this->loggingFunction  = [];
        $this->log_date_format  = 'dd.mm.yyyy';
        $this->log_name         = $logName;
        $this->log_folder       = "/otp_logs";
        $this->eol              = "\r\n";
        $this->debugPrefix      = '-debug';
        $this->fullFolderName	= $this->documentRoot . '/sites/' . $this->sitesBox[0]['folder'] . $this->log_folder;
        $this->fullNameLog		= $this->fullFolderName . $this->log_name;
        $this->fullNameDebugLog = str_replace( '.log', $this->debugPrefix, $this->fullNameLog ) . '.log';
        $this->boxLoggingIp     = [];
        $this->ipClient         = $this->ClientIp();
    }

    public function __destruct() {

        if( $this->IsStarting && ! $this->IsFinish )
            $this->LoggingFinish();
    }

    public function  SetLogOn( $state=true )  {
        $this->IsLogging        = $state;
    }

    public function SetShowTimeEachRow( $show ) {
        $this->showTimeEachRow				= $show;
    }

    public function  SetObjectName( $Name ) {
        $this->ObjectName       = $Name;
    }


    public function SetLogDateFormat( $format ) {
        $this->log_date_format	= $format;
    }

    public function  SetExternalLogging( $external_logging ) {

        $this->ext_logging      = true;
        $this->loggingFunction  = $external_logging[ 'function' ];
    }

    public function SetLoggingIp( $listIp ) {

        $this->boxLoggingIp     = $listIp;
    }

    public function SearchTextInBoxSimple( $text, $box, &$index=0 )
        //
        //   В $box ищются строка $text по полному совпадению ( строка поиска == строка в контейнере ) .
        //   если найдено хоть одно совпадение - тут-же возвращается TRUE,
        //   иначе все проверяется до конца и возвращается FALSE.
        //
    {
        $result     = false;
        $index      = 0;
        $text       = trim($text);

        if( ! empty( $text ) && ! empty( $box ) ) {

            $lowerField = mb_strtolower( $text );

            foreach( $box as $str ) {

                $str        = mb_strtolower( trim($str) );

                if( $str == $lowerField ) {
                    $result = true;
                    break;
                }

                $index++;
            }
        }

        return( $result );
    }


    public function ClientIp() {

        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {

                $boxKey = explode(',', $_SERVER[$key]);

                $ip = trim( end( $boxKey ) );

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }


    public function  CheckIp() {
      //
      // если $this->boxLoggingIp - пустой, то ничего не проверяется и возвращается разрешение - true
      // если $this->boxLoggingIp - содержит искомый IP, то возвращается разрешение - true
      //

        $result = false;

        if( empty( $this->boxLoggingIp ) )
            $result = true;
        else
        if( $this->SearchTextInBoxSimple($this->ipClient, $this->boxLoggingIp) )
            $result = true;

        return( $result );
    }



    public function  logging_debug( $text )
    //
    // выдать в лог текст или масссив
    //
    {
        if( ! $this->IsLogging )
            return;

        if( ! $this->CheckIp() )
            return;

        if( ! $this->IsStarting && ! $this->IsFinish && ! empty( $text ) )
            $this->LoggingStart();

        if( $this->ext_logging ) {

            call_user_func_array( $this->loggingFunction, [ $text ] );                  // вызов внешней функции для логирования

        } else {

            $full_folder_name	= $this->documentRoot . $this->log_folder;
            $full_log_name		= $full_folder_name . $this->log_name;

            $this->logging( $text, $full_folder_name, $full_log_name );
        }
    }

    private function  LoggingInternal( $text )
    //
    // выдать в лог текст или масссив
    //
    {
        if( ! $this->IsLogging )
            return;

        if( $this->ext_logging ) {

            call_user_func_array( $this->loggingFunction, [ $text ] );                  // вызов внешней функции для логирования

        } else {

            $full_folder_name	= $this->documentRoot . $this->log_folder;
            $full_log_name		= $full_folder_name . $this->log_name;

            $this->logging( $text, $full_folder_name, $full_log_name );
        }
    }


    public function  loggingBackTrace()
    //
    // выдать в лог стек вызова функций
    //
    {
        if( ! $this->IsLogging )
            return;

        $debugInfoAll = debug_backtrace();
        $debugInfo    = [];
        foreach( $debugInfoAll as $itemInfo ) {

            $debugInfo [] = $itemInfo['file'] . ', строка: ' . $itemInfo['line'] . ', функция: ' . $itemInfo['function'] . ', класс: ' . $itemInfo['class'];
        }


        if( $this->ext_logging ) {

            call_user_func_array( $this->loggingFunction, [ $debugInfo ] );      // вызов внешней функции для логирования

        } else {

            $full_folder_name	= $this->documentRoot . $this->log_folder;
            $full_log_name		= $full_folder_name . $this->log_name;

            $this->logging( $debugInfo, $full_folder_name, $full_log_name );
        }
    }


    public function  logging( $text, $full_folder_name, $full_log_name, $onlyTime=true )
    {
        if( ! $this->IsLogging )
            return;

        if ($this->showTimeEachRow) {
            date_default_timezone_set( $this->timeZone );

            if( $onlyTime )
                $dt = date( 'H:i:s' );
            else
                $dt = date( 'd.m.Y H:i:s' );

            if (empty($this->ObjectName))
                $before = $dt . ' : ';
            else
                $before = $dt . ' : ' . $this->ObjectName . ' : ';
        } else {
            $before = '';
        }

        if( file_exists( $full_log_name ) ) {

            $f = fopen( $full_log_name, "a+" );
        } else {
            if( ! is_dir( $full_folder_name ) ) {

                $prava = '777';
                mkdir( $full_folder_name, $prava );
                chmod( $full_folder_name, 0774 );
                chown( $full_folder_name, 'www-data' );
                chgrp( $full_folder_name, 'www-data' );
            }

            $f = fopen( $full_log_name, "w" );
            chmod( $full_log_name, 0664 );
            chown( $full_log_name, 'www-data' );
            chgrp( $full_log_name, 'www-data' );
        }

        if( is_array( $text ) || is_object( $text ) ) {

            if( ! empty( $text ) ) {

                if (!empty($before))
                    fwrite($f, $before);

                fwrite($f, print_r($text, true));
            }
        } else {

            if( ! empty( $text ) )
                fwrite( $f, $before . $text . $this->eol );
            else
                fwrite( $f,  $text . $this->eol );
        }

        fclose( $f );
    }



    public function  LoggingStart()
    //
    // Стартовая запись в лог в формате:
    //
    // ИДЕНТИФИКАТОР ДЛЯ ОБРЕЗАНИЯ ЛОГА
    // DD.MM.YYYY hh:mm:ss ( timestamp ) ( user info )
    //
    // timestamp - указывается если  $this->ShowTimeStamp == true
    // user info - указывается если  $this->showUser == true
    //
    {
        if( ! $this->IsStarting ) {

            $this->IsStarting = true;

            $currTime   = time();

            if ($this->showTimeEachRow)
                $curr_date  = date("d.m.Y", $currTime );
            else
                $curr_date  = date("d.m.Y H:i:s", $currTime );

            $writeText  = '';
            $this->LoggingInternal( $writeText );

            $writeText  = $curr_date;

            $this->LoggingInternal( $writeText );
        }
    }

    public function  LoggingFinish()
    {
        if( ! $this->IsFinish ) {

            $this->IsFinish = true;
        }
    }

    public function FormatMemoryResponse( $bytes, $precision = 2 )
    {
        $units = array("b", "kb", "mb", "gb", "tb");

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return( round($bytes, $precision) . " " . $units[$pow] );
    }

    public function UsedMemoryToLog()
    {
        $this->logging_debug( 'Использовано памяти: ' . $this->FormatMemoryResponse( memory_get_peak_usage() ) );
    }

    public function MemoryLimitToLog()
    {
        $limit  = ini_get('memory_limit');
//      $this->logging_debug( 'Доступно памяти: ' . $this->FormatMemoryResponse( $limit * 1024 ) );
        $this->logging_debug( 'Доступно памяти: ' . $limit );
    }

    public function ReadFixedStringFromEOF( $logName, $stringLength )
        //
        // читает строку заданной длины ( $stringLength ) из конца лога.
        // если ошибка - возвращаем false, иначе прочитанную строку.
        //
    {
        $result             = false;
        $handle             = false;
        $readingOffset      = $stringLength + strlen( $this->eol );

        if( file_exists( $logName ) ) {

            // можете использовать 'rb', чтобы принудительно включить бинарный режим, в котором ваши данные не будут преобразовываться.
            $handle = fopen( $logName, "rb" );
        } else {
            return( $result );
        }

        if( $handle ) {
            //
            // Jump to last character
            //
            fseek( $handle, -$readingOffset, SEEK_END );
            //
            // Read it and adjust line number if necessary
            // (Otherwise the result would be wrong if file doesn't end with a blank line)
            //
            $output = fread( $handle, $stringLength );
            fclose( $handle );
            $result = trim( $output );
        }

        return( $result );
    }

}
