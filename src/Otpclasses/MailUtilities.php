<?php

namespace Otpclasses\Otpclasses;

use Drupal\Core\Render\Markup;
use Otpclasses\Otpclasses\LogUtilities;
use Otpclasses\Otpclasses\CommonDataBox;
use Otpclasses\Otpclasses\Gumlet\ImageResize;
use Otpclasses\Otpclasses\Gumlet\ImageResizeException;

class MailUtilities extends LogUtilities
{

    public function __construct( $logName = '/mailutilities.log', $cuteIdentifier = 'MailUtilities.', $cuteModule = true, $withOldLog = true ) {

        parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

    }

    public function __destruct() {

        parent::__destruct();
    }




    public function SendMail($msg,
                             $subject,
                             $mail_to,
                             $mail_from,
                             $cc_to,
                             $bcc_to,
                             $type_html=true,
                             $charset='UTF-8',
                             $boxFiles=false )
      //
      // Отправка почтового сообщения
      //
      // $msg       : тело почтового сообщения
      // $type_html : true - html, false - text ( тип сообщения )
      // $charset   : кодировка сообщения
      // $subject   : тема сообщения
      // $mail_to   : e-mail кому отправить
      // $mail_from : e-mail от кого
      // $cc_to     : e-mail кому отправить копию
      // $bcc_to    : e-mail кому отправить скрытую копию
      // $boxFiles  : [
      //     'common' => [ 'max_file_size' => maxFileSize, '$max_image_pixel' => maxImagePixel ]
      //     'items'  => [
      //          [ 'full_file_name' => full_file_name ],
      //              ....
      //          [ 'full_file_name' => full_file_name ]
      //    ]
      // ]
      //
      // RETURN - true, если письмо было принято для дальнейшей передаче почтовому серверу
      //
    {
        $attache        = false;
        $boxFilePrepare = [];
        $eol            = "\r\n";
        $from           = trim( $mail_from, $eol );
        $to             = trim( $mail_to, $eol);
        $subj           = trim( $subject, $eol);
        $bcc            = trim( $bcc_to, $eol);
        $cc             = trim( $cc_to, $eol);
        $message        = str_replace( "\n", $eol, $msg );
        $un             = strtoupper( uniqid( time() ) );
        $head           = $body = '';
        //
        //
        //
        if( ! empty( $boxFiles ) ) {

          $attache        = true;
          $maxFileSize    = $boxFiles[ 'common' ][ 'max_file_size' ];
          $maxImagePixel  = $boxFiles[ 'common' ][ '$max_image_pixel' ];

          foreach( $boxFiles['items'] as $parFile ) {

            $boxFilePrepare[] = [
                'F_PATH' => $parFile[ 'full_file_name' ],
                'F_LINK' => $f = fopen( $parFile[ 'full_file_name' ], "rb"),
                'F_SIZE' => 0,
                'F_SEND' => true
              ];
          }
          //
          // Проверка размера приатаченных файлов (картинок)
          //
          foreach( $boxFilePrepare as & $preparedFile ) {

            $sizeFile               = filesize( $preparedFile["F_PATH"] );
            $preparedFile["F_SIZE"] = $sizeFile;
            $delta                  = $maxFileSize * 1024 * 1024 - $sizeFile;

            if( $delta < 0 ) {
              //
              // Размер файла $sizeFile превышает максимально-допустимый: $maxFileSize !!!
              // Еще надо проверить - является-ли файл картинкой! Если является, то
              // картинку мы можем отмасштабировать!!!
              //
              $isImage    = exif_imagetype( $preparedFile["F_PATH"] );

              $this->logging_debug( 'Exif image type: ' . $isImage );

              if( $isImage >= IMAGETYPE_GIF && $isImage <= IMAGETYPE_WEBP ) {

                $imageInfo  = getimagesize( $preparedFile["F_PATH"] );
                $image      = new ImageResize( $preparedFile["F_PATH"] );

                $this->logging_debug( 'File: ' . $preparedFile["F_PATH"] . ', size: ' . $sizeFile . ' byte, ' . $imageInfo[ 3 ] );

                if( $imageInfo[0] > $imageInfo[1] && $imageInfo[0] > $maxImagePixel ) {

                  $image->resizeToWidth( $maxImagePixel );
                  $this->logging_debug( 'Reduce to width: ' . $maxImagePixel );

                } elseif( $imageInfo[1] > $imageInfo[0] && $imageInfo[1] > $maxImagePixel ) {

                    $image->resizeToHeight( $maxImagePixel );
                    $this->logging_debug( 'Reduce to height: ' . $maxImagePixel );

                } else {

                    $firstFraction  = ceil( 100 * ($maxFileSize * 1024*1024) / $sizeFile );
                    $percent        = ceil( 100 * sqrt( ($maxFileSize * 1024*1024) / $sizeFile ) );

                    $image->scale( $percent );

                    $sizeFileNew    = $sizeFile * $firstFraction / 100;

                    $this->logging_debug( 'Reduce to: ' . $percent . ' %, new file size ~ ' . $sizeFileNew );
                }

                $image->save( $preparedFile["F_PATH"] );
                unset( $image );

                $imageInfoNew   = getimagesize( $preparedFile["F_PATH"] );

                $this->logging_debug( 'new image size: ' . $imageInfoNew[ 3 ] );
              } else {
                //
                // Файл не является картинкой, и его размер превышает допустимый !
                //
                $preparedFile["F_SEND"] = false;
              }
            }
          }
          //
          //
          //
        }
        //
        // header
        //
        $head .= "Mime-Version: 1.0".$eol;
        $head .= "From: $from".$eol;
        $head .= "Reply-To: $from".$eol;
        $head .= "X-Priority: 3 (Normal)".$eol;
        $head .= "X-MID: $un".$eol;
        $head .= "X-EVENT_NAME: ISALE_KEY_F_SEND".$eol;

        if( strpos( $cc, "@" ) !== false )
            $head .= "CC: $cc" . $eol;

        if( strpos( $bcc, "@" ) !== false )
            $head .= "BCC: $bcc" . $eol;

        $head .= "Content-Type: multipart/mixed; ";
        $head .= "boundary=\"----".$un."\"".$eol.$eol;

        // body
        $body = "------".$un.$eol;

        if(  ! $type_html )
            $body .= "Content-Type:text/plain; charset=" . $charset . $eol;
        else
            $body .= "Content-Type:text/html; charset=" . $charset . $eol;

        $body .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
        $body .= $message.$eol.$eol;

        if( $attache ) {

//        $this->logging_debug( 'Сформирован массив аттачей:' );
//        $this->logging_debug( $boxFilePrepare );

          foreach( $boxFilePrepare as $currentFile ) {

            if( $currentFile["F_SEND"] ) {

//            $this->logging_debug( 'отправляем аттач:' );
//            $this->logging_debug( $currentFile );

              $body .= "------" . $un . $eol;
              $body .= "Content-Type: application/octet-stream; name=\"" . basename($currentFile["F_PATH"]) . "\"" . $eol;
              $body .= "Content-Disposition:attachment; filename=\"" . basename($currentFile["F_PATH"]) . "\"" . $eol;
              $body .= "Content-Transfer-Encoding: base64" . $eol . $eol;
              $body .= chunk_split(base64_encode(fread($currentFile["F_LINK"], $currentFile["F_SIZE"]))) . $eol . $eol;
            }
          }
        }

        $body .= "------".$un."--";
        // send
        $result_mail = mail( $to, $subj, $body, $head  );

        return( $result_mail );
    }


