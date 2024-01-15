<?php

namespace Otpclasses\Otpclasses;

use Otpclasses\Otpclasses\LogUtilities;
use Drupal\Core\Site\Settings;

class UrlUtilities extends LogUtilities
{

    public  $CurrentSection;
    public  $LengthCurrentSection;
    public  $defaultIsHttps;        // если не можем определить протокол никакими способами, то берем значение по умолчанию!

    public function __construct( $logName = '/urlutilities.log', $cuteIdentifier = 'UrlUtilities.', $cuteModule = true, $withOldLog = true  ) {

        $this->defaultIsHttps       = true;         // по умолчанию считаем протокол: HTTPS
        $this->init();

        parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
    }

    public function __destruct() {

        parent::__destruct();
    }

    public function init() {
        $this->CurrentSection       = '';
        $this->LengthCurrentSection = 0;
    }

    //
    // в массиве $listExclude ищется текущая папка: $PathUrl.
    // каждый элемент массива проверяется на вхождение в $PathUrl.
    // если в текущем URI найдено хотя-бы одно вхожджение строк (папок) из $listExclude,
    // то:  return true, иначе: return false.
    //
    public function IsExcludePage( $listExclude ) {

        $PathUrl        = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

//      $this->logging_debug( 'Current page: ' . $PathUrl );

        $isExclude      = false;

        foreach($listExclude as $exclude) {

            $isExclude  = mb_strpos( $PathUrl, $exclude );
            if( $isExclude !== false )
                break;
        }

        return( $isExclude );
    }

    public function ProtocolHTTPS() {

        $resultHttps    = false;
        $protocol       = Settings::get('SITE_PROTOCOL');

        if( ! empty( $protocol ) ) {

          $protocol = mb_strtoupper( $protocol );
          if( $protocol == 'HTTPS' )
            $isHttps = true;
          else
            $isHttps = false;

        } else {
          $isHttps = false;
        }

//      $this->logging_debug( 'empty isHttps!' );
        if( empty( $isHttps ) ) {
            if( isset($_SERVER['HTTPS']) || isset($_SERVER['SERVER_PORT'])) {

                $resultHttps = ($_SERVER['HTTPS'] || $_SERVER['SERVER_PORT'] == "443") ? true : false;
//              $this->logging_debug( 'Is $_SERVER[ HTTPS ] or $_SERVER[ SERVER_PORT ] !' );

            } else {
                $resultHttps = $this->defaultIsHttps;
//              $this->logging_debug( 'empty $_SERVER[ HTTPS ] and $_SERVER[ SERVER_PORT ] !' );
            }
        } else {

          $resultHttps = $isHttps ? true : false;
        }

        $Protocol   = $resultHttps ? "https://" : "http://";

        return( $Protocol );
    }



    public function CurrentPath( $currentURL = false ) {

        if( empty( $currentURL ) ) {

            $PathUrl = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

        } else {

            $PathUrl = parse_url($currentURL, PHP_URL_PATH);
        }

        if( mb_substr( $PathUrl, -1 ) != '/' )
            $PathUrl    = $PathUrl . '/';

        return( $PathUrl );
    }



    public function CurrentHost( $currentURL = false )
    {
        $result     = false;
        $error      = false;

        if(empty($currentURL)) {
            $error      = true;
        } else {
            $url        = parse_url($currentURL);
            if( !empty($url['scheme'])) {
                $protocol   = $url['scheme'] . '://';
                $host       = $url['host'];
                $result     = $protocol . $host;
            } else {
                $error      = true;
            }
        }

        if( $error ) {
            $protocol   = $this->ProtocolHTTPS();

            if( !empty($_SERVER['SERVER_NAME']) ) {
                $host   = $_SERVER['SERVER_NAME'];
                $result = $protocol . $host;
            }
        }

        return($result);
    }

