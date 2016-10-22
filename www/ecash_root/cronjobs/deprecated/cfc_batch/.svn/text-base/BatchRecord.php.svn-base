<?php

require_once 'libolution/Object.1.php';

abstract class BatchRecord extends Object_1 implements Iterator
{
	private $formats = array('filler' => NULL,
					'corporation_number' => NULL,
					'account_number' => '%-17.17s',
					'product_code' => NULL,
					'subproduct_code' => '%-4.4s',
					'processing_type' => NULL,
					'name_last' => '%-25.25s',
					'name_first' => '%-12.12s',
					'name_middle' => '%-15.11s',
					'name_suffix' => '%-270.4s',
					'street' => '%-30.30s',
					'phone_home' => '%-19.16s',
					'unit' => '%-30.30s',
					'phone_work' => '%-16.16s',
					'fico_score' => '%-30.3s', //skew here on start char
					'other_phone_location' => '%1.1s',
					'phone_cell' => '%-16.16s',
   					'account_source' => '%-3.3s',
					'city' => '%-40.30s',
					'approval_type' => '%1.1s',
					'state' => '%-3.3s',
					'zip_code' => '%-17.9s',
					'bill_date' => '%-2.2s',
					'credit_limit' => "%'011.11s", //padded with zeroes?
                    'filler2' => '%18s',
					'cash_advance_limit' => "%'011.11s", //also zero padded?
                    'filler3' => '%12s',
					'card_type2_issue_count_name1' => '%-2.1s',
					'account_association_name1' => '%-2.1s',
					'dob' => '%-8.8s',
					'ssn' => '%-14.13s',
					'card_type2' => '%-670.1s',
					'tenancy_type' => '%1.1s',
					'has_checking' => '%1.1s',
					'has_savings' => '%1.1s',
					'rush_service' => '%1.1s',
					'opt_in' => '%1.1s',
					'understanding_credit' => '%1.1s',
					'credit_card' => '%1.1s'							 
							 );

	//some of these are pre-populated
	private $values = array(
		'filler' => '0000',
		'subproduct_code' => 'MCC',
		'processing_type' => '10',
		'credit_limit' => '300',
		'filler2' => '',
		'cash_advance_limit' => '000',
		'filler3' => '',
		'card_type2_issue_count_name1' => '1',
		'account_association_name1' => '9',
		'card_type2' => 'M',
		'rush_service' => 'Y',
		'understanding_credit' => 'Y',
		'credit_card' => 'Y'
	);
	
	protected function __construct($data)
	{
		$this->account_source = substr(date("ym", $data['approved_date']), 1);
		unset($data['approved_date']);
		foreach($data as $index => $value)
		{
			$this->{$index} = $value;
		}
	}

	public function __set($name, $value)
	{
		//set the value
		$value = strtoupper($value);
		$this->values[$name] = $value;
	}

	public function __get($name)
	{
		//return formatted value
		if(empty($this->values[$name]))
			throw new Exception("$name has not been set in this record and is required.");

		return $this->format($name, $this->values[$name]);
	}

	public function format($name, $value)
	{
		$format = $this->formats[$name];
		if($format !== NULL)
			return sprintf($format, $value);
		
		return $value;		
	}

	//for Iterator
	public function current()
	{
		$name = $this->key();		
		if(empty($this->values[$name]))
			return FALSE;
		
		return $this->format($name, $this->values[$name]);
	}

	public function key()
	{
		return key($this->formats);
	}

	public function next()
	{
		//advance the pointer
		next($this->formats);
		return $this->current();
	}

	public function rewind()
	{
		reset($this->formats);
		return $this->current();
	}

	public function valid()
	{
		return ($this->current() === FALSE) ? FALSE : TRUE;
	}
}

?>
