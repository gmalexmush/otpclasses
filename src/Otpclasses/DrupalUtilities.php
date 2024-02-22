<?php

namespace Otpclasses\Otpclasses;
//
// последняя версия от 2023.10.10
//
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Otpclasses\Otpclasses\StringUtilities;
use Otpclasses\Otpclasses\UrlUtilities;

class DrupalUtilities extends StringUtilities
{
      public $urlUtil;

     function __construct( $logName = '/utilities_for_drupal.log', $cuteIdentifier = 'DrupalInerfaceUtilities.', $cuteModule = true, $withOldLog = true ) {


       parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

       $this->urlUtil = new UrlUtilities( $this->log_name, $this->cute_identifier, false );
       $this->urlUtil->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
       $this->urlUtil->SetStarting( true );

     }

    public function __destruct() {

        parent::__destruct();
    }

    public function LoadImageFromRow( &$boxImg, $box, $fieldImage,
                                     $fieldAlt='', $fieldTitle='')
      //
      // загрузка одной картинки (тип IMAGE) из резалт-сета ( параметр: $box )
      //
    {
      $result = [ 'errorCode' => -1 ]; // ошибка


      if( !empty( $box[ $fieldImage ] ) ) {

        try {
          $file = File::load($box[$fieldImage][0]['target_id']);

          if (!empty($file)) {

            $imgSrc = \Drupal::service('file_url_generator')->generateString($file->getFileUri());

            if (!empty($fieldAlt))
              $imgAlt = $box[$fieldAlt][0]['value'];
            else
              $imgAlt = empty($meta) ? $file->label() : $meta['alt'];

            if (!empty($fieldTitle))
              $imgTitle = $box[$fieldTitle][0]['value'];
            else
              $imgTitle = empty($meta) ? $file->label() : $meta['alt'];

            $boxImg = [
              'file_id' => $file->id(),
              'created' => date('d-m-Y', $file->getCreatedTime()),
              'changed' => date('d-m-Y', $file->getChangedTime()),
              'name' => $file->getFilename(),
              'alt' => $imgAlt,
              'title' => $imgTitle,
              'url' => $imgSrc,
              'mime' => $file->getMimeType()
            ];

            $result = [];
          }
        } catch( \Exception $e ) {

          $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
          $this->logging_debug( '' );
          $this->logging_debug( 'Exception:' );
          $this->logging_debug( $result );
        }
      }

      return( $result );
    }

    public function LoadMediaImageFromRow( &$imgSrc, &$imgAlt, &$imgTitle,
                                            $box, $fieldImage,
                                            $fieldAlt='', $fieldTitle='' )
      //
      // загрузка одной картинки (тип MEDIA) из резалт-сета ( параметр: $box )
      //
    {

      $result = [ 'errorCode' => -1 ]; // ошибка

      if( !empty( $box[ $fieldImage ] ) && !empty( $box[$fieldImage][0]['target_id'] ) ) {

        try {
          $media = Media::load($box[$fieldImage][0]['target_id']);

          if( !empty( $media ) ) {
            $boxMedia = $media->toArray();
            $meta = $boxMedia['thumbnail'][0];

//        $this->logging_debug( '' );
//        $this->logging_debug( 'media:' );
//        $this->logging_debug( $boxMedia );
//        $this->logging_debug( '' );

            if (!empty($media)) {
              $fid = $media->getSource()->getSourceFieldValue($media);
              $file = File::load($fid);

              if (!empty($file)) {
                $imgSrc = \Drupal::service('file_url_generator')->generateString($file->getFileUri());

                if (!empty($fieldAlt))
                  $imgAlt = $box[$fieldAlt][0]['value'];
                else
                  $imgAlt = empty($meta) ? $file->label() : $meta['alt'];

                if (!empty($fieldTitle))
                  $imgTitle = $box[$fieldTitle][0]['value'];
                else
                  $imgTitle = empty($meta) ? $file->label() : $meta['alt'];

                $result = [];
              }
            }
          }

        } catch( \Exception $e ) {

          $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
          $this->logging_debug( '' );
          $this->logging_debug( 'Exception:' );
          $this->logging_debug( $result );
        }
      }

      return( $result );
    }


