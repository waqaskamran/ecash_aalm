<?php

require_once(SERVER_CODE_DIR . 'event_amount.class.php');

class Schedule_Event
{       
	public $event_schedule_id;
	public $transaction_register_id;
	public $date_event;
	public $event_name;
	public $date_effective;
	public $origin_id;
	public $origin_group_id;
	public $type;
	public $status;
	public $comment;
	public $context;
	public $associated_event;
	public $amounts;


	/** 
	 * MakeEvent takes parameters:
	 * 1. date event
	 * 2. date effective
	 * 3. amounts object
	 * 4. type name_short
	 * 5. comment (optional defaults '')
	 * 6. status (optional defaults 'scheduled')
	 * 7. context (optional defaults 'generated')
	 * 8. oid (optional defaults null)
	 * 9. ogid (optional defaults null)
	 */
	public static function MakeEvent($date_event, $date_effective, $amounts, $type, $comment='',$status='scheduled', 
					 $context='generated', $oid = null, $ogid = null, $is_shifted = null)
	{
		if(!is_string($date_event) || !strtotime($date_event))
		{
			throw(new Exception("$date_event should be a valid date string"));
		}
		if(!is_string($date_effective) || !strtotime($date_effective))
		{
			throw(new Exception("$date_effective should be a valid date string"));
		}

		$se = new stdClass();
		$se->event_schedule_id = 0;
		$se->transaction_register_id = 0;
		$se->origin_id = $oid;
		$se->origin_group_id = $ogid;
		$se->type = $type;
		$se->status = $status;
		$se->reattempt_count = 0;
		$se->date_event = date('Y/m/d', strtotime($date_event));
		$se->date_effective = date('Y/m/d', strtotime($date_effective));
		$se->context = $context;
		$se->associated_event = null;
		$se->amounts = $amounts;
		$se->is_shifted = $is_shifted;
		$se->comment = $comment;
		return $se;
	}

	// Basically a lie, but it makes sure the group counter is consistent :)
	public static function Load_From_Row($row)
	{		
		return ($row);
	}

	public static function CloneEvent($event, $is_reatt = false)
	{		
		$copy = new stdClass();
		
		foreach ($event as $key => $value)
		{
			if ($is_reatt)
			{
				switch($key)
				{
				case 'reattempt_count':
					$copy->$key = $value + 1; break;
				case 'status':
					$copy->$key = 'scheduled'; break;
				default:
					$copy->$key = $value; break;
				}
			}
			else
			{
				$copy->$key = $value;
			}
			
		}

		if ($is_reatt)
		{
			$copy->origin_id = $event->transaction_register_id;
		}

		return $copy;
	}

    /**
     * pass an array of event amount objects to update the row amount columns as it would have been
	 * PASSES ROW BY REFERENCE!!
     */
    public static function Set_Amounts_From_Event_Amount_Array(&$row, $amounts) 
	{
		$row->amount = 0;
        foreach ($amounts as $amount) 
		{
			$row->amount += $amount->amount;
			switch ($amount->event_amount_type) 
			{
				case 'principal': 
					$row->principal = $amount->amount;
					break;
				case 'service_charge': 
					$row->service_charge = $amount->amount;
					break;
				case 'fee': 
					$row->fee = $amount->amount;
					break;
				case 'irrecoverable': 
					$row->irrecoverable = $amount->amount;
					break;
			}
		}
        return $row;
    }

}


?>
