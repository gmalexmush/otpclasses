<?php
namespace Otpclasses\Otpclasses;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Database;
use Symfony\Component\HttpFoundation\Request;
use Otpclasses\Otpclasses\SimpleLogging;
use Otpclasses\Otpclasses\XMLUtility;
use \FluidXml\FluidXml;

class LogUtilities extends SimpleLogging
{
    public static $titleAgentCode  = 'logtitle: ';

    public $cuteForce;
    public $cute_identifier;
    public $cuteTimeStart;
    public $cuteTimeFinish;
    public $num_days_cut;
    public $ShowTimeStamp;
    public $timesAll;
    public $timeStart;
    public $timeFinish;
    public $logFileSizeLimit;
    public $logCode;
    public $logsInfoBlock;
    public $cuteBeModule;
    public $oldLogEnable;
    public $dontCuteLog;
    public $activatedCheckCute;

    public $showUser;
    public $isAnonymouse;
    public $currentUserLogin;
    public $currentUserName;
    public $currentUserAuthorized;
    public $currentUserIdValue;
    public $logIfRegistered;
    public $isOrderCut;

    function __construct( $logName = '/log_utilities.log', $cuteIdentifier = 'Log_Utilities.', $cuteModule = true, $withOldLog = true )
    {
        parent::__construct( $logName );

        $this->ShowTimeStamp    = false;
        $this->logFileSizeLimit = 16 * 1024 * 1024;             // 16 MB

        $this->cuteForce        = false; // в не модуля-агента не резать!
        $this->cute_identifier  = $cuteIdentifier;

        $this->SetCuteTimes('08:00:00', '09:00:00' );

        $this->num_days_cut     = 10;
        $this->timesAll         = 0;
        $this->timeStart        = 0;
        $this->timeFinish       = 0;

        $this->logsInfoBlock    = 'logscontrol';
        $this->logCode          = mb_substr( $logName, 1, -4 );     // отрезаем спереди слэшь и сзади точку с расширением
        $this->cuteBeModule     = $cuteModule;
        $this->oldLogEnable     = $withOldLog;
        $this->SetDontCuteLog( false );

        $this->showUser                 = false;
        $this->RefreshUserData();

        $this->activatedCheckCute       = $this->ReadFixedStringFromEOF( $this->fullNameLog, 1 );

        if( $this->activatedCheckCute   != '-' && $this->activatedCheckCute != '+' )
            $this->activatedCheckCute   = '-';

        $this->logIfRegistered  = false;
        $this->isOrderCut = false;
    }


    public function __destruct() {

        if( $this->IsStarting && ! $this->IsFinish )
            $this->LoggingFinish();
    }


    public function RefreshUserData() {
      $this->isAnonymouse             = \Drupal::currentUser()->isAnonymous();
      $this->currentUserIdValue       = \Drupal::currentUser()->id();
      $this->currentUserAuthorized    = \Drupal::currentUser()->isAuthenticated();
      $this->currentUserLogin         = \Drupal::currentUser()->getAccountName();
      $this->currentUserName          = \Drupal::currentUser()->getDisplayName();
    }

    public function  SetStarting( $state = true ) {
        $this->IsStarting       = $state;
        $this->IsFinish         = $state;
    }

    public function SetNumberDaysCut( $days ) {
        $this->num_days_cut					= $days;
    }

    public function SetLogFileSizeLimit( $limit )  {

        $this->logFileSizeLimit				= $limit;
    }

    public function SetCuteTimes( $start, $finish ) {

        $this->cuteTimeStart    = $start;
        $this->cuteTimeFinish   = $finish;
    }

    public function SetDontCuteLog( $enable = false )  {

        $this->dontCuteLog      = $enable;
    }

    public function SetLoggingIfRegistered( $value = true  ) {

      $this->logIfRegistered  = $value;
    }

  public function SetShowUser( $varShowUser=true )  {

    $this->showUser			= $varShowUser;
  }

  public function  logging_debug( $text, $log_debug = false )
    //
    // выдать в лог текст или масссив
    //
    {
        if( ! $this->IsLogging )
            return;

        if( ! $this->CheckIp() )
            return;

        if( $this->logIfRegistered && empty( $this->currentUserIdValue ) )
          return;

        if( ! $this->IsStarting && ! $this->IsFinish && ! empty( $text ) )
            $this->LoggingStart( $log_debug );

        if( $this->ext_logging ) {

            call_user_func_array( $this->loggingFunction, [ $text ] );                  // вызов внешней функции для логирования

        } else {

            if( ! $log_debug )
                $this->logging( $text, $this->fullFolderName, $this->fullNameLog );
            else
                $this->logging( $text, $this->fullFolderName, $this->fullNameDebugLog );
        }
    }

    private function  LoggingInternal( $text, $log_debug = false )
    //
    // выдать в лог текст или масссив
    //
    {
        if( ! $this->IsLogging )
            return;

        if( $this->ext_logging ) {

            call_user_func_array( $this->loggingFunction, [ $text ] );                  // вызов внешней функции для логирования

        } else {

            if( ! $log_debug )
                $this->logging( $text, $this->fullFolderName, $this->fullNameLog );
            else
                $this->logging( $text, $this->fullFolderName, $this->fullNameDebugLog);

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

            $this->logging( $debugInfo, $this->fullFolderName, $this->fullNameLog );
        }
    }


    public function  logging( $text, $full_folder_name, $full_log_name, $offset = 0, $onlyTime=true )
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

        $resultFseek    = 0;

