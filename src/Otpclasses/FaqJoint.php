<?php

namespace Otpclasses\Otpclasses;

use Otpclasses\Otpclasses\UrlUtilities;

class FaqJoint extends UrlUtilities
{
  public $cfgForm;
  public $statusPublished;
  public $boxBackgrounds;


  public function __construct( $logName = '/faqjoint.log', $cuteIdentifier = 'FaqJoint.', $cuteModule = true, $withOldLog = true  ) {

    $this->statusPublished = 1;
    $this->boxBackgrounds = ['virtual-cards', 'salary-cards', 'debit-cards', 'credit-cards', 'premium-cards' ];
    $this->cfgForm = \Drupal::configFactory()->getEditable('renderfaq.settings');

    parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
  }

  public function __destruct() {

    parent::__destruct();
  }

  public function GetBackgroundUri( $backgroundDefault )
  {
    //
    // Получаем URI на картинку для бакграунда из настроек модуля
    //
    $uri = \Drupal::request()->getRequestUri();
    $bgrCode = false;

    foreach ( $this->boxBackgrounds as $folder ) {

      $bgrCode = $this->IsFoldersInUri( [ $folder ], $uri );
      if( $bgrCode ) {
        $bgrCode = $folder;
        break;
      }
    }
    //
    // $bgrCode - содержит название одной из папок с картами, либо false
    //
    $backgroundImage = $backgroundDefault;

    if( $bgrCode != false ) {
      $idBgImage = $this->cfgForm->get('image_' . $bgrCode);

      $this->logging_debug('');
      $this->logging_debug('Faq idBgImage:');
      $this->logging_debug($idBgImage);

      if (!empty($idBgImage)) {
        $boxFile = \Drupal::entityTypeManager()->getStorage('file')->load($idBgImage[0]);
        $backgroundImage = \Drupal::service('file_url_generator')->generateString($boxFile->getFileUri());
      }
    }
    //
    //
    //
    return( $backgroundImage );
  }

  /**
   * В $dataFetched - массив прочитанных из БД нод с Faq-ами
   * Возвращает количество найденных нод удовлетворяющих заданным условиям.
   */
  public function ParseFetchedRows( $dataFetched, & $box, $useRecursive = false ) {

    $uri = \Drupal::request()->getRequestUri();

//  $this->logging_debug( '' );
//  $this->logging_debug( 'current uri: ' . $uri );

    foreach ($dataFetched as $node) {

      $resultSet = $node->toArray();
      $searchBox = array_column(  $resultSet['field_folderlink'] , 'value' );

//    $this->logging_debug( '' );
//    $this->logging_debug( 'searchBox:' );
//    $this->logging_debug( $searchBox );

      $is = array_search( $uri, $searchBox );

//    $this->logging_debug( '' );
//    $this->logging_debug( 'Recursive: ' . $useRecursive );

      if( $is === false && $useRecursive == false )  // если в массиве URI указанных в записи не найден текущий URI, пропускаем этот баннер!
        continue;
      //
      // в текущей папке нода не обнаружена! ищем ее рекурсивно в родительсикх папках ...
      //
      if( $is === false && $useRecursive ) {

        $isUriSegment = $this->IsFoldersInUri( $searchBox, $uri );

        if( ! $isUriSegment )
          continue;
      }
      //
      // Текущая нода для текущей папки подходит. показываем ее.
      //
//    $this->logging_debug('');
//    $this->logging_debug('Faq resultset:');
//    $this->logging_debug($resultSet);

      $title = empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'];
      $created = date('d-m-Y', $resultSet['created'][0]['value']);

      $box[] = [
        'id' => $resultSet['nid'][0]['value'],
        'code' => $resultSet['field_code100'][0]['value'],
        'date_from' => $created,
        'name' => empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'],
        'annonce' => empty( $resultSet['field_blocktitle'][0]['value'] ) ? '' : $resultSet['field_blocktitle'][0]['value'],
        'detail' => empty( $resultSet['field_detailshtml'][0]['value'] ) ? '' : $resultSet['field_detailshtml'][0]['value']
      ];
//    $this->logging_debug( '' );
//    $this->logging_debug( 'box:' );
//    $this->logging_debug( $box );
//    $this->logging_debug( '' );
    }
    //
    return( count( $box ) );
  }

  public function GetItemsData( & $items )
  {
    $result = [];
    $nids = [];
    $data = [];
    $countRows = 0;

    $useRecursive = $this->cfgForm->get('recursive'); // чекбокс - рекурсивный показ Faq в дочерних папках, если в них отсутствует свои Faq-и
    $sort = $this->cfgForm->get('sort_field') ?? 'field_sorting';
    $sortDirection = $this->cfgForm->get('sort_direct') ?? 'ASC';


    $nids = \Drupal::entityQuery('node')->accessCheck(FALSE)
      ->condition('status', $this->statusPublished)
      ->condition('type', 'Faq')
      ->sort($sort, $sortDirection)
      ->execute();

    if( !empty( $nids ) )
      $data = \Drupal\node\Entity\Node::loadMultiple($nids);

    if( !empty( $data ) ) {

      $countRows = $this->ParseFetchedRows($data, $items);
      if ($countRows == 0 && $useRecursive) {
        $countRows = $this->ParseFetchedRows($data, $items, $useRecursive);
      }
    }
    //
    return( $result );
  }


}

