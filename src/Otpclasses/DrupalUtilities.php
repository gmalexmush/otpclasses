<?php

namespace Otpclasses\Otpclasses;
//
// последняя версия от 2023.10.10
//
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Otpclasses\Otpclasses\StringUtilities;

class DrupalUtilities extends StringUtilities
{


     function __construct( $logName = '/utilities_for_drupal.log', $cuteIdentifier = 'DrupalInerfaceUtilities.', $cuteModule = true, $withOldLog = true ) {

        parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );


     }

    public function __destruct() {

        parent::__destruct();
    }


    public function LoadMediaImageFromRow( &$imgSrc, &$imgAlt, &$imgTitle,
                                            $box, $fieldImage,
                                            $fieldAlt='', $fieldTitle='' )
      //
      // загрузка одной картинки из резалт-сета ( параметр: $box )
      //
    {

      $result = [ 'errorCode' => -1 ]; // ошибка

      if( !empty( $box[ $fieldImage ] ) ) {

        try {
          $media = Media::load($box[$fieldImage][0]['target_id']);
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
        } catch( \Exception $e ) {

          $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
          $this->logging_debug( '' );
          $this->logging_debug( 'Exception:' );
          $this->logging_debug( $result );
        }
      }

      return( $result );
    }


    public function LoadSomeMediaImagesFromRow( &$imgBox, $box, $fieldImage )
    {
      //
      // загрузка нескольких картинок из резалт-сета ( параметр: $box )
      //
      $imgBox = [];
      $result = [ 'errorCode' => -1 ]; // ошибка

      if( ! empty( $box[ $fieldImage ] ) ) {

        try {

          foreach ($box[$fieldImage] as $iconItem) {

            $media = Media::load($iconItem['target_id']);
            $boxMedia = $media->toArray();
            $this->logging_debug( 'media:' );
            $this->logging_debug( $boxMedia );

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
        } catch( \Exception $e ) {

          $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
          $this->logging_debug( '' );
          $this->logging_debug( 'Exception:' );
          $this->logging_debug( $result );
        }
      }

      return( $result );
    }

    public function LoadByReferencedId( & $boxResult, $type, $boxId,
                                        $sort='field_sorting', $sortDirection='asc', $entity='node', $status=1 )
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

            $boxResult [] = [
                              'code' => $resultSet['field_code100'][0]['value'],
                              'title' => $resultSet['title'][0]['value'],
                              'details' => $resultSet['field_details100'][0]['value']
                            ];

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

}
