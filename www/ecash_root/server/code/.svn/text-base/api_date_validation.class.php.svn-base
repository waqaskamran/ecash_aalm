<?php

require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');
require_once(LIB_DIR . 'Payment_Card.class.php');

class API_Date_Validation implements Module_Interface
{
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
        $this->permissions = array(
            array('loan_servicing'),
            array('funding'),
            array('collections'),
            array('fraud'),
        );
	
	}
	
	public function get_permissions()
	{
		return $this->permissions; 
	}

	public function Main() 
	{
		$input = $this->request->params[0];

		switch ($input->action)
		{
			case 'has_date_passed':
				if (($ts = strtotime($input->date)) == FALSE)
				{
					return array('error' => 'Date is in unknown format');
				}
				
				if ($ts < strtotime(date('Y-m-d')))
					return TRUE;
				else
					return FALSE;

				break;
			case 'is_holiday_or_weekend':
				try
				{
					$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
				
					return ($pdc->Is_Holiday(strtotime($input->date)) == TRUE || $pdc->Is_Weekend(strtotime($input->date)) == TRUE);
				}
				catch (Exception $e)
				{
					$data = array('error' => 'Unknown error occurred');
				}

				break;
			default:
				throw new Exception("Unknown action {$input->action}");
		}
		return $data;
	}
}

?>
