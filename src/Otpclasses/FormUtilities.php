<?php

namespace Otpclasses\Otpclasses;

use OtpClasses\Otpclasses\LogUtilities;
use Otpclasses\Otpclasses\DateUtilities;
use Otpclasses\Otpclasses\DrupalUtilities;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Element\WebformEntityTrait;
use Drupal\webform\Plugin\WebformElement;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementInterface;

class FormUtilities extends LogUtilities
{
    public $dateHandle;
    public $drupalHandle;

    public function __construct( $logName = '/formutilities.log', $cuteIdentifier = 'FormUtilities.', $cuteModule = true, $withOldLog = true  ) {

      parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

      $this->dateHandle					= new DateUtilities( $this->log_name, $this->cute_identifier, $cuteModule, $withOldLog );
//    $this->dateHandle->SetDontCuteLog( true );
//    $this->dateHandle->SetShowTimeEachRow( $this->showTimeEachRow );
//    $this->dateHandle->SetLogDateFormat( $this->log_date_format );
//    $this->dateHandle->SetNumberDaysCut( $this->num_days_cut );
      $this->dateHandle->SetStarting( $this->IsStarting );
      $this->dateHandle->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );

      $this->drupalHandle     = new DrupalUtilities( $this->log_name, $this->cute_identifier, $cuteModule, $withOldLog );
      $this->drupalHandle->SetStarting( $this->IsStarting );
      $this->drupalHandle->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );

    }

    public function __destruct() {

        parent::__destruct();
    }



    public function LoadResultData( $idForm, $packSize, $boxFilter, $hostReferer, & $boxFields=[] )
    //
    // Чтение данных результатов формы.
    //
    // $boxFilter = [
    //  'name' => 'field_Name',
    //  'value' => 'field_Value',
    //  'sort' => 'field_Sort',
    //  'order' => 'sort_Order'
    // ]
    //
    {
      $results = [];

      try {
        $webform = Webform::load( $idForm );

        $database = \Drupal::service('database');

        if( is_array( $boxFilter['value'] ) ) {
          if( count( $boxFilter['value'] ) > 1 ) {

            $conditionOr = new \Drupal\Core\Database\Query\Condition('OR');

            foreach ( $boxFilter['value'] as $value ) {
              $conditionOr->condition('wsd.value', $value, '=');
            }

            $select = $database->select('webform_submission_data', 'wsd')
              ->fields('wsd', ['sid'])
              ->condition('wsd.webform_id', $idForm, '=')
              ->condition('wsd.name', $boxFilter['name'], '=')
              ->condition($conditionOr)
              ->orderBy( 'wsd.' . $boxFilter['sort'], $boxFilter['order']);

          } else {
            $conditionValue = $boxFilter['value'][0];

            $select = $database->select('webform_submission_data', 'wsd')
              ->fields('wsd', ['sid'])
              ->condition('wsd.webform_id', $idForm, '=')
              ->condition('wsd.name', $boxFilter['name'], '=')
              ->condition('wsd.value', $conditionValue, '=' )
              ->orderBy( 'wsd.' . $boxFilter['sort'], $boxFilter['order']);
          }

        } else {
          $conditionValue = $boxFilter['value'];

          $select = $database->select('webform_submission_data', 'wsd')
            ->fields('wsd', ['sid'])
            ->condition('wsd.webform_id', $idForm, '=')
            ->condition('wsd.name', $boxFilter['name'], '=')
            ->condition('wsd.value', $conditionValue, '=' )
            ->orderBy( 'wsd.' . $boxFilter['sort'], $boxFilter['order']);

//        $this->logging_debug('conditionValue: ' . $conditionValue );
        }

        $executed = $select->execute();
        // Get all the results.
        $sids = $executed->fetchAll(\PDO::FETCH_ASSOC);

//      $this->logging_debug('sids (id=' . $idForm . '):');
//      $this->logging_debug( $sids );

        if( !empty( $sids ) && count( $sids ) >= 1) {

          $i = 0;
          foreach ($sids as $sid) {
            //
            // $sid содержит типа [ "sid": "103" ]
            //
            $submissionObject = \Drupal\webform\Entity\WebformSubmission::load($sid['sid']);

            $box = $submissionObject->toArray();

//          $this->logging_debug( '' );
//          $this->logging_debug( 'submissionObject:' );
//          $this->logging_debug( $box );

            $data = $submissionObject->getData();

//          $this->logging_debug( '' );
//          $this->logging_debug( 'data:' );
//          $this->logging_debug( $data );

            foreach ( $data as $fieldName => &$fieldValue ) {

              $element = $webform->getElement( $fieldName );

              $errorReadData = true;
              $previousValue = $fieldValue;
              $setOptions    = isset( $element['#options'] );
              //
              // замена деприкатед метода: WebformEntityTrait::setOptions( $element );
              //
              if( $element['#type'] == 'webform_entity_select' ) {

                $elementOptions = [];
                $typeData = '';
                // ищем тип данных в select
                foreach( $element['#selection_settings']['target_bundles'] as $codeData => $itemData ) {
                  $typeData = $itemData;
                }
                // загружаем описания для найденного типа данных
                if( ! empty( $fieldValue ) ) {
                  $errorReadData = $this->drupalHandle->LoadDataById(
                    $elementOptions,
                    $typeData,
                    $fieldValue
                  );
                  // если данные загрузились, заполняем в переменную $fieldValue описание
                  if (!$errorReadData) {
                    $fieldValue = $elementOptions[$fieldValue]['title'];
                  }
                }
//              $this->logging_debug( '' );
//              $this->logging_debug( 'ЭЛЕМЕНТ:' );
//              $this->logging_debug( $element );

                $setOptions = false;
              }

//            WebformEntityTrait::setOptions( $element ); // depricated метод, который инициализирует справочник опций в селектах
              if( $setOptions ) {   // options - есть в элементе! это тип "select"
                // в переменную value загружается описание
                $value = WebformOptionsHelper::getOptionsText( (array) $fieldValue, $element['#options'] );

                if(count($value) > 1) {

                  $fieldValue = [
                    'type'  => $element['#type'],
                    'value' => $previousValue,
                    'caption' => $value
                  ];

                } elseif( count($value) === 1) {

                  $fieldValue = [
                    'type'  => $element['#type'],
                    'value' => $previousValue,
                    'caption' => reset($value)
                  ];

                }

//              $this->logging_debug( '' );
//              $this->logging_debug( '(' . $element['#type'] . ')' . $fieldName . ' (select):' );
//              $this->logging_debug( $fieldValue );

              } else { // не select

                $fieldValue = [
                  'type'  => $element['#type'],
                  'value' =>  $previousValue,
                  'caption' => $fieldValue
                ];

//              $this->logging_debug( '' );
//              $this->logging_debug( '(' . $element['#type'] . ')' . $fieldName . ':' );
//              $this->logging_debug( $fieldValue );
              }
            }

            $results[] = [
              'id' => $submissionObject->id(),
//            'created' => $this->dateHandle->TimeStampToStringForDrupalDataBase( $box['created'][0]['value'] ),
//            'completed' => $this->dateHandle->TimeStampToStringForDrupalDataBase( $box['completed'][0]['value'] ),
//            'changed' => $this->dateHandle->TimeStampToStringForDrupalDataBase( $box['changed'][0]['value'] ),
              'created' => date( 'd.m.Y H:i:s', $box['created'][0]['value'] ),
              'completed' => date( 'd.m.Y H:i:s', $box['completed'][0]['value'] ),
              'changed' => date( 'd.m.Y H:i:s', $box['changed'][0]['value'] ),
              'user_id' => $box['uid'][0]['target_id'],
              'fields' => $data
            ];
            $i++;

            if( $i >= $packSize )
              break;
          }

//        $this->logging_debug( '' );
//        $this->logging_debug( 'results:' );
//        $this->logging_debug( $results );

        } else {
//        $this->logging_debug( '' );
//        $this->logging_debug( 'Результатов формы для отправки на получение LID-а не найдено.' );
        }

      } catch( \Error $e ) {
        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'LoadResultData Error:' );
        $this->logging_debug( $result );
        $this->loggingBackTrace();
        throw $e;

      } catch( \Exception $e ) {
        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'LoadResultData Exception:' );
        $this->logging_debug( $result );
        $this->loggingBackTrace();
        throw $e;

      } finally {

        if( ! empty( $results ) ) {
          foreach ($results[0]['fields'] as $code => $value) {
            $boxFields[] = $code;
          }
        }
//      $this->logging_debug( '' );
//      $this->logging_debug( 'LoadResultData finished.' );
      }
      return( $results );
    }


  public function SaveResultData( $idForm, $idResult, $setFields )
    //
    // Запись данных результатов формы.
    //
    // $setFields = [
    //  'name' => 'VALUE',
    //  ...,
    //  'name' => 'VALUE'
    // ]
    // return:  [] - УСПЕШНО.
    //          ['errorCode' => '...', 'errorMessage' => ' .... ' ] - ОШИБКА
    //
  {
    $result = false;

    try {
      $submissionObject = \Drupal\webform\Entity\WebformSubmission::load( $idResult );

// Get submission data.
      $data = $submissionObject->getData();

// Change submission data.
      foreach ( $setFields as $fieldName => $fieldValue ) {
        $data[ $fieldName ] = $fieldValue;
      }

// Set submission data.
      $submissionObject->setData( $data );

//    $this->logging_debug( '' );
//    $this->logging_debug( 'Result submission data:' );
//    $this->logging_debug( $data );

// Save submission.
      $submissionObject->save();

    } catch( \Error $e ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'SaveResultData Error:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      throw $e;

    } catch( \Exception $e ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'SaveResultData Exception:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      throw $e;

    } finally {

//    $this->logging_debug( '' );
//    $this->logging_debug( 'SaveResultData finished.' );
    }
    return( $result );
  }



}

