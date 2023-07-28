<?php

namespace Otpclasses\Otpclasses;
//
// Здесь последняя версия утилит 2019.05.22
//
use Otpclasses\Otpclasses\LogUtilities;
use Otpclasses\Otpclasses\UrlUtilities;

class StringUtilities extends LogUtilities
{

	public $arr_chk_html;
	public $arr_add_html;
	public $arr_add_repl;

     function __construct( $logName = '/utilities_for_bitrix.log', $cuteIdentifier = 'BitrixInerfaceUtilities.', $cuteModule = true, $withOldLog = true ) {

        parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

        $this->arr_chk_html     = [ "&lt;", "&gt;", "&quot;", "&amp;" ];

        $this->arr_add_html     = [ "&lt", "&gt", "&quot", "&amp", "lt;", "gt;", "quot;", "amp;", "lt", "gt", "quot", "amp" ];
        $this->arr_add_repl     = [ '', '', '', '' , '', '', '', '', '', '', '', '' ];

     }

    public function __destruct() {

        parent::__destruct();
    }



    public function  ConvertArrayToListStringByDelimiters( $box, $delimiter = ':' )
	{
        $result = [];
//      self::logging_debug( "Box:" );
//      self::logging_debug( $box );

        foreach( $box as $key => $val ) {

            $result [] = $key . $delimiter . $val;
        }

        return( $result );
	}

    //
    // Округление с добавлением нулей справа
    //
    public function RoundPadding( $val, $dec )
    {
        $val_float = floatval( $val );
        $rnd_float = round( $val_float, $dec );
        $str       = strval( $rnd_float );

        for( $i=mb_strlen( $str ); $i > 0 ; $i-- ) {

            $simbol = mb_substr( $str, $i-1, 1 );

            if( $simbol == '.' || $simbol == ',' )
                break;
        }

        if( $i == 0 ) {
            $str  = $str . '.';
            $lens = mb_strlen( $str );
            $dec_pad = $lens + $dec;
        } else {
            $dec_pad = $i + $dec;
        }

        $result    = str_pad( $str, $dec_pad, "0" );

        return( $result );
    }
    //
    // Округление с добавлением нулей справа
    //
    public function Round( $val, $dec )
    {
        return( $this->RoundPadding( $val, $dec ) );
    }

    public function htmlspecialcharsBack( $str_html )
    {
        $is_html	= $this->check_htmlspecialchars_add( $str_html );

        if( $is_html ) {
            $str_pre		= $this->decode_htmlspecialchars( $str_html );
            $result			= str_ireplace( $this->arr_add_html, $this->arr_add_repl, $str_pre );
            return( $result );
        } else {
            return( $str_html );
        }
    }

    public function decode_htmlspecialchars( $str_html )
    {
    $is_html	= $this->check_htmlspecialchars( $str_html );

        if( $is_html ) {
            $str	= $str_html;

            while( $is_html ) {

//              $str_is	= empty( $is_html ) ? 'false' : 'true';
//              $this->logging_debug( 'Обнаружены спецсимволы в строке: ' . $str . ', is_html: ' . $str_is );

                $str		= htmlspecialchars_decode( $str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ); // штатная функция PHP
                $is_html	= $this->check_htmlspecialchars( $str );

//              $str_is	= empty( $is_html ) ? 'false' : 'true';
//              $this->logging_debug( $str . ', is_html: ' . $str_is );
            }

            return( $str );

        } else {
            return( $str_html );
        }
    }

    private function check_htmlspecialchars( $str_html )
    {
    $is_standard_html		= false;

    foreach( $this->arr_chk_html as $item ) {

        if( ! ( mb_strpos( $str_html, $item ) === false ) ) {
                $is_standard_html	= true;
                break;
        }
    }

    return( $is_standard_html );
    }

    private function check_htmlspecialchars_add( $str_html )
    {
    $is_html		= false;

    foreach( $this->arr_add_html as $item ) {

        if( ! ( mb_strpos( $str_html, $item ) === false ) ) {
                $is_html	= true;
                break;
        }
    }

    return( $is_html );
    }
     //
     // PHP utilities
     //
     public function First_To_UpperCase( $inp_str )
     //
     // Функция преобразует слово так, что первая буква становится заглавной, а все остальные прописными.
     //
     {

     $str    = trim( $inp_str );
     $first  = mb_substr( $str, 0, 1, 'UTF-8' ); //первая буква
     $last   = mb_substr( $str, 1 );             //все кроме первой буквы
     $first  = mb_strtoupper( $first, 'UTF-8' );
     $last   = mb_strtolower( $last, 'UTF-8' );

     $result = $first.$last;

     return $result;
     }


