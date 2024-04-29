<?php

namespace Otpclasses\Otpclasses;

use OtpClasses\Otpclasses\LogUtilities;
use Otpclasses\Otpclasses\DateUtilities;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;

class FormUtilities extends LogUtilities
{
    public $dateHandle;

    public function __construct( $logName = '/formutilities.log', $cuteIdentifier = 'FormUtilities.', $cuteModule = true, $withOldLog = true  ) {

      parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

      $this->dateHandle					= new DateUtilities( $this->log_name, $this->cute_identifier, $cuteModule, $withOldLog );
      $this->dateHandle->SetDontCuteLog( true );
      $this->dateHandle->SetShowTimeEachRow( $this->showTimeEachRow );
      $this->dateHandle->SetLogDateFormat( $this->log_date_format );
      $this->dateHandle->SetNumberDaysCut( $this->num_days_cut );
      $this->dateHandle->SetStarting( $this->IsStarting );
      $this->dateHandle->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
    }

    public function __destruct() {

        parent::__destruct();
    }



    public function LoadResultData( $idForm, $packSize, $selectFields, $hostReferer, & $boxFields=[] )
    //
    // Чтение данных результатов формы.
    //
    // $fields = [
    //  'name' => 'field_Name',
    //  'value' => 'field_Value',
    //  'sort' => 'field_Sort',
    //  'order' => 'sort_Order'
    // ]
    //
    {
      $results = [];

      try {
//      $webform = Webform::load( $idForm );

        $database = \Drupal::service('database');
        $select = $database->select('webform_submission_data', 'wsd')
          ->fields('wsd', ['sid'])
          ->condition('wsd.webform_id', $idForm, '=')
          ->condition('wsd.name', $selectFields['name'], '=')
          ->condition('wsd.value', $selectFields['value'], '=')
          ->orderBy( 'wsd.' . $selectFields['sort'], $selectFields['order']);

        $executed = $select->execute();
        // Get all the results.
        $sids = $executed->fetchAll(\PDO::FETCH_ASSOC);

//      $this->logging_debug('sids (id=' . $idForm . '):');
//      $this->logging_debug( $sids );

        if( !empty( $sids ) && count( $sids ) >= 1) {

          $i = 0;
          foreach ($sids as $sid) {

            $submissionObject = \Drupal\webform\Entity\WebformSubmission::load($sid['sid']);

            $box = $submissionObject->toArray();

//          $this->logging_debug( '' );
//          $this->logging_debug( 'submissionObject:' );
//          $this->logging_debug( $box );

            $results[] = [
              'id' => $submissionObject->id(),
//            'created' => $this->dateHandle->TimeStampToStringForDrupalDataBase( $box['created'][0]['value'] ),
//            'completed' => $this->dateHandle->TimeStampToStringForDrupalDataBase( $box['completed'][0]['value'] ),
//            'changed' => $this->dateHandle->TimeStampToStringForDrupalDataBase( $box['changed'][0]['value'] ),
              'created' => date( 'd.m.Y H:i:s', $box['created'][0]['value'] ),
              'completed' => date( 'd.m.Y H:i:s', $box['completed'][0]['value'] ),
              'changed' => date( 'd.m.Y H:i:s', $box['changed'][0]['value'] ),
              'user_id' => $box['uid'][0]['target_id'],
              'fields' => $submissionObject->getData()
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

