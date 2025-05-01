<?php

namespace Otpclasses\Otpclasses\LoanFactory;

use Otpclasses\Otpclasses\MailUtilities;
use Otpclasses\Otpclasses\LoanFactory\LoanFactory;

class ApiLead extends MailUtilities
{

    public function __construct( $logName = '/apilead.log', $cuteIdentifier = 'ApiLead.', $cuteModule = true, $withOldLog = true  ) {

      parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
    }



    public function __destruct() {

      parent::__destruct();
    }


    public function ApiLeadCreate( $requestParams,
                                   $boxRequest,
                                   & $boxResults,
                                   & $errorLevel,
                                   $guzzle,
                                   $cookie,
                                   $testMode,
                                   $nextStatus='0' )
  {
    $result         = [];

    $requestUri     = $requestParams['requestUri'] . 'api/Lead/Create';
    $requestUrl     = $requestParams['requestProtocol'] . $requestParams['requestDomen'] . $requestUri;
    $headers        = [
      'Content-Type'  => $requestParams['headers']['Content-Type'],
      'Accept' => $requestParams['headers']['Accept'],
      'User-Agent'=> $requestParams['headers']['User-Agent'],
      'Api-Key' => $requestParams['headers']['Api-Key']
    ];

    $phone  = '+';
    $phone .= $boxRequest['clientPhone'];
    $boxRequest['clientPhone'] = $phone;      // добавляем символ плюс в телефон, так надо!

//  $this->logging_debug( 'phone: ' . $phone );
//  $this->logging_debug( 'clientPhone: ' . $boxRequest['clientPhone'] );

//  $this->logging_debug( 'boxRequest: ' );
//  $this->logging_debug( $boxRequest );

    $this->logging_debug( '' );
    $this->logging_debug( 'Стартуем POST запрос создания лида - метод api/Leade/Create' );
    $this->logging_debug( 'URL: ' . $requestUrl );

//  $this->logging_debug( 'boxResults:' );
//  $this->logging_debug( $boxResults );

//  $this->logging_debug( 'boxRequest:' );
//  $this->logging_debug( $boxRequest );

    $this->logging_debug( 'Отправляем данные по результату ' . $boxResults[ 'id' ] . ':' );

    if( !empty( $boxRequest['clientName'] ) )
      $this->logging_debug( 'clientName:           ' . $boxRequest['clientName'] );
    if( !empty( $boxRequest['ipn'] ) )
      $this->logging_debug( 'ipn:                  ' . $boxRequest['ipn'] );
    if( !empty( $boxRequest['clientPhone'] ) )
      $this->logging_debug( 'clientPhone:          ' . $boxRequest['clientPhone'] );
    if( !empty( $boxRequest['source'] ) )
      $this->logging_debug( 'source:               ' . $boxRequest['source'] );
    if( !empty( $boxRequest['medium'] ) )
      $this->logging_debug( 'medium:               ' . $boxRequest['medium'] );
    if( !empty( $boxRequest['campaignName'] ) )
      $this->logging_debug( 'campaignName:         ' . $boxRequest['campaignName'] );
    if( !empty( $boxRequest['carName'] ) )
      $this->logging_debug( 'carName:              ' . $boxRequest['carName'] );
    if( !empty( $boxRequest['carPrice'] ) )
      $this->logging_debug( 'carPrice:             ' . $boxRequest['carPrice'] );
    if( !empty( $boxRequest['carLink'] ) )
      $this->logging_debug( 'carLink:              ' . $boxRequest['carLink'] );
    if( !empty( $boxRequest['branch'] ) )
      $this->logging_debug( 'branch:               ' . $boxRequest['branch'] );

    try {
//      $response = $this->guzzleClient->request('POST', $url, ['json' => $boxRequest, 'cookies' => $requestCcookieJar ] );
//      $response = $this->guzzleClient->request('POST', $url, ['json' => $boxRequest ] );
//      $response = $this->guzzleClient->request('POST', $url, ['json' => $boxRequest, 'cookies' => $this->cookieJar ] );

      $result['lead']['result_result']    = 'Exception';    // request_bpm

      $response = $guzzle->request('POST', $requestUrl,
        [ 'json' => $boxRequest,
          'headers' => $headers,
          'cookies' => $cookie ] );

      $this->logging_debug( 'Запрос отработал. Проверяем статус-код!' );

      $error_level = $response->getStatusCode();

      if ($error_level != 200 ) {

        $this->logging_debug('Результат отрицательный, статус-код: ' . $response->getStatusCode());

        $result['lead']['result_httpstatuscode']      = $error_level;
        $result['lead']['result_result']    = LoanFactory::$codeResultForLeadCreate;    // request_bpm

      } else {
        $body           = $response->getBody();
        $retJson        = $body->getContents();
        //
        $retInfo        = json_decode( $retJson, true );

        if( empty( $retInfo ) ) {
          $this->logging_debug( 'Неверный JSON-формат' );

          $retJson        .= "}}";    // разрабы сервера BPM потеряли 2 фигурные скобки ...
          $retInfo        = json_decode( $retJson, true, 32768 );
        }
        //
//        $this->logging_debug('return:');
//        $this->logging_debug( $retInfo );

//        $responseHeaders    = $response->getHeaders();
//        $responseHeaders    = $response->getHeader( 'Set-Cookie' );
//        $this->logging_debug( 'responseHeaders:' );
//        $this->logging_debug( $responseHeaders );

        if( ! empty( $retInfo['leadId'] ) && $retInfo['key'] == 'OK' ) {

          $result['lead']['result_guid']              = $retInfo['leadId'];           // GUID
          $result['lead']['result_status']            = $retInfo['status'];           // 1 - успешно
          $result['lead']['result_httpstatuscode']    = $retInfo['code'];
          $result['lead']['result_code']              = $retInfo['code'];
          $result['lead']['result_message']           = ($testMode == 'Y') ? 'ТЕСТОВЫЙ ЛИД' : $retInfo['message'];
          $result['lead']['result_result']            = $nextStatus; // следующая операция или по умолчанию завершить все операции
        } else {
          $result['lead']['result_guid']              = $retInfo['leadId'];           // GUID
          $result['lead']['result_status']            = $retInfo['status'];           //
          $result['lead']['result_httpstatuscode']    = $retInfo['code'];
          $result['lead']['result_code']              = 'leadError';
          $result['lead']['result_message']           = $retInfo['message'];
          $result['lead']['result_result']            = LoanFactory::$codeResultForLeadCreate;
        }
        $this->logging_debug('Error Code: ' . $result['lead']['result_httpstatuscode'] . '. Error Message: ' . $result['lead']['result_message'] );

        $this->logging_debug('Запроса к неизвестному серверу банка завершена однака.');
      }

    } catch( \Exception $e ) {

      $this->ApiLeadCreateException( 'Exception', $e, $boxRequest, $response, $result );
      $this->loggingBackTrace();
      throw $e;

    } catch( \Error $e ) {

      $this->ApiLeadCreateException( 'Error', $e, $boxRequest, $response, $result );
      $this->loggingBackTrace();
      throw $e;
    }

    return( $result );
  }




