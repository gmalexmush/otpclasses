<?php

namespace Otpclasses\Otpclasses;

use Drupal;
use Drupal\Core\Entity;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Otpclasses\Otpclasses\DateUtilities;
use Otpclasses\Otpclasses\LogUtilities;
use Otpclasses\Otpclasses\MailUtilities;
use Otpclasses\Otpclasses\FormUtilities;
use Otpclasses\Otpclasses\CommonDataBox;
use Otpclasses\Otpclasses\CustomExceptions\FileNotFoundException;


class AgentOptions extends LogUtilities
{
//
//  Блок полей, соответствующих полям элементов инфоблока - otpb_agents_options
//  НАЧАЛО
//
		public $showStartedTime;
		public $mailHandle;
		public $dateHandle;
    public $formHandle;
    public $AgentRunning;
    public $agent_user_id;
    public $moduleName;
    public $optionElementCode;
    public $settings;           // дополнительные настройки из json файла модуля (если он есть)
    public $nidModuleOptions;

    public $is_weekend;
    public $id_options;
    public $id_iblock;
    public $ib_options;
    public $weekday;
    public $agent_sort;
    public $agent_module_id;
    public $agent_function;
    public $time_start;
    public $time_finish;
    public $intervalForce;
    public $intervalNorm;
    public $intervalWeekend;
    public $max_number_shot;
    public $cnt_number_shot;
    public $max_number_retry;
    public $week_is_work;
    public $week_time_start;
    public $week_time_finish;
    public $week_max_number_shot;
    public $week_cnt_number_shot;
    public $week_max_number_retry;
    public $week_emulation;
    public $deactivation;
    public $forms_id;
		public $checkFormListId;						// список ID форм, для проверки их работоспособности
    public $action_start_prev;
    public $action_start;
    public $action_start_real;
    public $action_finish;
    public $next_start;
    public $action_time_last;
    public $action_time_max;
    public $time_time_max;
    public $action_active;
    public $StopAfterSuccessfullAtempt;
    public $SuccessfullAtempt;
    public $SuccessfullAtemptWeekend;
    public $request_sleep;
    public $id_processed;
    public $time_active_reset;
    public $is_new_day;
    public $agentuserlogin;
    public $agent_start_date;
    public $agent_finish_date;
    public $npp;
    public $socks_proxy;
    public $type_proxy;
    public $type_proxy_id;
    public $force_agent;
    public $APIYandexKey;			// массив вида: $BOX[ GEO_API_KEY1, GEO_API_KEY2, GEO_API_KEY3 ]
		public $APIYandexCount;		// массив вида: $BOX[ GEO_API_KEY ] = [ 'max' => COUNT_MAX, 'val' => COUNT_VALUE ]
    public $pack_size;
    public $sidPositive;
    public $sidNegative;
    public $sendCallCenter;
	  public $processingSpeed;
    public $triggerFlag;
	  public $timeLastMailSend;	// время последней отправки почтового сообщения
//
//  Блок полей, соответствующих полям элементов инфоблока - otpb_agents_options
//  ОКОНЧАНИЕ
//

//
//  Блок вспомогательных полей класса
//  НАЧАЛО
//
	public $useDateCikle;		// используются (true) либо нет (false) даты старта и окончания цикла обработки
	public $workingCikleFinish;	// true - рабочий цикл завершен, false - рабочий цикл не завершен.
	public $periodAdminMessage;
  public $statusPublished;
	//
	//  технические свойства класса
	//
	private $agentStartDatePrev;
	private $agentFinishDatePrev;
	private $agentCurrentDatePrev;
//
//  Блок вспомогательных полей класса
//  ОКОНЧАНИЕ
//

    public function __construct( $logName = '/agents.log', $cuteIdentifier = 'AgentIdentifier.', $cuteModule = false, $withOldLog = true ) {

    parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

    $this->SetShowTimeEachRow( false );
		$this->SetLogDateFormat( 'dd.mm.yyyy' );
		$this->SetNumberDaysCut( 10 );
		$this->SetDontCuteLog( true );
    $this->SetCuteTimes( '00:00:00', '00:00:10' );

    $this->mailHandle					= new MailUtilities( $this->log_name, $this->cute_identifier, $cuteModule, $withOldLog );
    $this->mailHandle->SetDontCuteLog( true );
    $this->mailHandle->SetShowTimeEachRow( $this->showTimeEachRow );
    $this->mailHandle->SetLogDateFormat( $this->log_date_format );
    $this->mailHandle->SetNumberDaysCut( $this->num_days_cut );
    $this->mailHandle->SetStarting( true );

    $this->dateHandle					= new DateUtilities( $this->log_name, $this->cute_identifier, $cuteModule, $withOldLog );
    $this->dateHandle->SetDontCuteLog( true );
    $this->dateHandle->SetShowTimeEachRow( $this->showTimeEachRow );
    $this->dateHandle->SetLogDateFormat( $this->log_date_format );
    $this->dateHandle->SetNumberDaysCut( $this->num_days_cut );
    $this->dateHandle->SetStarting( true );

    $this->formHandle					= new FormUtilities( $this->log_name, $this->cute_identifier, $cuteModule, $withOldLog );
    $this->formHandle->SetDontCuteLog( true );
    $this->formHandle->SetShowTimeEachRow( $this->showTimeEachRow );
    $this->formHandle->SetLogDateFormat( $this->log_date_format );
    $this->formHandle->SetNumberDaysCut( $this->num_days_cut );
    $this->formHandle->SetStarting( true );

		$this->showStartedTime		   = false;
		$this->useDateCikle			   = false;
		$this->workingCikleFinish	   = false;
		$this->AgentRunning            = 'Y';
		$this->agent_user_id           = 1;                                // id наобум любого пользователя
		$this->is_weekend              = false;
		$this->id_otions               = 0;
		$this->id_iblock               = 0;
		$this->ib_options              = "otpb_agents_options";            // инфоблок настроек агента
		$this->weekday                 = 0;

		$this->agent_sort              		= '';
		$this->agent_module_id         		= '';
		$this->agent_function          		= '';
		$this->time_start              		= '';
		$this->time_finish             		= '';
		$this->intervalForce           		= 300;
		$this->intervalNorm            		= 85800;
		$this->intervalWeekend         		= 3600;
		$this->max_number_shot         		= 3;
		$this->cnt_number_shot         		= 3;
		$this->max_number_retry        		= 5;
		$this->week_is_work            		= 'Y';
		$this->week_time_start         		= '';
		$this->week_time_finish        		= '';
		$this->week_max_number_shot    		= 3;
		$this->week_cnt_number_shot    		= 3;
		$this->week_max_number_retry   		= 5;
		$this->week_emulation          		= 'N';
		$this->deactivation            		= 'N';
		$this->forms_id                		= '';
		$this->checkFormListId		   			= '';
		$this->action_start_prev       		= 0;
		$this->action_start            		= 0;
		$this->action_finish           		= 0;
		$this->next_start                 = 0;
		$this->action_time_last        		= 0;
		$this->action_time_max         		= 0;
		$this->time_time_max           		= 0;
		$this->action_active           		= 'N';

		$this->StopAfterSuccessfullAtempt	= 'N';															// 'Y' - остановить работу модуля после взведения флага SuccessfullAtempt или SuccessfullAtemptWeekend
		$this->SuccessfullAtempt					= 'N';															// флага успешной операции в обычный день
		$this->SuccessfullAtemptWeekend		= 'N';															// флага успешной операции в выходной день

		$this->request_sleep           		= 0;                                // секунды
		$this->id_processed            		= '0';
		$this->time_active_reset       		= '60';                             // минуты
		$this->is_new_day              		= false;
		$this->agentuserlogin          		= 'admin';                          // admin login на обум
		$this->agent_start_date        		= false;
		$this->agent_finish_date       		= false;
		$this->npp                     		= 0;                                // какой-то номер по порядку
		$this->socks_proxy             		= '127.0.0.1:9050';
		$this->type_proxy              		= 0;
		$this->type_proxy_id           		= 0;
		$this->force_agent             		= 'N';
		$this->APIYandexKey            		= [];                               // 'f92bcc47-064a-4e34-a80e-f063986ac2dc';      // alexmushi@yandex.ru
		$this->APIYandexCount          		= [];
		$this->action_start_real       		= 0;
		$this->pack_size               		= 10;                               //  размер пачки
		$this->sidPositive             		= [];   //
		$this->sidNegative             		= [];   //
		$this->sendCallCenter          		= [];   //
		$this->processingSpeed		   			= [];
		$this->triggerFlag			   				= 0;

		$this->timeLastMailSend		   = 0;

    $this->statusPublished    = 1;            // читать только записи с активным статусом (опубликованные)
		$this->periodAdminMessage	= 1;						// по умолчанию раз в час
    $this->nidModuleOptions = 0;
	}


  public function __destruct() {
    parent::__destruct();
  }