    //
    // Для разбора URI на секции. сначала вызывается $this->init(), а затем $this->>NextSection()
    //
    public function NextSection( $Path ) {

        $SectionName = false;

//      $this->logging_debug( 'Path: ' . $Path );

        $pos       = mb_strpos( $Path, '.php' );
        if( $pos === false )
            $pos   = mb_strpos( $Path, '.PHP' );

        if( $pos !== false )
            $PathDir   = dirname(  $Path );
        else
            $PathDir   = $Path;

//      $this->logging_debug( 'PathDir: ' . $PathDir );

        if( mb_substr( $PathDir, 0, 1 ) == '/' ) {

          if( mb_strlen( $PathDir ) > 1 )
            $PathName   = mb_substr( $PathDir, 1 );
          else
            $PathName   = '';

        } else {
            $PathName   = $PathDir;
        }

        if( ! empty( $this->CurrentSection ) && ! empty( $PathName ) ) {


            $PositionCurrentSection = mb_strpos( $PathName, $this->CurrentSection );

            $PathName   = ( $PositionCurrentSection !== false ) ? mb_substr( $PathName, $PositionCurrentSection + $this->LengthCurrentSection ) : false;
            $PathName   = ( $PathName && mb_substr( $PathName, 0, 1 ) == '/' ) ? mb_substr( $PathName, 1 ) : $PathName;
        }

//      $this->logging_debug( 'PathName: ' . $PathName );

        if( ! empty( $PathName ) ) {
            $PositionSlash              = mb_strpos( $PathName, '/' );
            $SectionName                = $PositionSlash ? mb_substr( $PathName, 0, $PositionSlash ) : mb_substr( $PathName, 0 );
            $this->CurrentSection       = $SectionName;
            $this->LengthCurrentSection = mb_strlen( $this->CurrentSection );

//          $this->logging_debug( 'Section:         ' . $this->CurrentSection );
//          $this->logging_debug( 'LengthSection:   ' . $this->LengthCurrentSection );

        } else {
            $SectionName                = false;
//          $this->logging_debug( 'Section not found!' );
        }

        return( $SectionName );
    }



    //
    // Для разбора URI на секции. сначала вызывается $this->init(), а затем $this->NextSection(), $this->PrevSection()
    //
    public function PrevSection( $path, $currentSection ) {

        $result = '';
        $position = mb_strpos( $path, $currentSection );
        if ($position!==false) {
            $newPath = mb_substr($path, 0, $position);

//          $this->logging_debug( 'PrevSection new path: ' . $newPath );

            $result = $this->LastSection($newPath);

//          $this->logging_debug( 'PrevSection: ' . $result );
        }
        return($result);
    }



    //
    // Для разбора URI на секции. возвращает последнюю секцию из URI
    //
    public function LastSection( $path ) {

        $this->init();
        $NextFolder = true;

        while( $NextFolder ) {
            $PrevFolder = $NextFolder;
            $NextFolder = $this->NextSection( $path );
        }

        return( $PrevFolder );
    }


    public function GetDataFromUrl( $url, $boxParameters, &$boxParameterValue )
        //
        // в переменной $url ищутся параметры которые указаны в масиве $boxParameters.
        // т.е. ищутся любые параметры и добавляются в массив $boxParameterValue. а затем проверяется наличие в нем
        // параметров, которые указаны в масиве $boxParameters.
        // если все перечисленные параметры найдены в выходном массиве значений, то
        // возвращается - true, иначе - false
        //
    {
        $result     = true;
        $fragments  = parse_url( $url );
        $query      = $fragments[ 'query' ];

        if( !empty( $query ) ) {

            $parameterBox = explode( '&', $query );

            foreach ( $parameterBox as $parameterTwain ) {

                $boxTwain   = explode( '=', $parameterTwain );
                $boxParameterValue[ $boxTwain[0] ]    = $boxTwain[1];
            }

            foreach ($boxParameters as $item) {

                $itemValue   = $boxParameterValue[ $item ];

                if( empty($itemValue) ) {

//                  $this->logging_debug( 'parameter: ' . $item . ' - пустой' );

                    $result     = false;
                    break;
                }
            }
        } else {
//          $this->logging_debug( 'Параметры в запросе отсутствуют!' );
            $result = false;
        }

        return( $result );
    }



    public function IsUtmMarkup( $referer, $utmItem = 'utm_source' ) {
        //
        // проверяет наличие одной utm метки, по умолчанию - utm_source
        //
        $isUTM = mb_strpos( $referer, $utmItem . '=' );
        $result = ($isUTM === false) ? false : true;

        return( $result );
    }

    public function IsUtmMarkups( $referer, $boxUtm ) {
        //
        // проверяет наличие массива ($boxUtm) utm меток, если все метки найдены - true, иначе - false
        //
        $result = false;

        foreach( $boxUtm as $utm ) {

            $isUTM = mb_strpos( $referer, $utm . '=' );

            if( $isUTM === false) {

                $result = false;
                break;
            } else {
                $result = true;
            }
        }

      return( $result );
    }

