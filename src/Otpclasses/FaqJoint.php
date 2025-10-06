<?php

namespace Otpclasses\Otpclasses;

use Otpclasses\Otpclasses\DrupalUtilities;
use Otpclasses\Otpclasses\UrlUtilities;

class FaqJoint extends UrlUtilities
{
  public $drupalUtil;
  public $cfgForm;
  public $statusPublished;
  public $boxBackgrounds;


  public function __construct( $logName = '/faqjoint.log', $cuteIdentifier = 'FaqJoint.', $cuteModule = true, $withOldLog = true  ) {

    $logName  = "/faq.log";
    $logCute  = "RenderFaq.";

    $this->drupalUtil = new DrupalUtilities( $logName, $logCute, false );
    $this->drupalUtil->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
    $this->drupalUtil->SetStarting( true );

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
    $uri = $this->GetCurrentPathWithoutParameters(); // \Drupal::request()->getRequestUri();
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
   * Возвращает массив данных найденных нод удовлетворяющих заданным условиям.
   */
  public function LoadingDataRow( $resultSet, $folder ) {

    $result = [];
//  $this->logging_debug('');
//  $this->logging_debug('Faq resultset:');
//  $this->logging_debug($resultSet);

    $title = empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'];
    $created = date('d-m-Y', $resultSet['created'][0]['value']);

    if( ! empty( $resultSet['field_codefolder'] ) ) {
        $group = [
              'code' => empty($resultSet['field_codefolder'][0]['value']) ? '' : $resultSet['field_codefolder'][0]['value'],
              'title' => empty($resultSet['field_productcaption'][0]['value']) ? '' : $resultSet['field_productcaption'][0]['value'],
              'sort' => empty($resultSet['field_groupsorting'][0]['value']) ? 10 : $resultSet['field_groupsorting'][0]['value']
              ];
    } else {
      $group = [];
    }

    $result = [
      'folder' => $folder,
      'id' => $resultSet['nid'][0]['value'],
      'code' => $resultSet['field_code100'][0]['value'],
      'group' => $group,
      'date_from' => $created,
      'name' => empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'],
      'annonce' => empty( $resultSet['field_blocktitle'][0]['value'] ) ? '' : $resultSet['field_blocktitle'][0]['value'],
      'detail' => empty( $resultSet['field_detailshtml'][0]['value'] ) ? '' : $resultSet['field_detailshtml'][0]['value'],
      'template' => empty( $resultSet['field_template'][0]['value'] ) ? 'default' : $resultSet['field_template'][0]['value']
    ];

    //
    return( $result );
  }

  public function GetItemsData( & $items )
  {
    $result = [];
    $nids = [];
    $data = [];

    $uri = $this->GetCurrentPathWithoutParameters(); // \Drupal::request()->getRequestUri();
    $this->logging_debug('');
    $this->logging_debug('current path: ' . $uri );

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

    $this->drupalUtil->ReversalSearchFolderInURI(
      $items,
      $data,
      $uri,
      $useRecursive,
      'field_folderlink',
      'folder',
      [ 'function' => [ $this, "LoadingDataRow" ] ]   // передается метод загрузки данных: LoadingDataRow для Call Back вызова
    );
    //
    return( $result );
  }

  public function TransformByGroup( $items )
  {
    $result = [];
    $groups = [];
    $noGroup      = 'empty';
    $noGroupTitle = 'Empty group';
    $isEmptyGroup = false;

    foreach ( $items as & $item ) {
      if( ! empty( $item['group'] ) ) {
        if( ! in_array( $item['group']['code'], $groups ) ) {
          if( empty( $groups[$item['group']['sort']] ) )
            $groups[$item['group']['sort']] = $item['group']['code'];
          else
            $groups[$item['group']['sort'] + 1000] = $item['group']['code']; // автоисправление ошибки сортировки !!!
        }
      } else {
        $isEmptyGroup = true;
      }
    }

    if( ! empty( $groups ) ) {

      if( $isEmptyGroup ) {
        if (!in_array($noGroup, $groups) ) // обнаружены незаполненные группы наряду с заполненными - добавляем пустую группу
          $groups[0] = $noGroup;
      }

      ksort( $groups ); // сортировать по ключам массив

      foreach ( $groups as $sort => $group ) {
        $groupBox   = $this->GetGroupItems( $group, $noGroup, $noGroupTitle, $items );

//      $this->logging_debug( 'groupBox:' );
//      $this->logging_debug( $groupBox );

        foreach ( $groupBox as & $groupItem ) {
          $titleGroup = $groupItem['group']['title'];
          unset($groupItem['group']);
        }

        $result[ $group ] = [
                      'title' => $titleGroup,
                      'items' => $groupBox
                  ];
      }

    }

    return $result;
  }

  public function GetGroupItems( $group, $noGroup, $noGroupTitle, $items ) {
    $result = [];

    foreach ( $items as $item ) {
      if( ! empty( $item['group'] ) && $group != $noGroup ) {

        if( $item['group']['code'] == $group ) {
          $result[] = $item;
        }
      } elseif ( empty( $item['group'] ) && $group == $noGroup ) {
        $item['group']['code']  = $noGroup;
        $item['group']['title'] = $noGroupTitle;
        $result[] = $item;
      }
    }

    return $result;
  }

}