        if( file_exists( $full_log_name ) ) {

            if( $offset == 0 )
                $f = fopen( $full_log_name, "a+" );
            else {
                $f = fopen( $full_log_name, "r+" );
                $resultFseek    = fseek( $f, $offset );
            }

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

        if( $resultFseek    == 0 ) {

            if (is_array($text) || is_object($text)) {

                if (!empty($text)) {

                    if (!empty($before))
                        fwrite($f, $before);

//                  $writeBox = print_r($text, true);
                    $writeBox = json_encode( $text, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

                    fwrite($f, $writeBox, 655360 );
                }
            } else {

                if (!empty($text))
                    fwrite($f, $before . $text . $this->eol, 655360 );
                else
                    fwrite($f, $text . $this->eol, 655360 );
            }
        }

        fclose( $f );
    }



    public function  LoggingStart( $debugLogging=false )
    //
    // Стартовая запись в лог в формате:
    //
    // ИДЕНТИФИКАТОР ДЛЯ ОБРЕЗАНИЯ ЛОГА
    // DD.MM.YYYY hh:mm:ss ( timestamp )
    //
    // timestamp - указывается если  $this->ShowTimeStamp == true
    //
    {
        if( ! $this->IsStarting ) {

            $this->IsStarting = true;
            $this->MakeStartRecord( $debugLogging );
        }
    }

    public function MakeStartRecord( $debugLogging=false )
    {
        $currTime   = time();
        $curr_date  = date("d.m.Y H:i:s", $currTime );

        $writeText  = '';
        $this->LoggingInternal( $writeText, $debugLogging );

        if ($this->showTimeEachRow) {

            $this->showTimeEachRow = false;

            if( $debugLogging )
                $this->LoggingInternal(self::$titleAgentCode . $this->cute_identifier . $this->debugPrefix, $debugLogging );
            else
                $this->LoggingInternal( self::$titleAgentCode . $this->cute_identifier, $debugLogging );

            $writeText  = $curr_date;

            if( $this->ShowTimeStamp ) {
                $writeText  .= ', ' . $currTime;
            }

            if( $this->showUser ) {
              if ( $this->currentUserIdValue > 0) {
                if( $this->currentUserAuthorized )
                  $strAuthorized = 'авторизован';
                else
                  $strAuthorized = 'не авторизован';

                $writeText .= ', ( ' . $this->currentUserIdValue . ' : ' . $this->currentUserLogin . ' : ' . $this->currentUserName . ' : ' . $strAuthorized . ' )';
              } else {
                if( $this->isAnonymouse )
                  $writeText .= ', ( anonymouse )';
              }
            }

            $this->LoggingInternal( $writeText, $debugLogging );
            $this->WeekDayLogging( $debugLogging );
            $this->showTimeEachRow = true;

        } else {
            if( $debugLogging )
                $this->LoggingInternal(self::$titleAgentCode . $this->cute_identifier . $this->debugPrefix, $debugLogging );
            else
                $this->LoggingInternal(self::$titleAgentCode . $this->cute_identifier, $debugLogging);

            $writeText  = $curr_date;

            if( $this->ShowTimeStamp ) {
                $writeText  .= ', ' . $currTime;
            }

            if( $this->showUser ) {
              if( $this->currentUserIdValue > 0) {

                if( $this->currentUserAuthorized )
                  $strAuthorized = 'авторизован';
                else
                  $strAuthorized = 'не авторизован';

                $writeText .= ', ( ' . $this->currentUserIdValue . ' : ' . $this->currentUserLogin . ' : ' . $this->currentUserName . ' : ' . $strAuthorized . ' )';
              } else {
                if( $this->isAnonymouse )
                  $writeText .= ', ( anonymouse )';
              }
            }

            $this->LoggingInternal( $writeText, $debugLogging );
            $this->WeekDayLogging( $debugLogging );
        }

    }


    public function WeekDayLogging( $debugLogging=false )
    //
    // Запись в лог текущего дня недели
    //
    {

        $weekDay    = intval( date( 'w' ) );

        switch( $weekDay ) {

            case 1:
                $this->LoggingInternal( "Понедельник", $debugLogging );
                break;
            case 2:
                $this->LoggingInternal( "Вторник", $debugLogging );
                break;
            case 3:
                $this->LoggingInternal( "Среда", $debugLogging );
                break;
            case 4:
                $this->LoggingInternal( "Четверг", $debugLogging );
                break;
            case 5:
                $this->LoggingInternal( "Пятница", $debugLogging );
                break;
            case 6:
                $this->LoggingInternal( "Суббота", $debugLogging );
                break;
            case 7:
                $this->LoggingInternal( "Воскресенье", $debugLogging );
                break;

            default:
                $this->LoggingInternal( "Day of week: " . $weekDay, $debugLogging );
        }
    }


    public function  LoggingFinish()
    {
        if( ! $this->IsFinish ) {

            $this->IsFinish = true;
            $this->CheckCuteLog();
            $this->LoggingInternal( $this->activatedCheckCute );
        }
    }


    public function CheckCuteLog( $debugLogging=false )
    {
        $isCuteTime = $this->IsTimeInPeriod( time(), $this->cuteTimeStart, $this->cuteTimeFinish );

        $idCute     = $this->cute_identifier;
        $nameCute   = $this->documentRoot . $this->log_folder . $this->log_name;

        if( $debugLogging ) {
            $idCute     = $this->cute_identifier . $this->debugPrefix;
            $nameCute   = str_replace( '.log', $this->debugPrefix, $nameCute ) . '.log';
        }

        if( $isCuteTime ) {

            if( $this->activatedCheckCute == '-' && $this->cuteForce ) {
                //
                // изначально любой кдасс или скрипт мог самостоятельно подрезать свои логи,
                // но в друпале обнаружилась проблема с кэшэм, и от этого пришлось отказаться.
                // теперь свои логи могут подрезать только модули-агенты!!!
                // в которых флаг: cuteForce == true!
                //
                $this->isOrderCut = false;

                if ($this->showTimeEachRow) {

                    $this->showTimeEachRow = false;

                    $this->CutFileLog($nameCute,
                        $idCute,
                        $this->log_date_format,
                        $this->num_days_cut,
                        time(),
                        $this->logFileSizeLimit,
                        $this->cuteBeModule,
                        $this->oldLogEnable,
                        $this->dontCuteLog,
                        true, $debugLogging);

                    $this->showTimeEachRow = true;

                } else {

                    $this->CutFileLog($nameCute,
                        $idCute,
                        $this->log_date_format,
                        $this->num_days_cut,
                        time(),
                        $this->logFileSizeLimit,
                        $this->cuteBeModule,
                        $this->oldLogEnable,
                        $this->dontCuteLog,
                        true, $debugLogging);
                }

                if( $debugLogging == false && $this->isOrderCut )
                    $this->activatedCheckCute = '+';
            }

        } else {
            $this->activatedCheckCute = '-';
        }

    }



    public function IsTimeInPeriod( $checkTime, $startTime, $finishTime )
    //
    // $checkTime                   - unix time ( вызов time() )
    // $startTime, $finishTime      - строки типа: hh:mm:ss
    //
    // return - true, если время попало в указанный период!
    //
    {
        $result         = true;

        $curentDateWithoutTime  = date( 'Y-m-d' );
        $yyy                    = intval( mb_substr( $curentDateWithoutTime, 0, 4 ), 10 );
        $mmm                    = intval( mb_substr( $curentDateWithoutTime, 5, 2 ), 10 );
        $ddd                    = intval( mb_substr( $curentDateWithoutTime, 8, 2 ), 10 );
//      $this->logging_debug( 'curentDate: ' . $yyy . ' ' . $mmm . ' ' . $ddd );

        $h                      = intval( mb_substr( $startTime, 0, 2 ), 10 );
        $m                      = intval( mb_substr( $startTime, 3, 2 ), 10 );
        $s                      = intval( mb_substr( $startTime, 6, 2 ), 10 );
//      $this->logging_debug( 'start  time: ' . $h . ' ' . $m . ' ' . $s );

        $startUnixTime          = mktime( $h,$m,$s, $mmm, $ddd, $yyy );

        $h                      = intval( mb_substr( $finishTime, 0, 2 ), 10 );
        $m                      = intval( mb_substr( $finishTime, 3, 2 ), 10 );
        $s                      = intval( mb_substr( $finishTime, 6, 2 ), 10 );
//      $this->logging_debug( 'finish time: ' . $h . ' ' . $m . ' ' . $s );

        $finishUnixTime         = mktime( $h,$m,$s, $mmm, $ddd, $yyy );

//      $this->logging_debug( 'checkTime:       ' . $checkTime );
//      $this->logging_debug( 'startUnixTime:   ' . $startUnixTime );
//      $this->logging_debug( 'finishUnixTime:  ' . $finishUnixTime );

        if( $finishUnixTime - $startUnixTime <= 0 )  // если период указан неверно - возвращаем false
            $result = false;
        else {
            if ($finishUnixTime - $checkTime > 0 && $checkTime - $startUnixTime > 0) {
                $result = true;
//              $this->logging_debug( 'режем лог.' );
            } else {
                $result = false;
//              $this->logging_debug( 'не режем лог.' );
            }
        }

        return( $result );
    }

    public function IsDateValid( $str_date, $maket='dd.mm.yyyy' )
    {
        $result = true;

        $y = mb_strpos( $maket,'yyyy' );
        if( $y === false )
            return( false );

        $m = mb_strpos( $maket,'mm' );
        if( $m === false )
            return( false );

        $d = mb_strpos( $maket,'dd' );
        if( $d === false )
            return( false );

        switch( $y ) {
        case 0:
                $del = mb_substr( $maket, 4, 1 );
                $ch1 = mb_substr( $str_date, 4, 1 );
                $ch2 = mb_substr( $str_date, 7, 1 );
                break;
        case 3:
                $del = mb_substr( $maket, 2, 1 );
                $ch1 = mb_substr( $str_date, 2, 1 );
                $ch2 = mb_substr( $str_date, 7, 1 );
                break;
        case 6:
                $del = mb_substr( $maket, 5, 1 );
                $ch1 = mb_substr( $str_date, 2, 1 );
                $ch2 = mb_substr( $str_date, 5, 1 );
                break;
        default:
                $ch1 = '***';
        }

        if( $ch1 != $del || $ch2 != $del )
            $result = false;
        else {

            $yy			= mb_substr( $str_date, $y, 4 );
            $mm			= mb_substr( $str_date, $m, 2 );
            $dd			= mb_substr( $str_date, $d, 2 );

            $yyy		= intval( $yy, 10 );
            $mmm		= intval( $mm, 10 );
            $ddd		= intval( $dd, 10 );

            $result		= mktime( 0, 0, 0, $mmm, $ddd, $yyy );

        }
        return( $result );
    }

    public function IsDateValidVariants( $strDate, $maketBox=[] )
    {
        $result     = false;

        if( empty( $maketBox ) ) {
            $result = $this->IsDateValid( $strDate );
        } else {
            foreach ( $maketBox as $maket ) {
                $result     = $this->IsDateValid( $strDate, $maket );
                if( $result )
                    break;
            }
        }

        return( $result );
    }

    public function CutFileLog( $file_name,
                                $identifier,
                                $dt_format,
                                $num_days_cut,
                                $current_date,
                                $logSizeLimit = 16777216,   // 16*1024*1024 = 16 Mbyte
                                $cuteModule=false,
                                $withOld = false,
                                $dontCute = false,
                                $truncateLog = false,
                                $debugLogging = false )
    //
    // обрезает файл логов, если он длинее чем указанное количество дней.
    //
    // $file_name		- полное имя файла лога
    // $identifier		- строковый идентификатор агента
    // $dt_format		- формат даты. год полагается 4-х начным! месяцы и дни - 2-х значными. разделитель не имеет значения какой.
    // $num_days_cut	- через сколько дней делать обрезание (обычно - 180 дней, т.е. грубо - пол года)
    // $current_date	- дата в секундах (текущая), относительно которой отсчитывать через сколько дней обрезать логи
    // $cuteModule      - если истина, то усечение лога делается специальным модулем.
    // $withOld         - переименовывать обрезок в old.log
    // $dontCute        - не обрезать вообще!
    // $truncateLog     - если TRUE, не создавать BAK файл, а просто обнулять лог
    //
    // при вызове из модуля который сам режет свой лог:
    // $cuteModule=false, $withOld = true, $dontCute = false, $truncateLog = true
    //
    // return			true, если обрезание произошло и false, если нет
    //
    {
        $res_cut = false;
        $f  = false;

        if( $dontCute ) {
            $this->logging_debug('Обрезка лога запрещена!'  );
            return (false);
        }
        date_default_timezone_set($this->timeZone);

        $len_buf    = 8192;
        $numDaysCut = intval( $num_days_cut, 10 );
//      $this->logging_debug('количество дней обрезки: ' . $numDaysCut );

        $str_date   = date('d.m.Y', $current_date - $numDaysCut * 86400);

        if( ! $cuteModule ) {
            //
            $actual_strings = [];
            $this->logging_debug('Попытка обрезания лога по дате: ' . $str_date, $debugLogging );
            //
            // получим имя для промежуточного файла лога ( "имя_лога"-cut.log )
            // резаться может и не текущий лог, а какой-то внешний!
            //
            $preLogName = mb_substr( $file_name, 0, -4 );       // имя без расширения и точки
            $extLogName = mb_substr( $file_name, -4, 4 );       // точка и расширение
            $newLogName = $preLogName . '-cut' . $extLogName;
            $oldLogName = $preLogName . '-old' . $extLogName;
            $bakLogName = $preLogName . '-bak' . $extLogName;

//          $this->logging_debug('Обрезок:    ' . $newLogName, $debugLogging );
//          $this->logging_debug('Старый лог: ' . $oldLogName, $debugLogging );

            if (file_exists($newLogName)) {
                //
                // если найден промежуточный файл, то проверяем, он в работе или брошенный ( если время модификации
                // превышает 86400 секунд, тоесть - сутки )
                //
                $fileModifyTime = filemtime($newLogName);
                $currentDateTime = time();
                $logDays = $currentDateTime - $fileModifyTime - 86400;

                $this->logging_debug('Найден недорезанный файл лога! Последняя модификация: ' . ($logDays + 86400) . ' секунд назад.', $debugLogging);

                if ($logDays > 0) {
                    //
                    // найден недоусеченный лог. Загружаем его в массив лога и добавляем строку "восстановления"
                    //
                    $fileCut = fopen($newLogName, "a+");

                    while (($str = fgets($fileCut, $len_buf)) !== false) {
                        $actual_strings [] = $str;
                    }
                    $actual_strings [] = '<<<<<<<<<< восстановленный после неудачной обрезки лог <<<<<<<<<<' . $this->eol . $this->eol;

                    fclose($fileCut);

                } else {

                    $this->logging_debug('процесс усекания уже начат!', $debugLogging);
                    return (false);
                }
            } else {
//              $this->logging_debug( 'Файл усечки лога не найден!', $debugLogging );
            }

            $this->timesAll = 0;
            $this->timeStart = 0;
            $this->timeFinish = 0;

            gc_collect_cycles();

            $this->logging_debug('', $debugLogging);
//          $this->logging_debug( "Старт обрезки лога", $debugLogging );
            //
            //
            //
            $res_cut = false;
            $is_cut = false;
            $resultFgets = false;
            $resultBox = [];
            //
            // Определим размер лога и не превышает ли он заданное значение - $logSizeLimit
            //
            $logSize = filesize($file_name);
            $f = fopen($file_name, "a+");
            if( empty( $f ) ) {
              $this->logging_debug('', $debugLogging );
              $this->logging_debug('ошибка открытия файла лога: ' . $file_name, $debugLogging );
              return( $res_cut );
            }
            //
            $delta = $logSizeLimit - $logSize;

            if ($delta < 0) {

                $this->logging_debug('Превышен лимит, меняем позицию в файле. размер лога: ' . $logSize, $debugLogging );
                //
                // позиционируем смещение указателя файла таким образом, чтобы оставшийся кусок лога не превышал
                // наше ограничение ( от конца файла на $logSizeLimit байт! ).
                //
                fseek($f, -$logSizeLimit, SEEK_END);
                //
                // Ищем место даты усечки лога, с последующим позиционированием в найденную позицию в файле.
                //
                $positionCutoff = $this->SearchDatesCutoff($identifier, $dt_format, $current_date,
                    $numDaysCut,
                    $f,
                    $len_buf,
                    $resultBox);

                if ($positionCutoff) {

                    fseek($f, $positionCutoff);

                } else {
                    //
                    // Если дата усечки не найдена, то просто устанавливаемся на начало следующей строки
                    //
                    fseek($f, -$logSizeLimit, SEEK_END);
                    fgets($f, $len_buf);                          // становимся на следующую строку
                }

                $is_cut = true;

            } else {
                //
                // Лог лимит не превышает.
                // Ищем место даты усечки лога, с последующим позиционированием в найденную позицию в файле.
                //
                $positionCutoff = $this->SearchDatesCutoff($identifier, $dt_format, $current_date,
                    $numDaysCut,
                    $f,
                    $len_buf,
                    $resultBox);

                if ($positionCutoff) {

                    fseek($f, $positionCutoff);
                    $is_cut = true;
                } else {
                    $this->logging_debug('Дата усечки лога не найдена: ' . $str_date, $debugLogging );
                }
            }

            $days       = $resultBox['days'];
            $str_date   = $resultBox['date_cutoff'];

        } else {
            $is_cut     = true;
        }

//      $this->logging_debug( $resultBox, $debugLogging );

        if( $is_cut ) {
            //
            // позиция усечки лога определена
            //
          if( $cuteModule ) {
            $this->logging_debug('Готовим лог к обрезанию модулем.' );
//
// https://blog.birk-jensen.dk/drupal-setting-the-date-field-value
//
//          $requestedTime = \Drupal::time()->getRequestTime();
//          $currentDateTime = new DrupalDateTime();
//          $currentDateTime->setTimezone(new \DateTimeZone( DateTimeItemInterface::STORAGE_TIMEZONE ));
//          $currentDtime = $currentDateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

//          $currentDateTime = (new \Drupal\Core\Datetime\DrupalDateTime('now', new \DateTimeZone('UTC')));
//          $currentDtime = $currentDateTime->format('Y-m-d\TH:i:s');

            $currentDateTime  = new DrupalDateTime( 'now', new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE ) );
            $currentDtime     = $currentDateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT );
            $currentDate  = $currentDateTime->format(DateTimeItemInterface::DATE_STORAGE_FORMAT );

            if( $debugLogging ) {
              $codeElement = $this->logCode . $this->debugPrefix;
              $idCute      = $this->cute_identifier . $this->debugPrefix;
            } else {
              $codeElement = $this->logCode;
              $idCute      = $this->cute_identifier;
            }

            $properties = [
              'field_code100'               => $codeElement,
              'field_sorting'               => 1,
              'field_postindex5'            => 'Y',                     // LOG_JOBS
              'field_postindex6'            => 'cute',                  // LOG_CODE
              'field_age'                   => $this->num_days_cut,     // LOG_CUTE_DAYS
              'field_codefolder'            => $this->log_date_format,  // LOG_DATE_FORMAT
              'field_actionstart'           => $currentDtime,           // LOG_ACTIVATION_DATETIME ( datetime )
              'field_actionstartreal'       => $currentDtime,           // LOG_CHANGED_DATETIME ( datetime )
              'field_agentstartdate'        => $currentDate             // LOG_JOBS_DATE ( date )
            ];

            $resultGet = $this->GetElementByCode(
              $element,
              $this->logsInfoBlock,
              $codeElement,
              [
                'field_postindex5',       // LOG_JOBS
                'field_agentstartdate',   // LOG_JOBS_DATE
                'field_actionstart',      // LOG_ACTIVATION_DATETIME
                'field_actionstartreal'   // LOG_CHANGED_DATETIME
              ] );

            if( empty( $element ) ) {  // ничего не прочлось возможно ошибка, возможно нет соединения с бд

              $resultInsert   = $this->InsertNewElementToBlock(
                $this->logsInfoBlock,
                $idCute,
                $properties,
                'Y' );

              if( empty( $resultInsert[ 'errorCode' ] ) ) {
                $this->logging_debug('' );
                $this->logging_debug('Добавлен новый элемент отслеживания лога: ' . $codeElement . '.log, id: ' . $resultInsert[ 'element_id' ], $debugLogging );
              } else {
                $this->logging_debug('' );
                $this->logging_debug('Ошибка добавления элемента отслеживания лога: ' . $codeElement . '.log', $debugLogging );
              }
            } else {
              $this->logging_debug('прочитаны поля: ' , $debugLogging );
              $this->logging_debug( $element , $debugLogging );
              date_default_timezone_set( 'UTC' );

              $this->logging_debug('' );
              $this->logging_debug('Дата последней обрезки: ' . $element['PROPERTIES']['field_agentstartdate'], $debugLogging );
              $jobsDate           = $this->DateCreateFromPopularFormat( $element['PROPERTIES']['field_agentstartdate'] ); // LOG_JOBS_DATE
//            $this->logging_debug('Дата последней обрезки: ' . date( 'd-m-Y H:i:s', $element['PROPERTIES']['field_agentstartdate'] ), $debugLogging );
//            $jobsDate           = $element['PROPERTIES']['field_agentstartdate']; // LOG_JOBS_DATE

              $currentDate        = intval( 86400 * floor( ( time() )/86400 ), 10 );
              $deltaDate          = $currentDate - $jobsDate;

              date_default_timezone_set( $this->timeZone );

              $this->logging_debug('Разность дат в секундах: ' . $deltaDate, $debugLogging );

              if( $element['PROPERTIES']['field_postindex5'] != 'Y' && $deltaDate > 0 ) {   // LOG_JOBS
                //
                // устанавливаем задание на обрезку данного лога
                //
                $this->logging_debug('устанавливаем задание на обрезку лога: ' . $codeElement, $debugLogging );

                $element['PROPERTIES']['field_postindex5'] = 'Y';   // LOG_JOBS
                $properties   = $element['PROPERTIES'];
                $this->logging_debug('меняем поля: ' , $debugLogging );
                $this->logging_debug( $properties , $debugLogging );

                $this->isOrderCut = ! $this->ModifyPropertyValues( $element['FIELDS']['nid'], $properties);
                //
                // успешно проставили статус на обрезание лога
                //

                $this->logging_debug('', $debugLogging );
                $this->logging_debug('Установлен заказ обрезки лога: ' . $codeElement, $debugLogging );

              } else {

//                      $this->logging_debug('Отказ обрезки лога: ' . $codeElement );
//                      $this->logging_debug( $element );
//                      $this->logging_debug('Разность между текущей датой и датой обрезки лога в секундах: ' . $deltaDate . ', а должна быть > 0' );
              }
            }

          } else {
            //

            $res_put = false;
            $res_cut = false;
            //
            $this->logging_debug('загружаем остаток строк лога в буферный массив!', $debugLogging);
            //
            // в массиве возможно уже содержится найденный усеченный лог который был "потерян"
            //
            $this->timeStart = time();

            while (($str = fgets($f, $len_buf)) !== false) {
              $actual_strings [] = $str;
              $resultFgets = true;
            }

            $this->timeFinish = time();

            if ($resultFgets) {
              //
              // если загрузка строк прошла успешно, то:
              //
              if ($truncateLog) {
                //
                // обнуляем файл и отдаем его заполнять другим процессам!
                //
                $res_cut = ftruncate($f, 0);                // обрезаем файл до 0
                fclose($f);                                      // отдаем его заполнять другим процессам!
              } else {
                $res_cut = true;
                fclose($f);
                rename($file_name, $bakLogName);
                $this->logging_debug('другие процессы создадут файл заново!', $debugLogging);
              }
              //
              // Сбрасываем всю накопленную в массиве $actual_strings информацию в "-cut.log" файл!
              //
              $this->logging_debug('Сбрасываем всю накопленную в массиве информацию в файл: ' . $newLogName, $debugLogging);

              $f = fopen($newLogName, "w");
              chmod($newLogName, 0664);
              chown($newLogName, 'www-data');
              chgrp($newLogName, 'www-data');
              //
              // пишем в него все что прочли!
              //
              $res_put = false;
              foreach ($actual_strings as $strings) {

                if (!empty($strings)) {

                  $res_put = fwrite($f, $strings);
                  if (empty($res_put)) {

                    $this->logging_debug('Ошибка обрезания лога во время записи строки: >' . $strings . '<', $debugLogging);
                    break;
                  }
                }
              }

              fclose($f);
              //
              // "-cut.log" файл записан и закрыт. свободен.
              //
              if ($withOld) {

                $this->logging_debug('Переименовываем обрезок в OLD - лог: ' . $oldLogName, $debugLogging);
                rename($newLogName, $oldLogName);

              } else {
                $ostatok = file($file_name);

                $this->logging_debug('Переименовываем обрезок в лог: ' . $file_name, $debugLogging);
                rename($newLogName, $file_name);

                $f = fopen($file_name, "a+");
                //
                // дописываем остаток в прежний лог
                //
                $resOstatok = false;
                foreach ($ostatok as $strings) {

                  if (!empty($strings)) {

                    $resOstatok = fwrite($f, $strings);
                    if (empty($resOstatok)) {

//                                  $this->logging_debug('Ошибка обрезания лога во время записи строки: >' . $strings . '<', $debugLogging );
                      break;
                    }
                  }
                }

              }
            }

            if ($res_put && $res_cut) {
//            $this->logging_debug( $resultBox['messages'], $debugLogging );
              $this->logging_debug('Обрезан лог: ' . $file_name . ', по дате: ' . $str_date . ', текущее время: ' . date("d.m.Y H:i:s"), $debugLogging);
            } else {
              $this->logging_debug('Ошибка обрезания лога по дате: ' . $str_date, $debugLogging);
            }

          }




        } else {
            $this->logging_debug( 'Усекать лог нет необходимости, количество дней в логе меньше усекаемого: ' . $days, $debugLogging );
        }