  //
  // отправка сообщений админу
  //
  public function SendAdminMessage( $subject, $msgEmail='', $boxMessage=[] )
  {
    $resultSend             = false;

    //
    // используем e-mal для тестового сообщения
    //
    $localEmailTO     = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailTo' ];
    $localEmailCC     = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailCC' ];
    $localEmailBCC    = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailBCC' ];
    $localEmailFrom   = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailFrom' ];

    $resultSend = $this->SendMail(
      $msgEmail,
      $subject,
      $localEmailTO,
      $localEmailFrom,
      $localEmailCC,
      $localEmailBCC,
      false,                   // text/plain type
      'UTF-8',
      false                   // file box
    );

    if ($resultSend) {

      $templateBox = [
        'EMAIL_FROM' => $localEmailFrom,
        'EMAIL_TO' => $localEmailTO,
        'CC' => $localEmailCC,
        'BCC' => $localEmailBCC
      ];

      $this->logging_debug( '' );
      $this->logging_debug('Почтовое сообщение отправлено на адреса администраторов:');
      $this->logging_debug($templateBox);
    }

    return( $resultSend );
  }


  //
  // отправка сообщений по указанному почтовому шаблону
  //
  public function SendMessage( $msgBox, $twigScoringPath, $mailFrom, $mailTo, $mailCc='', $mailBcc='' )
  {
    $resultSend = false;
    //
    $subject    = $msgBox['SUBJECT'];
//  $twigBox['theme_hook_original'] = 'not-applicable';
//  $twigBox['msgboody'] = $msgBox;

    $msgEmail   = $this->getMessageBody( $msgBox, $twigScoringPath );

//  $this->logging_debug('Почтовое сообщение:');
//  $this->logging_debug($msgEmail);
//  $this->logging_debug('');

    $resultSend = $this->SendMail(
      $msgEmail,
      $subject,
      $mailTo,                  // mail to
      $mailFrom,                // mail from
      $mailCc,                  // mail cc
      $mailBcc,                 // mail bcc
      false,                    // text/plain type
      'UTF-8',
      false                     // file box
    );


    if ($resultSend) {
//    $this->logging_debug('Почтовое сообщение отправлено.');
//    $this->logging_debug($templateBox);
    }

    return( $resultSend );
  }



  public function getMessageBody( $twigBox, $twigPath )
    //
    // Возвращает готовое сформированное тело сообщения по шаблону: $twigPath с использованием данных: $twigBox
    //
  {
    $renderArray = [
      'msgbox' => $twigBox
    ];

//  $this->logging_debug('');
//  $this->logging_debug('twigbox:' );
//  $this->logging_debug( $twigBox );
//  $this->logging_debug('');

//  $fileBox = file_get_contents( $twigPath );

//  $this->logging_debug('');
//  $this->logging_debug('Шаблон: ' . $twigPath );
//  $this->logging_debug($fileBox);
//  $this->logging_debug('');

    try {
      $template = \Drupal::service('twig')->load($twigPath);

      try {
        $rendered = $template->render($renderArray);

        $this->logging_debug( '' );
        $this->logging_debug( 'rendered:' );
        $this->logging_debug( $rendered );

        $msgBoody = Markup::create($rendered);

      } catch(  \Exception $e ) {
        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'getMessageBody rendered Exception:' );
        $this->logging_debug( $result );
        $this->loggingBackTrace();
        $msgBoody = "Исключение при формировании почтового шаблона: " . $e->getMessage();
      }
    } catch( \Exception $e ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'getMessageBody template Exception:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      $msgBoody = "Исключение при формировании почтового шаблона: " . $e->getMessage();
    } catch( \Error $e ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'getMessageBody template Error:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      $msgBoody = "Ошибка при формировании почтового шаблона: " . $e->getMessage();
    }

    return ($msgBoody);
  }


}
