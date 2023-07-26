<?php

namespace Otpclasses\Otpclasses;

use OtpClasses\Otpclasses\LogUtilities;

class DirUtilities extends LogUtilities
{

    public $menuPrefix;
    public $listSearchPath;
    public $listIndexes;

    public function __construct( $logName = '/dirutilities.log', $cuteIdentifier = 'DirUtilities.', $cuteModule = true, $withOldLog = true  ) {

        parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
    }

    public function __destruct() {

        parent::__destruct();
    }


    public function searchFolderBackThatExists($url, $rootFolder, $lid)
    {

        $sourcefolder = $rootFolder . $url;
        $folder = (substr($sourcefolder, -1, 1) == "/") ? $sourcefolder : $sourcefolder . "/";
        $startFolder = (substr($rootFolder, -1, 1) == "/") ? $rootFolder : $rootFolder . "/";

        while ($folder != $startFolder) {

//          $this->logging_debug( 'folder: ' . $folder );

            $i = mb_strlen($folder) - 1 - 1; // index and without slash

            for ($simb = ''; $simb != "/" && $i > 0; $i--)
                $simb = mb_substr($folder, $i, 1);

            $folder = mb_substr($folder, 0, $i + 1 + 1);

            if (mb_strlen($folder) < mb_strlen($startFolder)) {

//              $this->logging_debug( 'startFolder: ' . $startFolder );
//              $this->logging_debug( 'folder: ' . $folder );
                $folder = $sourcefolder;
                break;
            }
        }

        $lengthFolder = mb_strlen($rootFolder);
        $result = mb_substr($folder, $lengthFolder);

        return ($result);
    }


    public function makeDirectoryIfNotExists($fullPath)
    {

        $fullFolderName = $fullPath;
        $isDir = is_dir($fullFolderName);
        $folders = [];

//      $this->logging_debug( 'проверяется папка:' . $fullFolderName );

        if ($isDir)
            $resMake = true;
        else {
            $resMake = false;

            while (!$isDir) {

                $folders [] = $fullFolderName;
                $fullFolderName = dirname($fullFolderName) . '/';    // возвращает последнюю папку без слэша!

//                  $this->logging_debug( 'проверяется папка:' . $fullFolderName );
                $isDir = is_dir($fullFolderName);
            }

            if ($isDir) {

                for ($i = count($folders); $i > 0; $i--) {                // папки в обратной последовательности находятся в массиве!

//                  $this->logging_debug( 'создается папка:' . $folders[ $i - 1 ] );

                    $resMake = $this->makeDirectory($folders[$i - 1]);
                    if (!$resMake)
                        break;

//                  $this->logging_debug( 'resMake: ' . $resMake );
                }
            }
        }

//      $this->logging_debug( '' );

        return ($resMake);
    }

    public function makeDirectory($folderName)
    {

        $result = false;

        if (!is_dir($folderName)) {

            $prava = '0777';
            $resMK = mkdir($folderName, $prava);
            if (!$resMK) {

                $errors = error_get_last();
                $this->logging_debug('папка:' . $folderName . ', ' . $errors['type'] . ' ' . $errors['message']);

            } else {

                chmod($folderName, 0774);
                chown($folderName, 'www-data');
                chgrp($folderName, 'www-data');

                $result = true;
            }

        } else {
            $result = true;
        }

        return ($result);
    }

    public function RightsFolderForApache($folderName)
    {

        $result = false;

        if (is_dir($folderName)) {

            chmod($folderName, 0774);
            chown($folderName, 'www-data');
            chgrp($folderName, 'www-data');

            $result = true;
        }

        return ($result);
    }

    public function RightsFileForApache($fullName)
    {

        $result = false;

        if (is_file($fullName)) {

            chmod($fullName, 0664);
            chown($fullName, 'www-data');
            chgrp($fullName, 'www-data');

            $result = true;
        }

        return ($result);
    }




    public function DirectoryFiles($path, $extension)
        //
        // возвращает либо пустой массив, либо заполненный такой структуры:
        // [
        // ['name' => $filename, 'size' => $sizeFile, 'date' => $dateFile ]
        // ]
        //
    {

        $boxFiles = [];

        $this->logging_debug('path: ' . $this->documentRoot . $path);

        foreach (scandir($this->documentRoot . $path) as $filename) {

            if ($filename != '.' && $filename != '..') {

                $parts = pathinfo($filename);
                if ($parts['extension'] == $extension) {

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

        return ($boxFiles);
    }

    public function DirectoryFolders( $path, $withPath=false, $excludeEntries=[] )
        //
        // сканируется указанная папка $path, и возвращаются папки, которых нет в $excludeEntries.
        // если $withPath == false, то вернется только имя папки без пути и без слеша в конце.
        // если $withPath == true, то спереди добавляется полный путь, а в конце слешь.
        //
    {
        $allFolders = [];

        $this->logging_debug( 'Массив исключений:' );
        $this->logging_debug( $excludeEntries );

        if( $handle = opendir( $path ) ) {

            while( false !== ( $entry = readdir( $handle ) ) ) {

                if( $entry == '.' || $entry == '..' )
                    continue;

                if( ! empty( $excludeEntries ) && in_array( $entry, $excludeEntries ) )
                    continue;

                if( is_dir( $path . '/' . $entry ) ) {

                    $this->logging_debug( 'добавляется папка: ' . $entry );

                    if( $withPath )
                        $allFolders [] = $path . '/' . $entry . '/';
                    else
                        $allFolders [] = $entry;
                }
            }
            closedir( $handle );
            natsort( $allFolders );
        }

        return( $allFolders );
    }


    public function CreateMessageFile($relativePathName, $message)
    //
    // создает по указанному в $relativePathName относительному имени файл,
    // и записывает в него сообщение - $message
    //
    {
        $fullPath   = pathinfo( $this->documentRoot . $relativePathName, PATHINFO_DIRNAME ) . '/';

        if( file_exists( $this->documentRoot . $relativePathName ) ) {

            $f = fopen( $this->documentRoot . $relativePathName, "a+" );
        } else {
            if( ! is_dir( $fullPath ) ) {

                $prava = '777';
                mkdir( $fullPath, $prava );
                chmod( $fullPath, 0774 );
                chown( $fullPath, 'www-data' );
                chgrp( $fullPath, 'www-data' );
            }

            $f = fopen( $this->documentRoot . $relativePathName, "w" );
            chmod( $this->documentRoot . $relativePathName, 0664 );
            chown( $this->documentRoot . $relativePathName, 'www-data' );
            chgrp( $this->documentRoot . $relativePathName, 'www-data' );
        }

        fwrite( $f, $message . "\r\n" );
        fclose( $f );
    }

    public function StringCountInFile( $relativePathName )
    //
    // Быстрый подсчет количества строк в файле
    //
    {
        $result = false;
        $n      = 0;

        if( file_exists( $this->documentRoot . $relativePathName ) ) {

            $f = fopen( $this->documentRoot . $relativePathName, "r" );

            while ( !feof( $f ))    {
                $bufer = fread( $f,1048576 );
                $n += mb_substr_count($bufer,"\n");
            }

            fclose($f);
            $result = $n;
        } else {
            $this->logging_debug( 'file: ' . $this->documentRoot . $relativePathName . ' - не найден!' );
        }

        return( $result );
    }


}