        if( is_resource( $f ) )
          fclose( $f );

//      $this->logging_debug( 'Сообщения в поиске даты: ', $debugLogging );
//      $this->logging_debug(  $resultBox['messages'], $debugLogging );

        $this->timesAll = $this->timeFinish - $this->timeStart;
//      $this->logging_debug( 'Общее время чтения строк: ' . $this->timesAll, $debugLogging );

        return( $res_cut );
    }


  public function DateCreateFromPopularFormat( $value, $roundByDays = false)
    //
    // $value:  дата в виде строки, любого PHP формата ( из списка популярных )
    // возвращает timestamp этой даты,
    // либо 0 если не смогло подобрать формат даты PHP.
    //
  {
    $popularDateFormats   = [
      'Y.m.d\TH:i:s',
      'Y.m.d H:i:s',
      'd.m.Y H:i:s',
      'm.d.Y H:i:s',
      'Y-m-d\TH:i:s',
      'Y-m-d H:i:s',
      'd-m-Y H:i:s',
      'm-d-Y H:i:s',
      'Y/m/d\TH:i:s',
      'Y/m/d H:i:s',
      'd/m/Y H:i:s',
      'm/d/Y H:i:s',
      'Y.m.d',
      'd.m.Y',
      'm.d.Y',
      'Y-m-d',
      'd-m-Y',
      'm-d-Y',
      'Y/m/d',
      'd/m/Y',
      'm/d/Y'
    ];

    return( $this->DateCreateFromSomeFormat( $popularDateFormats, $value, $roundByDays ) );
  }

  public function DateCreateFromSomeFormat( $boxFormat, $value, $roundByDays = false)
    //
    // $value:  дата в виде строки, любого PHP формата ( из списка заданного в $boxFormat )
    // возвращает timestamp этой даты,
    // либо 0 если не смогло подобрать формат даты PHP из заданного списка.
    //
  {

    $timeStamp  = 0;

    foreach ( $boxFormat as $format ) {

      $timeStamp  = $this->DateCreateFromFormat( $format, $value, $roundByDays );

//          $this->logging_debug( "date: " . $value . ', format: ' . $format . ', time: ' . $timeStamp );

      if( $timeStamp > 0 )
        break;
    }

    return( $timeStamp );
  }

  public function DateCreateFromFormat( $format, $value, $roundByDays = false )
    //
    //  $this->logging_debug( "Unixtime (timestamp):      " . date('U', $result) );
    //  $this->logging_debug( "YYYY-MM-DD HH:MM:SS+0X:00: " . date('Y.m.d H:i:s P', $result) );  // YYYY-MM-DD HH:MM:SS+0X:00
    //  $this->logging_debug( "YYYY-MM-DDTHH:MM:SS+0X00:  " . date('Y-m-d\TH:i:sO', $result) );  // YYYY-MM-DDTHH:MM:SS+0X00
    //  $this->logging_debug( "YYYY-MM-DDTHH:MM:SS.msec:  " . date('Y-m-d\TH:i:s.u', $result) ); // YYYY-MM-DDTHH:MM:SS.00
    //  $this->logging_debug( "YYYY-MM-DDTHH:MM:SS+0X:00: " . date('c', $result) );              // YYYY-MM-DDTHH:MM:SS+0X:00
    //  $this->logging_debug( "YYYY-MM-DDTHH:MM:SS+0X00:  " . date(DATE_ISO8601, $result) );     // YYYY-MM-DDTHH:MM:SS+0X00
    //
    // Метод возвращает timestamp указанной даты ($value), которая соответствует указанному формату ($format)
    // если указанная дата содержит устаревший фомат временной зоны - 'Europe/Kiev', то он заменяется на
    // новый (правильный) формат временной зоны. И если в формате даты отсутствует директива временной зоны - 'P',
    // то она добавляется в формат даты, чтобы преобразование даты в timestamp произошло правильно.
    // В случае ошибки возвращается - 0.
    //
  {
    $tzone          = '';
    $timeZoneKiev   = 'Europe/Kiev';
    $position       = mb_strpos( $value, $timeZoneKiev );

    if( $position !== false ) {

      //
      // получить смещение в часах для часового пояса
      //
      $onlyDate       = mb_substr( $value, 0, 19 );
      $dateCurrent    = new \DateTime( $onlyDate, new \DateTimeZone( $timeZoneKiev ) );

//    $this->logging_debug( "dateCurrent: "  );
//    $this->logging_debug( $dateCurrent );
//
//    DateTime Object(
//              [date] => 2021-03-18 14:38:07.000000
//              [timezone_type] => 3
//              [timezone] => Europe/Kiev
//            )

      $offset         = $dateCurrent->getOffset()/60/60;

//    $this->logging_debug( "onlyDate:    " . $onlyDate );
//    $this->logging_debug( "dateCurrent: " . $dateCurrent->format( $format ) );
//    $this->logging_debug( "offset:      " . $offset );

      if( $offset > 0 ) {
        $tzone      = '+0' . $offset . ':00';
      } else {
        $tzone      = '-0' . $offset . ':00';
      }

      $position   = mb_strpos( $format, ' P' );

      if( $position === false )
        $format .= ' P';
      //
      // меняем старый формат часового пояса, на новый в строке времени
      //
      $stringDate     = str_replace( $timeZoneKiev, $tzone, $value );
      $stringDate     = trim( $stringDate );

    } else {
      $stringDate     = $value;
    }

    $position       = mb_strpos( $format, $timeZoneKiev );

    if( $position !== false ) {

      $format     = str_replace( $timeZoneKiev, '', $format );
      $format     = str_replace( '  P', ' P', $format );
      $format     = trim( $format );
    }

//  $this->logging_debug( "format:     " . $format );
//  $this->logging_debug( "stringDate: " . $stringDate );

    $dateValue      = \DateTime::createFromFormat( $format, $stringDate );

    if( ! empty( $dateValue ) ) {
      $result         = $dateValue->getTimestamp();

      if( $roundByDays ) {
        $resultOffset   = $dateValue->getOffset() / 60 / 60;
        $result         = $this->RoundDateByDays( $result, $resultOffset ); // 00:00:00
      }

    } else {
//    $this->logging_debug( "Получена пустая дата: " . $dateValue );
      $result         = 0;
    }

    return( $result );
  }


    public function InsertNewElementToBlock( $type, $name, $props, $active='Y', $idUser = 0 )
    //
    // https://mycode.blog/lakshmi/how-create-entities-node-user-term-programmatically-drupal-89
    // https://www.drupal.org/docs/drupal-apis/entity-api/working-with-the-entity-api
    // https://niklan.net/blog/drupal-8-how-create-entities-programmatically
    //
    // Добавление новой ноды ( нового элемента в инфоблок типа: $type )
    // $active = 'Y' - status ноды активный,
    // $idUser = 0   - использовать id текущего пользователя
    //
    // если успешно, return: ['errorCode' => 0, 'errorMessage' => 'ok', 'element_id' => new element id
    // иначе: errorCode     - содержит код отличный от нуля, а
    //        errorMessage  - содержит сообщение об ошибке
    //
    {
      $result = ['errorCode' => 666, 'errorMessage' => 'InsertNewElementToBlock: ошибка'];

      try {
        $activeNode = ($active == 'Y') ? true : false;

//      $requestedTime = \Drupal::time()->getRequestTime();
//      $currentDateTime = new DrupalDateTime();
//      $currentDateTime->setTimezone(new \DateTimeZone( DateTimeItemInterface::STORAGE_TIMEZONE ));
//      $currentDtime = $currentDateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

//      $currentDateTime  = new \Drupal\Core\Datetime\DrupalDateTime( 'now', new \DateTimeZone('UTC') );
//      $currentDtime     = $currentDateTime->format('Y-m-d\TH:i:s');

        $currentDateTime  = new DrupalDateTime( 'now', new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE ) );
        $currentDtime     = $currentDateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT );
        $requestedTime    = $currentDateTime->getTimestamp();

        if( empty( $idUser ) ) {
          $idUser = $this->currentUserIdValue;
        }
        $this->logging_debug( 'Добавляем запись в справочник логов.' );