    public function IsFirstPartUtmMarkups( $referer, $boxUtm ) {
        //
        // проверяет наличие хотя-бы 'N' первых UTM меток из переданного массива ($boxUtm) utm меток, если найдены - true, иначе - false
        //
        $result   = false;

        foreach( $boxUtm as $utm ) {

            $isUTM = mb_strpos( $referer, $utm . '=' );

            if( $isUTM === false) {

                break;
            } else {
                $result = true;
            }
        }

      return( $result );
    }

    public function RedirectByParametersInUrls( $boxParameters, $urlRedirect, $codeRedirect=302 )
    {
        $boxParams  = [];
        $urlCurrent = $_SERVER['REQUEST_URI'];

        foreach ( $boxParameters as $item => $value ) {
            $boxParams  [] = $item;
        }
        $isAllParams    = false;
        $boxFounds      = $this->SearchSpecifiedParametersInUrls( [ $urlCurrent ], $boxParams, $isAllParams );

        if( $isAllParams ) {
            $checkCondition = true;

            foreach ( $boxParameters as $item => $value ) {

                if( $boxFounds[ $item ] != $value ) {
                    $checkCondition = false;
                    break;
                }
            }

            if( $checkCondition ) {

                $this->logging_debug( "Перенаправляется на страницу: " . $urlRedirect );

                header('Location: '. $urlRedirect, true, $codeRedirect );
            } else {

                $this->logging_debug( 'Найденные параметры переадресации в запросе не соответствуют заданным.' );
            }
        } else {

            $this->logging_debug( 'Заданые параметры переадресации в запросе не найдены.' );
        }
    }

    public function SearchSpecifiedParametersInUrls( $boxUrl, $boxParameters, & $isAllParameters )
    //
    // в массиве $boxUrl ищется заданный в $boxParameters список параметров с их значениями и возвращается.
    // если ничего не найдено - возвращается пустой массив
    //
    {
        $result             = [];
        $isAllParameters    = false;

        $boxParametersTwain = $this->SearchParametersInUrls( $boxUrl, $boxParameters, $isAllParameters );

        foreach ( $boxParameters as $item ) {

            if( ! empty( $boxParametersTwain[ $item ] ) )
                $result[ $item ]    = $boxParametersTwain[ $item ];
        }

        return( $result );
    }


    public function SearchParametersInUrls( $boxUrl, $boxParameters, & $isAllParameters )
    //
    // в массиве $boxUrl ищутся любые параметры и возвращаются в
    // массиве $result. Как только найден набор параметров, указанный в $boxParameters, процесс перебора URL
    // останавливается и заполненный массив $result возвращается.
    // если никаких параметров не обнаружено ни по одному из заданных URL,
    // то возвращается пустой массив.
    //
    {
        $result             = [];
        $isAllParameters    = false;
        //
        // в каждом URL ищем наличие всех меток из массива меток
        //
        foreach( $boxUrl as $url  ) {

            if( ! empty( $url ) ) {

                $isAllParameters    = $this->GetDataFromUrl( $url, $boxParameters, $result );

                if( $isAllParameters ) {
//                  $this->logging_debug( 'Параметры в запросе найдены:' );
//                  $this->logging_debug( $result );
                    break;
                }
            }
        }

        return( $result );
    }



    public function SearchUrlWithUtmMarkup( $boxUrl, $boxUtm, $listWithoutUtm  ) {
        //
        // 1. последовательность проверки URL-ов на UTM задается порядком в массиве:
        //    ( Action Link,  referrer из сессии, текущий referrer )
        //
        // 2. В каждом URL ищется наличие всех меток из массива меток ( 'utm_source', 'utm_medium' ).
        // 3. Если метки UTM ни где не найдены, то упрощаем условие поиска - ищем хотя-бы одну метку из массива меток
        // 4. Если метки UTM не найдены ни по заданному, ни по упрощенному условиям,
        //    то возвращается первое непустое значение из массива $listWithoutUtm
        //
        $result = '';
        //
        // в каждом URL ищем наличие всех меток из массива меток
        //
        foreach( $boxUrl as $url  ) {

            if( ! empty( $url ) ) {

                $isUTM = $this->IsUtmMarkups( $url, $boxUtm );

                if( $isUTM ) {
                    $result = $url;
                    break;
                }
            }
        }
        //
        // Если метки UTM ни где не найдены, то упрощаем условие поиска - ищем хотя-бы одну метку из массива меток
        //
        if( empty( $result ) ) {

            foreach( $boxUrl as $url  ) {

                if( ! empty( $url ) ) {

                    $isUTM = $this->IsFirstPartUtmMarkups( $url, $boxUtm );

                    if( $isUTM ) {
                        $result = $url;
                        break;
                    }
                }
            }
        }
        //
        // Если метки UTM не найдены ни по заданному, ни по упрощенному условиям, то нужно проставить хоть какой-то REFERER или ACTION_LINK
        //
        if( empty( $result ) ) {

            foreach( $listWithoutUtm as $itemWithout ) {

                if( ! empty( $itemWithout ) ) {
                    $result = $itemWithout;
                    break;
                }
            }
        }

        return( $result );
    }


