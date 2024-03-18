<?php

namespace Otpclasses\Otpclasses;

use Otpclasses\Otpclasses\LogUtilities;
use Otpclasses\Otpclasses\DirUtilities;
use Otpclasses\Otpclasses\StringUtilities;
use Otpclasses\Otpclasses\CommonDataBox;
use Otpclasses\Otpclasses\UrlUtilities;

class OtpUtilities extends LogUtilities
{
    public $StringUtils;
    public $dirUtils;
    public $urlUtil;

    public $localEmailTO;
    public $localEmailCC;
    public $localEmailBCC;
    public $localEmailFrom;

    public function __construct( $logName = '/otputilities.log', $cuteIdentifier = 'OtpUtilities.', $cuteModule = true, $withOldLog = true  ) {

        $this->init();

        parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

        $this->StringUtils      = new StringUtilities( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
        $this->dirUtils         = new DirUtilities( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
        $this->urlUtil          = new UrlUtilities( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

        $this->num_days_cut     = 7;

        $this->StringUtils->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
        $this->StringUtils->SetStarting( true );
        $this->dirUtils->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
        $this->dirUtils->SetStarting( true );
        $this->urlUtil->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
        $this->urlUtil->SetStarting( true );
    }

    public function __destruct() {

        parent::__destruct();

        unset($this->dirUtils);
        unset($this->StringUtils);
    }



    public function init() {

        $this->localEmailTO     = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailTo' ];
        $this->localEmailCC     = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailCC' ];
        $this->localEmailBCC    = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailBCC' ];
        $this->localEmailFrom   = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailFrom' ];

    }

    public function date_i18n( $format, $currentTime, $codeLang='ru' ) {

        switch ( $codeLang ) {

        case 'ru':
                    $currentLocale  = 'ru_RU';                                              // setlocale( LC_ALL, 'ru_RU', 'ru_RU.UTF-8', 'ru', 'russian');
                    break;
        case 'ua':
                    $currentLocale  = 'uk_UA';                                              // setlocale( LC_ALL, 'uk_UA', 'uk_UA.UTF-8', 'ua', 'ukrainian');
                    break;
        case 'en':
                    $currentLocale  = 'en_US';                                              // setlocale( LC_ALL, 'en_US', 'en_US.UTF-8', 'en', 'english');
                    break;
        }

        if( $format == 'r' && class_exists( "\IntlDateFormatter" ) ) {

            $formatter  = new \IntlDateFormatter(   $currentLocale,
                                                    \IntlDateFormatter::FULL,
                                                    \IntlDateFormatter::FULL,
                                                    \date_default_timezone_get(),
                                                    \IntlDateFormatter::GREGORIAN,
                                                    "ccc, dd LLL yyyy HH:mm:ss ZZZ" );

            $result     = $formatter->format( $currentTime );

        } else {

            $formatter  = new \IntlDateFormatter(   $currentLocale,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \date_default_timezone_get(),
                \IntlDateFormatter::GREGORIAN,
                $format );

            $result     = $formatter->format( $currentTime );
        }

        return( $result );
    }









    public function CheckBeforeMailSend( $filterList,  &$arFields, &$arTemplate ) {

        $skip = false;

        foreach( $filterList as $code => $filters ) {

//          $this->logging_debug( 'code: ' . $code );
//          $this->logging_debug( 'filters:'  );
//          $this->logging_debug( $filters );

            $fieldValue = $arFields[$code];

            if( empty( $fieldValue ) ) {

                foreach( $arFields as $fieldCode => $value ) {

                    $pos = mb_strpos( $fieldCode, $code );

                    if( $pos !== false ) {
                        $fieldValue = $value;
                        break;
                    }
                }
            }

            $skip = $this->CheckStringByFilterList( $filters, $fieldValue );

            if( $skip )
                 break;  // найдено тестовое значение в строке
        }

        if( $skip ) {

            $this->logging_debug( 'Есть тестовые данные в форме: [' . $arFields['RS_FORM_ID'] . '] ' . $arFields['RS_FORM_NAME'] . ', result ID: ' . $arFields['RS_RESULT_ID' ] );

            $this->setEmailToLocal( $arTemplate );

//          $this->logging_debug( 'Fields:' );
//          $this->logging_debug( $arFields );

//          $this->logging_debug( 'Template:' );
//          $this->logging_debug( $arTemplate );

        } else {
            $this->logging_debug( '' );
            $this->logging_debug( 'Нет тестовых данных в форме: [' . $arFields['RS_FORM_ID'] . '] ' . $arFields['RS_FORM_NAME'] . ', result ID: ' . $arFields['RS_RESULT_ID' ] );
        }

        return( $skip );
    }

    public function setEmailToLocal( &$arTemplate ) {

        $stringTo  = '';

        if( empty( $stringTo ) )
            $stringTo  = $this->localEmailTO;  // 'gmsandromush@gmail.com, nikolay.bogdanyuk@gmail.com'

        $arTemplate[ 'EMAIL_FROM' ] = $this->localEmailFrom;
        $arTemplate[ 'EMAIL_TO' ]   = $stringTo;

        if( ! empty( $arTemplate[ 'CC' ] ) )
            $arTemplate[ 'CC' ] = $this->localEmailCC;

        if( ! empty( $arTemplate[ 'BCC' ] ) )
            $arTemplate[ 'BCC' ] = $this->localEmailBCC;

    }


    public function CheckStringByFilterList( $filter, $field ) {

        //
        //   В $field ищются строки из массива $filter. если найдено хоть одно совпадение - тут-же возвращается TRUE,
        //   иначе все проверяется до конца и возвращается FALSE.
        //

        $result     = false;

        if( ! empty( $field ) ) {

            $lowerField = mb_strtolower( $field );

            foreach( $filter as $str ) {

                $pos       = mb_strpos( $lowerField, $str  );
                $logString = ($pos === false) ? "False" : $pos;

//              $this->logging_debug( 'field: ' . $lowerField . ', filter: ' . $str . ', pos: ' . $logString );

                if( $pos !== false ) {

                    $this->logging_debug( '' );
                    $this->logging_debug( 'ВНИМАНИЕ!' );
                    $this->logging_debug( 'field: ' . $lowerField . ', filter: ' . $str . ', pos: ' . $logString );
                }
                $result = ($pos === false) ? false : true;

                if( $result )
                    break;
            }
        }

        return( $result );
    }











    public function GetAttributHtmlTag( $strTag, $tagSearch ) {
    //
    //  Ищет в строке $strTag свойство $tagSearch и возвращает значение этого свойства
    //
        $value     = '';
        $tag       = ' ' . $tagSearch . '=';
        $lenTag    = mb_strlen( $tag );
        $isTag     = mb_stripos( $strTag, $tag );

        if( $isTag !== false  ) {

            $partSrc = mb_substr( $strTag, $isTag + $lenTag );

            if( mb_substr( $partSrc, 0, 1 ) == "\"" ) {

                $partSrc = mb_substr( $partSrc, 1 );
                $lenPart   = mb_strlen( $partSrc );

                for( $i = 0; $i < $lenPart; $i++ ) {
                        if( mb_substr( $partSrc, $i, 1 ) == "\"" ) {
                            $value = mb_substr( $partSrc, 0, $i );
                            break;
                        }
                }
            }
        }

        return( $value );
    }

    public function ArrayToJsonFile( $fullFileNameJson, $box )
    {
        $handle = fopen( $fullFileNameJson, 'w' );
        fwrite( $handle, json_encode( $box ) );
        fclose( $handle );
    }

    public function LoadJsonFileToArray( $fullFileNameJson )
    {
        $json   = file_get_contents( $fullFileNameJson );
        $result = json_decode( $json );

        return( $result );
    }












    public function FormatPhoneDescription( $mobile )
    //
    // приведение телефона к формату: "+ 38 (067) 214 70 13"
    //
    {

      if( mb_substr( $mobile, 0, 1 ) == '+' )
        $mobile = mb_substr( $mobile, 1 );
  //
  //  это для России
  //
  //  $result = preg_replace("/(\\d{1})(\\d{3}|\\d{4})(\\d{3}|\\d{4})(\\d{2})(\\d{2})$/i", "+ $1 ($2) $3 $4 $5 $6", $mobile );
  //
      $result = preg_replace("/(\\d{2})(\\d{3}|\\d{4})(\\d{3}|\\d{4})(\\d{2})(\\d{2})$/i", "+ $1 ($2) $3 $4 $5 $6", $mobile );

      return( $result );
    }

    public function UaPhoneBoxPrepareForHREF( $box )
      //
      // $box       - простой массив телефонов в виде строк
      // $result    - массив вида:
      // [
      //    [ 'href' => '+380679814455', 'desc' => '38 067-981-44-55' ],
      //    ...
      //    [ 'href' => '+380959814455', 'desc' => '38 095-981-44-55' ]
      // ]
      //
    {
      $result = [];

      foreach ( $box as $itemPhone ) {

        $phone      = '';
        $item       = str_replace( [ "–", "-", "(", ")", "{", "}", " " ],  "", $itemPhone );

        if( mb_substr( $item, 0, 1 ) != '+' ) {

          $phone = '+';

          if( mb_substr( $item, 0, 2 ) != '38' ) {

            $phone .= '38' . $item;
          } else {
            $phone .= $item;
          }
        } else {
          $phone  = $item;
        }
        //
        // тут полный телефон в нужном формате под ссылку
        // делаем проверку по длине строки. она должна быть равна: 13
        //
        $itemLength = mb_strlen( $phone );
        /*
                if( $itemLength != 13 )
                  $phone  = '+380*********';
        */
        $phoneDesc = $this->FormatPhoneDescription( $itemPhone );

        $result [] = [ 'href' => $phone, 'desc' => $phoneDesc ];
      }

      return( $result );
    }

    public function UaPhoneStringToArrayForHREF( $phones ) {
      //
      // переводит строку с телефонами, разделенную запятыми или точкой с запятой в массив с телефонами под тег: 'a'
      // для аттрибута: href, в виде: href="tel:+499123456789"
      // если формат телефона не распознан, то для телефона формируется строка вида: '+380*********'
      //
      $result = [];

      $box    = explode( ',', $phones );

      if( count( $box ) < 2 ) {

        $box  = explode( ';', $phones );
      }

      foreach ( $box as $itemPhone ) {

        $phone      = '';
        $item       = str_replace( [ "–", "-", "(", ")", "{", "}", " " ],  "", $itemPhone );

        if( mb_substr( $item, 0, 1 ) != '+' ) {

          $phone = '+';

          if( mb_substr( $item, 0, 2 ) != '38' ) {

            $phone .= '38' . $item;
          } else {
            $phone .= $item;
          }
        } else {
          $phone  = $item;
        }
        //
        // тут полный телефон в нужном формате под ссылку
        // делаем проверку по длине строки. она должна быть равна: 13
        //
        $itemLength = mb_strlen( $phone );

        $result [] = [ 'href' => $phone, 'desc' => $itemPhone ];
      }

      return( $result );
    }


  public function jsonFixer($json)
  {
    $patterns     = [];
    /** garbage removal */
    $patterns[0]  = "/([\s:,\{}\[\]])\s*'([^:,\{}\[\]]*)'\s*([\s:,\{}\[\]])/"; //Find any character except colons, commas, curly and square brackets surrounded or not by spaces preceded and followed by spaces, colons, commas, curly or square brackets...
    $patterns[1]  = '/([^\s:,\{}\[\]]*)\{([^\s:,\{}\[\]]*)/'; //Find any left curly brackets surrounded or not by one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[2]  =  "/([^\s:,\{}\[\]]+)}/"; //Find any right curly brackets preceded by one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[3]  = "/(}),\s*/"; //JSON.parse() doesn't allow trailing commas
    /** reformatting */
    $patterns[4]  = '/([^\s:,\{}\[\]]+\s*)*[^\s:,\{}\[\]]+/'; //Find or not one or more of any character except spaces, colons, commas, curly and square brackets followed by one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[5]  = '/["\']+([^"\':,\{}\[\]]*)["\']+/'; //Find one or more of quotation marks or/and apostrophes surrounding any character except colons, commas, curly and square brackets...
    $patterns[6]  = '/(")([^\s:,\{}\[\]]+)(")(\s+([^\s:,\{}\[\]]+))/'; //Find or not one or more of any character except spaces, colons, commas, curly and square brackets surrounded by quotation marks followed by one or more spaces and  one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[7]  = "/(')([^\s:,\{}\[\]]+)(')(\s+([^\s:,\{}\[\]]+))/"; //Find or not one or more of any character except spaces, colons, commas, curly and square brackets surrounded by apostrophes followed by one or more spaces and  one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[8]  = '/(})(")/'; //Find any right curly brackets followed by quotation marks...
    $patterns[9]  = '/,\s+(})/'; //Find any comma followed by one or more spaces and a right curly bracket...
    $patterns[10] = '/\s+/'; //Find one or more spaces...
    $patterns[11] = '/^\s+/'; //Find one or more spaces at start of string...

    $replacements     = [];
    /** garbage removal */
    $replacements[0]  = '$1 "$2" $3'; //...and put quotation marks surrounded by spaces between them;
    $replacements[1]  = '$1 { $2'; //...and put spaces between them;
    $replacements[2]  = '$1 }'; //...and put a space between them;
    $replacements[3]  = '$1'; //...so, remove trailing commas of any right curly brackets;
    /** reformatting */
    $replacements[4]  = '"$0"'; //...and put quotation marks surrounding them;
    $replacements[5]  = '"$1"'; //...and replace by single quotation marks;
    $replacements[6]  = '\\$1$2\\$3$4'; //...and add back slashes to its quotation marks;
    $replacements[7]  = '\\$1$2\\$3$4'; //...and add back slashes to its apostrophes;
    $replacements[8]  = '$1, $2'; //...and put a comma followed by a space character between them;
    $replacements[9]  = ' $1'; //...and replace by a space followed by a right curly bracket;
    $replacements[10] = ' '; //...and replace by one space;
    $replacements[11] = ''; //...and remove it.

    $result = preg_replace($patterns, $replacements, $json);

    return( $result );
  }

  public function FormatInteger( $amount )
  {
    return ( number_format ($amount, 0, '.', ' ') );
  }

  public function FormatFloat( $amount )
  {
    return ( number_format ($amount, 2, '.', ' ') );
  }

  public function GetRequestValue( &$form, $field, $defaultValue )
  {
    //
    // функция заполняет поле формы ($field в массиве формы $form) значением по умолчанию,
    // которое берется из аргумента $defaultValue,
    //
    if( ! empty( $form['elements'][$field] )
        && ! empty( $form['elements'][$field]['#prepopulate'] )
        && ! empty( $defaultValue ) ) {

      $form['elements'][$field]['#default_value'] = $defaultValue;
      $this->logging_debug( $field . ': ' . $form['elements'][$field]['#default_value'] );
    }
  }

  public function GetParametersInSources( $referer,
                                         $boxParams=[ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' ] )
    //
    // В строке $referer и $_SERVER['HTTP_REFERER'] осуществляется поиск параметров из $boxParams.
    // резулбьтат возвращается в массиве в виде: ПАРАМЕТР => ЗНАЧЕНИЕ
    //
  {

    $isAllUtm = false;
    $anotherReferer  =  $_SERVER['HTTP_REFERER'] ?? '';
    $resultBox = $this->urlUtil->SearchSpecifiedParametersInUrls( [ $referer, $anotherReferer ],
                                                                  $boxParams,
                                                                  $isAllUtm );

    foreach ( $resultBox as $name => &$value ) {

      $value = rawurldecode( $value );
      $this->logging_debug( 'before: ' . $name . ' => ' . $value );

      $value     = str_replace( '+', ' ', $value );

      if( mb_strpos( $value, ' ' ) !== false )
        $value = '"' . $value . '"';

      $this->logging_debug( 'after: ' . $name . ' => ' . $value );
    }

    return( $resultBox );
  }

}