     public function First_To_UpperCase_EveryWord( $inp_str )
     //
     // Функция преобразует КАЖДОЕ слово так, что первая буква становится заглавной, а все остальные прописными.
     //
     {
     $result  = "";

     $inpstr  = trim( $inp_str );

     if( mb_strpos( $inpstr, ' ' ) > 0 )
         $splitter = ' ';
     else
     if( mb_strpos( $inpstr, '-' ) > 0 )
         $splitter = '-';
     else
         $splitter = '';

     if( $splitter != '' ) {

         $arr_str = explode( $splitter, $inpstr );
         $len     = count( $arr_str );
         $i       = 0;

         foreach( $arr_str as $str ) {

            $str    = trim( $str );
            $first  = mb_substr( $str, 0, 1, 'UTF-8' ); //первая буква

            if( $first == '(' || $first == '[' || $first == ')' || $first == ']' ) {

                $first = mb_substr( $str, 0, 2, 'UTF-8' );  //первая буква
                $last  = mb_substr( $str, 2 );              //все кроме первой буквы

            } else {
                $last   = mb_substr( $str, 1 );             //все кроме первой буквы
            }

            $first  = mb_strtoupper( $first, 'UTF-8' );
            $last   = mb_strtolower( $last, 'UTF-8' );

            if( $i > 0 && $i < $len  )
                $result .= $splitter;

            $result .= $first . $last;

            $i++;
         }

     } else {

            $str    = trim( $inp_str );
            $first  = mb_substr( $str, 0, 1, 'UTF-8' ); //первая буква

            $last   = mb_substr( $str, 1 );             //все кроме первой буквы

            $first  = mb_strtoupper( $first, 'UTF-8' );
            $last   = mb_strtolower( $last, 'UTF-8' );

            $result = $first . $last;
     }

     return $result;
     }



     public function recode_lat_kir( $str )
     {
     $arr_eng = [ "a" , "A", "B", "e", "E", "I", "i", "K", "M", "H",
                  "O", "o", "P", "C", "c", "T", "X", "x" ]; // 18 simbols

     $arr_kir = [ "а", "А", "В", "е", "Е", "І", "і", "К", "М", "Н",
                  "О", "о", "Р", "С", "с", "Т", "Х", "х" ]; // 18 simbols


     $arr_rec =  array_combine( $arr_eng, $arr_kir );


     $result = strtr( $str, $arr_rec );

     return $result;
     }

     public function replace_lat_kir( $str )
     {
     $arr_eng = [ "a" , "A", "B", "e", "E", "I", "i", "K", "M", "H",
                  "O", "o", "P", "C", "c", "T", "X", "x" ]; // 18 simbols

     $arr_kir = [ "а", "А", "В", "е", "Е", "І", "і", "К", "М", "Н",
                  "О", "о", "Р", "С", "с", "Т", "Х", "х" ]; // 18 simbols


     $result = str_replace( $arr_eng, $arr_kir, $str );

     return $result;
     }


    public function PrepareStringToCompare( $str )
    {

        $cyr    = $this->replace_lat_kir( $str );
        $cyrill = str_replace( [ 'Ё', 'ё' ], [ 'Е', 'е' ], $cyr );
        $cyrill = $this->ReplaceMultibyteWhiteSpace( $cyrill );
        $result = trim( mb_strtolower( $cyrill ) );

        return( $result );
    }


    public function ReplaceMultibyteWhiteSpace( $str, $simbol = ' ' )
    {

        $result = str_replace( "\xC2\xA0", $simbol, $str );

        return( $result );
    }



     public function replace_kir_lat( $str )
     {
        $arr_kir    = [ 'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я',
                    'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',' ',
                    'Є','є','Ґ','ґ','Ї', 'ї', 'І', 'і' ];

        $arr_eng    = [ 'a','b','v','g','d','e','e','gh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','y','y','y','e','yu','ya',
                    'a','b','v','g','d','e','e','gh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','y','y','y','e','yu','ya',' ',
                    'e','e','g','g','i', 'i', 'i', 'i' ];

        $result     = str_replace( $arr_kir, $arr_eng, $str );

        return $result;
     }


    public function TrimStringToConsonant( $str )
    //
    // Обрезание строки до согласной
    //
    {
        $boxConsonant   = [ 'Б','В','Г','Д','Ж','З','К','Л','М','Н','П','Р','С','Т','Ф','Х','Ц','Ч','Ш','Щ',
                            'б','в','г','д','ж','з','к','л','м','н','п','р','с','т','ф','х','ц','ч','ш','щ',
                            'Ґ','ґ' ];

        $boxEng         = [ 'a', 'b', 'c', 'd' , 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
                            'A', 'B', 'C', 'D' , 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];

        $isEnglish      = false;

        for ($i = mb_strlen($str) - 1; $i >= 0; $i--) {

            $simbol = mb_substr($str, $i, 1);
            if( in_array( $simbol, $boxEng) ) {
                $isEnglish  = true;
                break;
            }
        }

        if( ! $isEnglish ) {

            for ($i = mb_strlen($str) - 1; $i >= 0; $i--) {
                $simbol = mb_substr($str, $i, 1);
//              $this->logging_debug('str: ' . $str . ', simbol: ' . $simbol);

                if (in_array($simbol, $boxConsonant, true)) {
                    $str = mb_substr($str, 0, $i + 1);
                    break;
                } else {
                    $str = mb_substr($str, 0, $i);
                }
            }
        }

        return($str);
    }

