<?php

namespace Otpclasses\Otpclasses;

use OtpClasses\Otpclasses\LogUtilities;

class DateUtilities extends LogUtilities
{

    public $popularFormats;

    public function __construct($logName = '/dateutilities.log', $cuteIdentifier = 'DateUtilities.', $cuteModule = true, $withOldLog = true)
    {

        parent::__construct($logName, $cuteIdentifier, $cuteModule, $withOldLog);

        $this->popularFormats   = [
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
    }

    public function __destruct()
    {

        parent::__destruct();
    }


    public function UnixTimeDatetimeToDate($dateTime = false)
        //
        // получим дату в формате unixtime без учета времени
        //
    {
        date_default_timezone_set('Europe/Kiev');

        if (empty($dateTime))
            $dateTime = time();

        $stringDate = date("Y.m.d", $dateTime);
        $result = mktime(0, 0, 0, mb_substr($stringDate, 5, 2),
            mb_substr($stringDate, 8, 2),
            mb_substr($stringDate, 0, 4));

        return ($result);
    }

    public function StringTimeToTime( $stringTime )
    {
        date_default_timezone_set('Europe/Kiev');

        $hours    	= intval( mb_substr( $stringTime, 0, 2 ) );
        $minutes  	= intval( mb_substr( $stringTime, 3, 2 ) );
        $seconds  	= intval( mb_substr( $stringTime, 6, 2 ) );

        $result		= mktime( $hours, $minutes, $seconds,  0, 0, 0 ); 		// время в секундах

        return( $result );
    }

    public function OnlyTime( $unixDateTime=false, & $unixDate=0 )
    {
        date_default_timezone_set('Europe/Kiev');

        if( empty( $unixDateTime ) )
            $unixDateTime = $this->actionOffsetDebug * 3600 + time();

        $stringDate = date("Y.m.d H:i:s", $unixDateTime );

        $result 	= mktime(	mb_substr($stringDate, 11, 2),
            mb_substr($stringDate, 14, 2),
            mb_substr($stringDate, 17, 2),
            0,
            0,
            0 );                                                // время в секундах

        $unixDate 	= mktime(0,
            0,
            0,
            mb_substr($stringDate, 5, 2),
            mb_substr($stringDate, 8, 2),
            mb_substr($stringDate, 0, 4));                          // дата в секундах


        return( $result );
    }


    public function DateGlueTime( $unixDate, $unixTime, $stringDateFormat="d.m.Y H:i:s" )
        //
        // Склеивание даты (в Unixtime) и времени (в Unixtime) в одно целое в формате $stringDateFormat.
        // Просто сложить их оказывается не катит.
        //
    {
        date_default_timezone_set('Europe/Kiev');

        $stringDate = date("Y.m.d", $unixDate );
        $stringTime = date("H:i:s", $unixTime );

        if( $unixTime >= 0 ) {

            $dateTime = mktime(mb_substr($stringTime, 0, 2),
                mb_substr($stringTime, 3, 2),
                mb_substr($stringTime, 6, 2),
                mb_substr($stringDate, 5, 2),
                mb_substr($stringDate, 8, 2),
                mb_substr($stringDate, 0, 4)
            );


        } else {
            $this->logging_debug('DateGlueTime (время неверно - отрицательное значение): ' . $stringTime );
            $dateTime   = $unixDate + $unixTime;    // вряд-ли это правильно, нужна отладка!
        }

        $result = date($stringDateFormat, $dateTime);

        return( $result );
    }


    public function PeriodIncludeTime($checkTime, $startTime, $finishTime)
        //
        // $checkTime                   - unix time ( вызов time() )
        // $startTime, $finishTime      - строки типа: hh:mm:ss
        //
        // return - true, если время попало в указанный период!
        //
    {
        date_default_timezone_set('Europe/Kiev');

        $result = true;

        $curentDateWithoutTime = date('Y-m-d');
        $yyy = intval(mb_substr($curentDateWithoutTime, 0, 4), 10);
        $mmm = intval(mb_substr($curentDateWithoutTime, 5, 2), 10);
        $ddd = intval(mb_substr($curentDateWithoutTime, 8, 2), 10);
//      $this->logging_debug( 'curentDate: ' . $yyy . ' ' . $mmm . ' ' . $ddd );

        $h = intval(mb_substr($startTime, 0, 2), 10);
        $m = intval(mb_substr($startTime, 3, 2), 10);
        $s = intval(mb_substr($startTime, 6, 2), 10);
//      $this->logging_debug( 'start  time: ' . $h . ' ' . $m . ' ' . $s );

        $startUnixTime = mktime($h, $m, $s, $mmm, $ddd, $yyy);

        $h = intval(mb_substr($finishTime, 0, 2), 10);
        $m = intval(mb_substr($finishTime, 3, 2), 10);
        $s = intval(mb_substr($finishTime, 6, 2), 10);
//      $this->logging_debug( 'finish time: ' . $h . ' ' . $m . ' ' . $s );

        $finishUnixTime = mktime($h, $m, $s, $mmm, $ddd, $yyy);

//      $this->logging_debug( 'checkTime:       ' . $checkTime );
//      $this->logging_debug( 'startUnixTime:   ' . $startUnixTime );
//      $this->logging_debug( 'finishUnixTime:  ' . $finishUnixTime );

        if ($finishUnixTime - $startUnixTime <= 0)  // если период указан неверно - возвращаем false
            $result = false;
        else {
            if ($finishUnixTime - $checkTime > 0 && $checkTime - $startUnixTime > 0) {
                $result = true;
//              $this->logging_debug( 'дата входит в заданный промежуток времени: ' . $startTime . ' - ' . $finishTime );
            } else {
                $result = false;
//              $this->logging_debug( 'дата не входит в заданный промежуток времени: ' . $startTime . ' - ' . $finishTime );
            }
        }

        return ($result);
    }


    public function TimestampFromStringDate($str_date, $maket = 'dd.mm.yyyy')
    {
        date_default_timezone_set('Europe/Kiev');

        $result = true;

        $y = mb_strpos($maket, 'yyyy');
        if ($y === false)
            return (false);

        $m = mb_strpos($maket, 'mm');
        if ($m === false)
            return (false);

        $d = mb_strpos($maket, 'dd');
        if ($d === false)
            return (false);

        switch ($y) {
            case 0:
                $del = mb_substr($maket, 4, 1);
                $ch1 = mb_substr($str_date, 4, 1);
                $ch2 = mb_substr($str_date, 7, 1);
                break;
            case 3:
                $del = mb_substr($maket, 2, 1);
                $ch1 = mb_substr($str_date, 2, 1);
                $ch2 = mb_substr($str_date, 7, 1);
                break;
            case 6:
                $del = mb_substr($maket, 5, 1);
                $ch1 = mb_substr($str_date, 2, 1);
                $ch2 = mb_substr($str_date, 5, 1);
                break;
            default:
                $ch1 = '***';
        }

        if ($ch1 != $del || $ch2 != $del)
            $result = false;
        else {

            $yy = mb_substr($str_date, $y, 4);
            $mm = mb_substr($str_date, $m, 2);
            $dd = mb_substr($str_date, $d, 2);

            $yyy = intval($yy, 10);
            $mmm = intval($mm, 10);
            $ddd = intval($dd, 10);

            $result = mktime(0, 0, 0, $mmm, $ddd, $yyy);

        }
        return ($result);
    }




    public function CreateFromPopularDetectFormat( $value, & $detectedFormat, $roundByDays = false)
        //
        // $value:  дата в виде строки, любого PHP формата ( из списка популярных )
        // возвращает timestamp этой даты,
        // либо 0 если не смогло подобрать формат даты PHP.
        // Если дата найдена, то в переменную: $detectedFormat возвращается найденный формат даты.
        //
    {
        return( $this->CreateFromDetectFormat( $this->popularFormats, $value, $detectedFormat, $roundByDays ) );
    }


    public function CreateFromDetectFormat( $boxFormat, $value, & $detectedFormat, $roundByDays = false)
        //
        // $value:  дата в виде строки, любого PHP формата ( из списка заданного в $boxFormat )
        // возвращает timestamp этой даты,
        // либо 0 если не смогло подобрать формат даты PHP из заданного списка.
        // Если дата найдена, то в переменную: $detectedFormat возвращается найденный формат даты.
        //
    {

        $timeStamp  = 0;

        foreach ( $boxFormat as $format ) {

            $timeStamp  = $this->CreateFromFormat( $format, $value, $roundByDays );

//          $this->logging_debug( "date: " . $value . ', format: ' . $format . ', time: ' . $timeStamp );

            if( $timeStamp > 0 ) {

                $detectedFormat     = $format;
                break;
            }
        }

        return( $timeStamp );
    }



    public function CreateFromPopularFormat( $value, $roundByDays = false)
    //
    // $value:  дата в виде строки, любого PHP формата ( из списка популярных )
    // возвращает timestamp этой даты,
    // либо 0 если не смогло подобрать формат даты PHP.
    //
    {
        return( $this->CreateFromSomeFormat( $this->popularFormats, $value, $roundByDays ) );
    }



    public function CreateFromSomeFormat( $boxFormat, $value, $roundByDays = false)
    //
    // $value:  дата в виде строки, любого PHP формата ( из списка заданного в $boxFormat )
    // возвращает timestamp этой даты,
    // либо 0 если не смогло подобрать формат даты PHP из заданного списка.
    //
    {

        $timeStamp  = 0;

        foreach ( $boxFormat as $format ) {

            $timeStamp  = $this->CreateFromFormat( $format, $value, $roundByDays );

//          $this->logging_debug( "date: " . $value . ', format: ' . $format . ', time: ' . $timeStamp );

            if( $timeStamp > 0 )
                break;
        }

        return( $timeStamp );
    }



    public function CreateFromFormat( $format, $value, $roundByDays = false )
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

//          $this->logging_debug( "dateCurrent: "  );
//          $this->logging_debug( $dateCurrent );
//
//            DateTime Object  (
//              [date] => 2021-03-18 14:38:07.000000
//              [timezone_type] => 3
//              [timezone] => Europe/Kiev
//            )

            $offset         = $dateCurrent->getOffset()/60/60;

//          $this->logging_debug( "onlyDate:    " . $onlyDate );
//          $this->logging_debug( "dateCurrent: " . $dateCurrent->format( $format ) );
//          $this->logging_debug( "offset:      " . $offset );

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

//      $this->logging_debug( "format:     " . $format );
//      $this->logging_debug( "stringDate: " . $stringDate );

        $dateValue      = \DateTime::createFromFormat( $format, $stringDate );

        if( ! empty( $dateValue ) ) {
            $result         = $dateValue->getTimestamp();

            if( $roundByDays ) {
                $resultOffset   = $dateValue->getOffset() / 60 / 60;
                $result         = $this->RoundDateByDays( $result, $resultOffset ); // 00:00:00
            }

        } else {
//          $this->logging_debug( "Получена пустая дата: " . $dateValue );
            $result         = 0;
        }

        return( $result );
    }