  public function init( $moduleFolderName, $elementCode, $settingsJsonName )
  {
    $result = true;
    $this->logging_debug('Инициализация модуля: ' . $moduleFolderName );

    $this->moduleName        = $moduleFolderName;
    $this->optionElementCode = $elementCode;

//  $this->logging_debug( 'Загружаем настройки агента.' );
    $resultOptions = $this->GetOptions( $elementCode );

    if( empty( $resultOptions ) ) {
//    $this->logging_debug('');
//    $this->logging_debug('Настройки агента загружены.');
    } else {
      $result = false;
    }
    //
    $this->weekday    = intval( date( 'w', time() ) );

    if( $this->weekday == 6 || $this->weekday == 7 )
      $this->is_weekend = true;
    else
      $this->is_weekend = false;

    $this->workingCikleFinish 	= $this->IsFinishCicle( $this->useDateCikle );
    //
    $fullSettingsFileName = $this->GetSettingsFolder( $this->moduleName, $settingsJsonName );
    //
//  $this->logging_debug('');
//  $this->logging_debug( "fullSettingsFileName: " . $fullSettingsFileName );
    //
    $this->settings = $this->LoadSettingsFromJson($fullSettingsFileName);

    if( empty( $this->settings ) )
      $result = false;

    return( $result );
  }