    public function IsLowerSimbol( $simbol )
    //
    // Проверяет находится ли символ (кирилический или латиница) в нижнем регистре
    //
    {
        $isLower    = false;
        $boxCyr     = [ 'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я','є','ґ','ї', 'і' ];
        $boxEng     = [ 'a', 'b', 'c', 'd' , 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z' ];

        if( in_array( $simbol, $boxCyr) ) {
            $isLower  = true;
        } elseif ( in_array( $simbol, $boxEng) ) {
            $isLower  = true;
        }

        return( $isLower );
    }

     public function IsWordUpperCase( $str )
     //
     // Проверяет является ли вся строка в верхнем регистре
     //
     {
         $isUpper   = true;

         for($i=0;$i<mb_strlen($str);$i++) {

             $simbol    = mb_substr( $str, $i, 1 );
             if( $this->IsLowerSimbol($simbol) ) {
                 $isUpper   = false;
                 break;
             }
         }

         return( $isUpper );
     }


    public function IsFirstUpperCase( $str )
    //
    // Проверяет является ли первый символ строки в верхнем регистре
    //
    {
        $isUpper   = true;
        $simbol    = mb_substr( $str, 0, 1 );

        if( $this->IsLowerSimbol($simbol) )
            $isUpper   = false;

        return( $isUpper );
    }



    public function CountWordsInArrayLengthMoreThan( $boxString, $minLength = 1 )
    //
    // Подсчет числа элементов массива строк, с длиной строки более чем $minLength
    //
    {
        $countWords = 0;

        foreach ( $boxString as $item ) {
            $lengthString   = mb_strlen( $item );
            if( $lengthString > $minLength )
                $countWords++;
        }

        return( $countWords );
    }


    public function StringToCode( $str )
    //
    // Превращение кирилической строки в код латиницей - вспомогательный метод (с дополнительной проверкой)
    //
    {
        $countWords = 0;
        $result     = $this->StringToCodeAttempt( $str, $countWords );

        if( $countWords > 6 ) {

            $boxString     = explode( ' ', $str );

            for ($i=4;$i<count($boxString);$i++) {

                $lengthStr  = mb_strlen($boxString[$i]);

                if( $lengthStr >= 3 ) {
                    $found1 = strrpos($boxString[$i], "!");
                    $found2 = strrpos($boxString[$i], ".");
                    $found3 = strrpos($boxString[$i], "?");
                    $found4 = strrpos($boxString[$i], ";");
                    $found5 = false;

                    if ($found1 !== false || $found2 !== false || $found3 !== false || $found4 !== false || $found5 !== false) {

                        if ($i + 1 < count($boxString)) {

                            array_splice($boxString, $i + 1);
                            $str = implode(' ', $boxString);
                            $result = $this->StringToCodeAttempt($str, $countWords);
                            break;
                        }
                    }
                }

            }
        }

        return( $result );
    }


    public function StringToCodeAttempt( $str, &$countWors )
    //
    // Превращение кирилической строки в код латиницей - основной метод
    //
    {
         $str           = str_replace( [ "/", "-", "(", ")", "|", "«", "»", "%", "№" ],  " ", $str );
         $str           = trim( $str );
         $boxString     = explode( ' ', $str );
         //
         // удаление из строки мелких предлогов
         //
         for( $i=0; $i < count( $boxString );   ) {

             $boxString[ $i ]   = trim( $boxString[ $i ] );

             if( empty( trim( $boxString[ $i ] ) ) ) {                                                              // если пустое слово - удаляем его
                 array_splice($boxString, $i, 1);
                 continue;
             }

             $isUpper           = $this->IsFirstUpperCase( $boxString[ $i ] );

             if( $this->CountWordsInArrayLengthMoreThan( $boxString, 3 ) > 3 && mb_strlen( $boxString[ $i ] ) < 2 && !is_numeric( $boxString[ $i ] ) ) {
                 array_splice( $boxString, $i, 1  );
                 continue;
             } elseif ( $this->CountWordsInArrayLengthMoreThan( $boxString, 3 ) > 5 && mb_strlen( $boxString[ $i ] ) < 4 && !is_numeric( $boxString[ $i ] ) && ! $isUpper ) {
                 array_splice( $boxString, $i, 1  );
                 continue;
             } else {
                 $i++;
             }
         }
//       $this->logging_debug( 'boxString:' );
//       $this->logging_debug( $boxString );
         //
         // приклеивание цифр к следующему или предыдущему слову
         //
         for( $i=0; $i < count( $boxString );   ) {

             $boxString[ $i ]   = trim( $boxString[ $i ] );

             if( is_numeric( $boxString[ $i ] ) ) {

                 if( $i + 1 < count( $boxString ) ) {

                     $boxString[$i] = $boxString[$i] . $boxString[$i + 1];
                     array_splice($boxString, $i + 1, 1);
                 } else {

                     $boxString[$i - 1] = $boxString[$i - 1] . $boxString[$i];
                     array_splice($boxString, $i, 1);
                 }
             }

             $i++;
         }

         for( $i=0; $i < count( $boxString );  ) {

             $boxString[ $i ]   = preg_replace( "/&#?[a-z0-9]{2,8};/i", "", $boxString[ $i ] ); // удаляем спец-символы

             $boxString[ $i ]   = str_replace( [ "amp;", "quot;", "lt;", "gt;" ], "", $boxString[ $i ] );   // удаляем ошметки от спец-символов

             if( empty( trim( $boxString[ $i ] ) ) ) {                                                              // если пустое слово - удаляем его
                 array_splice($boxString, $i, 1);
                 continue;
             }

             if( $this->CountWordsInArrayLengthMoreThan( $boxString, 3 ) > 3 && mb_strlen( $boxString[ $i ] ) > 10 )
                 $boxString[ $i ]   = mb_substr( $boxString[ $i ], 0, 10 );                                // если общее количество слов больше 3, а слово больше 10 символов - обрезаем до 10

             if( $this->CountWordsInArrayLengthMoreThan( $boxString, 3 ) > 3 && mb_strlen( $boxString[ $i ] ) > 6 )
                 $boxString[ $i ]   = $this->TrimStringToConsonant( $boxString[ $i ] );                             // если общее количество слов больше 3, а длина слова больше 6, то убираем гласную в конце

             $boxString[ $i ]   = $this->replace_kir_lat( $boxString[ $i ] );                                       // заменяем кирилицу на латиницу
//           $this->logging_debug( 'boxString[' . $i . ']: ' . $boxString[ $i] );
             $boxString[ $i ]   = preg_replace( '%[^A-Za-z0-9]%', '', $boxString[ $i ] );       // убираем всякие знаки припинания и прочие лишние символы
             $boxString[ $i ]   = trim( $boxString[ $i ] );

             if( empty( $boxString[ $i ] ) ) {                                                                      // если после всех чисток образовалось пустое слово - удаляем его
                 array_splice($boxString, $i, 1);
                 continue;
             }

             $i++;
         }

//       $this->logging_debug( 'boxString: ' );
//       $this->logging_debug( $boxString );
         $countWors = count( $boxString );
         $result    = implode( '_', $boxString );                                                               // склеиваем все слова через подчерк
         $result    = strtolower( $result );                                                                         // переводим все в нижний регистр

         return( $result );
    }

    public function InText( $text, $box, &$pos=false, &$subString=false )
    //
    //   В $text ищются строки из массива $box. если найдено хоть одно совпадение - тут-же возвращается TRUE,
    //   иначе все проверяется до конца и возвращается FALSE.
    //
    {
        $result     = false;

        if( ! empty( $text ) ) {

            $lowerField = mb_strtolower( $text );

            foreach( $box as $str ) {

                $str        = mb_strtolower( $str );
                $pos        = mb_strpos( $lowerField, $str  );
                $logString  = ($pos === false) ? "False" : $pos;

                if( $pos !== false ) {
                    $lenSubstr  = mb_strlen($str);
                    $subString  = mb_substr( $text, $pos, $lenSubstr );
//                  $this->logging_debug( '' );
//                  $this->logging_debug( 'текст: ' . $lowerField . ', слово: ' . $str . ', найдено: ' . $subString . ', позиция: ' . $logString . ', символов: ' . $lenSubstr );
                }
                $result = ($pos === false) ? false : true;

                if( $result )
                    break;
            }
        }

        return( $result );
    }

    public function IsTextInBox( $text, $box, &$indexSearch=0, $isReplace=0, $stringReplace='', &$isEndOfBox=0 )
        //
        //   В $box ищются строка $text. если найдено хоть одно совпадение - тут-же возвращается TRUE,
        //   иначе все проверяется до конца и возвращается FALSE.
        //   Не смотря на то, что в этом методе есть проверки на пробелы и скобки слева и справа,
        //   этот метод неприемлем для поиска совпадений, когда наименьшая состовляющая поиска
        //   может входить в иные более полные состовляющие. Например город Запорожье и Малое Запорожье
        //   суть города разные! Но при поиске просто города Запорожье, может быть найден город
        //   Малое Запорожье !!!
        //
    {
        $result     = false;
        $index      = 0;

        $text       = trim($text);

        if( ! empty( $text ) && ! empty( $box ) ) {

            $lowerField = mb_strtolower( $text );
            $lengthText = mb_strlen( $text );

            foreach( $box as $str ) {

                if( $index < $indexSearch ) {  // становимся на указанную в $indexSearch строку в контейнере строк
                    $index++;
                    continue;
                }

                $str        = mb_strtolower( trim($str) );
                $lengthStr  = mb_strlen( $str );
                $pos        = mb_stripos( $str, $lowerField  );

                if( $pos == 0 && $lengthStr > $lengthText ) {
                    $pos    = mb_stripos( $str, $lowerField . ' '  );
                    if( $pos === false)
                        $pos    = mb_stripos( $str, $lowerField . '('  );
                } elseif ($pos > 0 && $pos + $lengthText < $lengthStr ) {
                    $pos    = mb_stripos( $str, ' ' . $lowerField . ' '  );
                    if( $pos === false)
                        $pos    = mb_stripos( $str, ' ' . $lowerField . '('  );

                    if( $pos === false)
                        $pos    = mb_stripos( $str, ')' . $lowerField . ' '  );
                } elseif ($pos > 0 && $pos + $lengthText == $lengthStr ) {
                    $pos    = mb_stripos( $str, ' ' . $lowerField );

                    if( $pos === false)
                        $pos    = mb_stripos( $str, ')' . $lowerField );
                }

                $logString  = ($pos === false) ? "False" : $pos;

                if( $pos !== false ) {
//                  $this->logging_debug( '' );
//                  $this->logging_debug( 'текст: ' . $lowerField . ', найден в словосочетании: ' . $str . ', позиция: ' . $logString );
                }
                $result = ($pos === false) ? false : true;

                if( $result ) {
                    break;
                }

                $index++;
            }
            if( $index >= count( $box ) )
                $isEndOfBox = true;

        }

        if( $result ) {
            $indexSearch    = $index;
        }

        return( $result );
    }



    public function IsEntryTextInBox( $text,
                                      &$box,
                                      &$indexSearch=0,
                                      $posEntry=false,
                                      $isReplace=0,
                                      $stringReplace='',
                                      $caseSensitive=false,
                                      $allOccurences=true,
                                      &$isEndOfBox=0 )
        //
        //   добавлено 2022-09-01
        //
        //   В $box ищются строка $text. если найдено хоть одно вхождение - тут-же возвращается TRUE,
        //   иначе все проверяется до конца и возвращается FALSE.
        //   если ищется несколько включений, то при повторном поиске, $indexSearch нужно подставлять
        //   с инкременацией на 1-цу.
        //   Если поиск с заменой ( $isReplace == true ), то все найденное меняется на: $stringReplace
        //   Если $caseSensitive = true, включается регистрозависимый поиск.
        //   Если $allOccurences = true, то ищутся и заменяются все включения данной строки в той, в которой ищется.
        //   Если достигнут конец массива, то в $isEndOfBox возвращается true
        //
    {
        $result     = false;
        $index      = 0;
        $text       = trim($text);
        $lengthText = mb_strlen( $text );

        if( ! empty( $text ) && ! empty( $box ) ) {

            foreach( $box as &$str ) {

                if( $index < $indexSearch ) {  // становимся на указанную в $indexSearch строку в контейнере строк
                    $index++;
                    continue;
                }

                if( $caseSensitive )
                    $pos            = mb_strpos( $str, $text  );                // ищем $text в $str
                else
                    $pos            = mb_stripos( $str, $text  );               // ищем $text в $str

                $result         = ($pos === false) ? false : true;

                if( $result ) {

                    $posEntry   = $pos;

                    if( $isReplace ) {

                        $this->logging_debug( 'IsEntryTextInBox::Перед модификацией: ' . $str );

                        if( $caseSensitive ) {

                            if( $allOccurences ) {
                                $str = str_replace($text, $stringReplace, $str);
                            } else {

                                $beginStr   = mb_substr( $str, 0, $posEntry );
                                $endStr     = mb_substr( $str, $posEntry + $lengthText );
                                $str        = $beginStr . $stringReplace . $endStr;
//                              $str        = substr_replace( $str, $stringReplace, $posEntry, $lengthText );   // надо проверить !!!
                            }

                        } else {

                            if( $allOccurences ) {
                                $str = str_ireplace($text, $stringReplace, $str);
                            } else {

                                $beginStr   = mb_substr( $str, 0, $posEntry );
                                $endStr     = mb_substr( $str, $posEntry + $lengthText );
                                $str        = $beginStr . $stringReplace . $endStr;
//                              $str        = substr_replace( $str, $stringReplace, $posEntry, $lengthText );   // надо проверить !!!
                            }
                        }

                        $this->logging_debug( 'IsEntryTextInBox::После модификации: ' . $str );
                    }
                    break;
                }
                $index++;
            }
            if( $index >= count( $box ) )
                $isEndOfBox = true;
        }

        if( $result ) {
            $indexSearch    = $index;
        }

        return( $result );
    }




    public function SearchTextInBox( $text,
                                     &$box,
                                     &$indexSearch=0,
                                     $isReplace=0,
                                     $stringReplace='',
                                     $caseSensitive=false,
                                     $formating=false,
                                     $isPHP=false,
                                     &$isEndOfBox=0 )
        //
        //   2021-09-01
        //
        //   В $box ищются строка $text по полному совпадению ( строка поиска == строка в контейнере ) .
        //   если найдено хоть одно совпадение - тут-же возвращается TRUE,
        //   иначе все проверяется до конца и возвращается FALSE.
        //   если ищется несколько включений, то при повторном поиске, $indexSearch нужно подставлять
        //   с инкременацией на 1-цу.
        //   если $isReplace = true, производится замена найденных строк на $stringReplace.
        //   если $caseSensitive = true, включается регистрозависимый поиск.
        //   если $formating = true, сравниваемые строки предварительно форматируются.
        //   если $isPHP = true, форматирование производится с учетом, что это код PHP.
        //   если достигнут конец массива, то возвращаемое:  $isEndOfBox = true
        //
        //
    {
        $result     = false;
        $index      = 0;

        $text       = trim($text);
        $lengthText = mb_strlen( $text );

//      if( $formating )
//          $this->logging_debug( 'SearchTextInBox::Форматирование включено.' );
//      else
//          $this->logging_debug( 'SearchTextInBox::Форматирование выключено.' );

//      $this->logging_debug( 'Ищем: ' . $text . ' в:'  );
//      $this->logging_debug( $box  );
//      $this->logging_debug( 'SearchTextInBox:: индекс начальной строки поиска: ' . $indexSearch );

        if( ! empty( $text ) && ! empty( $box ) ) {

            foreach( $box as &$str ) {

                if( $index < $indexSearch ) {  // становимся на указанную в $indexSearch строку в контейнере строк
                    $index++;
                    continue;
                }
//              $this->logging_debug( 'Ищем по индексу строки: ' . $index . ', проверяется строка: ' . $str );

//              $this->logging_debug( 'Ищем: ' . $text . ' в ' . $str . '.' );

                if( $this->IsStringEqual( $str, $text, $caseSensitive, $formating, $isPHP ) ) {
//                  $this->logging_debug( 'Совпало: ' . $text . ' == ' . $str );

                    if( $isReplace ) {

                        $this->logging_debug( 'SearchTextInBox::Перед модификацией: ' . $str );
                        $str = $stringReplace . PHP_EOL;
                        $this->logging_debug( 'SearchTextInBox::После модификации:  ' . $str );
                    }

                    $result = true;
                    break;

                } else {
//                  $this->logging_debug( 'Не найдена строка: ' . $text . ' в ' . $str );
                }

                $index++;
            }

            if( $index >= count( $box ) )
                $isEndOfBox = true;
        }

        if( $result ) {
            $indexSearch    = $index;
        }

        return( $result );
    }



    public function StringBlockInBox( $textBlock,
                                      &$box,
                                      &$indexSearch=0,
                                      $isReplace=0,
                                      $stringReplace=[],
                                      $caseSensitive=false,
                                      $formating=false,
                                      $isPHP=false,
                                      &$isEndOfBox=0 )
        //
        //   добавлено 2022-09-01
        //
        //   В $box ищется блок строк $textBlock по полному совпадению ( строка поиска == строка в контейнере ) .
        //   если найдено совпадение - возвращается TRUE ( в $indexSearch - возвращается индекс найденного блока
        //   в массиве - box ), иначе все проверяется до конца и возвращается FALSE.
        //   поиск начинается со строки с номером: $indexSearch.
        //   если $formating = true, сравниваемые строки предварительно форматируются.
        //   если $isPHP = true, форматирование производится с учетом, что это код PHP.
        //   если $isReplace = true, производится замена найденных строк на $stringReplace.
        //   если $caseSensitive = true, включается регистрозависимый поиск.
        //   если достигнут конец массива, то возвращаемое:  $isEndOfBox = true
        //
    {
        $result     = false;
        $found      = false;
        $item       = 0;
        $index      = $indexSearch;

//      if( $formating )
//          $this->logging_debug( 'StringBlockInBox::Форматирование включено.' );
//      else
//          $this->logging_debug( 'StringBlockInBox::Форматирование выключено.' );

        foreach ( $textBlock as $str ) {

            if( $item == 0 ) {

//              $this->logging_debug( 'Ищем:' );
//              $this->logging_debug( $textBlock );

                $found  = $this->SearchTextInBox(
                    $str,
                    $box,
                    $index,
                    false,
                    $stringReplace,
                    $caseSensitive,
                    $formating,
                    $isPHP,
                    $isEndOfBox );

                if( $found ) {
//                  $this->logging_debug( 'Совпала первая строка: "' . $str . '" == "' . $box[ $index + $item ] . '" найдена. index: ' . $index . ', item: ' . $item );
                    $item++;
                } else {
                    break;
                }

            } else {
                if( $found ) {

                    if( $this->IsStringEqual( $str, $box[ $index + $item ], $caseSensitive, $formating, $isPHP ) ) {

//                      $this->logging_debug( 'Искомая строка: ' . $str . ' == ' . $box[ $index + $item ] . ' найдена. index: ' . $index . ', item: ' . $item );
                        $item++;

                    } else {

//                      $this->logging_debug( 'Искомая строка: ' . $str . ' не найдена. index: ' . $index . ', item: ' . $item );
//                      $this->logging_debug( $box[ $index ] );
//                      $this->logging_debug( $box[ $index + $item ] );
//                      $this->logging_debug( $box[ $index + $item + 1 ] );

                        $found  = false;
                        break;
                    }

                } else {
                    break;
                }
            }
        }

        if( $found && $isReplace ) {

            $countDel   = count( $textBlock );
            array_splice( $box, $index, $countDel, $stringReplace );
        }

        $result         = $found;
        $indexSearch    = $index;

        return( $result );
    }



    public function DeleteEmptyElementsFromArray( $box )
        //
        //   добавлено 2022-09-01
        //
        // Чистит массив от элементов с пустыми значениями
        //
    {

         $result    = [];

         foreach ( $box as $boxItem ) {

             $boxItem   = trim( $boxItem );

             if( ! empty( $boxItem ) )
                $result [] = $boxItem;
         }

         return( $result );
    }

    public function FormatCodeString( $phpString, $isPHP=false )
    //
    //   добавлено 2022-09-01
    //
    //   форматирование строк исходного кода скриптов (html+php),
    //   из строки $phpString удаляются лишние пробелы. А если $isPHP=true - проверяется корректность php тегов
    //   ( "<?" заменяется на "<?php" )
    //
    {
        $result = '';

        $boxCheck   = [
            'class="'   => [ 'class = "', 'class ="', 'class= "' ],
            'id="'      => [ 'id = "', 'id ="', 'id= "' ],
            '">'        => [ '   ">', '  ">', ' ">', '   " >', '  " >', ' " >' ],
            '"/>'       => [ '" />' ],
            ';?>'       => [ '; ?>' ]
        ];

        $phpString  = trim( $phpString, " \t\v\x00" );  // не убирать из строки: '\n\r'

        if( $isPHP ) {
            //
            // проверка на неправильный php идентификатор
            //
            $lengthString   = mb_strlen( $phpString );
            $pos            = mb_strpos( $phpString, "<?" );

            if( $pos !== false && $pos + 2 < $lengthString ) {

                $character = mb_substr( $phpString, $pos + 2, 1 );

                if ($character == ' ') {
                    $before = mb_substr( $phpString, 0, $pos );
                    $after = mb_substr( $phpString, $pos + 2 );
                    $phpString = $before . "<?php" . $after;
                }
            }
        }
        //
        // удаляем лишние ( больше чем один ) пробелы
        //
        $box        = explode( ' ', $phpString );
        $boxResult  = [];

        foreach ( $box as $boxItem ) {

            $boxItem   = trim( $boxItem, " \t\v\x00" );  // не убирать из строки: '\n\r'

            if( ! empty( $boxItem ) )
                $boxResult [] = $boxItem;
        }

        $result     = implode( ' ', $boxResult );
        //
        // собрали обратно строку с максимум - одним пробелом
        // теперь убираем мелкие огрехи форматирвоания
        //
//      $this->logging_debug( 'до выкусывания мелочей: =' . $result . '=' );

        foreach ( $boxCheck as $replaceString => $searchItems ) {

            foreach ( $searchItems as $search ) {

                $result = str_ireplace( $search, $replaceString, $result );
            }
        }

//      $this->logging_debug( 'после выкусывания     : =' . $result . '=' );

        return( $result );
    }


    public function IsStringEqual( $firstString, $secondString, $caseSensitive=true, $formating=false, $isPHP=false )
        //
        //   добавлено 2022-09-01
        //   сравнение строк целиком. Может быть применено для любых строк, при этом: $formating и $isPHP должны быть - false.
        //   если $caseSensitive=true (по умолсанию) - то регистрозависимое
        //   если $formating=true                    - предварительно строки форматируются (если это строки с html тегами),
        //                                             из них удаляются лишние пробелы.
        //   если $isPHP=true                        - проверяется корректность php тегов ( "<?" заменяется на "<?php" )
        //
    {
        $result         = false;
        $stringFormating= '';

        $firstString    = trim( $firstString );
        $secondString   = trim( $secondString );

        if( $formating ) {
            $stringFormating= 'Форматирование включено - ';
            $firstString    = $this->FormatCodeString( $firstString, $isPHP );
            $secondString   = $this->FormatCodeString( $secondString, $isPHP );
        }

        if( ! $caseSensitive ) {    // поправил 26 сентября 2022

            $firstString    = mb_strtolower( $firstString );
            $secondString   = mb_strtolower( $secondString );
        }
//      $this->logging_debug( $stringFormating . 'сравниваем: =' . $firstString . '= и =' . $secondString . '=' );

        if( $firstString == $secondString )
            $result = true;

        return( $result );
    }



    public function IsMatchInString( $one, $two, $percent=90, &$index=0 )
        //
        // Ищет совпадения строк $one и $two с указанным процентом совпадения и по указанному смещению от начала строк.
        // перед сравнением из строк удаляются скобки, минусы, плюсы, разные виды апострофов, которые любят лкпить
        // разные люди.
        // СОВПАДЕНИЯ ПО УКРАИНСКИМ СЛОВАМ - МЕТОД НЕ ПОКАЗЫВАЕТ! НУЖЕН БОЛЕЕ СЛОЖНЫЙ АЛГОРИТМ ПРОВЕРОК !!!
        //
    {
        $result = false;

        if( ! empty( $one ) && ! empty( $two ) ) {

            $stringOne = $this->PrepareStringToCompare($one);
            $stringOne = str_replace( [ "(", ")", "[", "]", "-", "+", "*" ], " ", $stringOne);
            $stringOne = str_replace( [ "'", "’", "`", "\"" ], "", $stringOne);

            $stringTwo = $this->PrepareStringToCompare($two);
            $stringTwo = str_replace( [ "(", ")", "[", "]", "-", "+", "*" ], " ", $stringTwo);
            $stringTwo = str_replace( [ "'", "’", "`", "\"" ], "", $stringTwo);

            $result = $this->IsFragmentInString( $stringOne, $stringTwo, $percent, $index );

            if( ! $result ) {

                $result = $this->IsFragmentInString( $stringTwo, $stringOne, $percent, $index );
            }
        }

        return( $result );
    }


    public function IsStreetInAddress( $address, $street, $percent=90, &$index=0 )
    //
    //   В $address ищются строки из $street ($street - строка, но если в ней несколько слов,
    //   то при поиске она разбивается на отдельные слова).
    //   Ищется с разными подстановками, типа ї, і и т.д.
    //   Так-же имеется параметр позволяющий сравнивать не полностью слова из названия улицы, а по проценту.
    //   Если найдено хоть одно совпадение - тут-же возвращается TRUE,
    //   иначе все проверяется до конца и возвращается FALSE.
    //
    {
        $result     = false;

        if( ! empty( $address ) ) {

            $street     = mb_strtolower($street);
            $street     = str_replace( ["(", ")","-"], " ", $street );

            $address    = $this->PrepareStringToCompare( $address );
            $address    = str_replace( ["(", ")", "-"], " ", $address );
            $lowerField = mb_strtolower( $address );

            $result         = $this->IsFragmentInString( $lowerField, $street, $percent, $index );

            if( ! $result ) {
                $streetVariant  = str_replace("'","", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("'","\"", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("’","", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("'","`", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("`","'", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("'","’", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("’","'", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("е","і", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("є","і", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("є","е", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("е","є", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("о","і", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("і","о", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("ї","і", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("і","ї", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("і","и", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }

            if( ! $result ) {
                $streetVariant  = str_replace("и","і", $street );
                $result         = $this->IsFragmentInString( $lowerField, $streetVariant, $percent, $index );
            }
        }

        return( $result );
    }

    public function IsFragmentInString( $parString, $fragment, $percent=90, &$index=0 ) {

        $result     = false;
        $box        = explode( ' ', $fragment );

        foreach( $box as $str ) {

            $str            = trim($str);
            $lengthPart     = mb_strlen( $str );
            $lengthCompare  = round( ( $lengthPart * $percent )/ 100, 0, PHP_ROUND_HALF_DOWN );
            if( !( $lengthCompare > 0 && $lengthCompare < $lengthPart ) ) {
                $lengthCompare  = $lengthPart - 1;
            }
//          $this->logging_debug( '' );
//          $this->logging_debug( 'Часть улицы: ' . $str . ', lengthPart: ' . $lengthPart . ', lengthCompare: ' . $lengthCompare );

            $str        = mb_substr( $str, 0, $lengthCompare );     // откусываем ( 100 - $percent ) последних символ в пользу идиотов !!!
            if( mb_strlen( $str ) < 2 ) {
                $index++;
                continue;                                           // если в итоге получили 1 символ - пропускаем
            }

            $pos        = mb_strpos( $parString, $str  );
            $logString  = ($pos === false) ? "False" : $pos;

            if( $pos !== false ) {
//                  $this->logging_debug( '' );
//                  $this->logging_debug( 'строка: ' . $lowerField . ', часть фрагмента: ' . $str . ', позиция: ' . $logString );
            }
            $result = ($pos === false) ? false : true;

            if( $result )
                break;

            $index++;
        }

        return( $result );
    }




    public function IsDigitSimbolInText( $text, &$pos=false, &$simbol=false )
        //
        //   В $text ищется цифровой символ. если найдено хоть одно совпадение - тут-же возвращается TRUE,
        //   иначе все проверяется до конца и возвращается FALSE.
        //
    {
        $result = false;

        if( !empty( $text ) ) {

            $strLength = mb_strlen( $text );
            $isHouse     = false;

//          $this->logging_debug( 'House: ' . $result );

            for ( $i=0; $i < $strLength; $i++ ) {

                $simbol  = mb_substr( $text, $i, 1 );

                if( ctype_digit( $simbol ) ) {
                    $result     = true;
                    $pos        = $i;
                    break;
                } else {
//                 $this->logging_debug( 'Simbol: ' . $simbol );
                }
            }
        }

        return($result);
    }


    public function RemoveWordInBrackets( $text )
    {
        $result     = '';
        $lengthText = mb_strlen( $text );
        $i          = 0;
        $isLeft     = false;
        $isRight    = false;

        for( ; $i < $lengthText; $i++) {
            if( mb_substr( $text, $i, 1 ) == "(" ) {
                $isLeft     = $i;
                break;
            }
        }

        if( $isLeft !== false ) {

//          $this->logging_debug( 'фраза в скобках в тексте: ' . $text . ', i=' . $i . ', left = ' . $isLeft . ', right = ' . $isRight );

            for (; $i < $lengthText; $i++) {
                if (mb_substr($text, $i, 1) == ")") {
                    $isRight    = $i;
                    break;
                }
            }
        }

        if( $isLeft && $isRight ) {
            $lengthBrackets = $isRight - ($isLeft - 1);
            $brackets   = mb_substr( $text, $isLeft, $lengthBrackets );
            $result     = trim( str_replace( $brackets, '', $text ) );
//          $this->logging_debug( 'удалена фраза в скобках: ' . $brackets );

        } else {
            $result     = $text;
//          $this->logging_debug( 'не найдена фраза в скобках в тексте: ' . $text . ', i=' . $i . ', left = ' . $isLeft . ', right = ' . $isRight );
        }

        return( $result );
    }

    public function InArrayAndSubarray( $caption, $box, $keySearch, &$indexFirst, $nextBoxField='items' )
    //
    // метод ищет в сложном массиве по двум уровням, перебирая в цикле первый уровень, и в каждой итерации
    // берется поле - $nextBoxField, и в нем ищется значение - $caption по полю с названием - $keySearch
    // в переменной - $indexFirst возвращается индексное смещение в массиве на первом уровне (в котором успешно
    // найдено искомое на втором уровне), и возвращает значение индекса которое соответствует
    // найденному значению - $caption.
    // Если ничего не найденно, возвращается значение === false
    //
    {
        $indexFirst     = 0;
        $indexResult    = 0;

        foreach( $box as $item ) {
            //
            // поиск по массиву $item[$nextBoxField] по колонке - $keySearch строки - $caption
            //
            $is = array_search( $caption, array_column(  $item[$nextBoxField] , $keySearch) );

            if( $is !== false ) {
                $indexResult    = $is;
                break;
            }
            $indexFirst++;
        }

        return( $indexResult );
    }

    public function ArrayMerge( & $box, $boxMerged ) {

         foreach ( $boxMerged as $item ) {
             $box []    = $item;
         }
    }

}