/*
        $nodeStorage = \Drupal::service('entity_type.manager')->getStorage('node');
        $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
        $element =   $nodeStorage->create(
          [
            'type' => $type
          ]);
        $element->set('title', $name);
*/

        $element = Node::create(
          [
            'type' => $type,
            'title' => $name,
            'created' => $requestedTime,    // $currentDtime
            'changed' => $requestedTime,    // $currentDtime
            'status' => $activeNode,
            'uid' => $idUser
          ]);

        $this->logging_debug( 'Заполняем поля созданной записи в справочник логов.' );
        $this->logging_debug( 'created: ' . $currentDtime );

        foreach ($props as $prop => $value) {

//        $this->logging_debug( '' );
//        $propObj = $element->get($prop);
//        $propType = $propObj->getFieldDefinition()->getType();
//        $this->logging_debug( $prop . ' type: ' . $propType . ', before set:' );
//        $propField = $propObj->getValue();
//        $this->logging_debug( empty( $propField ) ? 'empty' : $propField );

          $element->set($prop, $value);

//        $propField = $element->get($prop)->getValue();
//        $this->logging_debug( '' );
//        $this->logging_debug( 'after set:' );
//        $this->logging_debug( $propField );
        }

//      $box = $element->toArray();
//      $this->logging_debug( 'new row fields:' );
//      $this->logging_debug( $box );

