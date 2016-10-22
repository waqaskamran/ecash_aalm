<?php

require_once 'libolution/Object.1.php';

abstract class BatchRecord extends Object_1 implements Iterator
{
	private $formats = array(//							Begin	End	Length		Note

		'filler' => NULL,//								1		4	4			Filler, always "0000"
		'corporation_number' => NULL,//					5		6	2			if "ISSUINGBANK"=01 THEN VALUE IS 93 IF "ISSUINGBANK"=02 THEN VALUE IS 94
		'account_number' => '%-17.17s',//				7		22	16			ISSUINGBANK=01 VALUE IS 513693 + 10 SPACES/ 02 VALUE IS 512607 + 10 SPACES
		// ACCOUNT NUMBER HAS 1 EXTRA BLANK SPACE TO ACCOUNT FOR THE MISSING CHAR
		'product_code' => NULL,//						24		26	3			01 VALUE="FBT" / 02 VALUE="DEL"
		'subproduct_code' => '%-4.4s',//				27		29	3			VALUE ALWAYS "MCC"
		// SUBPRODUCT_CODE HAS 1 EXTRA BLANK SPACE TO ACCOUNT FOR THE MISSING CHAR
		'processing_type' => NULL,//					31		32	2			VALUE ALWAYS "10"
		'name_last' => '%-25.25s',//					33		57	25			CARDHOLDER'S LAST NAME
		'name_first' => '%-12.12s',//					58		69	12			CARDHOLDER'S FIRST NAME
		'name_middle' => '%-15.11s',//					70		80	11			CARDHOLDER'S MIDDLE NAME
		//NAME_MIDDLE HAS 4 EXTRA BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'name_suffix' => '%-270.4s',//					85		88	4			CARDHOLDER'S NAME SUFFIX (JR,SR,III,ETC)
		//NAME_SUFFIX HAS 266 EXTRA BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'street' => '%-30.30s',//						355		384	30			CARDHOLDER'S ADDRESS LINE1 
		'phone_home' => '%-19.16s',//					385		400	16			CARDHOLDER'S HOME PHONE#
		//PHONE_HOME HAS 3 EXTRA BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'unit' => '%-30.30s',//							404		433	30			CARDHOLDER'S ADDRESS LINE2 (UNIT#, SUITE#,ETC)
		'phone_work' => '%-16.16s',//					434		449	16			CARDHOLDER'S WORK PHONE#
		'fico_score' => '%-33.3s', //					450		452	3			CARDHOLDER'S FICO SCORE (WE DO NOT HAVE A VALUE FOR THIS, SO WE MAKE IT BLANK)skew here on start char
		//FICO_SCORE HAS 30 EXTRA BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'other_phone_location' => '%1.1s',//			483		483	1			DENOTES THE EXISTANCE OF ANOTHER PHONE# (LIKE CELLPHONE)
		'phone_cell' => '%-16.16s',//					484		499	16			CARDHOLDER'S CELLPHONE#
		'account_source' => '%-3.3s',//	???				500		502	3			DENOTES THE 1 DIGIT YEAR AND 2 DIGIT MONTH (DEC 2005 = 512)
		'city' => '%-40.30s',//							503		535	30			CARDHOLDER'S CITY
		//CITY HAS 10 EXTRA BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'approval_type' => '%1.1s',//					543		543	1			TRANSTYPE 1="I" OR "A" 2="R" 3="O"
		'state' => '%-3.3s',//							544		546	3			CARDHOLDER'S STATE
		'zip_code' => '%-17.9s',//						547		555	9			CARDHOLDER'S ZIPCODE
		//ZIP_CODE HAS 8 EXTRA BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'billing_date' => '%-2.2s',//					564		565	2			2 DIGIT BILL DATE CODE.  DETERMINED WITH A VALUE FROM THE billing_date TABLE
		'credit_limit' => "%'011.11s", //				566		576	11			VALUE IS CURRENTLY ALWAYS "300" NO DECIMAL VALUES ARE ALLOWED //padded with zeroes?
		'filler2' => '%18s',//							577		594	18			18 BLANK SPACES TO ACCOUNT FOR MISSING CHARS
		'cash_advance_limit' => "%'011.11s", //			595		605	11			VALUE IS CURRENTLY ALWAYS "000" NO DECIMAL VALUES //also zero padded?
		'filler3' => '%12s',//							606		617	12			12 BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'card_type2_issue_count_name1' => '%-2.1s',//	618		618	1			VALUE IS CURRENTLY ALWAYS "1"
		//CARD_TYPE2_ISSUE_COUNT_NAME1 HAS 1 EXTRA BLANK SPACE TO ACCOUNT FOR THE MISSING CHARS
		'account_association_name1' => '%-2.1s',//		620		620	1			VALUE IS CURRENTLY ALWAYS "1"
		//ACCOUNT_ASSOCIATION_NAME1 HAS 1 EXTRA BLANK SPACE TO ACCOUNT FOR THE MISSING CHARS
		'dob' => '%-8.8s',//							622		629	8			CARDHOLDER'S BIRTHDATE. IN DDMMYYYY FORMAT
		'ssn' => '%-14.13s',//							630		642	13			CARDHOLDER'S SSN
		//SSN HAS 1 EXTRA BLANK SPACE TO ACCOUNT FOR THE MISSING CHAR
		'card_type2' => '%-670.1s',//					644		644	1			VALUE IS CURRENTLY ALWAYS "M"
		//CARD_TYPE2 HAS 669 EXTRA BLANK SPACES TO ACCOUNT FOR THE MISSING CHARS
		'tenancy_type' => '%1.1s',//					1314	13141			CARDHOLDER'S HOUSING STATUS O(OWN)/R(RENT)/X(OTHER)/Z(NONE PROVIDED)
		'has_checking' => '%1.1s',//					1315	13151			CARDHOLDER'S CHECKING ACCOUNT Y(YES)/N(NO)/Z(NONE PROVIDED)
		'has_savings' => '%1.1s',//						1316	13161			CARDHOLDER'S SAVINGS ACCOUNT Y(YES)/N(NO)/Z(NONE PROVIDED)
		'rush_service' => '%1.1s',//					1317	13171			''			 RUSH SERVICE Y(YES)/N(NO)/Z(NONE PROVIDED)
		'opt_in' => '%1.1s',//							1318	13181			''			 OPT-IN Y(YES)/N(NO)/Z(NONE PROVIDED)
		'understanding_credit' => '%1.1s',//			1319	13191			''			 UNDERSTANDING CREDIT Y(YES)/N(NO)/Z(NONE PROVIDED)
		'credit_card' => '%1.1s'//						1320	13201			''			 OTHER CREDIT CARD Y(YES)/N(NO)/Z(NONE PROVIDED)
	);

	//some of these are pre-populated
	private $values = array(
		'filler' => '0000',
		'subproduct_code' => 'MCC',
		'processing_type' => '10',
		'approval_type' => '', //This gets left blank! 
		'billing_date' => '', //Per Lynn:  Billing date is to be left blank, and they will generate the number!
		'credit_limit' => '300',
		'fico_score' => '',
		'filler2' => '',
		'cash_advance_limit' => '000',
		'filler3' => '',
		'card_type2_issue_count_name1' => '1',
		'account_association_name1' => '1',
		'card_type2' => 'M',
		'has_savings' => 'Z',
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
		if(!array_key_exists($name, $this->values))
		{
			echo "{$name} is a problem!";die;
			throw new Exception("$name has not been set in this record and is required.");
			
		}
			

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
		if(!array_key_exists($name, $this->values))
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