    public function ChangeDateFormat( $timeStamp, $destinationFormat )
    {
        $timeZoneKiev   = 'Europe/Kiev';

//      $this->logging_debug( "timeStamp:         " . $timeStamp );
//      $this->logging_debug( "destinationFormat: " . $destinationFormat );

        $dateValue  = new \DateTime();
        $dateValue->setTimestamp( $timeStamp );

        $position   = mb_strpos( $destinationFormat, $timeZoneKiev );

        if( $position !== false ) {
            $destinationFormat  = str_replace( $timeZoneKiev, '', $destinationFormat );
            $destinationFormat  = trim( $destinationFormat );
            $result             = $dateValue->format( $destinationFormat ) . ' ' . $timeZoneKiev;
        } else {

            $result             = $dateValue->format( $destinationFormat );
        }



        if( empty( $result ) ) {
            $this->logging_debug( "ошибка преобразования формата даты!" );
        }

        return( $result );
    }

    public function RoundDateByDays( $timeStamp, $timeZoneNumberHour )
    {
        $dayCorrection      = 0;

        if( $timeZoneNumberHour > 0 )
            $dayCorrection  = 24;

        $result     = 86400 * round( $timeStamp/86400, 0, PHP_ROUND_HALF_DOWN ) - 3600 *
                    ( $timeZoneNumberHour + $dayCorrection );  // делаем 00:00:00 часов!

        return( $result );
    }

}

