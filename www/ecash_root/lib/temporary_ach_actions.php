<?php

require_once(COMMON_LIB_DIR  . "general_exception.1.php");

class Temporary_ACH_Actions
{
	private $server;
	private $db;
	private $log;
	private $company_abbrev;
	private $company_id;

	private $ach_trans_types;

	private $holiday_query;
	private $holiday_ary;
	private $paydate_obj;
	
	private $ttype_map;
	private $ttypes_end_status_failed;
	private $ttypes_end_status_complete;
	private $pending_period_max;
	private $biz_days;
	
	public function __construct($server)
	{
		$this->server			= $server;
		$this->db				= ECash::getMasterDb();
		$this->log				= $server->log;
		$this->company_abbrev	= strtolower($server->company);
		$this->company_id		= strtolower($server->company_id);
		$holidays = Fetch_Holiday_List();
		$this->paydate_obj		= new Pay_Date_Calc_3($holidays);

		$this->Get_Transaction_Type_Map();
		$this->Get_Past_Business_Days();
	}
	
	private function Get_Transaction_Type_Map()
	{
		// This should ONLY cache the non-ACH transaction types !!
		$query = "
				SELECT
					transaction_type_id,
					name_short,
					pending_period,
					end_status
				FROM 
					transaction_type
				WHERE
					clearing_type = 'ach'
		";

		$this->ach_trans_types	= array();
		$this->ttype_map		= array();
		$this->ttypes_end_status_failed	  = array();
		$this->ttypes_end_status_complete = array();
		$this->pending_period_max = 0;

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$this->ach_trans_types[] = $row['transaction_type_id'];

			$this->ttype_map[$row['transaction_type_id']] = $row;
			
			if ($row['end_status'] == 'complete')
			{
				$this->ttypes_end_status_complete[] = $row['transaction_type_id'];
				
				if ($row['pending_period'] > $this->pending_period_max)
				{
					$this->pending_period_max = $row['pending_period'];
				}
			}
		}
	}

	private function Get_Past_Business_Days()
	{
		$this->biz_days = array();
		
		$date_wk = date('Y-m-d');
		$this->biz_days[0] = $date_wk;
		for ($i = 0; $i < $this->pending_period_max; $i++)
		{
			$date_wk = $this->paydate_obj->Get_Last_Business_Day($date_wk);
			$this->biz_days[$i + 1] = $date_wk; 
		}
	}

	public function Conversion_ACH_Post_o_Matic ()
	{
		// This li'l bastard will post the fake ACH transactions entered as a result of the conversion process;
		//	 there are no ach_id's for these.

		$today = date("Y-m-d");

		$query = "
					SELECT
						transaction_register_id,
						application_id
						transaction_type_id,
						date_effective
					FROM
						transaction_register
					WHERE
							company_id = {$this->company_id}
						AND	transaction_type_id	IN (" . implode(",", $this->ach_trans_types) . ")
						AND transaction_status = 'pending'
						AND ach_id IS NULL
		";

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if ( $row->date_effective <= $this->biz_days[$this->ttype_map[$row->transaction_type_id]['pending_period']] )
			{
				$post_result = Post_Transaction($row->application_id, $row->transaction_register_id);
			}
		}
		
		return true;
	}

}
?>