    public function NameFirstFolder( $pathSection ) {
        //
        //  выделяет из параметра $pathSection имя первой секции и возвращает его
        //

//      $this->logging_debug( 'Path Section: ' . $pathSection );

        if( mb_substr( $pathSection, 0, 1 ) == '/' ) {

            $pathName   = mb_substr( $pathSection, 1 );
            $posSlash   = mb_strpos( $pathName, '/' );
            $sectionName= $posSlash ? mb_substr( $pathName, 0, $posSlash ) : mb_substr( $pathName, 0 );
        } else {
            $pathName   = $pathSection;
            $posSlash   = mb_strpos( $pathName, '/' );
            $sectionName= $posSlash ? mb_substr( $pathName, 0, $posSlash ) : mb_substr( $pathName, 0 );
        }

        return( $sectionName );
    }

    public function IsParametersInUrl( $url, $lengthValidUrl = 3, $offsetParams=0, $emptyQuery=false ) {

        $result         = false;
        $vopros         = '';

        $boxUrl         = parse_url( $url );
        $lengthQuery    = mb_strlen( $boxUrl[ 'query' ] );

        if( $offsetParams > 0 ) {

            $vopros         = mb_substr( $url, $offsetParams, 1 );

        }

        if( $vopros != '?' ) {

            // ищем вопрос
            for( $i=0;$i < mb_strlen( $url ); $i++) {

                $vopros = mb_substr( $url, $i, 1 );

                if( $vopros == '?' ) {
                    $offsetParams = $i;
                    break;
                }
            }
        }

        if( $vopros == '?' && $offsetParams == mb_strlen( $url ) - 1 && $emptyQuery == true )  // если указан только один символ вопроса, но $emptyQuery == true то возвращаем - true
            $result =   true;

//      $this->logging_debug( 'query: ' . $boxUrl[ 'query' ] ); // если получили что-то типа: "a=d" - то все ок!

        if( $lengthQuery >= $lengthValidUrl )
            $result =   true;

        return( $result );
    }

  /**
   * Поиск родительской папки
   */
  public function ParentFolder( $folder ) {

    $result = $folder;
    $parentFolder = '';

    $len = mb_strlen( $folder );

    if( mb_substr( $folder, -1, 1 ) == '/' )
      $start = $len - 2;
    else
      $start = $len - 1;

    for( $i = $start; $i >= 0; $i-- ) {
      $simbol = mb_substr( $folder, $i, 1 );
      if( $simbol == '/' ) {
        $parentFolder = mb_substr( $folder, 0, $i+1 );
        break;
      }
    }
    if( !empty( $parentFolder ) && mb_strlen( $parentFolder ) > 0 ) // было 3!!!
      $result = $parentFolder;

    return( $result );
  }

  public function IsFoldersInUri( $folders, $uri, $levels=10 )
  {
    $previous = $uri;
    $parentFolder = '';
    $isUriSegment = false;
    $i = 0;
    while ( $i < $levels ) { // 10 уровней вложенности должно хватить ...

      $parentFolder = $this->ParentFolder( $previous );
      $this->logging_debug( '' );
      $this->logging_debug( 'parentFolder: ' . $parentFolder . ', previous: ' . $previous );

      if( $parentFolder != $previous ) {
        $isUriSegment = in_array( $parentFolder, $folders );
        if( $isUriSegment ) {
          $isUriSegment = $parentFolder;
          break;
        }

        $previous = $parentFolder;
      } else {  // $parentFolder == $previous - дошли до корня! стоп машина...
        break;
      }
      $i++;
    }

    return( $isUriSegment );
  }


}

