<?php

require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");
require_once(SQL_LIB_DIR . "util.func.php");

class Paydate_Handler
{
	private $pdc;
	private $model;
	private $exception_caught;
	private static $FREQUENCY_MAP = array("dw" => "weekly",
	"dwpd" => "bi_weekly",
	"dmdm" => "twice_monthly",
	"wwdw" => "twice_monthly",
	"dm" => "monthly",
	"wdw" => "monthly",
	"dwdm" => "monthly");

	private static $MODEL_MAP = array( 'day_of_week' => 'day_string_one',
	'day_of_month_1' => 'day_int_one',
	'day_of_month_2' => 'day_int_two',
	'week_1' => 'week_one',
	'week_2' => 'week_two',
	'last_paydate' => 'last_paydate');


	public function __construct()
	{
		$this->pdc = new Pay_Date_Calc_3(ECash::getFactory()->getReferenceList('Holiday')->toArray('holiday'));
		$this->exception_caught = FALSE;
	}

	public function Get_Model($app_row)
	{
		$app_row->model = array(
		'model_name' => $app_row->paydate_model,
		'frequency_name' => $app_row->income_frequency,
		'direct_deposit' => $app_row->income_direct_deposit
		);

		$this->Prepare_Model($app_row);

		$app_row->model['paydate'] = $this->Format_Wizard_Data($app_row);
		return $app_row->model;
	}

	function Get_Paydate_String($model)
	{
		if( $this->exception_caught !== FALSE )
		{
			return "<b>Error calculating pay dates:</b> {$this->exception_caught}";
		}
		
		//echo "<pre>" . To_String($model) . "</pre>";
		if( !empty($model["day_string_one"]) && $model["day_string_one"] == 32 )
		{
			$model["day_string_one"] = "LAST";
		}

		if( !empty($model["day_int_one"]) && $model["day_int_one"] == 32 )
		{
			$model["day_int_one"] = "LAST";
		}

		if( !empty($model["day_int_two"]) && $model["day_int_two"] == 32 )
		{
			$model["day_int_two"] = "LAST";
		}

		//#17795
		if( !empty($model["day_int_one"]) && $model["day_int_one"] == 33 )
		{
			$model["day_int_one"] = "1st business";
		}

		if( isset($model["day_string_one"]) )
		$model["day_string_one"] = strtoupper($model["day_string_one"]);

		switch ($model['model_name'])
		{
			case "dw":
			$paydate_string = "<b>WEEKLY</b> on weekday <b>".
			(isset($model["day_string_one"]) ? $model["day_string_one"] : '') . "</b>";
			break;
			case "dwpd":
			$paydate_string = "<b>BI-WEEKLY</b> on weekday <b>" .
			(isset($model["day_string_one"]) ? $model["day_string_one"] : '') . "</b>";
			break;
			case "dmdm":
				if ($model["day_int_one"] == "LAST") {
					$t = $model["day_int_one"];
					$model["day_int_one"] = $model["day_int_two"];
					$model["day_int_two"] = $t;
				}
			$paydate_string = "<b>TWICE-MONTHLY</b> on the <b>".
			(isset($model["day_int_one"]) ? $model["day_int_one"]  . $this->date_suffix($model["day_int_one"])  : '') ."</b> and <b>".
			(isset($model["day_int_two"]) ? $model["day_int_two"]  . $this->date_suffix($model["day_int_two"])  : '') ."</b>";
			break;
			case "wwdw":
			$paydate_string = "<b>TWICE-MONTHLY</b> in weeks <b>".
			(isset($model["week_one"]) ? $model["week_one"] : '') ."</b> and <b>".
			(isset($model["week_two"]) ? $model["week_two"] : '') ."</b> on <b>".
			(isset($model["day_string_one"]) ? $model["day_string_one"] : '') ."</b>";
			break;
			case "dm":
			// [#20759]
			$paydate_string = "<b>MONTHLY</b> on the <b>".
			(isset($model["day_int_one"]) ? $model["day_int_one"] : '') ."</b> day of the month";
			break;
			case "wdw":
			$paydate_string = "<b>MONTHLY</b> on <b>". (isset($model["day_string_one"]) ? $model["day_string_one"] : '') . "</b> of week <b>".
			(isset($model["week_one"]) ? ($model["week_one"] == 5 ? 'LAST' : $model["week_one"]) : '') . "</b>";
			break;
			case "dwdm":
			$paydate_string = "<b>MONTHLY</b> on weekday <b>".
			(isset($model["day_string_one"]) ? $model["day_string_one"] : '') ."</b> after the <b>".
			(isset($model["day_int_one"]) ? $model["day_int_one"]  . $this->date_suffix($model["day_int_one"]) : '') ."</b>";
			break;
			default:
			$paydate_string = $model['model_name'] . " NOT HANDLED ? ?";
			break;
		}
		return $paydate_string;
	}

