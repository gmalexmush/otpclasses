<?php

namespace Otpclasses\Otpclasses\LoanFactory;

use Otpclasses\Otpclasses\MailUtilities;
use Otpclasses\Otpclasses\LoanFactory\LoanFactory;

class Scoring extends MailUtilities
{

    public function __construct( $logName = '/scoring.log', $cuteIdentifier = 'Scoring.', $cuteModule = true, $withOldLog = true  ) {

      parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
    }



    public function __destruct() {

      parent::__destruct();
    }


    public function TranslateDecision( $english ) {

      $result = $english;

      $scoringDecision = [
        'Decline' => 'Відмова',
        'Verify' => 'Погоджено',
        'Approve' => 'Погоджено',
        'Review' => 'не задіяно в процесі прескорингу станом на зараз',
        'scoringError' => 'Помилка прескорингу'
      ];

      $result = $scoringDecision[ $english ];

      if( empty( $result ) )
        $result = $english;

      return( $result );
    }

  public function ApiScoring( $requestParams,
                              $boxRequest,
                              & $boxResults,
                              & $errorLevel,
                              & $scoringStatus,
                              $guzzle,
                              $cookie,
                              $testMode,
                              $nextStatus='0' )
   //
   // Выполняет операцию API "scoring"
   // код результата операции будет в: $result['scoring']['result_code']
   // А сообщение результата операции будет в: $result['scoring']['result_scoringDecision']
   // Оба значения, для дальнейшего использования, необходимо сохранить в результатах формы.
   //
  {

    $result         = [];
    $scoringStatus  = '';

    $requestUri     = $requestParams['requestUri'] . 'api/Scoring/GetScoring';
    $requestUrl     = $requestParams['requestProtocol'] . $requestParams['requestDomen'] . $requestUri;
    $headers        = [
      'Content-Type'  => $requestParams['headers']['Content-Type'],
      'Accept' => $requestParams['headers']['Accept'],
      'User-Agent'=> $requestParams['headers']['User-Agent'],
      'Api-Key' => $requestParams['headers']['Api-Key']
    ];

    $phone  = '+';
    $phone .= $boxRequest['mobileNumber'];
    $boxRequest['mobileNumber'] = $phone;      // добавляем символ плюс в телефон, так надо!

    $this->logging_debug( 'boxRequest: ' );
    $this->logging_debug( $boxRequest );

    $this->logging_debug( '' );
    $this->logging_debug( 'Стартуем POST запрос прескоринга - метод api/Scoring/GetScoring' );
    $this->logging_debug( 'URL: ' . $requestUrl );

    $this->logging_debug( 'Отправляем данные по результату ' . $boxResults[ 'id' ] . ':' );

    if( !empty( $boxRequest['mobileNumber'] ) )
      $this->logging_debug( 'mobileNumber:         ' . $boxRequest['mobileNumber'] );
    if( !empty( $boxRequest['ipn'] ) )
      $this->logging_debug( 'ipn:                  ' . $boxRequest['ipn'] );
    if( !empty( $boxRequest['employmentType'] ) )
      $this->logging_debug( 'employmentType:       ' . $boxRequest['employmentType'] );
    if( !empty( $boxRequest['workExperience'] ) )
      $this->logging_debug( 'workExperience:       ' . $boxRequest['workExperience'] );
    if( !empty( $boxRequest['incomeAmount'] ) )
      $this->logging_debug( 'incomeAmount:         ' . $boxRequest['incomeAmount'] );
    if( !empty( $boxRequest['loanAmount'] ) )
      $this->logging_debug( 'loanAmount:           ' . $boxRequest['loanAmount'] );
    if( !empty( $boxRequest['ppd'] ) )
      $this->logging_debug( 'ppd:                  ' . $boxRequest['ppd'] );
    if( !empty( $boxRequest['product'] ) )
      $this->logging_debug( 'product:              ' . $boxRequest['product'] );
    if( !empty( $boxRequest['productTerm'] ) )
      $this->logging_debug( 'productTerm:          ' . $boxRequest['productTerm'] );


    try {
//    $response = $guzzle->request('POST', $url, ['json' => $boxRequest, 'cookies' => $requestCcookieJar ] );
//    $response = $guzzle->request('POST', $url, ['json' => $boxRequest ] );
//    $response = $guzzle->request('POST', $url, ['json' => $boxRequest, 'cookies' => $cookie ] );

      $response = $guzzle->request('POST', $requestUrl, [
        'json' => $boxRequest,
        'headers' => $headers,
        'cookies' => $cookie ] );

      $this->logging_debug( 'Запрос отработал. Проверяем статус-код!' );

      $error_level = $response->getStatusCode();

      if ($error_level != 200 ) {

        $this->logging_debug('Результат отрицательный, статус-код: ' . $response->getStatusCode());

        $result['scoring']['result_httpstatuscode']      = $error_level;
        $result['scoring']['result_result']    = LoanFactory::$codeResultForScoring;    // вернуть в изначальное состояние

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
        $this->logging_debug('return:');
        $this->logging_debug( $retInfo );

//      $responseHeaders    = $response->getHeaders();
//      $responseHeaders    = $response->getHeader( 'Set-Cookie' );
//      $this->logging_debug( 'responseHeaders:' );
//      $this->logging_debug( $responseHeaders );

        if( ! empty( $retInfo['requestId'] ) && $retInfo['key'] == 'OK' ) {

          $scoringStatus                                 = $retInfo['scoringDecision'];

          $result['scoring']['result_request_id']        = $retInfo['requestId'];        // Request ID
          $result['scoring']['result_status']            = $retInfo['status'];           // 1 - успешно
          $result['scoring']['result_httpstatuscode']    = $retInfo['code'];
          $result['scoring']['result_message']           = ($testMode == 'Y') ? 'ТЕСТОВЫЙ СКОРИНГ' : $retInfo['message'];
          $result['scoring']['result_source']            = $retInfo['source'];
          $result['scoring']['result_creditLimitAmount'] = $retInfo['creditLimitAmount'];
          $result['scoring']['result_code']              = $retInfo['scoringDecision'];
          $result['scoring']['result_scoringDecision']   = $this->TranslateDecision( $retInfo['scoringDecision'] );
          $result['scoring']['result_isSuccessful']      = $retInfo['isSuccessful'];
          $result['scoring']['result_stackTrace']        = $retInfo['stackTrace'];
          $result['scoring']['result_result']            = $nextStatus; // следующая операция или по умолчанию завершить все операции
        } else {
          $result['scoring']['result_request_id']        = $retInfo['requestId'];        // Request ID
          $result['scoring']['result_status']            = $retInfo['status'];           //
          $result['scoring']['result_httpstatuscode']    = $retInfo['code'];
          $result['scoring']['result_message']           = $retInfo['message'];
          $result['scoring']['result_source']            = '';
          $result['scoring']['result_creditLimitAmount'] = 0;
          $result['scoring']['result_code']              = 'scoringError';
          $result['scoring']['result_scoringDecision']   = $this->TranslateDecision( $result['scoring']['result_code'] );
          $result['scoring']['result_isSuccessful']      = false;
          $result['scoring']['result_stackTrace']        = $retInfo['stackTrace'];
          $result['scoring']['result_result']            = LoanFactory::$codeResultForScoring;
        }
        $this->logging_debug('Error Code: ' . $result['scoring']['result_httpstatuscode'] . '. Error Message: ' . $result['scoring']['result_message'] );

        $this->logging_debug('Запроса к неизвестному серверу банка завершена однака.');
      }

    } catch( \Exception $e ) {

      $this->ApiScoringException( 'Exception', $e, $boxRequest, $response, $result );
      $this->loggingBackTrace();
//    throw $e;

    } catch( \Error $e ) {

      $this->ApiScoringException( 'Error', $e, $boxRequest, $response, $result );
      $this->loggingBackTrace();
//    throw $e;
    }


    return( $result );
  }


  public function ApiScoringException( $errorName, $e, $boxRequest, $response, & $result )
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
        $errorInfo    = 'ошибка ' . LoanFactory::$codeResultForScoring . ', ответ от неизвестного сервера банка не получен. получен ' . $errorName . '!';
    }

    $error_level = LoanFactory::$codeResultForScoring;

    $result['scoring']['result_httpstatuscode']    = $error_level;
    $result['scoring']['result_status']  = $error_level;
    $result['scoring']['result_message'] = $msg;
    $result['scoring']['result_source']            = '';
    $result['scoring']['result_creditLimitAmount'] = 0;
    $result['scoring']['result_code']              = 'scoringError';
    $result['scoring']['result_scoringDecision']   = $this->TranslateDecision( $result['scoring']['result_code'] );
    $result['scoring']['result_isSuccessful']      = false;
    $result['scoring']['result_stackTrace']        = '';
    $result['scoring']['result_result']  = $error_level;

    $this->logging_debug('ApiLeadCreate ' . $errorName . ':');
    $this->logging_debug( $result['scoring']['result_message'] );

  }



}