    public function LoadSomeMediaImagesFromRow( &$imgBox, $box, $fieldImage, $fieldCaption='', $fieldTitle='' )
    {
      //
      // загрузка нескольких картинок  (тип MEDIA)  из резалт-сета ( параметр: $box )
      // результат в массиве: $imgBox.
      // если в $fieldCaption указано имя поля с капшинами, то вычитываются капшины и кладутся в $imgBox[$i]['caption']
      // если в $fieldTitle указано имя поля с заголовками, то вычитываются заголовки и кладутся в $imgBox[$i]['title']
      //
      $imgBox = [];
      $result = [ 'errorCode' => -1 ]; // ошибка

      if( ! empty( $box[ $fieldImage ] ) ) {

        try {

          foreach ($box[$fieldImage] as $iconItem) {

            $media = Media::load($iconItem['target_id']);
            $boxMedia = $media->toArray();
//          $this->logging_debug( 'media:' );
//          $this->logging_debug( $boxMedia );

            $meta = $boxMedia['thumbnail'][0];

//          $this->logging_debug( 'meta:' );
//          $this->logging_debug( $meta );

            if (!empty($media)) {
              $source = $media->getSource();
              $fid = $source->getSourceFieldValue($media);
              $file = File::load($fid);

              if (!empty($file)) {

                $url = \Drupal::service('file_url_generator')->generateString($file->getFileUri());

                $imgBox [] = [
                  'file_id' => $file->id(),
                  'created' => date('d-m-Y', $file->getCreatedTime()),
                  'changed' => date('d-m-Y', $file->getChangedTime()),
                  'name' => $file->getFilename(),
                  'label' => empty($meta) ? $file->label() : $meta['alt'],
                  'url' => $url,
                  'mime' => $file->getMimeType()
                ];

                $result = [];
              }
            }
          }

          if( ! empty( $fieldCaption ) ) {

            for( $i=0; $i < count( $box[ $fieldCaption ] ); $i++ ) {
              $caption = $box[$fieldCaption][$i]['value'];
              if( ! empty( $imgBox[$i] ) )
                $imgBox[$i]['caption'] = $caption;
            }
          }

          if( ! empty( $fieldTitle ) ) {

            for( $i=0; $i < count( $box[ $fieldTitle ] ); $i++ ) {
              $title = $box[$fieldTitle][$i]['value'];
              if( ! empty( $imgBox[$i] ) )
                $imgBox[$i]['title'] = $title;
            }
          }

        } catch( \Exception $e ) {

          $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
          $this->logging_debug( '' );
          $this->logging_debug( 'Exception:' );
          $this->logging_debug( $result );
        }
      }

      return( $result );
    }

