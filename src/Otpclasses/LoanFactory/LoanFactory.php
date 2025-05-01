<?php

namespace Otpclasses\Otpclasses\LoanFactory;

use Otpclasses\Otpclasses\UrlUtilities;

class LoanFactory extends UrlUtilities
{
    public static $codeResultFinishAll      = '0';
    public static $codeResultForScoring     = '333';
    public static $codeResultForLeadCreate  = '666';

    public function __construct( $logName = '/loanfactory.log', $cuteIdentifier = 'LoanFactory.', $cuteModule = true, $withOldLog = true  ) {

      parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
    }



    public function __destruct() {

      parent::__destruct();
    }


      public function LinkBoxForRequest( $operation, $itemData, $boxFormFields )
        //
        // возврашает заполненный массив для пост запросов
        //
        // $boxFormFields - контейнер с названиями полей формы и соответствующих полей запроса.
        // $itemData - контейнер с данными результата формы.
        //
      {

        $result = [];

        foreach( $boxFormFields['FormFields'] as $requestFields => $formFields ) {

          $formFields['defaultValue'] = $formFields['defaultValue'] ?? '';

//        $this->logging_debug( '' );
//        $this->logging_debug( $requestFields . ' : ' . $formFields['name'] . ':' );
//        $this->logging_debug( $itemData[ $formFields['name'] ] );
          //
          // в файле настроек поле в б.д. ( $formFields['name'] ) может быть опущено,
          // и присутствовать только значение по умолчанию: $formFields['defaultValue']
          //
          if( ! empty( $formFields['name'] ) ) {

            switch ($itemData['fields'][$formFields['name']]['type']) {

              case 'checkbox':
              case 'webform_entity_select':

                if (!empty($formFields['name'])) {
                  if (empty($itemData['fields'][$formFields['name']]['value'])) {

                    $requestValue = $formFields['defaultValue'];
                  } else {
                    $requestValue = intval($itemData['fields'][$formFields['name']]['value'], 10);
                  }
                } else {
                  $requestValue = $formFields['defaultValue'];
                }
                break;

              case 'hidden':
              case 'select':
              case 'number':
              case 'textfield':

                if (!empty($formFields['name'])) {
                  if (empty($itemData['fields'][$formFields['name']]['value'])) {

                    $requestValue = $formFields['defaultValue'];
                  } else {
                    $requestValue = $itemData['fields'][$formFields['name']]['value'];
                  }
                } else {
                  $requestValue = $formFields['defaultValue'];
                }
                break;

              default:
                $requestValue = (empty($formFields['name']) || empty(trim($itemData[$formFields['name']]['value'])))
                  ? $formFields['defaultValue']
                  : trim($itemData[$formFields['name']]['value']);

                if (!empty($formFields['name'])) {
                  //
                  // если параметр action_link или url, то убираем из его значения (URL) параметры запроса, оставляем только URL.
                  //
                  switch ($formFields['name']) {
                    case 'action_link':
                    case 'url':
                      if (!empty($requestValue))
                        $requestValue = $this->UrlClipQuery($requestValue);
                      break;
                  }
                }

            }
          } else {
            $requestValue = $formFields['defaultValue'];
          }
          //
          //  если значение - строка и она пустая, исключаем такой параметр из запроса!
          //
          if( is_string( $requestValue ) ) {
            if( !empty( $requestValue )  )
              $result[$requestFields] = $requestValue;
          } else {
            $result[$requestFields] = $requestValue;
          }
        }

        switch( $operation ) {
          case 'lead':
            $result['additionalLeadInfo'] = [ 'websitePartner' => $result['carLink'] ];
            break;
        }

        return( $result );
      }

}

