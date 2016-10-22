<?php

class Paydate_Info
{
	public $direct_deposit;
	public $income_frequency;
	public $paydate_model;
	public $dow;
	public $last_paydate;
	public $day_of_month_1;
	public $day_of_month_2;
	public $day_int_one;
	public $day_int_two;
	public $week_1;
	public $week_2;

	public function Load_From_Row($r)
	{
		$row = (is_array($r)) ? (object) $r : $r;
		$this->direct_deposit = $row->income_direct_deposit == 'yes' ? TRUE : FALSE;
		$this->income_frequency = $row->income_frequency;
		$this->paydate_model = $row->paydate_model;
		$this->dow = $row->day_of_week;
		$this->last_paydate = $row->last_paydate;
		$this->day_of_month_1 = $row->day_of_month_1;
		$this->day_of_month_2 = $row->day_of_month_2;
		$this->day_int_one = $row->day_int_one;
		$this->day_int_two = $row->day_int_two;
		$this->week_1 = $row->week_1;
		$this->week_2 = $row->week_2;
		//$this->model = $row->model;
	}
}

?>