  public function ApiLeadCreateException( $errorName, $e, $boxRequest, $response, & $result )
  {

    $this->logging_debug('');
    $this->logging_debug( $errorName . ' во время запроса к неизвестному серверу банка.');

    if( ! empty( $boxRequest ) ) {
//    $this->logging_debug('request: ');
//    $this->logging_debug($boxRequest);
    }

    if( ! empty( $response ) ) {
//    $this->logging_debug('result: ');
//    $this->logging_debug($response);

      $errorBody = $response->getBody();
      $errorJson = $errorBody->getContents();
      $errorInfo = json_decode($errorJson, true);

      if (empty($errorInfo)) {
        $this->logging_debug('Неверный JSON-формат ответа с кодом ошибки.');

        $errorJson .= "}}";    // разрабы сервера BPM потеряли 2 фигурные скобки ...
        $errorInfo = json_decode($errorJson, true, 32768);
      }

      $this->logging_debug('Ответ с кодом ошибки:');
      $this->logging_debug($errorInfo);

    } else {
      $msg          = $e->getMessage();

      if( empty( $errorInfo ) )
        $errorInfo    = 'ошибка ' . LoanFactory::$codeResultForLeadCreate . ', ответ от неизвестного сервера банка не получен. получен Exception!';
    }

    $error_level = LoanFactory::$codeResultForLeadCreate;
    $result['lead']['result_httpstatuscode']    = $error_level;
    $result['lead']['result_status']  = $error_level;
    $result['lead']['result_message'] = $msg;
    $result['lead']['result_result']  = $error_level;

    $this->logging_debug('ApiLeadCreate ' . $errorName . ':');
    $this->logging_debug( $result['lead']['result_message'] );
  }



}