	public function GetSettingsFolder( $moduleName, $settingsFileName )
  {
    $result = '';
//  $this->logging_debug('');
//  $this->logging_debug( "sitebox:" );
//  $this->logging_debug( $this->sitesBox );

    if( !empty( $this->sitesBox ) ) {

      try {

//      $path = $this->documentRoot . '/sites/' . $this->sitesBox[0]['folder'] . '/agents/' . $moduleName . '/' . $settingsFileName;
        $path = $this->documentRoot . '/../private/agents/' . $moduleName . '/' . $settingsFileName;

        if( file_exists( $path ) ) {
          $result = realpath( $path );
          $this->logging_debug( 'Settings file path: ' . $result );
        } else {
          throw new FileNotFoundException( 'File not found.', 100 );
        }

      } catch( \Error $e ) {
        $boxError = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'Error:' );
        $this->logging_debug( $boxError );
      } catch( \Exception $e ) {
        $boxError = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'Exception:' );
        $this->logging_debug( $boxError );
      } catch( FileNotFoundException $e ) {
        $boxError = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
        $this->logging_debug( '' );
        $this->logging_debug( 'Throwable:' );
        $this->logging_debug( $boxError );
      } finally {
//      $this->logging_debug( '' );
//      $this->logging_debug( 'GetSettingsFolder finished.' );
      }
    } else {
      $this->logging_debug('');
      $this->logging_debug( "sitebox is empty!" );
    }

		return( $result );
	}

	public function LoadSettingsFromJson( $fullSettingsFileName )
  {

    $result = [];

    if( file_exists( $fullSettingsFileName ) ) {

      $box = file_get_contents($fullSettingsFileName);

//    $this->logging_debug( "box:" );
//    $this->logging_debug( $box );

      $result = json_decode($box, true);
    }

    return( $result );
  }


	public function IsWeekEnd()
	{
	$result		= $this->week_emulation == 'Y' ? true : $this->is_weekend;

	if( $result )
		$this->logging_debug( "week_emulation: " . $this->week_emulation . ', is_weekend: ' . ($this->is_weekend?'Y':'N') );

	return $result;
	}





	public function IsDateValid( $str_date, $maket='dd.mm.yyyy' )
    {
		$result = true;

		$y = strpos( $maket,'yyyy' );
		if( $y === false )
			return( false );

		$m = strpos( $maket,'mm' );
		if( $m === false )
			return( false );

		$d = strpos( $maket,'dd' );
		if( $d === false )
			return( false );

		switch( $y ) {
		case 0:
				$del = substr( $maket, 4, 1 );
				$ch1 = substr( $str_date, 4, 1 );
				$ch2 = substr( $str_date, 7, 1 );
				break;
		case 3:
				$del = substr( $maket, 2, 1 );
				$ch1 = substr( $str_date, 2, 1 );
				$ch2 = substr( $str_date, 7, 1 );
				break;
		case 6:
				$del = substr( $maket, 5, 1 );
				$ch1 = substr( $str_date, 2, 1 );
				$ch2 = substr( $str_date, 5, 1 );
				break;
		default:
				$ch1 = '***';
		}

		if( $ch1 != $del || $ch2 != $del )
			$result = false;
		else {

			$yy			= substr( $str_date, $y, 4 );
			$mm			= substr( $str_date, $m, 2 );
			$dd			= substr( $str_date, $d, 2 );

			$yyy		= intval( $yy, 10 );
			$mmm		= intval( $mm, 10 );
			$ddd		= intval( $dd, 10 );

			$result		= mktime( 0, 0, 0, $mmm, $ddd, $yyy );

		}
		return( $result );
    }




	public function  CheckAndSetNewDay()
	{
		if( ! $this->is_new_day ) {

			$dt_curr = date('Y.m.d', time() );
			$dt_prev = date('Y.m.d', $this->action_start_real );

//    $this->logging_debug( "CheckAndSetNewDay, текущая дата: " . date('Y.m.d H:i:s', time() ) . ' и предыдущая дата: ' . date('Y.m.d H:i:s', $this->action_start_real )  );

			if ($dt_curr != $dt_prev) {
				//
				// НАЧАЛО НОВОГО ДНЯ БЫВАЕТ ТОЛЬКО РАЗ в СУТКИ
				//
				$this->is_new_day = true;

				foreach ($this->APIYandexKey as $keyAPI) {
					$this->APIYandexCount[$keyAPI]['val'] = $this->APIYandexCount[$keyAPI]['max'] - 3;
					$this->logging_debug("Ключ: " . $keyAPI . ', счетчик взведен: ' . $this->APIYandexCount[$keyAPI]['val'] );
				}

				$currentTime = date('Y.m.d H:i:s', time());
				$previousTime = date('Y.m.d H:i:s', $this->action_start_real);

				$this->SuccessfullAtempt					= 'N';
				$this->SuccessfullAtemptWeekend		= 'N';

				$this->logging_debug("New day detected! Previous activity time: " . $previousTime . ', new activity time: ' . $currentTime);
			} else
				$this->is_new_day = false;
		} else {
      $this->logging_debug("Новый день уже открыт.");
    }

	}



	public function  AgentStart()
	{
    $this->action_active	= 'Y';
    $this->action_start		= $this->action_start_prev;

    $this->CheckAndSetNewDay();

    $this->action_start_real = time();	// ПОЛУЧАЕМ РЕАЛЬНОЕ ВРЕМЯ СТАРТА ДЛЯ ФИКСАЦИИ ЕГО В БАЗЕ ДАННЫХ СРАЗУ ПРИ СТАРТЕ !!!

    $this->SetOptions();

    $this->action_start	= time();

    if( $this->showStartedTime )
      $this->logging_debug( "Agent started: " . $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->action_start, 'd.m.Y H:i:s' ) );
    else
      $this->logging_debug( "Agent started." );
  }


	public function  AgentFinish()
	{

		$this->action_active	= 'N';

		$this->action_finish	= time();
		$this->action_time_last	= $this->action_finish - $this->action_start;

		if( $this->action_time_last > $this->action_time_max || $this->is_new_day ) {

			$this->action_time_max	= $this->action_time_last;
			$this->time_time_max	= time();
		}

		$this->SetOptions();

		if( $this->is_new_day ) {
			$this->CutFileLog(
			  $this->fullNameLog,
        $this->cute_identifier,
        $this->log_date_format,
        $this->num_days_cut,
        time(),
        $this->logFileSizeLimit,
        $this->cuteBeModule,
        $this->oldLogEnable );
		}

    $this->logging_debug( '' );
		$this->logging_debug( "Agent finished: " . date( "Y.m.d H:i:s", $this->action_finish ) );
	}





	public function Make_Time( $time, $date, $log_enable = true ) //	time: hh:mm:ss, date: dd.mm.yyyy
	//
	//	time: hh:mm:ss
	//	date: dd.mm.yyyy
	//
	{
	$hours    = intval( mb_substr( $time, 0, 2 ) );
	$minutes  = intval( mb_substr( $time, 3, 2 ) );
	$seconds  = intval( mb_substr( $time, 6, 2 ) );
	$day      = intval( mb_substr( $date, 0, 2 ) );
	$month    = intval( mb_substr( $date, 3, 2 ) );
	$year     = intval( mb_substr( $date, 6, 4 ) );

	if( $log_enable )
		$this->logging_debug( "hours: $hours, minutes: $minutes, seconds: $seconds, day: $day, month: $month, year: $year" );

	$mt = mktime( $hours, $minutes, $seconds, $month, $day, $year );

	return $mt;
	}


	public function SetSuccessfullAtempt() {

				if( $this->IsWeekEnd() ) {
					$this->SuccessfullAtemptWeekend	= 'Y';
				} else {
					$this->SuccessfullAtempt				= 'Y';
				}
	}

	public function IsSuccessfullAtempt() {

    $result = false;

		if( $this->IsWeekEnd() ) {
			  if( $this->SuccessfullAtemptWeekend	== 'Y' ) {
			  		$result	=	true;
				}

		} else {
			  if( $this->SuccessfullAtempt				== 'Y' ) {
						$result	=	true;
				}
		}

		return( $result );
	}



	public function  ConvertArrayToListStringByDelimiters( $box, $delimiter = ':' )
	//
	// Преобразование массива вида: $BOX[ FORM_ID ] = VALUE к массиву строк вида: FORM_ID : VALUE
	//
	{
        $result = [];
//      $this->logging_debug( "Box:" );
//      $this->logging_debug( $box );

        foreach( $box as $key => $val ) {

            $result [] = $key . $delimiter . $val;
        }

        return( $result );
	}



	public function PrepareCountBoxToListStringByDelimiters( $box, $delimiter = ':' )
	//
	// Преобразование массива вида: $BOX[ GEO_API_KEY ] = [ 'max' => COUNT_MAX, 'val' => COUNT_VALUE ]
  // к массиву строк вида: COUNT_MAX : COUNT_VALUE
	//
	{
		$result = [];
//      $this->logging_debug( "Box:" );
//      $this->logging_debug( $box );
    if( !empty( $box ) ) {
      foreach ($box as $key => $item) {

        if( empty( $item ) || empty( $item['max'] ) || empty( $item['val'] ) )
          continue;

        $result [] = $item['max'] . $delimiter . $item['val'];
      }
    }

		return( $result );
	}



	public function  ActionIsActive()
	{
        $check_reset = 0;
                                                                                //  $this->action_start_prev
		$result	= $this->AgentCheckReset(
		  $this->action_active,
      $this->action_start,
      $this->action_start_real,
      $this->time_active_reset,
      $check_reset );

		if( $result )
			$this->action_active = 'Y';
		else
			$this->action_active = 'N';

		return( $result );
	}

    public function  AgentCheckReset( $flag_active, $var_action_start, $var_action_start_prev, $var_time_reset, & $check_reset )
    //
    // универсальная проверка для внешнего использования
    //
    {
        $result		= $flag_active == 'Y' ? true : false;

        if( $result ) {

            $dt_curr				= time();
            $dt_prev				= $var_action_start_prev;

            $d_curr					= date( 'Y.m.d', $dt_curr );
            $d_prev					= date( 'Y.m.d', $dt_prev );

            if( $d_curr != $d_prev )
                $curr_day_is_new	= true;
            else
                $curr_day_is_new	= false;

            if( $curr_day_is_new ) {

                $check_reset	= round( ( $var_action_start - $dt_prev )/60 );                         // прошло минут с последнего успешного старта агента

            } else {

                $check_reset	= round( ( $var_action_start - $dt_prev )/60 );                         // прошло минут с последнего успешного старта агента
            }

//          $this->logging_debug( "                     check_reset: " . $check_reset );
//          $this->logging_debug( "                  var_time_reset: " . $var_time_reset );

            if( $check_reset - $var_time_reset > 0 ) {                                                  // если прошло больше минут, чем указанное время для сброса флага активности,
                                                                                                        // то значит было аварийное выключение - сбрасываем флаг активности !!!
                $result		= false;
            }

        }

        return( $result );
    }

	public function  SetOptionsFromArray( $opts )
	{
	}






	public function GetAgentsOptions()
	//
	//	Читает все опции всех агентов в ассоциативный массив,
	//  в качестве ключа по которому размещается массив с опциями является код элемента опций.
	//  Метод возвращает этот массив.
	//
	{

		$result	= [];


		return( $result );
	}





  public function GetOptions( $elementCode )
	{
    $result = [];
    $data = [];
    $sort = 'field_sorting';
    $sortDirection = 'ASC';

//  $this->logging_debug( "Загрузка настроек агента с кодом: " . $elementCode );

	  try {

      $nids = \Drupal::entityQuery('node')->accessCheck(FALSE)
        ->condition('status', $this->statusPublished)
        ->condition('type', 'AgentOptions')
        ->condition('field_code100', $elementCode )
        ->sort($sort, $sortDirection)
        ->execute();

//    $this->logging_debug( "Выполнен entityQuery. Получен nids:" );
//    $this->logging_debug( $nids );

      if( !empty( $nids ) ) {
        $data = \Drupal\node\Entity\Node::loadMultiple($nids);
      } else {
        $this->logging_debug("Результат entityQuery - пустой.");
      }

      if( !empty( $data ) ) {
        //
        // node id в дебильном формате, да еще и в массиве не смотря на то что он один!
        //
        $nidsBox = $nids;
        $this->nidModuleOptions = intval(array_shift( $nidsBox ));

//      $this->logging_debug( '' );
//      $this->logging_debug( "nidModuleOptions: " . $this->nidModuleOptions );

//      $this->logging_debug( "Получили данные в объект data." );
//      $this->logging_debug( $data );

        foreach ($data as $node) {
          /*
          $this->logging_debug( "" );
          $this->logging_debug( "node TimeStart:" );
          $this->logging_debug( $node->field_code->value );  // bundle: agentoptions
          $this->logging_debug( "" );
          $this->logging_debug( 'Node ID: ' . $node->id() );
          $this->logging_debug( "" );
          $this->logging_debug( 'Node label: ' . $node->label() );
          $this->logging_debug( "" );
          $this->logging_debug( 'Node boundle: ' . $node->bundle() );
          */
          $resultSet = $node->toArray();

//        $this->logging_debug('');
//        $this->logging_debug('resultset:');
//        $this->logging_debug($resultSet);

          $opts = $this->GetOneAgentOptions($resultSet, $sort);

          $this->id_options = $opts['id_options'];
          $this->agent_sort = $opts['agent_sort'];
          $this->time_start = $opts['time_start'];
          $this->intervalForce = $opts['intervalForce'];
          $this->intervalNorm = $opts['intervalNorm'];
          $this->intervalWeekend = $opts['intervalWeekend'];
          $this->time_finish = $opts['time_finish'];
          $this->week_is_work = $opts['week_is_work'];              // "Y" / "N"
          $this->max_number_retry = $opts['max_number_retry'];
          $this->week_time_start = $opts['week_time_start'];
          $this->week_time_finish = $opts['week_time_finish'];
          $this->week_max_number_retry = $opts['week_max_number_retry'];
          $this->week_emulation = $opts['week_emulation'];            // "Y" / "N"
          $this->deactivation = $opts['deactivation'];              // "Y" / "N"
          $this->forms_id = $opts['forms_id'];                    // 133,123,93
          $this->checkFormListId = $opts['check_form_list_id'];
          $this->action_start = $opts['action_start'];
          $this->action_start_prev = $opts['action_start_prev'];        // возвращает тоже что и PHP функция - time();
          $this->action_start_real = $opts['action_start_real'];
          $this->action_finish = $opts['action_finish'];              // возвращает тоже что и PHP функция - time();
          $this->next_start = $opts['next_start'];
          $this->action_time_last = $opts['action_time_last'];
          $this->action_time_max = $opts['action_time_max'];
          $this->time_time_max = $opts['time_time_max'];              // возвращает тоже что и PHP функция - time();
          $this->action_active = $opts['action_active'];
          $this->id_processed = $opts['id_processed'];
          $this->request_sleep = $opts['request_sleep'];
          $this->time_active_reset = $opts['time_active_reset'];
          $this->agentuserlogin = $opts['agentuserlogin'];
          $this->agent_user_id = $opts['agent_user_id'];
          $this->agent_start_date = $opts['agent_start_date'];
          $this->agent_finish_date = $opts['agent_finish_date'];
          $this->npp = $opts['npp'];
          $this->socks_proxy = $opts['socks_proxy'];
          $this->type_proxy = $opts['type_proxy'];                  // ПОЛУЧИЛИ ТИП ПРОКСИ (номер)
          $this->force_agent = $opts['force_agent'];
          $this->pack_size = $opts['pack_size'];
          $this->APIYandexKey = $opts['apiyandexkey'];
          $this->APIYandexCount = $opts['yandex_geo_api_counts'];
          $this->triggerFlag = $opts['trigger_flag'];
          $this->timeLastMailSend = $opts['last_mail_send'];
          $this->StopAfterSuccessfullAtempt = $opts['stop_after_successfull_atempt'];
          $this->SuccessfullAtempt = $opts['successfull_atempt'];
          $this->SuccessfullAtemptWeekend = $opts['successfull_atempt_weekend'];
          //
//        $this->logging_debug('');
//        $this->logging_debug( 'Массив настроек:' );
//        $this->logging_debug( $opts );
        }
      } else {
        $this->logging_debug( "Объект data - пустой." );
      }

    } catch( \Error $e ) {

      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'GetOptions Error:' );
      $this->logging_debug( $result );
    } catch( \Exception $e ) {

      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'GetOptions Exception:' );
      $this->logging_debug( $result );
    } finally {
//    $this->logging_debug('');
//    $this->logging_debug( 'Метод GetOptions завершен.' );
    }

    return( $result );
	}

  public function GetOneAgentOptions( $resultSet, $sort )
  //
  // Метод возвращает в виде массива готовые к использованию опции текущего элемента инфоблока агентов
  //
  {
    $result		= [];
    date_default_timezone_set('Europe/Kiev');

    try {
      $varActionStartPrev = ( empty($resultSet['field_actionstart']) || empty($resultSet['field_actionstart'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_actionstart'][0]['value']);  // поле ACTION_START (Время (timestamp) старта обработки агентом)

      $varActionStartReal = ( empty($resultSet['field_actionstartreal']) || empty($resultSet['field_actionstartreal'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_actionstartreal'][0]['value']); // Реальное время старта агента

      $varActionFinish = ( empty($resultSet['field_actionfinish']) || empty($resultSet['field_actionfinish'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_actionfinish'][0]['value']); // Время окончания обработки агентом (timestamp)

      $varNextStart = ( empty($resultSet['field_nextstart']) || empty($resultSet['field_nextstart'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_nextstart'][0]['value']);    // Следующее время старта обработки агентом (timestamp)

      $varTimeTimeMax = ( empty($resultSet['field_timetimemax']) || empty($resultSet['field_timetimemax'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_timetimemax'][0]['value']); // Время пиковой нагрузки (timestamp)

      $varAgentStartDate = ( empty($resultSet['field_agentstartdate']) || empty($resultSet['field_agentstartdate'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_agentstartdate'][0]['value']); // Дата начала обработки (timestamp)

      $varAgentFinishDate = ( empty($resultSet['field_agentfinishdate']) || empty($resultSet['field_agentfinishdate'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_agentfinishdate'][0]['value']); // Дата окончания обработки (timestamp)

      $varLastMailSend = ( empty($resultSet['field_lastmailsend']) || empty($resultSet['field_lastmailsend'][0]['value']) )
        ? 0
        : $this->dateHandle->TimeStampFromStringDateTime($resultSet['field_lastmailsend'][0]['value']); // Время последней отправки сообщения (timestamp)


      $result = [
        'title' => empty($resultSet['title'][0]['value']) ? '' : $resultSet['title'][0]['value'],
        'created' => date('d-m-Y', $resultSet['created'][0]['value']),
        'id_options' => $resultSet['nid'][0]['value'],         // id записи
        'agent_sort' => $resultSet[$sort][0]['value'],       // значение поля сортировки
        "time_start" => $resultSet['field_code'][0]['value'],  // Время начала активности
        "intervalForce" => $resultSet['field_consttermstep'][0]['value'], // периодичность запуска агента (сек) в рабочие дни
        "intervalNorm" => $resultSet['field_interval'][0]['value'], // периодичность суточной активности агента  (в секундах, но кратно дням)
        "intervalWeekend" => $resultSet['field_addonimagepercent'][0]['value'], // периодичность запуска агента (сек) в выходные
        "time_finish" => $resultSet['field_codecontenttype'][0]['value'],  // Время окончания активности
        "week_is_work" => empty($resultSet['field_paramsflip'][0]['value']) ? 'N' : 'Y',  // Работа в выходные дни ( Y / N )
        "max_number_retry" => $resultSet['field_groupsorting'][0]['value'],  // Счетчик попыток для каждой итерации
        "week_time_start" => $resultSet['field_timestartweekend'][0]['value'],  // Выходные - время начала активности
        "week_time_finish" => $resultSet['field_timefinishweekend'][0]['value'], // Выходные - время окончания активности
        "week_max_number_retry" => $resultSet['field_employmentterm'][0]['value'], // Выходные - счетчик попыток для каждой итерации
        "week_emulation" => empty($resultSet['field_paramsdebug'][0]['value']) ? 'N' : 'Y', // Эмуляция выходного дня ( Y / N )
        "deactivation" => empty($resultSet['field_bigblock'][0]['value']) ? 'N' : 'Y', // Подавить активность агента ( Y / N )
        "forms_id" => $resultSet['field_formsid'],
        'check_form_list_id' => $resultSet['field_checkformlistid'],
        "action_start" => time(), // Время старта обработки агентом
        //
        // стандартный функционал чтения данных видимо уже перевел время по часовому поясу!
        //
        "action_start_prev" => $varActionStartPrev,  // поле ACTION_START (Время (timestamp) старта обработки агентом)
        'action_start_real' => $varActionStartReal, // Реальное время старта агента
        "action_finish" => $varActionFinish, // Время окончания обработки агентом (timestamp)
        "next_start" => $varNextStart,    // Следующее время старта обработки агентом (timestamp)

        "action_time_last" => empty($resultSet['field_percentcasco']) ? 0 : $resultSet['field_percentcasco'][0]['value'], // Длительность последней обработки, сек
        "action_time_max" => empty($resultSet['field_actiontimemax']) ? 0 : $resultSet['field_actiontimemax'][0]['value'], // Максимальная длительность обработки за сутки, сек

        "time_time_max" => $varTimeTimeMax, // Время пиковой нагрузки (timestamp)

        "action_active" => empty($resultSet['field_showaction'][0]['value']) ? 'N' : 'Y', // Флаг выполнения агента (Y/N)
        "id_processed" => empty($resultSet['field_idprocessed']) ? 0 : $resultSet['field_idprocessed'][0]['value'], // какой-то Обрабатываемый ID (временное хранение)
        "request_sleep" => $resultSet['field_requestsleep'][0]['value'] ?? 0, // Задержка повторения запроса в секундах
        "time_active_reset" => $resultSet['field_timeactivereset'][0]['value'] ?? 60, // Время сброса флага активности (минуты)
        "agentuserlogin" => $resultSet['field_agentuserlogin'][0]['value'], // Логин админа для агента
        "agent_user_id" => 0,

        "agent_start_date" => $varAgentStartDate, // Дата начала обработки (timestamp)
        "agent_finish_date" => $varAgentFinishDate, // Дата окончания обработки (timestamp)

        "npp" => empty($resultSet['field_numbercode']) ? 0 : $resultSet['field_numbercode'][0]['value'],  // Какой-то номер по порядку
        "socks_proxy" => $resultSet['field_socksproxy'] ?? '',  // список прокси серверов: IP:port, IP:port, IP:port ...
        "type_proxy" => $resultSet['field_typeproxy'][0]['value'], // Тип прокси который выбран из приведенного списка серверов
        "force_agent" => empty($resultSet['field_showhtmlthree'][0]['value']) ? 'N' : 'Y', // Форсировать запуск агента (Y/N)
        'pack_size' => $resultSet['field_age'][0]['value'] ?? 1, // Размер пачки
        'apiyandexkey' => $resultSet['field_yandexgeoapikey'] ?? '',   // список Yandex GEO API KEY
        'yandex_geo_api_counts' => $resultSet['field_yandexgeoapicounts'] ?? 0, // список счетчиков YANDEX GEO API: MAX:CURRENT
        'trigger_flag' => $resultSet['field_triggerflag'][0]['value'], // Триггер флаг (0/1)

        'last_mail_send' => $varLastMailSend, // Время последней отправки сообщения (timestamp)

        'stop_after_successfull_atempt' => empty($resultSet['field_paramsfilterbottom'][0]['value']) ? 'N' : 'Y', // Остановить АГЕНТА после успешной попытки (Y/N)
        'successfull_atempt' => empty($resultSet['field_showgetting'][0]['value']) ? 'N' : 'Y', // Флаг остановки АГЕНТА после успешной попытки (Y/N)
        'successfull_atempt_weekend' => empty($resultSet['field_showhtmlone'][0]['value']) ? 'N' : 'Y'// Флаг остановки АГЕНТА после успешной попытки в выходные (Y/N)
      ];
    } catch( \Error $e ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'GetOneAgentOptions Error:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      throw $e;
    } catch( \Exception $e ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'GetOneAgentOptions Exception:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      throw $e;
    }

    return( $result );
  }


  public function  SetOptions()
  {
    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load( $this->nidModuleOptions );

      $node->field_showaction->value = $this->action_active == 'Y' ? true : false;  // ActionActive
      $node->field_actionstart->value = $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->action_start ); // ActionStart
      $node->field_actionstartreal->value = $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->action_start_real ); // ActionStartReal
      $node->field_actionfinish->value = $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->action_finish ); // ActionFinish
      $node->field_nextstart->value = $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->next_start ); // NextStart
      $node->field_lastmailsend->value = $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->timeLastMailSend ); // LastMailSend
      $node->field_yandexgeoapicounts->value = $this->PrepareCountBoxToListStringByDelimiters( $this->APIYandexCount, ':' ); // YandexGeoApiCounts
      $node->field_triggerflag->value = $this->triggerFlag; // TriggerFlag
      $node->field_idprocessed->value = $this->id_processed; // IdProcessed
      $node->field_timetimemax->value = $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->time_time_max ); // TimeTimeMax
      $node->field_actiontimemax->value = $this->action_time_max; // ActionTimeMax
      $node->field_percentcasco->value = $this->action_time_last; // ActionTimeLast

      $node->save();

//    $this->logging_debug( '' );
//    $this->logging_debug( 'Опции агента сохранены в Б.Д.' );

    } catch( \Error ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'SetOptions Error:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      throw $e;
    } catch( \Exception ) {
      $result = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'SetOptions Exception:' );
      $this->logging_debug( $result );
      $this->loggingBackTrace();
      throw $e;
    }

  }



	public function ChangeNormal()
		//
		//
    //
	{
  	$this->ChangeForce();
	}


	public function ChangeForce()
	//
	// здесь проверяется время, и если оно достигло time finish, то следующий старт активируется через $this->intervalNorm
  // ( обычно это 86400 т.е. сутки )
	// и берется время старта из настроек.
	//
	// добавилось 2 аргумента:  $module_agent_id, $module_agent_name
	//
	{
		if( empty( $current_agent_sort ) )
			$current_agent_sort		= $this->agent_sort;

		$this->workingCikleFinish 	= $this->IsFinishCicle( $this->useDateCikle );

		if( $this->workingCikleFinish ) {
			$interval		= $this->intervalNorm;
		} else {
			$interval		= 0;
		}

		$currtime 			= time();

//	$this->logging_debug( 'ChangeForce start!' );

		if( $this->IsWeekEnd() ) {

			if( $this->week_time_finish != "" && $this->week_time_start != "" ) {

				$this->GetStartDateTime( 	$this->week_time_start,
											$this->week_time_finish,
											$interval,
											"d.m.Y H:i:s",
											$startTimeStamp );	// получение ТЕКУЩЕЙ даты и времени старта

				$currdate			= $this->GetFinishDate( $this->week_time_start, $this->week_time_finish );
				$finishTimeStamp	= $this->Make_Time( $this->week_time_finish, $currdate, false );

//				$this->logging_debug( "Finish  time: " . date( "d.m.Y H:i:s", $finishTimeStamp ) );

			} else {
				$startTimeStamp		= time() - 100;		// текущее время + смещение отладки - 100 секунд!
				$finishTimeStamp	= time() + 100;		// текущее время + смещение отладки + 100 секунд!
			}

		} else {

			if( $this->time_finish != "" && $this->time_start != "" ) {

				$this->GetStartDateTime(	$this->time_start,
											$this->time_finish,
											$interval,
											"d.m.Y H:i:s",
											$startTimeStamp );	// получение ТЕКУЩЕЙ даты и времени старта

				$currdate			= $this->GetFinishDate( $this->time_start, $this->time_finish );
				$finishTimeStamp	= $this->Make_Time( $this->time_finish, $currdate, false );

//				$this->logging_debug( "Finish  time: " . date( "d.m.Y H:i:s", $finishTimeStamp ) );

			} else {
				$startTimeStamp    = time() - 100;		// текущее время + смещение отладки - 100 секунд!
				$finishTimeStamp   = time() + 100;		// текущее время + смещение отладки + 100 секунд!
			}
		}

  	$this->logging_debug('Finish: ' . date("d.m.Y H:i:s", $finishTimeStamp) . ', Finish date: ' . $currdate);

		$msgOffset	= 'Текущее время: ' . date( "d.m.Y H:i:s", $currtime  );
		$this->logging_debug( $msgOffset );

		if( $startTimeStamp - $finishTimeStamp < 0 &&	// $finish > $start
			$finishTimeStamp - $currtime > 0 &&
			$startTimeStamp - $currtime < 0 ) {

			$this->logging_debug( 'Финиш еще не наступил! Идет рабочий цикл. finish: ' . date("d.m.Y H:i:s", $finishTimeStamp ) . ' > start: ' . date("d.m.Y H:i:s", $startTimeStamp ) );

			if( $this->IsWeekEnd() ) {
				$next_exec = date( "d.m.Y H:i:s", time() + $this->intervalWeekend );		// здесь прибавлять смещение не нужно!!!
				$this->SetAgentNextTimeStart( $next_exec );
			} else {
				$next_exec = date( "d.m.Y H:i:s", time() + $this->intervalForce );			// здесь прибавлять смещение не нужно!!!
				$this->SetAgentNextTimeStart( $next_exec );
			}
		} elseif (  $startTimeStamp - $finishTimeStamp > 0 &&	// $finish < $start
					$finishTimeStamp - $currtime > 0 &&
					$startTimeStamp - $currtime > 0  ) {

			$this->logging_debug( 'Финиш еще не наступил! Идет рабочий цикл. finish: ' . date("d.m.Y H:i:s", $finishTimeStamp ) . ' < start: ' . date("d.m.Y H:i:s", $startTimeStamp ) );

			if( $this->IsWeekEnd() ) {
				$next_exec = date( "d.m.Y H:i:s", time() + $this->intervalWeekend );		// здесь прибавлять смещение не нужно!!!
				$this->SetAgentNextTimeStart( $next_exec );
			} else {
				$next_exec = date( "d.m.Y H:i:s", time() + $this->intervalForce );			// здесь прибавлять смещение не нужно!!!
				$this->SetAgentNextTimeStart( $next_exec );
			}

		} else {

			$this->logging_debug( 'Финиш уже наступил! Устанавливаем следующее время старта.' );

			$this->SetNextStart( $module_agent_id, $module_agent_name );
		}

//		$this->logging_debug( 'ChangeForce finish!' );
	}



  public function SetAgentNextTimeStart( $nextDateStart )
    //
    // бывший метод: AgentChangeInterval
    // $nextDateStart - строка даты в формате:  "d.m.Y H:i:s"
    // Установить следующее время старта с реальной записью в базу данных в инфоблок: agents, элемент: $this->optionElementCode
    //
    //  https://www.colinbusby.com/posts/entitytypemanager-querying-nodes
    //  https://gorannikolovski.com/blog/set-date-field-programmatically
    //
  {
    $result = true;

    try{
      $this->next_start   = $this->dateHandle->CreateFromPopularDetectFormat( $nextDateStart, $detectedFormat );
      $nextStringDate     = $this->dateHandle->TimeStampToStringForDrupalDataBase( $this->next_start );

//    $this->logging_debug( '' );
//    $this->logging_debug( 'Следующий запуск агента будет: ' . $nextDateStart . ", определен формат даты: " . $detectedFormat );
//    $this->logging_debug( 'Проверка преобразования      : ' . date( "d.m.Y H:i:s", $this->next_start ) . ", формат даты: d.m.Y H:i:s" );
      $this->logging_debug( 'Следующий запуск агента будет: ' . $nextDateStart );

//    $this->logging_debug( 'nidModuleOptions:' );
//    $this->logging_debug( $this->nidModuleOptions );

      $node = \Drupal::entityTypeManager()->getStorage('node')->load( $this->nidModuleOptions );
//    $this->logging_debug( '' );
//    $this->logging_debug( 'Next string date (UTC): ' . $nextStringDate );
//    $this->logging_debug( '' );
//    $this->logging_debug( 'Node:' );
//    $this->logging_debug( $node->toArray() );

      $node->field_nextstart->value = $nextStringDate;

      $node->save();

    } catch ( \Error $e ) {
      $boxError = ['errorCode' => $e->getCode(), 'errorMessage' => $e->getMessage()];
      $this->logging_debug( '' );
      $this->logging_debug( 'SetAgentNextTimeStart Error:' );
      $this->logging_debug( $boxError );
      $this->loggingBackTrace();
      throw $e;
    }


    //
    return( $result );
  }





  public function SetNextStart( $module_agent_id, $module_agent_name, $current_agent_sort = false )
		//
		// активируется следующий старт через $this->intervalNorm ( обычно это 86400 т.е. сутки )
		// время старта берется из настроек. И переустанавливаются счетчики контрольных выстрелов на максимум.
		//
	{
		if( empty( $current_agent_sort ) )
			$current_agent_sort		= $this->agent_sort;

		$this->workingCikleFinish 	= $this->IsFinishCicle( $this->useDateCikle );

		if( $this->IsWeekEnd() ) {

			if( $this->workingCikleFinish ) {
				//
				$nextDate	= $this->GetNextStartDateTime( $this->week_time_start, $this->week_time_finish, $this->intervalNorm, "d.m.Y H:i:s" );
				$this->logging_debug( 'по штатному алгоритму, устанавливаем следующую дату старта через заданный интервал:  ' .  $nextDate );
			} else {
				//
				$nextDate	= $this->GetNextStartDateTime( $this->week_time_start, $this->week_time_finish, 0, "d.m.Y H:i:s" );
				$this->logging_debug( 'устанавливаем следующее время старта:  ' .  $nextDate );
			}

		} else {
			if( $this->workingCikleFinish ) {
				//
				$nextDate	= $this->GetNextStartDateTime( $this->time_start, $this->time_finish, $this->intervalNorm, "d.m.Y H:i:s" );
				$this->logging_debug( 'по штатному алгоритму, устанавливаем следующую дату старта ' . $nextDate . ' через заданный интервал:  ' . $this->intervalNorm );
			} else {
				//
				$nextDate	= $this->GetNextStartDateTime( $this->time_start, $this->time_finish, 0, "d.m.Y H:i:s" );
				$this->logging_debug( 'устанавливаем следующее время старта:  ' .  $nextDate );
			}
		}

		$this->SetAgentNextTimeStart( $nextDate ); // 86400

	}



	public function IsFinishCicle( & $use=true )
		//
		//		возвращаем TRUE, если цикл завершен либо если ошибка в указании дат или логическая ошибка.
		//		возвращаем FALSE, если цикл еще не завершен.
		//
	{

		$result	= $this->IsFinishCicleUniversale( $this->agent_start_date, $this->agent_finish_date, false, $use );

		return( $result );
	}



	public function GetStartDateTime( $start, $finish, $interval, $stringDateFormat="d.m.Y H:i:s", &$startTimeStamp=0 )
		//
		// $start		- hh:mm:ss
		// $finish		- hh:mm:ss
		// $interval    - интервал повторения работы агента, обфчно - сутки, т.е. 86400
		//
		// возвращает текущее (ПОСЛЕДНЕЕ) реальное время старта в виде строки формата "d.m.Y H:i:s"
		// (с учетом перехода через полночь)
		//
	{
		date_default_timezone_set('Europe/Kiev');

		$startTime	= $this->dateHandle->StringTimeToTime( $start );
		$finishTime = $this->dateHandle->StringTimeToTime( $finish );

		$unixDateTime	= time();	// полная текущая дата со временем
		$currentDate	= 0;
		$currentTime	= $this->dateHandle->OnlyTime( $unixDateTime, $currentDate );	// текущее время (возможно смещенное)
		//
		// $currentDate - содержит текущую дату (возможно смещенную!) в секундах (Unixtime)
		//
		$controlDate	= 0;
		$this->dateHandle->OnlyTime( time(), $controlDate );	// получим текущую дату без смещения в $controlDate (Unixtime)
		//
//		$this->logging_debug('currentDate: ' . date( "d.m.Y H:i:s", $currentDate ) );
//		$this->logging_debug('controlDate: ' . date( "d.m.Y H:i:s", $controlDate ) );
		//
		$this->logging_debug( 'GetStartDateTime, start: ' . date( "H:i:s", $startTime) . ', finish: ' . date( "H:i:s", $finishTime ) . ', текущее: ' . date( "H:i:s", $currentTime ) );

		if( $startTime - $finishTime > 0 ) {		// чистое время без даты!
			//
			// ( обычно НОЧЬ например:  с 21:00:00 до 06:00:00 )
			//
			if( $currentTime > $startTime ) {	// до 24 (00:00) часов ночи ( дата еще не инкременировалась )

				$result	= $this->dateHandle->DateGlueTime( $currentDate, $startTime, $stringDateFormat );
				$this->logging_debug( 'ПЕРЕХОД ЧЕРЕЗ НОЧЬ - ФИНИШ НА СЛЕДУЮЩИЙ ДЕНЬ. Текущее время старта: ' . $result );
			} else {							// после 24 (00:00) часов ночи ( дата инкременировалась! )

				$result	= $this->dateHandle->DateGlueTime( $currentDate - $interval, $startTime, $stringDateFormat );
				$this->logging_debug( 'СЛЕДУЮЩИЙ ДЕНЬ УЖЕ НАСТУПИЛ. Текущее время старта: ' . $result );
			}

		} else {
			//
			// ( обычно ДЕНЬ например: с 01:00:00 до 23:00:00 )
			// $startTime  <  $finishTime
			//
      if($currentTime > $finishTime && $currentTime > $startTime) {
        //
        // МОМЕНТ ПОСЛЕДНЕГО ЗАПУСКА МОДУЛЯ - когда устанавливается следующее время старта !
        // ( например в 23:00:10 ) т.е. до полуночи, до 24:00:00 (00:00:00)
        // Дата еще не перещелкнулась.
        //
        $result = $this->dateHandle->DateGlueTime($currentDate, $startTime, $stringDateFormat);
        $this->logging_debug('Без смещения. МОМЕНТ ПОСЛЕДНЕГО ЗАПУСКА МОДУЛЯ. Текущее время старта: ' . $result);
        //
      } elseif ( $currentTime < $finishTime && $currentTime > $startTime ) {
        //
        // Это время, когда агент активен и работает, старт уже произошел, а финиш еще не наступил!
        //
        $result = $this->dateHandle->DateGlueTime($currentDate, $startTime, $stringDateFormat);
        $this->logging_debug('Без смещения. ОБЫЧНЫЙ ДЕНЬ В ДЕНЬ И ФИНИШ ЕЩЕ НЕ НАСТУПИЛ. Текущее время старта: ' . $result);

      } else {
        //
        // теоретически это уже время простоя агента, когда оно
        // перевалило за полночь, т.е. после 24:00:00 (00:00:00)
        // Дата уже перещелкнулась - ее НАДО откатить. (вот этот момент никогда не наступит!)
        //
        $result = $this->dateHandle->DateGlueTime($currentDate - $interval, $startTime, $stringDateFormat);
        $this->logging_debug('Без смещения. ОБЫЧНЫЙ ДЕНЬ В ДЕНЬ. Текущее время старта: ' . $result);
      }
			//
			//
			//
		}

		$startTimeStamp	= $this->dateHandle->CreateFromFormat( $stringDateFormat, $result );

		return( $result );
	}



	public function GetNextStartDateTime( $start, $finish, $interval, $stringDateFormat="d.m.Y H:i:s", &$startTimeStamp=0 )
		//
		// $start		- hh:mm:ss
		// $finish		- hh:mm:ss
		// $interval    - интервал повторения работы агента, обфчно - сутки, т.е. 86400
		//
		// возвращает следующее реальное (НОВОЕ ИСКОМОЕ) время старта в виде строки формата "d.m.Y H:i:s"
		// (с учетом перехода через полночь)
		//
	{
		date_default_timezone_set('Europe/Kiev');

		$startTime	= $this->dateHandle->StringTimeToTime( $start );
		$finishTime = $this->dateHandle->StringTimeToTime( $finish );

		$unixDateTime	= time();	// полная текущая дата со временем
		$currentDate	= 0;
		$currentTime	= $this->dateHandle->OnlyTime( $unixDateTime, $currentDate );	// текущее время (возможно смещенное)
		//
		// $currentDate - содержит текущую дату (возможно смещенную!) в секундах (Unixtime)
		//
		$controlDate	= 0;
		$this->dateHandle->OnlyTime( time(), $controlDate );	// получим текущую дату без смещения в $controlDate (Unixtime)
		//
//		$this->logging_debug('currentDate: ' . date( "d.m.Y H:i:s", $currentDate ) );
//		$this->logging_debug('controlDate: ' . date( "d.m.Y H:i:s", $controlDate ) );
		//
		$this->logging_debug( 'GetNextStartDateTime, start: ' . date( "H:i:s", $startTime) . ', finish: ' . date( "H:i:s", $finishTime ) . ', текущее: ' . date( "H:i:s", $currentTime ) );

		if( $startTime - $finishTime > 0 ) {	// только время без даты
			//
			// ( обычно НОЧЬ например:  с 21:00:00 до 06:00:00 )
			//
			if( $currentTime > $startTime ) {	// до 24 (00:00) часов ночи ( дата еще не инкременировалась )

				$result	= $this->dateHandle->DateGlueTime( $currentDate + $interval, $startTime, $stringDateFormat );
				$this->logging_debug( 'GetNextStartDateTime, ПЕРЕХОД ЧЕРЕЗ НОЧЬ - ФИНИШ НА СЛЕДУЮЩИЙ ДЕНЬ, искомое время старта: ' . $result . ', interval: ' . $interval );
//			$this->loggingBackTrace();
			} else {							// после 24 (00:00) часов ночи ( дата инкременировалась! )

				$result	= $this->dateHandle->DateGlueTime( $currentDate, $startTime, $stringDateFormat );
				$this->logging_debug( 'GetNextStartDateTime, СЛЕДУЮЩИЙ ДЕНЬ УЖЕ НАСТУПИЛ, искомое время старта: ' . $result );
			}

		} else {
			//
			// ( обычно ДЕНЬ например: с 01:00:00 до 23:00:00 )
			// $startTime  <  $finishTime
			//
      if($currentTime > $finishTime && $currentTime > $startTime) {
        //
        // МОМЕНТ ПОСЛЕДНЕГО ЗАПУСКА МОДУЛЯ - когда устанавливается следующее время старта !
        // ( например в 23:00:10 ) т.е. до полуночи, до 24:00:00 (00:00:00)
        // Дата еще не перещелкнулась - ее надо инкрементировать.
        //
        if( $interval - 86400 < 0 )
          $interval	= 86400;	// в этой ситуации 100% переход через полночь, это + 24 часа!!!

        $result = $this->dateHandle->DateGlueTime($currentDate + $interval, $startTime, $stringDateFormat);
        $this->logging_debug('GetNextStartDateTime, Без смещения. ПЕРЕХОД ЧЕРЕЗ НОЧЬ - СТАРТ НА СЛЕДУЮЩИЙ ДЕНЬ, искомое время старта: ' . $result);

      } elseif ( $currentTime < $finishTime && $currentTime > $startTime ) {
        //
        // Это время, когда агент активен и работает, старт уже произошел, а финиш еще не наступил!
        //
        if( $this->useDateCikle )
            $this->logging_debug('GetNextStartDateTime, useDateCikle: true' );
        else
          $this->logging_debug('GetNextStartDateTime, useDateCikle: false' );

        if( $this->workingCikleFinish )
          $this->logging_debug('GetNextStartDateTime, workingCikleFinish: true' );
        else
          $this->logging_debug('GetNextStartDateTime, workingCikleFinish: false' );

        if( $this->useDateCikle && $this->workingCikleFinish ) {
          //
          // НУЖНО СДЕЛАТЬ ФИНИШ РАБОЧЕГО ЦИКЛА
          //
          $result = $this->dateHandle->DateGlueTime($currentDate + $interval, $startTime, $stringDateFormat);
          $this->logging_debug('GetNextStartDateTime, Без смещения. РАБОЧИЙ ЦИКЛ ЗАВЕРШИЛСЯ - ПЕРЕВОДИМ СЛЕДУЮЩИЙ СТАРТ НА ДАТУ СЛЕДУЮЩЕГО ЦИКЛА, искомое время старта: ' . $result);
        } else {
          //
          // ФИНИШ таки, еще не наступил
          //
          $result = $this->dateHandle->DateGlueTime($currentDate, $startTime, $stringDateFormat);
          $this->logging_debug('GetNextStartDateTime, Без смещения. ОБЫЧНЫЙ ДЕНЬ В ДЕНЬ И ФИНИШ ЕЩЕ НЕ НАСТУПИЛ, искомое время старта: ' . $result);
        }

      } else {
        //
        // теоретически это уже время простоя агента, когда оно
        // перевалило за полночь, т.е. после 24:00:00 (00:00:00)
        // Дата уже перещелкнулась - ее НЕ НАДО инкрементировать. (вот этот момент никогда не наступит!)
        //
        $result = $this->dateHandle->DateGlueTime($currentDate, $startTime, $stringDateFormat);
        $this->logging_debug('GetNextStartDateTime, Без смещения. ОБЫЧНЫЙ ДЕНЬ В ДЕНЬ, искомое время старта: ' . $result);
      }
			//
			//
			//
		}

		$startTimeStamp	= $this->dateHandle->CreateFromFormat( $stringDateFormat, $result );

		return( $result );
	}






	public function GetStartDate( $start, $finish, $interval, $stringDateFormat="d.m.Y", & $unixDate=0 )
		//
		// возвращает реальную дату старта в виде строки формата "d.m.Y" (с учетом перехода через полночь)
		//
	{
		date_default_timezone_set('Europe/Kiev');

		$startTime	= $this->dateHandle->StringTimeToTime( $start );
		$finishTime = $this->dateHandle->StringTimeToTime( $finish );

//		$this->logging_debug( 'GetStartDate, startTime: ' . date( "H:i:s", $startTime) . ', finishTime: ' . date( "H:i:s", $finishTime ) );

		$unixDateTime	= time();
		$currentDate	= 0;
		$currentTime	= $this->dateHandle->OnlyTime( $unixDateTime, $currentDate );

//		$this->logging_debug( 'GetStartDate, текущее время: ' . date( "H:i:s", $currentTime) );

		if( $startTime - $finishTime > 0 ) {
			//
			if( $currentTime > $startTime ) {

				$unixDate	= $currentDate + 86400 + $interval;
				$result		= date( $stringDateFormat, $unixDate );
//				$this->logging_debug( 'ПЕРЕХОД ЧЕРЕЗ НОЧЬ - ФИНИШ НА СЛЕДУЮЩИЙ ДЕНЬ, дата старта: ' . $result );
			} else {

				$unixDate	= $currentDate + $interval;
				$result	= date( $stringDateFormat, $unixDate );
//				$this->logging_debug( 'СЛЕДУЮЩИЙ ДЕНЬ УЖЕ НАСТУПИЛ, дата старта: ' . $result );
			}

		} else {
			//
			$unixDate	= $currentDate + 86400 + $interval;
			$result		= date( $stringDateFormat, $unixDate );
//			$this->logging_debug( 'ОБЧНЫЙ ДЕНЬ В ДЕНЬ, дата старта: ' . $result );
		}

		return( $result );
	}


	public function GetFinishDate( $start, $finish, $stringDateFormat="d.m.Y" )
		//
		// возвращает реальную финишную дату в виде строки формата "d.m.Y" (с учетом перехода через полночь)
		//
	{
		date_default_timezone_set('Europe/Kiev');

		$startTime	= $this->dateHandle->StringTimeToTime( $start );
		$finishTime = $this->dateHandle->StringTimeToTime( $finish );

//	$this->logging_debug( 'GetFinishDate, startTime: ' . $startTime . ', finishTime: ' . $finishTime );
//	$this->logging_debug( 'GetFinishDate, startTime: ' . date( "d.m.Y H:i:s", $startTime) . ', finishTime: ' . date( "d.m.Y H:i:s", $finishTime ) );

		$unixDateTime	= time();
		$currentDate	= 0;
		$currentTime	= $this->dateHandle->OnlyTime( $unixDateTime, $currentDate );
//	$this->logging_debug( 'GetFinishDate, текущее время: ' . $currentTime );
//	$this->logging_debug( 'GetFinishDate, текущее время: ' . date( "d.m.Y H:i:s", $currentTime) );

		if( $startTime - $finishTime > 0 ) {
			//
			if( $currentTime > $startTime ) {

				$result	= date( $stringDateFormat, $unixDateTime + 86400 );
//			$this->logging_debug( 'ПЕРЕХОД ЧЕРЕЗ НОЧЬ - ФИНИШ НА СЛЕДУЮЩИЙ ДЕНЬ, дата финиша: ' . $result );
			} else {

				$result	= date( $stringDateFormat, $unixDateTime );
//			$this->logging_debug( 'СЛЕДУЮЩИЙ ДЕНЬ УЖЕ НАСТУПИЛ, дата финиша: ' . $result );
			}

		} else {
			//
			$result	= date( $stringDateFormat, $unixDateTime );
//		$this->logging_debug( 'ОБЧНЫЙ ДЕНЬ В ДЕНЬ, дата финиша: ' . $result );
		}

		return( $result );
	}




	public function CheckActivityTime( $tstart, $week_tstart, $tfinish, $week_tfinish, $cnt = 0, $week_cnt = 0, $log_enable = true )
	//
	//	return: TRUE		- агент в рамках своего рабочего времени
	//	return: FALSE		- рабочее время агента завершено
	//
	{
		$result				= false;									// false - рабочий день агента завершен
		$counter			= false;

		$currtime			= time();
		$currdate			= date( "d.m.Y", $currtime );

		if( $this->IsWeekEnd() ) {
			//
			//	ВЫХОДНОЙ ДЕНЬ
			//
			$finishDate	= $this->GetFinishDate( $week_tstart, $week_tfinish );

			if( $week_cnt > 0 ) {															// выходной счетчик еще не обнулился
				$counter	= true;
			}

			if( $week_tstart != "" ) {														// 09:00
				$start		= $this->Make_Time( $week_tstart, $currdate, $log_enable );
			} else {
				$start		= $currtime - 100;
			}

			if( $week_tfinish != "" ) {														// 18:00
				$finish		= $this->Make_Time( $week_tfinish, $finishDate, $log_enable );
			} else {
				$finish		= $currtime + 100;
			}

		} else {
			//
			//	РАБОЧИЙ ДЕНЬ
			//
			$finishDate	= $this->GetFinishDate( $tstart, $tfinish );

			if( $cnt > 0 ) {																// счетчик еще не обнулился
				$counter	= true;
			}

			if( $tstart != "" ) {															// 08:00
				$start		= $this->Make_Time( $tstart, $currdate, $log_enable );
			} else {
				$start		= $currtime - 100;
			}

			if( $tfinish != "" ) {															// 19:00
				$finish		= $this->Make_Time( $tfinish, $finishDate, $log_enable );
			} else {
				$finish		= $currtime + 100;
			}
		}

		if( $finish - $currtime > 0 && $currtime - $start > 0 ) {
			//
			//	конец рабочего дня для агента еще не наступил
			//
			$result		= true;

		} else {
			if( $counter )
				$result	= true;
			else
				$result	= false;
		}

		return( $result );
	}





	public function IsActiveByDate(  & $asd, & $afd, $interval_work, $log_enable = true  )
	//
	//	Анализируются даты старта и финиша цикла обработки. так-же проверяется период повторения цикла обработки.
	//  если даты определены, то проверяется их корректность и завершился ли цикл обработки. если завершился, то возвращаем- false, иначе - true.
	//  бывает так, что даты не определены, но период повторения цикла обработки больше чем сутки, в этом случае лучше вернуть - false!
	//
	{
		$p_use				= false;													// учитывать период для определения режима работы по датам
		$d_use				= ( empty( $asd ) ) ? false : true;							// использовать даты старта и финиша для определения режима работы по датам

		if( ! $d_use ) {
			$p_use			= ($interval_work - 86400 > 0) ? true : false;				// период повторения может стоять больше суток, но дата старта и финиша неопределены
																						// (не нужны, агент отрабатывает быстрее чем проходят сутки)
																						// но при этом форсировать запуск нельзя. вернее не нужно - возвращаем false!
		}


		if( $p_use == false ) {

			if( $d_use )
				$is_finish		= $this->IsFinishCicleUniversale( $asd, $afd, $log_enable );
			else
				$is_finish		= false;												// цикл от даты старта до даты финиша - не используется, нужно вернуть - true

			$result			= ! $is_finish;												// если цикл от даты старта до даты финиша не закончился (false), то возвращаем - true

		} else {
				$result			= false;
		}

		return( $result );
	}





	public function IsFinishCicleUniversale( & $asd, & $afd, $log_enable = true, & $useDateCikle = true )
	//
	//      asd - 'YYYY.MM.DD' дата старта рабочего цикла
	//      afd - 'YYYY.MM.DD' дата окончания рабочего цикла
	//		возвращаем TRUE, если цикл завершен либо если ошибка в указании дат или логическая ошибка.
	//		возвращаем FALSE, если цикл еще не завершен.
	//
	{

		$isDouble	= false;
		$cd			= time();
		$acd 		= date( "Y.m.d", $cd );
		//
		//  этот блок нужен, чтобы в лог не сыпались сообщения при многократных вызовах
		//
		if( $this->agentStartDatePrev == $asd &&
			$this->agentFinishDatePrev  == $afd &&
			$this->agentCurrentDatePrev == $acd ) {

//		$this->logging_debug( 'IsFinishCicle: даты старта, финиша, и текущая не поменялись. старт: ' . $asd . ', финиш: ' . $afd . ', текущая дата: ' . $acd );
			$isDouble	= true;
		} else {
			$this->agentStartDatePrev 	= $asd;
			$this->agentFinishDatePrev  = $afd;
			$this->agentCurrentDatePrev = $acd;
		}
		//
		//
		//
		if( ! $asd && ! $afd ) {

			$useDateCikle	= false;

			if( $log_enable && ! $isDouble )
				$this->logging_debug( 'IsFinishCicle: обе даты не указаны - значит они не используются, возвращаем FINISH' );

			return( true );
		}

		$ds		= $this->IsDateValid( $asd, 'yyyy.mm.dd' );
		$df		= $this->IsDateValid( $afd, 'yyyy.mm.dd' );

//	$this->logging_debug( 'IsFinishCicle, start datetime: ' . $ds . ', finish datetime: ' . $df );

		if( ! $ds ) {

			$asd			= false;
			$afd			= false;
			$useDateCikle	= true;		// 2021.11.23

			if( $log_enable && ! $isDouble )
				$this->logging_debug( 'IsFinishCicle: дата старта не указана, а дата финиша указана - значит ошибка, возвращаем FINISH' );

			return( true );
		}

		if( $cd - $ds < 0 ) {

			$asd			= false;
			$afd			= false;
			$useDateCikle	= true;		// 2021.11.23

			if( $log_enable && ! $isDouble )
				$this->logging_debug( 'IsFinishCicle: дата старта больше текущей - значит ошибка, возвращаем FINISH.' );

			return( true );
		}

		if( $df && $cd - $df < 0 ) {

			$asd			= false;
			$afd			= false;
			$useDateCikle	= true;		// 2021.11.23

			if( $log_enable && ! $isDouble )
				$this->logging_debug( 'IsFinishCicle: дата финиша больше текущей - значит ошибка, возвращаем FINISH.' );


			return( true );
		}

		if( $df && $cd - $df >= 0 ) {

			$useDateCikle	= true;		// 2021.11.23

			if( $log_enable && ! $isDouble )
				$this->logging_debug( 'IsFinishCicle: дата финиша меньше либо равна текущей - цикл завершился, возвращаем FINISH' );

			return( true );
		}
		//
		//	если мы дошли до этого места, значит цикл еще не завершился - возвращаем FALSE.
		//
		$useDateCikle	= true;		// 2021.11.23

		if(  ! $isDouble )
		 	$this->logging_debug( 'IsFinishCicle: - цикл еще не завершился, возвращаем FALSE. старт: ' . $asd . ', финиш: ' . $afd . ', текущая дата: ' . date( "Y.m.d", $cd ) );

		return( false );
	}



	public function GetYandexAPIKey()
	//
	// Выбор ключа ГЕО АПИ у которого еще не закончился лимит на запросы
	//
	{
		$result 	= '';

		foreach ( $this->APIYandexKey as $keyAPI ) {

			if( $this->APIYandexCount[ $keyAPI ]['val'] > 0 ) {

				$result	= $keyAPI;
				$this->logging_debug( 'Выбран ГЕО АПИ ключ: ' . $keyAPI );
				break;
			}
		}

		if( empty( $result ) )
			$this->logging_debug( 'Лимиты всех ключей на сегодня использованы!' );

		return( $result );
	}





	public function SendMail( $message, $subject,
                            $mailTo, $mailFrom=false, $mailCc=false, $mailBcc = false,
                            $typeHtml=true, $charset='UTF-8',
                            $boxAttaches=[], $maxFileSize=3, $maxImagePixel=1920 )
	{
    $boxFiles = [];

	  if( ! empty( $boxAttaches ) ) {

      $boxFiles = [
        'common' => ['max_file_size' => $maxFileSize, 'max_image_pixel' => $maxImagePixel],
        'items' => []
      ];

      foreach ($boxAttaches as $file) {
        $boxFiles['items'][] = ['full_file_name' => $file];
      }

    }

	  if( empty( $mailFrom ) ) {
      $mailFrom = \Drupal::config('system.site')->get('mail');
    }

//  $this->logging_debug( '' );
//  $this->logging_debug( 'Mail message:' );
//  $this->logging_debug( $message );

		$result	= $this->mailHandle->SendMail(
		  $message, $subject,
			$mailTo, $mailFrom, $mailCc,$mailBcc,
      $typeHtml, $charset,
      $boxFiles );

//		$this->logging_debug( 'SendMail result:' );
//		$this->logging_debug( $result );

		return( $result );
	}



	public function SendAdminMessagePeriodically( $subject, $msgEmail, $force=false )
		//
		// Периодическая отправка сообщений админу ( раз в заданный период ), чтобы не валило кулем ...
		//
	{

		$resultSend             = false;
		$datetimeLastMailSend   = empty( $this->timeLastMailSend ) ? time() - 3600 * $this->periodAdminMessage : $this->timeLastMailSend;
		$secondPassed           = time() - $datetimeLastMailSend;
		$periodMailing          = 3600 * $this->periodAdminMessage;
		$secondsLeft            = $periodMailing - $secondPassed;

		if( $secondsLeft <= 0 || $force ) {
			//
			// используем e-mal для тестового сообщения
			//
			$localEmailTO     = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailTo' ];
			$localEmailCC     = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailCC' ];
			$localEmailBCC    = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailBCC' ];
			$localEmailFrom   = CommonDataBox::$boxEmail[ 'TestSending' ][ 'EmailFrom' ];

			$templateBox = [
				'EMAIL_FROM' => $localEmailFrom,
				'EMAIL_TO' => $localEmailTO,
				'CC' => $localEmailCC,
				'BCC' => $localEmailBCC
			];

			$resultSend = $this->SendMail(
        $msgEmail,
        $subject,
				$templateBox['EMAIL_TO'],
        $templateBox['EMAIL_FROM'],
				$templateBox['CC'],
				$templateBox['BCC']
			);

			if ($resultSend) {
				$this->timeLastMailSend  = time();
				$this->logging_debug('Почтовое сообщение отправлено на адреса администраторов:');
				$this->logging_debug($templateBox);
			}
		} else {

			$this->logging_debug( 'Время отправки почтового сообщения еще не наступило, ждем ' . $secondsLeft . ' секунд! Последняя отправка была: ' . date( "d-m-Y H:i:s", $datetimeLastMailSend ) );
			$this->logging_debug( $msgEmail );
		}

		return( $resultSend );
	}


  public function UserName( $id ) {
    $account = \Drupal\user\Entity\User::load( $id );
    $name = $account->getAccountName();

    return( $name );
  }

}
?>
