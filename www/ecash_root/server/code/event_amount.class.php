<?php

/**
 * A container class for dealing with the event_amount table
 *
 */
class Event_Amount
{
	public $event_schedule_id;
	public $event_amount_type;
	public $amount;
	public $num_reattempt;

	/**
	 * Returns a new Event Amount Object
	 *
	 * @param string $event_amount_type
	 * @param float $amount
	 * @param int $num_reattempt
	 * @return Event_Amount
	 */
	public static function MakeEventAmount($event_amount_type, $amount, $num_reattempt = 0)
	{

		$ea = new Event_Amount();
		$ea->event_amount_type = $event_amount_type;
		$ea->amount = $amount;
		$ea->num_reattempt = $num_reattempt;
		return $ea;
	}
	
	/**
	 * To load, the passed row should have:
	 * principal
	 * principal_reatt
	 * principal_reatt_count
	 * service_charge
	 * service_charge_reatt
	 * service_charge_reatt_count
	 * fee
	 * fee_reatt
	 * fee_reatt_count
	 * irrecoverable
	 * irrecoverable_reatt
	 * irrecoverable_reatt_count
	 */
	public static function Load_Amounts_From_Fetch_Schedule_Row($row) {
		$amounts = array();
		$amounts[] = self::MakeEventAmount('principal', $row->principal, 0);
		$amounts[] = self::MakeEventAmount('service_charge', $row->service_charge, 0);
		$amounts[] = self::MakeEventAmount('fee', $row->fee, 0);
		$amounts[] = self::MakeEventAmount('irrecoverable', $row->irrecoverable, 0);
		
		return $amounts;
	}

}


?>
