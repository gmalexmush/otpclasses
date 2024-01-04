<?php

namespace Otpclasses\Otpclasses;

use Otpclasses\Otpclasses\UrlUtilities;

class SocialsJoint extends UrlUtilities
{
  public $cfgForm;
  public $statusPublished;


  public function __construct( $logName = '/socialsjoint.log', $cuteIdentifier = 'SocialsJoint.', $cuteModule = true, $withOldLog = true  ) {

    $this->statusPublished = 1;
    $this->cfgForm = \Drupal::configFactory()->getEditable('footers.settings');

    parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
  }

  public function __destruct() {

    parent::__destruct();
  }

  /**
   * В $dataFetched - массив прочитанных из БД нод с SocialServise-ами
   * Возвращает количество найденных нод удовлетворяющих заданным условиям.
   */
  public function ParseFetchedRows( $dataFetched, & $box, $useRecursive = false ) {

    $uri = \Drupal::request()->getRequestUri();

//  $this->logging_debug( '' );
//  $this->logging_debug( 'current uri: ' . $uri );

    foreach ($dataFetched as $node) {

      $resultSet = $node->toArray();
      $searchBox = array_column(  $resultSet['field_folderlink'] , 'value' );
/*
      $this->logging_debug( '' );
      $this->logging_debug( 'searchBox:' );
      $this->logging_debug( $searchBox );
*/
      $isUriSegment = false;
      $is = array_search( $uri, $searchBox );

      if( $is === false && $useRecursive == false )  // если в массиве URI указанных в записи не найден текущий URI, пропускаем этот контакт!
        continue;

      if( $is !== false )
        $isUriSegment = $searchBox[ $is ];

      if( $is === false && $useRecursive ) {
        //
        // в текущей папке нода не обнаружена! ищем ее рекурсивно в родительсикх папках ...
        //
        $isUriSegment = $this->IsFoldersInUri( $searchBox, $uri );

        $textIsUriSegment = empty( $isUriSegment ) ? "false" : "true";
/*
        $this->logging_debug( '' );
        $this->logging_debug( 'isUriSegment: ' . $textIsUriSegment );
*/
        if( ! $isUriSegment )
          continue;
      }
      //
      // Текущая нода для текущей папки подходит. показываем ее.
      //
//    $this->logging_debug('');
//    $this->logging_debug('Socials resultset:');
//    $this->logging_debug($resultSet);

      $title = empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'];
      $created = date('d-m-Y', $resultSet['created'][0]['value']);

      $linkUrl    = '';
      $linkText   = '';
      $linkTitle  = '';
      $linkParams = '';
      if( ! empty( $resultSet['field_formlink'][0]['value'] ) ) {
        $linkUrl    = $resultSet['field_formlink'][0]['value'];
        $linkText   = ! empty( $resultSet['field_formlink'][1]['value'] ) ? $resultSet['field_formlink'][1]['value'] : 'Link';
        $linkTitle  = ! empty( $resultSet['field_formlink'][2]['value'] ) ? $resultSet['field_formlink'][2]['value'] : '';
        $linkParams = ! empty( $resultSet['field_formlink'][4]['value'] ) ? $resultSet['field_formlink'][4]['value'] : '';
      }

      $link = [ 'href' => $linkUrl, 'text' => $linkText, 'title' => $linkTitle, 'params' => $linkParams ];

      $box[] = [
        'id' => $resultSet['nid'][0]['value'],
        'code' => $resultSet['field_code100'][0]['value'],
        'date_from' => $created,
        'name' => empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'],
        'icon_class' => empty( $resultSet['field_codefolder'][0]['value'] ) ? '' : $resultSet['field_codefolder'][0]['value'],
        'folder' => $isUriSegment,
        'uri' => $uri,
        'link' => $link
      ];

    }
    /*
    $this->logging_debug( '' );
    $this->logging_debug( 'box:' );
    $this->logging_debug( $box );
    $this->logging_debug( '' );
    */
    return( count( $box ) );
  }

  public function GetItemsData( & $items )
  {
    $result = [];
    $nids = [];
    $data = [];
    $countRows = 0;

    $useRecursive = $this->cfgForm->get('recursive_socials'); // чекбокс - рекурсивный показ Faq в дочерних папках, если в них отсутствует свои Faq-и
    $sort = $this->cfgForm->get('sort_field_socials') ?? 'field_sorting';
    $sortDirection = $this->cfgForm->get('sort_direct_socials') ?? 'ASC';

    $textUseRecursive = empty( $useRecursive ) ? "false" : "true";
/*
    $this->logging_debug( '' );
    $this->logging_debug( 'Recursive: ' . $textUseRecursive );
*/
    $nids = \Drupal::entityQuery('node')->accessCheck(FALSE)
      ->condition('status', $this->statusPublished)
      ->condition('type', 'SocialService')
      ->sort($sort, $sortDirection)
      ->execute();

    if( !empty( $nids ) )
      $data = \Drupal\node\Entity\Node::loadMultiple($nids);

    if( !empty( $data ) ) {
/*
      $this->logging_debug( 'Not empty data.' );
*/
      $countRows = $this->ParseFetchedRows($data, $items);
      if ($countRows == 0 && $useRecursive) {
        $countRows = $this->ParseFetchedRows($data, $items, $useRecursive);
      }
    }
    //
    return( $result );
  }


}

