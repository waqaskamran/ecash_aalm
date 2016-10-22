<?php

class FraudColumns
{
	/** If you add columns here, you must also insure that those
	 ** fields are being checked automatically when making changes to
	 ** the application.  This work should be done in
	 ** server/code/edit.class.php -- JRF
	 */
	public static $columns = array("name_first" => "First Name",									
								    "name_middle" => "Middle Name",
									"name_last" => "Last Name",
									"phone_home" => "Home Phone",
									"phone_cell" => "Cell Phone",
									"phone_work" => "Work Phone",
									"phone_fax" => "Fax Phone",
									"street" => "Address",
									"city" => "City",
									"state" => "State",
									"zip_code" => "ZIP Code",
									"ssn" => "SSN",
									"email" => "Email",
									"bank_name" => "Bank Name",
									"bank_account" => "Bank Account #",
									"employer_name" => "Employer Name",
									"job_title" => "Job Title",
									);

	public static function formatForDisplay($columns_array)
	{
		$formatted_array = array();
		foreach($columns_array as $column_name)
		{
			$formatted_array[] = self::$columns[$column_name];
		}
		return $formatted_array;
	}
}

?>