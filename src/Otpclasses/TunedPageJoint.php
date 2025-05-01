<?php

namespace Otpclasses\Otpclasses;

use Otpclasses\Otpclasses\DrupalUtilities;
use Otpclasses\Otpclasses\OtpUtilities;
use Otpclasses\Otpclasses\UrlUtilities;

use Drupal\deposits\Plugin\Block\DepositCalcDataLoader;
use Drupal\loans\Plugin\Block\CalcDataLoader;


class TunedPageJoint extends UrlUtilities
{
  public $drupalUtil;
  public $otpUtil;
  public $cfgForm;
  public $statusPublished;
  public $url;
  public $uri;
  public $referer;
  public $boxUTM;
  public $currentFolder;
//
// $cfgSettings содержит строку вида: 'код_модуля.settings'
//
  public function __construct( $cfgSettings, $logName = '/tunedpagejoint.log', $cuteIdentifier = 'TunedPageJoint.', $cuteModule = true, $withOldLog = true  ) {

/*
    $logName  = "/tunedpagejoint.log";
    $logCute  = "TunedPageJoint.";

    $this->drupalUtil->SetNumberDaysCut(3);
    $this->drupalUtil->SetCuteTimes('06:00:00', '08:00:00');
    $this->drupalUtil->SetShowTimeEachRow(false);
    $this->drupalUtil->SetLogOn(true);
*/
    $this->drupalUtil = new DrupalUtilities( $logName, $cuteIdentifier, false );
    $this->drupalUtil->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
    $this->drupalUtil->SetStarting( true );

    $this->otpUtil = new OtpUtilities( $logName, $cuteIdentifier, false );
    $this->otpUtil->SetExternalLogging( [ 'function' => [ $this, "logging_debug" ] ] );
    $this->otpUtil->SetStarting( true );

    $this->statusPublished = 1;
    $this->cfgForm = \Drupal::configFactory()->getEditable( $cfgSettings );

    $this->url = $this->GetCurrentUrl();   // определяем текущий URL
    $this->uri = $this->GetCurrentPathWithoutParameters(); // \Drupal::request()->getRequestUri();
    $this->currentFolder = $this->LastSection( $this->uri );  // текущая папка
    //
    $this->drupalUtil->logging_debug( 'uri: '. $this->uri . ', currentFolder: ' . $this->currentFolder );
    //
    $httpReferer = $_SERVER['HTTP_REFERER'] ?? '';

    $this->referer = $this->SearchUrlWithUtmMarkup(
      [ $this->url, $httpReferer ],
      [ 'utm_source', 'utm_medium' ],
      [ $httpReferer, $this->url ]
    );  // определяем текущий referer исходя из наличия utm - меток. если в одном из адресов они есть, значит это и есть referer

    $this->boxUTM = $this->otpUtil->GetParametersInSources( $this->referer,
      [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' ] );

    parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );
  }

  public function __destruct() {

    parent::__destruct();
  }


  /**
   * Возвращает массив данных найденных нод удовлетворяющих заданным условиям.
   * $folder - текущий URI!
   */
  public function LoadingDataRow( $resultSet, $folder ) {

    $result = [];
    $this->logging_debug('');
//  $this->logging_debug('TunedPageJoint');
//  $this->logging_debug('resultset:');
//  $this->logging_debug($resultSet);

    $title = empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'];
    $created = date('d-m-Y', $resultSet['created'][0]['value']);
    //
    // Накрыть страницей продукта нижнюю часть баннера.
    //
    $coverBanner = empty( $resultSet['field_bigblock'][0]['value'] )  ? 'N'  : 'Y';
    $classCoverBanner = ($coverBanner == 'Y') ? 'doc-sheet' : '';
    //
    $charCodePage = empty($resultSet['field_code100'][0]['value'])
      ? ''
      : $resultSet['field_code100'][0]['value'];  // код страницы
    //
    // данные по калькулятору
    //
    $infoblockCalc = empty($resultSet['field_codecontenttype'][0]['value'])
      ? ''
      : $resultSet['field_codecontenttype'][0]['value'];       // код инфоблока калькулятора

    $codeCalcFolder = empty($resultSet['field_codefolder'][0]['value'])
      ? ''
      : $resultSet['field_codefolder'][0]['value'];           // код папки калькулятора
    $calcMainTitle = empty($resultSet['field_cardadvantagestitle'][0]['value'])
      ? ''
      : $resultSet['field_cardadvantagestitle'][0]['value'];                          // главный заголовок калькулятора
    $calcInnerFirstTitle = empty($resultSet['field_cardconditionstitle'][0]['value'])
      ? ''
      : $resultSet['field_cardconditionstitle'][0]['value'];                          // первый внутренний заголовок
    $calcInnerSecondTitle = empty($resultSet['field_cardgettingtitle'][0]['value'])
      ? ''
      : $resultSet['field_cardgettingtitle'][0]['value'];                             // второй внутренний заголовок
    $calcResultTitle = empty($resultSet['field_calcresulttitle'][0]['value'])
      ? ''
      : $resultSet['field_calcresulttitle'][0]['value'];                              // заголовок результата
    //
    $showCalc = 'N';
    $defaultPaymentCode = '';
    $defaultDepositIndex  = 0;
    $productCaption = '';
    //
    $calc = [
      'default_deposit_index' => $defaultDepositIndex,
      'code_infoblock_calc' => $infoblockCalc,
      'code_folder' => $codeCalcFolder,
      'show'  => $showCalc,
      'show_otpbankua' => 'Y',
      'title' => $calcMainTitle,
      'product' => $productCaption,
      'inner_title_first' => $calcInnerFirstTitle,
      'inner_title_second' => $calcInnerSecondTitle,
      'result_calc' => $calcResultTitle
    ];
    //
    // ПАРАМЕТРЫ ВЫЗОВА КАЛЬКУЛЯТОРОВ
    //
    $calcCallArguments = empty($resultSet['field_calccallarguments'][0]['value'])
      ? ''
      : $resultSet['field_calccallarguments'][0]['value'];

    $itemsCalcArguments = explode("\n", $calcCallArguments );

    $args = [];   // аргументы вызова калькулятора

    foreach( $itemsCalcArguments as $itemArg ) {

      if( empty( trim( $itemArg ) ) )
        continue;

      $itemsArg   = explode( ";", $itemArg );
      $argFolder  = empty( $itemsArg[ 0 ] ) ? '' : $itemsArg[ 0 ];   // код папки
      $argCode    = empty( $itemsArg[ 1 ] ) ? '' : $itemsArg[ 1 ];   // код калькулятора
      $argProduct = empty( $itemsArg[ 2 ] ) ? '' : $itemsArg[ 2 ];   // продукт
      $argTitle   = empty( $itemsArg[ 3 ] ) ? '' : $itemsArg[ 3 ];   // название расчета

      $args []    = [ 'folder' => $argFolder, 'code' => $argCode, 'product' => $argProduct, 'title' => $argTitle ];
    }
    //
    if( !empty( $infoblockCalc ) && !empty( $codeCalcFolder ) && !empty( $args ) ) {
      //
      // чтение данных по указанному коду калькулятора
      // ( $infoblockCalc[ТИП МАТЕРИАЛА КАЛЬКУЛЯТОРА] + $codeCalcFolder[КОД ПАПКИ В САМОМ ТИПЕ КАЛЬКУЛЯТОРА] )
      // + для сложных калькуляторов: $args с дополнительными параметрами вызова.
      //
      $showCalc = 'N';

      switch( $infoblockCalc ) {
        case 'CalcDeposit':

          $calcLoad = new DepositCalcDataLoader( $this->drupalUtil );
          $calcLoad->LoadCalcDeposit( $calcData, $infoblockCalc, $codeCalcFolder );
          //
          if( empty( $calcData['send_button'] ) ) {
            $calcData['send_button'] = [
              'action' => 'order/',
              'caption' => t('Send order'),
              'show' => 'Y'
            ];
          }
          //
          foreach ( $calcData['payment_term'] as $code => $params ) {
            $defaultPaymentCode = $code;
            break;
          }
          //
          if( ! empty( $calcData['items'] ) ) {
            $calc['default_box'] = $calcData['items'][$defaultDepositIndex];
            $calc['default_step_relative'] = $calcData['items'][$defaultDepositIndex]['ParamsMath']['UAH'][$defaultPaymentCode . '_RELATIVE'];
          }
          $calc['data']  = $calcData;
          //
          if( !empty( $calcData ) && ! empty( $calcData['items'] ) ) {

            $showCalc = 'Y';
          }
          break;

        case 'CalcCashLoan':
          $calcLoad = new CalcDataLoader( $this->drupalUtil );
          $calcLoad->LoadCalcCashLoan( $calcData, $infoblockCalc, $args );

          $calc['data']  = $calcData;
          if( !empty( $calcData ) && ! empty( $calcData['items'] ) ) {

            $showCalc = 'Y';
          }
          break;

        case 'CalcLoanUniversal':
          $calcLoad = new CalcDataLoader( $this->drupalUtil );
          $calcLoad->LoadCalcsLoanUniversal( $calcData, $infoblockCalc, $args );

          $calc['data']  = $calcData;
          $calc['json']  = htmlspecialchars( json_encode( $calcData ), ENT_QUOTES, 'UTF-8');
          $calc['result_percent_addon'] = '%';
          $calc['result_amount_addon'] = t( 'uah' );
          $calc['result_xirr'] = ( 'XIRR' );
          $calc['text'] = [
                'slider_title_top' => t( 'Cash loan' ),
                'slider_title_middle' => t( 'Down payment' ),
                'slider_title_bottom' => t( 'Term of credit' ),
                'slider_title_grace' => t( 'Term of grace' ),
                'input_unit_percent' => '%',
                'amount_addon_text' => t( 'UAH.' ),
                'term_addon_text' => t( 'MON.' ),
                'term_label' => t( 'Credit term' ),
                'result_monthly_payment' => t( 'Monthly payment including commission' ),
                'result_total_overpayment' => t( 'Total consumer loan expenses' ),
                'result_comission_amount' => t( 'Monthly commission' ),
                'result_amount_all' => t( 'Total credit amount' ),
                'result_insurance' => t( 'Insurance' ),
                'result_loan_amount' => t( 'Loan amount' ),
                'result_fee' => t( 'One-time fee' ),
                'result_casco' => t( 'CASCO insurance' ),
                'result_sms_reference' => t( 'SMS-reference' ),
                'result_notarius' => t( 'Notary services' ),
                'result_pension_amount' => t( 'Compulsory state pension insurance' )
              ];

          if( !empty( $calcData ) && ! empty( $calcData['items'] ) ) {

            $showCalc = 'Y';
          }
          break;
      }

      $calc['show'] = $showCalc;
    }
    //
    // БЛОК HTML С ДВУМЯ ЗАКЛАДКАМИ
    //
    $showTwoTabsHtml    = empty( $resultSet['field_showtwotabshtml'][0]['value'] ) ? 'N' : 'Y';
    $twoTabsHtml        = [];
    $titleTwoTabsHtml   = '';
    $titleTabFirstHtml  = '';
    $titleTabSecondHtml = '';
    $tabHtmlFirst  = [];
    $tabHtmlSecond = [];

    if( $showTwoTabsHtml == 'Y' ) {

      $titleTwoTabsHtml = empty($resultSet['field_titletwotabshtml'][0]['value']) ? '' : $resultSet['field_titletwotabshtml'][0]['value'];
      $titleTabFirstHtml = empty($resultSet['field_titletabfirsthtml'][0]['value']) ? '' : $resultSet['field_titletabfirsthtml'][0]['value'];
      $titleTabSecondHtml = empty($resultSet['field_titletabsecondhtml'][0]['value']) ? '' : $resultSet['field_titletabsecondhtml'][0]['value'];
      $tabHtmlFirst = empty($resultSet['field_tabfirsthtml'][0]['value']) ? '' : $resultSet['field_tabfirsthtml'][0]['value'];
      $tabHtmlSecond = empty($resultSet['field_tabsecondhtml'][0]['value']) ? '' : $resultSet['field_tabsecondhtml'][0]['value'];
    }

    $twoTabsHtml  = [
      'show' => $showTwoTabsHtml,
      'title' => $titleTwoTabsHtml,
      'title_first' => $titleTabFirstHtml,
      'title_second' => $titleTabSecondHtml,
      'html_first' => $tabHtmlFirst,
      'html_second' => $tabHtmlSecond
    ];
    //
    // БЛОК ИКОНОК С ДВУМЯ ЗАКЛАДКАМИ
    //
    $showTwoTabsIcons    = empty( $resultSet['field_showtwotabsblock'][0]['value'] ) ? 'N' : 'Y';
    $twoTabsIcons        = [];
    $titleTwoTabsIcons   = '';
    $titleTabFirstIcons  = '';
    $titleTabSecondIcons = '';
    $tabIconsFirst  = [];
    $tabIconsSecond = [];

    if( $showTwoTabsIcons == 'Y' ) {

      $titleTwoTabsIcons = empty($resultSet['field_titletwotabsblock'][0]['value']) ? '' : $resultSet['field_titletwotabsblock'][0]['value'];
      $titleTabFirstIcons = empty($resultSet['field_titletabfirst'][0]['value']) ? '' : $resultSet['field_titletabfirst'][0]['value'];
      $titleTabSecondIcons = empty($resultSet['field_titletabsecond'][0]['value']) ? '' : $resultSet['field_titletabsecond'][0]['value'];

      $this->drupalUtil->LoadSomeMediaImagesFromRow(
        $tabIconsFirst,
        $resultSet,
        'field_cardtopicicons',
        'field_tabiconcaptionsfirst');

      if (empty($tabIconsFirst)) {
        $showTwoTabsIcons = 'N';
      }

      $this->drupalUtil->LoadSomeMediaImagesFromRow(
        $tabIconsSecond,
        $resultSet,
        'field_tabiconssecond',
        'field_tabiconcaptonssecond');

      if (empty($tabIconsSecond)) {
        $showTwoTabsIcons = 'N';
      }
    }

    $twoTabsIcons  = [
      'show' => $showTwoTabsIcons,
      'title' => $titleTwoTabsIcons,
      'title_first' => $titleTabFirstIcons,
      'title_second' => $titleTabSecondIcons,
      'icons_first' => $tabIconsFirst,
      'icons_second' => $tabIconsSecond
    ];
    //
    // БЛОК ICONS ONE
    //
    $iconsOne = [];
    $iconsOneTitle = empty($resultSet['field_titleone'][0]['value']) ? '' : $resultSet['field_titleone'][0]['value'];

    $this->drupalUtil->LoadSomeMediaImagesFromRow(
      $iconsOne,
      $resultSet,
      'field_iconsone',
      'field_iconsonecaptions' );

    $iconsOneShow = 'N';
    if( ! empty( $iconsOne ) ) {
      $iconsOneShow = 'Y';
    }
    $iconsOneColCnt = $this->IconsColCount( $iconsOne );
    $icons1 = [
      'show' => $iconsOneShow,
      'title' => $iconsOneTitle,
      'icons' => $iconsOne,
      'count' => empty( $iconsOne ) ? 0 : count( $iconsOne ),
      'col'   => $iconsOneColCnt
    ];
    //
    // БЛОК ICONS TWO
    //
    $iconsTwo = [];
    $iconsTwoTitle = empty($resultSet['field_titletwo'][0]['value']) ? '' : $resultSet['field_titletwo'][0]['value'];

    $this->drupalUtil->LoadSomeMediaImagesFromRow(
      $iconsTwo,
      $resultSet,
      'field_iconstwo',
      'field_iconstwocaptions' );

    $iconsTwoShow = 'N';
    if( ! empty( $iconsTwo ) ) {
      $iconsTwoShow = 'Y';
    }
    $iconsTwoColCnt = $this->IconsColCount( $iconsTwo );
    $icons2 = [
      'show' => $iconsTwoShow,
      'title' => $iconsTwoTitle,
      'icons' => $iconsTwo,
      'count' => empty( $iconsTwo ) ? 0 : count( $iconsTwo ),
      'col'   => $iconsTwoColCnt
    ];
    //
    // БЛОК ACCORDION ONE
    //
    $accordeonOneShow  = empty( $resultSet['field_showhtmlone'][0]['value'] ) ? 'N' : 'Y';
    $accordeonOne = [];
    $accordeonOneCount = count( $resultSet['field_titlehtmlone'] );
    $cnt = count( $resultSet['field_htmlone'] );
    if( $cnt < $accordeonOneCount )
      $accordeonOneCount = $cnt;

    if( $accordeonOneShow == 'Y'
      && ! empty( $resultSet['field_titlehtmlone'] )
      && ! empty( $resultSet['field_htmlone'] ) ) {

      for( $i=0; $i < $accordeonOneCount; $i++ ) {
        $caption = $resultSet['field_titlehtmlone'][$i]['value'];
        $accordeonOne [] = [ 'caption' => $caption ];
      }

      for( $i=0; $i < $accordeonOneCount; $i++ ) {
        $html = $resultSet['field_htmlone'][$i]['value'];
        $accordeonOne[ $i ]['html'] = $html;
      }
    }
    $accordeon1 = [
      'show' => $accordeonOneShow,
      'items' => $accordeonOne,
      'count' => $accordeonOneCount
    ];
    //
    // WebFormModuleObject
    //
    $webFormBox = [
      'show' => 'N',
      'title' => '',
      'value' => []
    ];
    if( !empty( $resultSet['field_webformmoduleobject'][0]['target_id'] ) ) {

      $this->drupalUtil->logging_debug( 'form:' );
      $this->drupalUtil->logging_debug( $resultSet['field_webformmoduleobject'][0] );

      $webFormCode = $resultSet['field_webformmoduleobject'][0]['target_id'];
      $form = \Drupal\webform\Entity\Webform::load( $webFormCode );
      //
      // Generate the output from the generated webform object
      //
      $formBox = \Drupal::entityTypeManager()->getViewBuilder('webform')->view( $form );

      $webFormBox['form_id'] = $webFormCode;
      $webFormBox['value'] = $formBox;
      $webFormBox['show'] = 'Y';
      $webFormBox['title'] = empty( $resultSet['field_formtitleforward'][0]['value'] )
        ? $formBox['elements']['form_name']['#default_value']
        : $resultSet['field_formtitleforward'][0]['value'];
    }
    //
    // ВТОРОЙ HTML БЛОК (первый - аккордеон)
    //
    $htmlTwo = [];
    $htmlTwoShow  = empty( $resultSet['field_showhtmltwo'][0]['value'] ) ? 'N' : 'Y';
    $htmlTwoTitle = empty($resultSet['field_titlehtmltwo'][0]['value']) ? '' : $resultSet['field_titlehtmltwo'][0]['value'];
    $htmlTwo['show'] = $htmlTwoShow;

    if( $htmlTwoShow == 'Y'
      && ( ! empty( $htmlTwoTitle ) || ! empty( $resultSet['field_htmltwo'] ) ) ) {

      $html = empty(  $resultSet['field_htmltwo'][0]['value'] )
        ? ''
        : $resultSet['field_htmltwo'][0]['value'];

      $htmlTwoShow  = empty( $html ) ? 'N' : 'Y';

      if( $htmlTwoShow == 'Y' ) {
        $htmlTwo = [];
        $htmlTwo = [
          'show' => $htmlTwoShow,
          'showtitle' => empty( $htmlTwoTitle ) ? 'N' : 'Y',
          'title' => $htmlTwoTitle,
          'html' => $html
        ];
      }
    }
    //
    // ТРЕТИЙ HTML БЛОК
    //
    $htmlThree = [];
    $htmlThreeShow  = empty( $resultSet['field_showhtmlthree'][0]['value'] ) ? 'N' : 'Y';
    $htmlThreeTitle = empty($resultSet['field_titlehtmlthree'][0]['value']) ? '' : $resultSet['field_titlehtmlthree'][0]['value'];
    $htmlThree['show'] = $htmlThreeShow;

    if( $htmlThreeShow == 'Y'
      && ( ! empty( $htmlThreeTitle ) || ! empty( $resultSet['field_htmlthree'] ) ) ) {

      $html = empty(  $resultSet['field_htmlthree'][0]['value'] )
        ? ''
        : $resultSet['field_htmlthree'][0]['value'];

      $htmlThreeShow  = empty( $html ) ? 'N' : 'Y';

      if( $htmlThreeShow == 'Y' ) {
        $htmlThree = [];
        $htmlThree = [
          'show' => $htmlThreeShow,
          'showtitle' => empty( $htmlThreeTitle ) ? 'N' : 'Y',
          'title' => $htmlThreeTitle,
          'html' => $html
        ];
      }
    }
    //
    // Четвертый HTML БЛОК
    //
    $htmlFour = [];
    $htmlFourShow  = empty( $resultSet['field_showhtmlfour'][0]['value'] ) ? 'N' : 'Y';
    $htmlFourTitle = empty($resultSet['field_titlehtmlfour'][0]['value']) ? '' : $resultSet['field_titlehtmlfour'][0]['value'];
    $htmlFour['show'] = $htmlFourShow;

    if( $htmlFourShow == 'Y'
      && ( ! empty( $htmlFourTitle ) || ! empty( $resultSet['field_htmlfour'] ) ) ) {

      $html = empty(  $resultSet['field_htmlfour'][0]['value'] )
        ? ''
        : $resultSet['field_htmlfour'][0]['value'];

      $htmlFourShow  = empty( $html ) ? 'N' : 'Y';

      if( $htmlFourShow == 'Y' ) {
        $htmlFour = [];
        $htmlFour = [
          'show' => $htmlFourShow,
          'showtitle' => empty( $htmlFourTitle ) ? 'N' : 'Y',
          'title' => $htmlFourTitle,
          'html' => $html
        ];
      }
    }
    //
    // пятый HTML БЛОК
    //
    $htmlFive = [];
    $htmlFiveShow  = empty( $resultSet['field_showhtmlfive'][0]['value'] ) ? 'N' : 'Y';
    $htmlFiveTitle = empty($resultSet['field_titlehtmlfive'][0]['value']) ? '' : $resultSet['field_titlehtmlfive'][0]['value'];
    $htmlFive['show'] = $htmlFiveShow;

    if( $htmlFiveShow == 'Y'
      && ( ! empty( $htmlFiveTitle ) || ! empty( $resultSet['field_htmlfive'] ) ) ) {

      $html = empty(  $resultSet['field_htmlfive'][0]['value'] )
        ? ''
        : $resultSet['field_htmlfive'][0]['value'];

      $htmlFiveShow  = empty( $html ) ? 'N' : 'Y';

      if( $htmlFiveShow == 'Y' ) {
        $htmlFive = [];
        $htmlFive = [
          'show' => $htmlFiveShow,
          'showtitle' => empty( $htmlFiveTitle ) ? 'N' : 'Y',
          'title' => $htmlFiveTitle,
          'html' => $html
        ];
      }
    }
    //
    // БЛОК ICONS THREE
    //
    $iconsThree = [];
    $iconsThreeTitle = empty($resultSet['field_titlethree'][0]['value']) ? '' : $resultSet['field_titlethree'][0]['value'];

    $this->drupalUtil->LoadSomeMediaImagesFromRow(
      $iconsThree,
      $resultSet,
      'field_iconsthree',
      'field_iconsthreecaptions' );

    $iconsThreeShow = 'N';

    if( ! empty( $iconsThree ) ) {
      $iconsThreeShow = 'Y';
    }
    $iconsThreeColCnt = $this->IconsColCount( $iconsThree );

    $icons3 = [
      'show' => $iconsTwoShow,
      'title' => $iconsThreeTitle,
      'icons' => $iconsThree,
      'count' => empty( $iconsThree ) ? 0 : count( $iconsThree ),
      'col'   => $iconsThreeColCnt
    ];
    //
    // БЛОК ACTION
    //
    $action = [];
    $actionShow  = empty( $resultSet['field_showaction'][0]['value'] ) ? 'N' : 'Y';
    $actionTitle = empty($resultSet['field_actiontitle'][0]['value']) ? '' : $resultSet['field_actiontitle'][0]['value'];
    $action['show'] = $actionShow;
    //
    if( $actionShow == 'Y'
      && ! empty( $resultSet['field_actionicons'] )
      && ! empty( $resultSet['field_actioniconscaptions'] ) ) {

      $actionBackground = '';
      $actionBackgroundTitle = '';
      $actionBackgroundHint = '';

      $this->drupalUtil->LoadMediaImageFromRow(
        $actionBackground,
        $actionBackgroundTitle,
        $actionBackgroundHint,
        $resultSet,
        'field_blockbackground' );
      //
      $actionIcons = [];
      $this->drupalUtil->LoadSomeMediaImagesFromRow(
        $actionIcons,
        $resultSet,
        'field_actionicons',
        'field_actioniconscaptions' );
      //
      $actionShow = empty( $actionIcons ) ? 'N' : 'Y';
      $action['show'] = $actionShow;

      if( $actionShow == 'Y' ) {
        $action = [];
        $action = [
          'show' => $actionShow,
          'title' => $actionTitle,
          'bg_image' => $actionBackground,
          'icons' => $actionIcons,
          'count' => count( $actionIcons )
        ];
      }
    } else {
      $action['show'] = 'N';
    }
    //
    // ССЫЛКА НА ФОРМУ ЗАКАЗА
    //
    $formLink = [];
    $linkUrl    = '';
    $linkText   = '';
    $linkTitle  = '';
    $linkAlt    = '';
    $linkTarget = '';
    if( ! empty( $resultSet['field_formlink'][0]['value'] ) ) {
      $linkUrl    = $resultSet['field_formlink'][0]['value'];
      $linkText   = $resultSet['field_formlink'][1]['value'];
      $linkTitle  = $resultSet['field_formlink'][2]['value'];
      $linkAlt    = $resultSet['field_formlink'][3]['value'];
      $linkTarget = $resultSet['field_formlink'][4]['value'];

      $formLink = [
        'show'=> 'Y', 'href' => $linkUrl, 'text' => $linkText, 'title' => $linkTitle, 'alt' => $linkAlt, 'target' => $linkTarget
      ];
    } else {
      $formLink = [ 'show'=> 'N' ];
    }
    //
    // БЛОК НОВОСТЕЙ
    //
    $pageNews = [];
    $pageNewsShow = 'N';
    $pageNews['show'] = $pageNewsShow;
    $pageNewsTitle  = empty( $resultSet['field_blocktitle'][0]['value'] )
      ? ''
      : $resultSet['field_blocktitle'][0]['value'];

    $idOneNews = empty( $resultSet['field_linkoneto'][0]['target_id'] ) ? 0 : $resultSet['field_linkoneto'][0]['target_id'];
    $idSecondNews = empty( $resultSet['field_linktwoto'][0]['target_id'] ) ? 0 : $resultSet['field_linktwoto'][0]['target_id'];
    $idThirdNews = empty( $resultSet['field_linkthirdto'][0]['target_id'] ) ? 0 : $resultSet['field_linkthirdto'][0]['target_id'];

    $boxNews = [];
    $boxId   = [ $idOneNews, $idSecondNews, $idThirdNews ];

    $this->drupalUtil->LoadMultyTypeByReferencedId(
      $boxNews,
      ['DepositNews','CardNews', 'LoanNews' ],
      $boxId,
      [ 'code' => 'field_code100',
        'title' => 'title',
        'html' => 'field_contentshort',
        'detail_link' => 'field_linkshowdetail' ], // символьный код, html и линк на детальную информацию в инфоблоке DepositNews
      'field_annoncepicture'); // поле картинки аннонса в инфоблоке DepositNews

    if( ! empty( $boxNews ) ) {
      $pageNews = [];
      $pageNewsShow = 'Y';
      $pageNews = [
        'show' => $pageNewsShow,
        'title' => $pageNewsTitle,
        'news' => $boxNews,
        'count' => count( $boxNews )
      ];
    }
    //
    // БЛОК С ДВУМЯ НАБОРАМИ ИКОНОК - ПЕРЕКЛЮЧАТЕЛЬ: КНОПКИ ( жуткий изврат!!! не знаю зачем такое делать ... )
    //
    $showTwoButtons    = empty( $resultSet['field_showtwobuttonsblockicons'][0]['value'] ) ? 'N' : 'Y';
    $twoButtonsIcons   = [];
    $titleTwoButtons   = '';
    $titleButtonFirst  = '';
    $titleButtonSecond = '';
    $buttonIconsFirst  = [];
    $buttonIconsSecond = [];

    if( $showTwoButtons == 'Y' ) {

      $titleTwoButtons = empty($resultSet['field_titletwobuttonsblockicons'][0]['value']) ? '' : $resultSet['field_titletwobuttonsblockicons'][0]['value'];
      $titleButtonFirst = empty($resultSet['field_titlebuttoniconsfirst'][0]['value']) ? '' : $resultSet['field_titlebuttoniconsfirst'][0]['value'];
      $titleButtonSecond = empty($resultSet['field_titlebuttoniconssecond'][0]['value']) ? '' : $resultSet['field_titlebuttoniconssecond'][0]['value'];

      $this->drupalUtil->LoadSomeMediaImagesFromRow(
        $buttonIconsFirst,
        $resultSet,
        'field_buttoniconsfirst',
        'field_buttoniconcaptionsfirst');

      if (empty($buttonIconsFirst)) {
        $showTwoButtons = 'N';
      }

      $this->drupalUtil->LoadSomeMediaImagesFromRow(
        $buttonIconsSecond,
        $resultSet,
        'field_buttoniconssecond',
        'field_buttoniconcaptionssecond');

      if (empty($buttonIconsSecond)) {
        $showTwoButtons = 'N';
      }
    }

    $twoButtonsIcons  = [
      'show' => $showTwoButtons,
      'title' => $titleTwoButtons,
      'title_first' => $titleButtonFirst,
      'title_second' => $titleButtonSecond,
      'icons_first' => $buttonIconsFirst,
      'icons_second' => $buttonIconsSecond
    ];
    //
    // Channel
    //
    $utmCompaign = $this->boxUTM['utm_campaign'] ?? '';
    $utmContent = $this->boxUTM['utm_content'] ?? '';
    $channel = empty( $webFormBox['form_id'] ) ? '' : $webFormBox['form_id'] . '-' . $utmCompaign . '-' . $utmContent;
    //
    // BlockOrderList: БЛОК С ПОРЯДКОМ СЛЕДОВАНИЯ ВСЕХ БЛОКОВ СТРАНИЦЫ
    //
    $blockList = empty( $resultSet['field_blockorderlist'] )
      ? []
      : $resultSet['field_blockorderlist'];

    if( ! empty( $blockList ) ) {
      foreach ($blockList as $blockItem) {

        $blockCode = '';

        if (!empty($blockItem['value'])) {
          $blockBox = explode(' ', $blockItem['value']);
          $blockCode = $blockBox[0];
        }

        $blockOrderList [] = empty($blockCode) ? 'empty' : $blockCode;
      }
    } else {
      $blockOrderList = self::$blockOrderListDefault;
    }
    //
    //
    //
    $result = [
      'folder' => $folder,
      'id' => $resultSet['nid'][0]['value'],
      'page_code' => $charCodePage,
      'date_from' => $created,
      'name' => empty( $resultSet['title'][0]['value'] ) ? '' : $resultSet['title'][0]['value'],
      'cover_banner' => $classCoverBanner,
      'form' =>  $webFormBox,
      'calc' => $calc,
      'host' => $this->CurrentHost(),
      'action_link' => $this->referer, // referer
      'url' => $this->url,
      'uri' => $this->uri,
      'current_folder' => $this->currentFolder,
      'box_utm' => $this->boxUTM,
      'channel' => $channel,
      'two_tabs_html' => $twoTabsHtml,
      'two_tabs_icons' => $twoTabsIcons,
      'two_buttons_icons' => $twoButtonsIcons,
      'icons_1' => $icons1,
      'icons_2' => $icons2,
      'icons_3' => $icons3,
      'accordeon_1' => $accordeon1,
      'action' => $action,
      'form_link' => $formLink,
      'news' => $pageNews,
      'html_2' => $htmlTwo,
      'html_3' => $htmlThree,
      'html_4' => $htmlFour,
      'html_5' => $htmlFive,
      'order'  => $blockOrderList
    ];

//  $this->logging_debug('TunedPageJoint');
    //
    return( $result );
  }



  public function IconsColCount( $icons )
    //
    // В зависимости от количества иконок, метод определяет на сколько колонок их разделить
    //
  {
    $result = 1;

    if( ! empty( $icons ) ) {

      $iconsCount = count( $icons );
      switch( $iconsCount ) {
        case 5:
        case 6:
          $result = 2;
          break;
        case 4:
          $result = 3;
          break;
        case 3:
          $result = 4;
          break;
        case 2:
          $result = 6;
          break;
        case 1:
          $result = 12;
          break;
        default:
          $result = 1;
          break;
      }
    }

    return( $result );
  }




  public function GetItemsData( & $items )
  {
    $result = [];
    $nids = [];
    $data = [];

    $uri = $this->GetCurrentPathWithoutParameters(); // \Drupal::request()->getRequestUri();
/*
    $useRecursive = $this->cfgForm->get('recursive'); // чекбокс - рекурсивный показ в дочерних папках, если в них отсутствует свои ноды такого типа
*/
    $useRecursive = false;
    $sort = $this->cfgForm->get('sort_field') ?? 'field_sorting';
    $sortDirection = $this->cfgForm->get('sort_direct') ?? 'ASC';


    $nids = \Drupal::entityQuery('node')->accessCheck(FALSE)
      ->condition('status', $this->statusPublished)
      ->condition('type', 'TunedPage')
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


}