  public function LoadBannerMediaImagesFromRow( &$imgBox,
                                                $box,
                                                $fieldImage,
                                                $width=1440,
                                                $height=500,
                                                $fieldCaption='',
                                                $imgClass='',
                                                $imgClassAddon='' )
  {
    //
    // загрузка нескольких картинок (тип MEDIA) для БАННЕРА из резалт-сета ( параметр: $box )
    //
    $imgBox = [];
    $images = [];
    $result = [ 'errorCode' => -1 ]; // ошибка
    $widthDefault   = 1440;
    $heightDefault  = 500;
    $bannerImgClass = $imgClass;

    if( ! empty( $imgClassAddon ) )
      $bannerImgClass .= ' ' . $imgClassAddon;


    if( ! empty( $box[ $fieldImage ] ) ) {

      try {

        foreach ($box[$fieldImage] as $iconItem) {

          $media = Media::load($iconItem['target_id']);

          if( empty( $media ) )
            continue;

          $boxMedia = $media->toArray();

//        $this->logging_debug( 'media:' );
//        $this->logging_debug( $boxMedia );

          $meta = $boxMedia['thumbnail'][0];

//          $this->logging_debug( 'meta:' );
//          $this->logging_debug( $meta );

          if (!empty($media)) {
            $source = $media->getSource();
            $fid = $source->getSourceFieldValue($media);
            $file = File::load($fid);

            if (!empty($file)) {

              $url = \Drupal::service('file_url_generator')->generateString( $file->getFileUri() );

              if( $width == 0 || $height == 0 ) {

                $streamWrapperManager = \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri());
                $fileImagePath = $streamWrapperManager->realpath();
                $imageInfo = getimagesize($fileImagePath);
                $widthFromImage   = $imageInfo[0];
                $heightFromImage  = $imageInfo[1];

                $width     = empty( $widthFromImage ) ? $widthDefault : $widthFromImage;
                $height    = empty( $heightFromImage ) ? $heightDefault : $heightFromImage;
              }
/*
              $htmlBanner = "<img alt=\"" . $media->label() . "\" imgtype=\"banner\" src=\"" .
                            $url . "\" style=\"border: 0;\" width=\"" .
                            $width . "\" height=\"" . $height . "\" class=\"" . $bannerImgClass . "\">";
*/
              $images [] = [
                'file_id' => $file->id(),
                'created' => date('d-m-Y', $file->getCreatedTime()),
                'changed' => date('d-m-Y', $file->getChangedTime()),
                'name' => $file->getFilename(),
                'label' => empty($meta) ? $file->label() : $meta['alt'],
                'url' => $url,
                'alt' => $media->label(),
                'width' => $width,
                'height' => $height,
//              'body' => $htmlBanner,
                'mime' => $file->getMimeType()
              ];

              $result = [];
            }
          }
        }

        if( ! empty( $fieldCaption ) ) {

          for( $i=0; $i < count( $box[ $fieldCaption ] ); $i++ ) {
            $caption = $box[$fieldCaption][$i]['value'];
            if( ! empty( $images[$i] ) )
              $images[$i]['caption'] = $caption;
          }
        }

        $htmlBanner = '';

        foreach ( $images as $img ) {

          if( ! empty( $img['caption'] ) )
            $title = ' title="' . $img['caption'] . '"';
          else
            $title = '';

          $htmlBanner .= "<img alt=\"" . $img['alt'] . "\" imgtype=\"banner\" src=\"" .
            $img['url'] . "\" style=\"border: 0;\" width=\"" .
            $img['width'] . "\" height=\"" . $img['height'] . "\" class=\"" . $bannerImgClass . "\"" . $title . ">";
        }

        $imgBox = [
          'body' => $htmlBanner,
          'images' => $images
        ];

      } catch( \Exception $e ) {

        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'Exception:' );
        $this->logging_debug( $result );
      }
    }

    return( $result );
  }



    public function LoadByReferencedId( & $boxResult, $type, $boxId, $boxFields=[], $fieldImage='',
                                        $sort='field_sorting', $sortDirection='asc', $entity='node', $status=1 )
      //
      // $boxResult =
      // [ 0 =>
      //      [
      //           'title' => '....',
      //         { 'image' => ['url'=>'...', ... ] } - необязательное, только если указано: $fieldImage
      //         {  ...                            } - необязательное, только если указано: $boxFields
      //      ]
      //
      //   1 =>
      //      [
      //           'title' => '....',
      //           ...
      //      ]
      //
      //   ...
      // ]
      //
    {
      $result = [ 'errorCode' => -1 ]; // ошибка

      try {
        $nids = \Drupal::entityQuery( $entity )->accessCheck(FALSE)
          ->condition('status', $status )
          ->condition('type', $type )
          ->condition('nid', $boxId, 'IN' )
          ->sort($sort, $sortDirection)
          ->execute();

        if (!empty($nids))
          $data = \Drupal\node\Entity\Node::loadMultiple($nids);

        if (!empty($data)) {
          foreach ($data as $node) {

            $resultSet = $node->toArray();
            $item = [];
            $item = [
              'title' => $resultSet['title'][0]['value'],
            ];

            if( ! empty( $fieldImage ) ) {
              $media = Media::load( $resultSet[ $fieldImage ][0]['target_id'] );
              $boxMedia = $media->toArray();
//            $this->logging_debug( 'media:' );
//            $this->logging_debug( $boxMedia );
              $meta = $boxMedia['thumbnail'][0];

              if (!empty($media)) {

                $source = $media->getSource();
                $fid = $source->getSourceFieldValue($media);
                $file = File::load($fid);

                if (!empty($file)) {

                  $url = \Drupal::service('file_url_generator')->generateString($file->getFileUri());

                  $imgBox = [
                    'file_id' => $file->id(),
                    'created' => date('d-m-Y', $file->getCreatedTime()),
                    'changed' => date('d-m-Y', $file->getChangedTime()),
                    'name' => $file->getFilename(),
                    'label' => empty($meta) ? $file->label() : $meta['alt'],
                    'url' => $url,
                    'mime' => $file->getMimeType()
                  ];

                  $item['image'] = $imgBox;
                }
              }
            }

            if( ! empty( $boxFields ) ) {
              foreach ( $boxFields as $code => $name ) {

                if( count( $resultSet[$name] ) > 1 ) {

//                $this->logging_debug( '' );
//                $this->logging_debug( 'code: ' . $code . ', name: ' . $name );
//                $this->logging_debug( $resultSet[ $name ] );

                  $i=0;
                  foreach ($resultSet[$name] as $value) {
                    $item[$code][] = $value['value'];
                    $i++;
                  }
                } else {
                  $item[$code] = empty( $resultSet[$name][0]['value'] ) ? '' : $resultSet[$name][0]['value'];
                }
              }
            }

            $boxResult [] = $item;
          }
        }
      } catch( \Exception $e ) {

        $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'Exception:' );
        $this->logging_debug( $result );
      }

      return( $result );
    }

  public function GetImage( $idImage )
  {
    //
    // Получаем URI на картинку из настроек модуля
    // $idImage - id картинки который отдает модуль настроек через форму настроек.
    //
    $image = false;
    /*
        $this->logging_debug('');
        $this->logging_debug('idBgImage:');
        $this->logging_debug($idImage);
    */
    if (!empty($idImage)) {
      $boxFile = \Drupal::entityTypeManager()->getStorage('file')->load($idImage[0]);
      $image = \Drupal::service('file_url_generator')->generateString($boxFile->getFileUri());
    }
    //
    //
    //
    return( $image );
  }

  /**
   * Анализируются адреса текущей ноды, и в зависимсоти от условий дается ответ, подходит ли текущая нода, или нет.
   *
   * $uri - текущий URI страницы, $searchBox - массив URI на которых должна отображаться текущая нода.
   *
   * Возвращает true, если URI ноды найден в $uri текущей страницы (или в $uri родительской страницы, если $useRecursive = true).
   * Возвращает false, если URI ноды не найден ни как ($useRecursive = true или false) в $uri текущей или родительской страницы.
   */
  public function CheckUriFetchedRowRecursive( $uri, $searchBox, $useRecursive = false ) {

    $result = true;

    if( mb_substr( $uri, -1, 1 ) != '/' )
      $uri .= '/';                          // добавляем слэшь в конце

    foreach ( $searchBox as &$item ) {

      if( mb_substr( $item, -1, 1 ) != '/' )
        $item .= '/';                          // добавляем слэшь в конце
    }

    $this->logging_debug( '' );
    $this->logging_debug( 'current uri: ' . $uri );
    $this->logging_debug( '' );
    $this->logging_debug( 'searchBox:' );
    $this->logging_debug( $searchBox );

    $is = array_search( $uri, $searchBox );
    if( $is !== false ) {
      $isUriSegment = $uri;
      $result = $isUriSegment;   // Текущая нода для текущей папки подходит. показываем ее.
    }
//  $this->logging_debug( '' );
//  $this->logging_debug( 'Recursive: ' . $useRecursive );

    if( $is === false && $useRecursive == false ) { // если в массиве $searchBox не найден текущий URI, пропускаем эту запись

      $result = false;
    }
    //
    // текущей папке $uri нода не подходит! проверяем ее рекурсивно в родительских папках $uri если $useRecursive == true ...
    //
    if( $is === false && $useRecursive ) {

      $isUriSegment = $this->urlUtil->IsFoldersInUri( $searchBox, $uri, 10 );

      if( empty( $isUriSegment ) ) {
        $result = false;  // нода не обнаружена ни где! в том числе и рекурсивно в родительсикх папках ...

        $this->logging_debug( '' );
        $this->logging_debug( 'Нода не обнаружена в родительских папках!' );

      } else {
        $result = $isUriSegment;   // Текущая нода для текущей папки подходит. показываем ее.
      }
    }
    //
    return( $result );
  }

  public function ReversalSearchFolderInURI( &$items, $data, $uri, $useRecursive, $foldersField, $uriField, $pluginLoadingDataRow ) {

    $result = [];
    $dataNodes = [];
    $isUriSegment = false;
    $isLocalUriSegment = false;

    if (!empty($data)) {
      //
      // Ищем подходящие ноды соответствующие текущему $uri
      //
      foreach ($data as $node) {

        $resultSet = $node->toArray();

//      $this->drupalUtil->logging_debug( 'resultSet:' );
//      $this->drupalUtil->logging_debug( $resultSet );

        $searchBox = array_column( $resultSet[ $foldersField ], 'value' );

//      $this->drupalUtil->logging_debug( 'searchBox:' );
//      $this->drupalUtil->logging_debug( $searchBox );

        $isLocalUriSegment = $this->CheckUriFetchedRowRecursive( $uri, $searchBox, false );

        if( ! empty( $isLocalUriSegment ) ) {

          $isUriSegment = $isLocalUriSegment;
//        $dataNodes[] = $this->LoadingDataRow( $resultSet, $isUriSegment );
          $dataNodes[] = call_user_func_array( $pluginLoadingDataRow[ 'function' ], [ $resultSet, $isUriSegment ] );

        } else {
          continue;
        }
      }
      //
      // Если не найдено ни одной ноды соответствующей текущему $uri,
      // только в этом случае, и если $useRecursive == true, ищем ноды в родительских папках текущего $uri.
      //
      if( empty( $isUriSegment ) && $useRecursive == true ) {

        foreach ($data as $node) {

          $resultSet = $node->toArray();
          $searchBox = array_column($resultSet[ $foldersField ], 'value');

//        $this->drupalUtil->logging_debug( 'searchBox:' );
//        $this->drupalUtil->logging_debug( $searchBox );

          $isLocalUriSegment = $this->CheckUriFetchedRowRecursive($uri, $searchBox, $useRecursive );

          if( ! empty( $isLocalUriSegment ) ) {

            $isUriSegment = $isLocalUriSegment;
//          $dataNodes[] = $this->LoadingDataRow( $resultSet, $isUriSegment );
            $dataNodes[] = call_user_func_array( $pluginLoadingDataRow[ 'function' ], [ $resultSet, $isUriSegment ] );

          } else {
            continue;
          }
        }
      }

      $currentFolder = '';
      foreach ( $dataNodes as $item ) {
        if( mb_strlen( $currentFolder ) < mb_strlen( $item[ $uriField ] ) )
          $currentFolder = $item[ $uriField ];
      }

      foreach ( $dataNodes as $item ) {
        if( $currentFolder == $item[ $uriField ] )
          $items[] = $item;
      }

    }

    return( $result );
  }

}
