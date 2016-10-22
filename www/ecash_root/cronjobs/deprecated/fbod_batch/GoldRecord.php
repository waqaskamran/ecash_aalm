<?php

require_once 'BatchRecord.php';

class GoldRecord extends BatchRecord
{
	public function __construct($data)
	{
		parent::__construct($data);
		$this->corporation_number = '71';
		$this->account_number = '540639';
		$this->product_code = 'MGO';
		$this->subproduct_code = '000';
		$this->account_association_name1 = '1';
	}
}