 function date_suffix($num) {
    switch ($num) {
        case 1:
        case 21:
        case 31:
            return "st";
        case 2:
        case 22:
            return "nd";
        case 3:
        case 23:
            return "rd";
        case 'LAST':
        	return "";
        default:
            return "th";
    }
 }
	private function Format_Wizard_Data($row)
	{
		//echo "<pre>Formatting row:\n<pre>";var_export($row); echo "</pre>";
		$wizard = array('frequency' => $row->income_frequency);

		// get paydate model information
		//echo "Format Wizard Data <pre>";var_dump($wizard);
		//var_dump($row);var_dump($dow_map);

		switch( $row->income_frequency )
		{
			case "weekly":
			$wizard["weekly_day"] = $row->day_of_week;
			break;

			case "bi_weekly":
			if (isset($row->day_of_week))
			{
				$wizard["biweekly_day"] = $row->day_of_week;
				if (!is_numeric($row->last_paydate)){
					$wizard["biweekly_date"] = date("Y-m-d", strtotime($row->last_paydate));
				}else{
					$wizard["biweekly_date"] = date("Y-m-d", $row->last_paydate);
				}
				break;
			}

			case "twice_monthly":
			if( $row->paydate_model == "dmdm" )
			{
				$wizard["twicemonthly_type"] = "date";
				$wizard["twicemonthly_date1"] = $row->day_of_month_1;
				$wizard["twicemonthly_date2"] = $row->day_of_month_2;
			}
			else
			{
				if (isset($row->day_of_week))
				{
					$wizard["twicemonthly_type"] = "week";
					$wizard["twicemonthly_week"] = $row->week_1."-".$row->week_2;
					$wizard["twicemonthly_day"] = $row->day_of_week;
				}
			}
			break;

			case "monthly":
			if( $row->paydate_model == "dm" )
			{
				$wizard["monthly_type"] = "date";
				$wizard["monthly_date"] = $row->day_of_month_1;
			}
			elseif( $row->paydate_model == "wdw" )
			{
				if (isset($row->day_of_week))
				{
					$wizard["monthly_type"] = "day";
					$wizard["monthly_week"] = $row->week_1;
					$wizard["monthly_day"] = $row->day_of_week;
				}
			}
			else
			{
				if (isset($row->day_of_week))
				{
					$wizard["monthly_type"] = "after";
					$wizard["monthly_after_day"] = $row->day_of_week;
					$wizard["monthly_after_date"] = $row->day_of_month_1;
				}
			}
			break;
		}
		return $wizard;
	}

	public function Get_Paydates($model, $alt_format = NULL)
	{
		//echo "Get_Paydates -> model <pre>";var_dump($model);
		if( count($model) )
		{
			$this->exception_caught = FALSE;
			
			// Uppercase the model name as this is what the calc class expects.
			if( isset($model['model_name']) )
			{
				$model['model_name'] = strtoupper($model['model_name']);
			}

			if(empty($model['direct_deposit']))
			{
				$model['direct_deposit'] = FALSE;
			}
			else
			{
				$model['direct_deposit'] = $model['direct_deposit'] == 'yes' ? TRUE : FALSE;
			}

			//echo "Get Paydates: <pre>" . To_String($model) . "</pre>";
			// Find corresponding paydates for model
			if (!isset($model['model_name']))
			{
				return FALSE;
			}
			
			try 
			{
				$paydate_array = $this->pdc->Calculate_Pay_Dates($model['model_name'], $model, $model['direct_deposit']);
			}
			catch(Exception $e)
			{
				$this->exception_caught = $e->getMessage();
				$paydate_array = FALSE;
			}						
			
			//echo "<pre>" . To_String($paydate_array) . "</pre>";
			$pay_array = array('paydates' => array('','','',''), 'paydays' => array('','','',''), 'alt_paydates' => array(),
			'income_date_one_day' => '',
			'income_date_one_month' => '',
			'income_date_one_year' => '',
			'income_date_two_day' => '',
			'income_date_two_month' => '',
			'income_date_two_year' => '',
			);
			
			if ($paydate_array === FALSE || count($paydate_array) == 0)
			{
				return $pay_array;
			}

			foreach($paydate_array as $key => $paydate)
			{
				//the dates in display format
				$time = strtotime($paydate);
				$pay_array['paydates'][$key] = date("m-d-Y", $time);

				//the dow in display format
				$pay_array['paydays'][$key] = date("D", $time);

				//an alternate format (currently used for cashline)
				if($alt_format != NULL)
				$pay_array['alt_paydates'][$key] = date($alt_format, $time);

				//a broken down format of the first two paydates for copia
				if($key == 0)
				{
					$pay_array['income_date_one_month'] = date("m", $time);
					$pay_array['income_date_one_day'] = date("d", $time);
					$pay_array['income_date_one_year'] = date("Y", $time);
				}
				elseif($key == 1)
				{
					$pay_array['income_date_two_month'] = date("m", $time);
					$pay_array['income_date_two_day'] = date("d", $time);
					$pay_array['income_date_two_year'] = date("Y", $time);
				}
			}
			return $pay_array;
		}
		return FALSE;
	}

	// Switch from db style format to format expected by pay_date_calc (this sucks).
	private function Prepare_Model($data)
	{
		foreach(self::$MODEL_MAP as $column => $model_key)
		{
			if( isset($data->$column) && strlen($data->$column) )
			{
				$data->model[$model_key] = $data->$column;
			}
		}
	}

	// Switch back from the above.
	public function Reverse_Model($data)
	{
		$db_model = array();

		$model_map = array_flip(self::$MODEL_MAP);

		if( is_array($data) )
		{
			foreach($data as $db_name => $value)
			{
				if( isset($model_map[$db_name]) && strlen( trim($value) ) )
				{
					$db_model[$model_map[$db_name]] = $value;
				}
			}
		}

		return $db_model;
	}

	public function Get_Frequency($model_name)
	{
		if(!empty(self::$FREQUENCY_MAP[$model_name]))
		{
			return self::$FREQUENCY_MAP[$model_name];
		}
		return FALSE;
	}
}

?>
