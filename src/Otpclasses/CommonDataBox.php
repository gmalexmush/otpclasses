<?php

namespace Otpclasses\Otpclasses;

class CommonDataBox
{
    //
    // Общие данные прроекта
    //
    public static $boxFormTestFields = [
    'phone'     => [ '111111111', '222222222', '333333333' ],
    'gsm'       => [ '111111111', '222222222', '333333333' ],
    'fio'       => [ 'тест', 'test' ],
    'pib'       => [ 'тест', 'test' ],
    'name'      => [ 'тест', 'test' ],
    'owner'     => [ 'тест', 'test' ],
    'contacts'  => [ 'test' ],
    'okpo'      => [ '1111111111', '1234567890' ],
    'inn'       => [ '1111111111', '1234567890' ]
    ];

    public static $boxEmail = [
    'TestSending'   => [
        'EmailTo'   => 'gmsandromush@gmail.com, tretiak.oleg@gmail.com, webmaster@otpbank.com.ua, gang.ukr.dp@gmail.com',
        'EmailCC'   => 'oleg.tretiak@otpbank.com.ua',
        'EmailBCC'  => '',
        'EmailFrom' => 'webmaster@otpbank.com.ua'
        ]
    ];

}