//      $this->logging_debug( 'prop:' );
//      $this->logging_debug( $props );
        $this->logging_debug( '' );
        $this->logging_debug( 'Сохраняем поля.' );

        $element->enforceIsNew();

        $element->save();

        $result = [
          'errorCode' => 0,
          'errorMessage' => 'ok',
          'element_id' => $element->id()
        ];

      } catch ( \Exception $e ) {
        $result = ['errorCode' => 200, 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'InsertNewElementToBlock Exception:' );
        $this->logging_debug( $result );
//      $this->loggingBackTrace();
      } catch ( \Error $e ) {
        $result = ['errorCode' => 200, 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'InsertNewElementToBlock Error:' );
        $this->logging_debug( $result );
//      $this->loggingBackTrace();
      }

      return( $result );
    }



    public function ModifyPropertyValues( $elementId, $properties )
      //
      // $elementId  - Ид элемента, который апдейтим.
      // $properties - список полей с их значениями, которые нужно изменить.
      //
      // если элемента с таким Id не существует, то return: массив с кодом ошибки и сообщением об ошибке
      // если элемент найден, и не было ошибки при его апдейте, то return: []
      //
    {
      $result = ['errorCode' => 666, 'errorMessage' => 'ModifyPropertyValues: не найден узел с nid: ' . $elementId ];

      try {
        $typeManager = \Drupal::entityTypeManager();
        $node = $typeManager->getStorage('node')->load( $elementId );
//      $node = \Drupal\node\Entity\Node::load( $elementId );

        if( ! empty( $node ) ) {

          foreach ($properties as $property => $value) {

            $node->set($property, $value);
//        $this->logging_debug( $property . ' => ' . $value  );
          }

//      $resultSet = $node->toArray();
//      $this->logging_debug( 'ModifyPropertyValues: сохраняем изменения ноды:' );
//      $this->logging_debug( $resultSet );

          $node->save();
          $result = [];       // успешно
        }

      } catch ( \Exception $e ) {
        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'ModifyPropertyValues Exception:' );
        $this->logging_debug( $result );
      } catch ( \Error $e ) {
        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'ModifyPropertyValues Error:' );
        $this->logging_debug( $result );
      }

      return( $result );
    }


    public function GetElementByCode( &$element, $codeBlock, $codeElement, $properties )
        //
        // Проверяет наличие элемента инфоблока. Если элемент с таким фильтром не существует, то возвращается ПУСТОЙ МАССИВ.
        // если элемент существует, то:
        // вычитываются указанные свойства элемента. Но только те типы свойств, которые вида:
        // [
        //    [ 'value' => значение ]
        // ]
        // в массиве $element возвращается считанные [ 'FIELDS' ] и [ 'PROPERTIES' ] подмассивы.
        //
        // return [] - успешно прочитано, или массив с кодом ошибки и сообщением
        //
    {
      $result   = ['errorCode' => 666, 'errorMessage' => 'GetElementByCode: ошибка'];
      $element  = [];

      try {
//      $this->logging_debug( 'GetElementByCode: before entityQuery.' );

        $nids = \Drupal::entityQuery( 'node' )->accessCheck(FALSE)
          ->condition('status', true )
          ->condition('type', $codeBlock )
          ->condition('field_code100', $codeElement )
          ->sort( 'title', 'asc' )
          ->execute();

//      $this->logging_debug( 'GetElementByCode: after entityQuery.' );

        if (!empty($nids)) {
          $data = \Drupal\node\Entity\Node::loadMultiple($nids);

          if (!empty($data)) {

            foreach ($data as $node) {
              //
              $resultSet = $node->toArray();
              //
//            $this->logging_debug( 'resultSet:' );
//            $this->logging_debug( $resultSet );
              //
              $element['FIELDS']['type'] = $resultSet['type'][0]['target_id'];
              $element['FIELDS']['nid'] = $resultSet['nid'][0]['value'];
              $element['FIELDS']['status'] = $resultSet['status'][0]['value'];
              $element['FIELDS']['title'] = $resultSet['title'][0]['value'];
              $element['FIELDS']['field_code100'] = $resultSet['field_code100'][0]['value'];
              $element['FIELDS']['field_sorting'] = $resultSet['field_sorting'][0]['value'];
              $element['FIELDS']['created'] = $resultSet['created'][0]['value'];
              $element['FIELDS']['changed'] = $resultSet['changed'][0]['value'];
              $element['FIELDS']['uid'] = $resultSet['uid'][0]['target_id'];
              //
              if( ! empty( $properties ) ) {
                foreach ($properties as $fieldName) {
                  $element['PROPERTIES'][$fieldName] = empty($resultSet[$fieldName][0]['value']) ? '' : $resultSet[$fieldName][0]['value'];
                }
              }

              $result = []; // успешно!
              break;        // выбирается всегда один элемент инфоблока.
                            // field_code100 - должно быть уникальным
            }
          }
        }

      } catch( \Exception $e ) {

        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'GetElementByCode Exception:' );
        $this->logging_debug( $result );
      } catch( \Error $e ) {

        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'GetElementByCode Error:' );
        $this->logging_debug( $result );
      }

      return( $result );
    }



    public function GetLogFileTitle( $fullLogName )
      //
      // Определяет заголовок для записи по указанному лог файлу.
      //
    {
      $result = '';
      $lengthBuffer = 8192;

      if( file_exists( $fullLogName ) ) {
        //
        // можете использовать 'rb', чтобы принудительно включить бинарный режим, в котором ваши данные не будут преобразовываться.
        //
        $handle = fopen( $fullLogName, "rb" );
      } else {
        return( $result );
      }

      if( $handle ) {

        while (($str = fgets($handle, $lengthBuffer)) !== false) {

          if (feof($handle))
            break;          // конец файла.

          $pos = mb_strpos( $str, self::$titleAgentCode );
          if( $pos !== false ) {
              $part = trim( mb_substr( $str, $pos + mb_strlen( self::$titleAgentCode ) ) );
              $partlen = mb_strlen( $part );
              $result = mb_substr( $part, 0, $partlen );
              break;
          }

        }
        //
        // Close file
        //
        fclose( $handle );

      } else {  // файл не окрыт, $handle не получен.
        return( $result );
      }


      return $result;
    }



    public function SearchDatesCutoff( $identifier,
                                       $dateFormat,
                                       $currentDateTime,
                                       $daysCutoff,
                                       $f,
                                       $lengthBuffer = 8192,
                                       &$resultBox = [] )
    //
    // Поиск в логе даты усекания лога
    // В этом методе не может быть никаких логов! т.к. иначе сместится позиция записи в лог файл!
    //
    {
        $result             = false;
        $flag_find_date		= false;
        $flagNeedToCute     = false;
        $positionCutoff     = false;
        $str_date = '';
        $days = '';
        $nb					= 0;
        $daysCutoff         = intval( $daysCutoff, 10 );
        $dateCutting        = date( "d.m.Y", $currentDateTime - $daysCutoff * 86400 );
        $boxMessage         = [];

/*
        $this->timeStart    = time();
*/
        try {
          while (($str = fgets($f, $lengthBuffer)) !== false) {                                                        // 0 секунд

//          $boxMessage []  = 'прочитано : ' . $str;

            if (feof($f))
              break;                                          // конец файла, этот файл можно не резать.

            if (!$flag_find_date) {

              if (mb_strpos($str, $identifier) === false) {   // идентификатор агента не найден, читаем лог дальше
                continue;
              } else {                                        // обнаружен идентификатор агента, за ним должна следовать дата.
                $flag_find_date = true;
                $nb = mb_strlen($str, 'CP1251');
                $boxMessage [] = 'обнаружен идентификатор агента, за ним должна следовать дата.';
                continue;
              }
            }

            $str_date = mb_substr($str, 0, 10);                 // считываем предполагаемую дату.
            //
            // проверяем предполагаемую дату на корректность, и если порядок то получаем ее
            //
            $log_date = $this->IsDateValidVariants($str_date, [$dateFormat, 'yyyy.mm.dd', 'mm.dd.yyyy', 'dd.mm.yyyy']);

            if ($log_date) {

              $boxMessage [] = 'дата: ' . date("d.m.Y", $log_date);

              $days = floor(($currentDateTime - $log_date) / 86400);
              $days = intval($days, 10);

              if ($days - $daysCutoff > 0) {
                $boxMessage [] = 'Дней больше, чем ищем. щелкаем дальше: ' . $days;
                $flag_find_date = false;
                $flagNeedToCute = true;         // лог длинее чем задано, требуется обрезка!
                continue;
              }
              //
              // найдена крайняя дата, по ней можно обрезать файл, если он длинее чем нужно!
              //
              if ($flagNeedToCute) {
                //
                // можно обрезать файл
                // в этом месте делаем обрезание
                //
                $nb = $nb + mb_strlen($str, 'CP1251');          // в кодировке CP1251 эта функция
                // показывает длину в байтах, что нам и нужно!
                // если указать '8bit' то будет тоже самое!

                $curr_pos = ftell($f);
                $positionCutoff = $curr_pos - $nb;

                if ($positionCutoff <= 0) {
                  break;                                      // резать некуда!
                }
                //
                // найдена позиция для обрезания !!! возвращаем ее!
                //
                $result = $positionCutoff;
                break;
              } else {
                $boxMessage [] = 'Размер лога не требует подрезания. Дата усечки: ' . $dateCutting . ', а крайняя дата: ' . date("d.m.Y", $log_date);
                break;
              }

            } else {
              $flag_find_date = false;
              $boxMessage [] = 'Дата имеет неправильный формат: ' . $str_date . ', вернуло: ' . date("d.m.Y", $log_date);
              continue;
            }
          }
        } catch(\Exception $e ) {
          $errMsg = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
          $this->logging_debug( '' );
          $this->logging_debug( 'SearchDatesCutoff Exception:' );
          $this->logging_debug( $errMsg );
          $this->loggingBackTrace();
        } catch( \Error $e ) {
          $errMsg = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
          $this->logging_debug( '' );
          $this->logging_debug( 'SearchDatesCutoff Error:' );
          $this->logging_debug( $errMsg );
          $this->loggingBackTrace();
        }
/*
        $this->timeFinish           = time();
*/
        $resultBox[ 'days' ]        = $days;
        $resultBox[ 'date_cutoff' ] = $str_date;
        $resultBox[ 'messages' ]    = $boxMessage;

        return( $result );
    }


    public function SaveArrayToXML( $title, $author, $boxData, $nameFileXml, $items='items', $withBak=true, $formatDateTime=false )  {
        //
        // Записывает массив в XML файл, вернет true в случае успеха и false если возникнет ошибка!
        // возможен Exception:
        // Invalid Character Error
        // Возникает, если в названии тега недопустимый символ, например: '-'
        //
        $result  = true;

        try {
            $ext    = mb_substr( $nameFileXml, -4 );
            $name   = mb_substr( $nameFileXml, 0, -4);

            if( $formatDateTime ) {
                $bak = $name . '-' . date( $formatDateTime ) . $ext;
            } else {
                $bak = $name . '-bak' . $ext;
            }

            $book   = new FluidXml( null, ['root' => 'body', 'version' => '1.0', 'encoding' => 'UTF-8'] );

            $book->addChild('title', $title );
            $book->addChild('author', $author );

            $bookItems  = $book->addChild( $items, true );
            $bookItems->add( $boxData );

            if( $withBak ) {

                $resultrename   = rename($nameFileXml, $bak);       // переименовываем в bak файл

                if( $resultrename ) {
//                  $this->logging_debug('bak: ' . $bak);
                }
            }

            $book->save( $nameFileXml );

        } catch (\Exception $e)
        {
            $this->logging_debug( 'Exception:' );
            $this->logging_debug( $e->getMessage() );

            $result  = false;
        }

        return( $result );
    }

    public function LoadFromXMLToArray( $nameFileXml, $nameItems='items' ) {
        //
        // Метод загружает XML файл в массив. Пустые массивы заменяются на пустые строки!
        // возвращает загруженный массив.
        //

        $result                 = [];

        try {
            $xml = new XMLUtility($this->log_name, $this->cute_identifier, $this->cuteBeModule, $this->oldLogEnable );
            $xml->SetStarting( true );
            $xml->SetDontCuteLog( true );
            $xml->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );

            $box = $xml->SimpleXmlToArray($nameFileXml);

//          $this->logging_debug( 'xml BOX:' );
//          $this->logging_debug( $box );

            if (is_array($box[$nameItems])) {

                $result = $this->UnsetArrayIfEmpty($box[$nameItems], 100);

            } else {
                $result = $box[$nameItems];
            }
        } catch (\Exception $e)
        {
            $this->logging_debug( 'Exception:' );
            $this->logging_debug( $e->getMessage() );
        }

        return( $result );
    }


    public function UnsetArrayIfEmpty( $items, $recursmentLimiter = 100 ) {
        //
        // Метод заменяет пустые массивы на пустые строки. Использует рекурсивный вызов с ограничением количества
        // вызовов по рекрсии при помощи счетчика - $recursmentLimiter. Счетчик считает не уровни вложенности, а
        // количество вызовов!
        //
        $result = [];

        try {
            foreach ($items as $code => $nextItems) {

                if (empty($nextItems)) {

                    $result[$code] = '';
                    continue;
                }

                if (is_array($nextItems)) {

                    $recurseCounter = $recursmentLimiter;
                    $result[$code] = $this->UnsetArrayIfEmptyRecurcive($nextItems, $recurseCounter);

                } else {
                    $result[$code] = $nextItems;
                }
            }
        } catch (\Exception $e)
        {
            $this->logging_debug( 'Exception:' );
            $this->logging_debug( $e->getMessage() );
        }

        return( $result );
    }

    private function UnsetArrayIfEmptyRecurcive( $items, & $recurseCounter ) {
        //
        // Вспомогательный рекурсивный метод, который заменяет пустые массивы на пустые строки.
        // Рекурсивный вызов имеет ограничение количества вызовов по рекурсии при помощи счетчика - $recurseCounter.
        // Счетчик считает не уровни вложенности, а количество вызовов!
        //
        $result = [];

        if( $recurseCounter > 0 ) {

            $recurseCounter = $recurseCounter - 1;

            foreach ($items as $code => $nextItems) {

                if (empty($nextItems)) {

                    $result[$code] = '';
                    continue;
                }

                if (is_array($nextItems)) {

                    $result[$code] = $this->UnsetArrayIfEmptyRecurcive($nextItems, $recurseCounter );

                } else {
                    $result[$code] = $nextItems;
                }
            }

            return( $result );

        } else {

            return( $items );
        }
    }


    public function ReadLastLogLines( $logName, &$offset, $lines = 1, $adaptive = true )
        //
        // взято: https://gist.github.com/lorenzos/1711e81a9162320fde20
        //
        // читает последние $lines - штук записей из лога.
        // если $adaptive == true, то размер буфера чтения определяется автоматом, в противном случае == 4096 байт
        // если ошибка - возвращаем false, иначе прочитанную строку.
        // в переменной $offset - возвращается смещение на последнюю строку в файле ( для ее возможной модификации )
        //
    {

        $result             = false;
        $handle             = false;
/*
        $folderName = $this->documentRoot . $this->log_folder;
        $logName	= $folderName . $this->log_name;
*/
        if( file_exists( $logName ) ) {

            // можете использовать 'rb', чтобы принудительно включить бинарный режим, в котором ваши данные не будут преобразовываться.
            $handle = fopen( $logName, "rb" );
        } else {
            return( $result );
        }

        if( $handle ) {
            //
            // Sets buffer size, according to the number of lines to retrieve.
            // This gives a performance boost when reading a few lines from the file.
            //
            if (!$adaptive) $buffer = 4096;
            else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
            //
            // Jump to last character
            //
            fseek( $handle, -1, SEEK_END );
            //
            // Read it and adjust line number if necessary
            // (Otherwise the result would be wrong if file doesn't end with a blank line)
            //
            if( fread( $handle, 1 ) != "\n") $lines -= 1;
            //
            // Start reading
            //
            $output     = '';
            $chunk      = '';
            //
            // While we would like more
            //
            $offset = ftell( $handle );
            while( $offset > 0 && $lines >= 0 ) {
                //
                // Figure out how far back we should jump
                //
                $seek = min( ftell( $handle ), $buffer );
                //
                // Do the jump (backwards, relative to where we are)
                //
                fseek( $handle, -$seek, SEEK_CUR );
                //
                // Read a chunk and prepend it to our output
                //
                $output = ( $chunk = fread( $handle, $seek ) ) . $output;
                //
                // Jump back to where we started reading
                //
                fseek( $handle, -mb_strlen( $chunk, '8bit' ), SEEK_CUR );
                //
                // Decrease our line counter
                //
                $lines -= substr_count( $chunk, "\n" );
                $offset = ftell( $handle );
            }
            //
            // While we have too many lines
            // (Because of buffer size we might have read too many)
            //
            if( $lines < 0 ) {

                while ($lines++ < 0) {
                    //
                    // Find first newline and remove all text before that
                    //
                    $output = substr($output, strpos($output, "\n") + 1);

                }
                //
                // ищем начало считанной строки - шагаем вперед
                //
                while ($offset > 0) {

                    if (fread($handle, 1) != "\n") {

                        $offset = ftell($handle);
                        continue;

                    } else {
                        //
                        // нашли позицию
                        //
                        $offset = ftell($handle);
                        break;
                    }
                }

            } else {
                //
                // ищем начало считанной строки - шагаем назад
                //
                while ($offset > 0) {

                    fseek($handle, -1, SEEK_CUR);

                    if (fread($handle, 1) != "\n") {

                        fseek($handle, -1, SEEK_CUR);
                        $offset = ftell($handle);
                        continue;

                    } else {
                        //
                        // нашли позицию
                        //

                        break;
                    }
                }
            }
            //
            // Close file and return
            //
            fclose( $handle );
            $result = trim( $output );
        }

        return( $result );
    }


    public function ScanFolderBoxKeyFileName( $fullFolderName )
    //
    // возвращает массив: содержимого массива по убыванию.
    // вверху самое свежее дата-время. ключ - имя файла, значение - время модификации.
    //
    {
      date_default_timezone_set( 'Europe/Kiev' );

      $result	= [];
      foreach( scandir( $fullFolderName ) as $filename ) {

        if( $filename != '.' && $filename != '..' ) {

          $dat = filemtime( $fullFolderName . $filename );

          if( $dat === false )
            $result[ $filename ] = 9999999999;
          else
            $result[ $filename ] = $dat;
        }
      }
      //
      // Сортировка содержимого массива по убыванию. вверху самое свежее дата-время.
      // ключ - имя файла, значение - время модификации.
      //
      arsort( $result );
      //
      return( $result );
    }

  public function ScanFolderToBox($path, $extension, $excludeBox=[], $ignoreBak=true, $ignoreOld=true )
    //
    // $path - путь относительно корня сайта со слэшэм в конце!
    // $extension - расширение файлов ( 'bin', 'log', 'txt, и т.д. )
    //
    // возвращает либо пустой массив, либо заполненный такой структуры:
    // [
    // ['name' => $filename, 'size' => $sizeFile, 'date' => $dateFile ]
    // ]
    //
  {

    $boxFiles = [];

//  $this->logging_debug('path: ' . $this->documentRoot . $path);

    foreach (scandir($this->documentRoot . $path) as $filename) {

      $parts = pathinfo($filename);

      if( !empty( $excludeBox ) ) {
//      $this->logging_debug('в массиве исключения ищем: ' . $filename );
        if( in_array( trim( $filename ), $excludeBox ) ) {

//          $this->logging_debug('в массиве исключения найдено: ' . $filename );
            continue;
        }
      }

      if ($filename != '.' && $filename != '..') {

        if ($parts['extension'] == $extension) {

          $tailName = mb_strtolower( mb_substr( $parts['filename'], -4 ) );

          $checkBack = true;
          if( $ignoreBak ) {
              $checkBack = false;
              if( $tailName != '-bak' )
                  $checkBack = true;
          }

          $checkOld = true;
          if( $ignoreOld ) {
            $checkOld = false;
            if( $tailName != '-old' )
              $checkOld = true;
          }

          if( $checkBack && $checkOld ) {
            $dateFile = filemtime($this->documentRoot . $path . $filename);
            $sizeFile = filesize($this->documentRoot . $path . $filename);

            $boxFiles [] = [
              'name' => $filename,
              'size' => $sizeFile,
              'date' => $dateFile
            ];
          }
        }
      }
    }

    return ($boxFiles);
  }



}
