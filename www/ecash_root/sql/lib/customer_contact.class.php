<?php

class Customer_Contact
{
	private $db;
	
	public function __construct(DB_Database_1 $db)
	{
		$this->db = $db;
	}
	
	/*
	 * $application_id  This is the application id.
	 */
	public function Get_Contact_Info($application_id)
	{
		$query = "
					SELECT
						af.column_name,
						afa.field_name,
						af.agent_id
					FROM application_field af
					INNER JOIN application_field_attribute afa
						ON (afa.application_field_attribute_id = af.application_field_attribute_id)
					WHERE
						table_name = 'application'
					AND table_row_id = {$application_id}";
	
		$values = array();
		$result = $this->db->query($query);
		return $result->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Grouped_Contact_Info($application_id, $company_id)
	{
		$query = "
					SELECT 
						af.column_name,
						afa.field_name,
						af.agent_id
					FROM application_field af
					INNER JOIN application_field_attribute afa
						ON (afa.application_field_attribute_id = af.application_field_attribute_id)
					WHERE
						table_name = 'application'
					AND table_row_id = {$application_id}
					AND company_id = {$company_id} 
					ORDER BY af.column_name  
					";
	
		$values = array();
		$result = $this->db->query($query);
		return $result->fetchAll(PDO::FETCH_OBJ);
	}

	public function Add_Many_Columns($company_id, $id, $columns, $flag, $agent_id, $table_name='application')
	{
		if(count($columns) != 0)
		{
			/* I changed this query to INSERT IGNORE rather then ON
			 * DUPLICATE KEY UPDATE so the affect_rows count will be more
			 * indicative of what was inserted -- JRF
			 */
			$query = "INSERT IGNORE INTO application_field
							(date_modified,
							 date_created,
							 company_id,
							 table_row_id,
							 table_name,
							 column_name,
							 agent_id,
							 application_field_attribute_id)
						VALUES
						";

			$values = array();
			foreach($columns as $column_name)
			{
				$values[] = "		(now(),
							 now(), 
							 {$company_id},
							 {$id},
							 '{$table_name}',
							 '{$column_name}',
							  {$agent_id},
							 (select application_field_attribute_id from application_field_attribute where field_name = '{$flag}'))
							 ";
			}
			$query .= join(",\n", $values);
			//$query .= " ON DUPLICATE KEY UPDATE date_modified=now()";

			return $this->db->exec($query);
		}
		//no rows affected (query not run)
		return 0;
	}

	public function Remove_All_Of_Type($company_id, $id, $flag, $table_name = 'application')
	{
		$query = "
                 DELETE FROM  application_field
                 WHERE	company_id = {$company_id}
				 AND	table_row_id = {$id}
				 AND	table_name = '{$table_name}'
				 AND	application_field_attribute_id = (select application_field_attribute_id from application_field_attribute where field_name = '{$flag}')";

		return $this->db->exec($query);
	}

	/**
	 * $id:  This is the primary key for $table_name (usually $application_id).
	 * $column_name:  This is the column name.
	 * $flag:  Values include (but are not limited to) 'best_contact', 'do_not_contact',
	 *			'do_not_market', 'bad_info', 'high_risk', 'fraud'.
	 * $table_name: The name of the table to associate this attribute to.
	 */
	private function Add_Attribute($company_id, $id, $column_name, $flag, $agent_id, $table_name='application')
	{
		$column_name = trim(strtolower($column_name));	
		$flag = trim(strtolower($flag));
		
		$query = "
						INSERT INTO application_field
							(date_modified,
							 date_created,
							 company_id,
							 table_row_id,
							 table_name,
							 column_name,
							 agent_id, 
							 application_field_attribute_id)
						VALUES 
							(now(),
							 now(), 
							 {$company_id},
							 {$id},
							 '{$table_name}',
							 '{$column_name}',
							  {$agent_id},
							 (select application_field_attribute_id from application_field_attribute where field_name = '{$flag}'))
						 ON DUPLICATE KEY UPDATE date_modified = now()";
	
		return $this->db->exec($query);
	}

	//mantis:4646
	function Change_Contact($company_id, $application_id, $flag, $column_array, $agent_id)
	{
		foreach($column_array as $key => $value)
		{
			//use value to turn on (insert) attribute
			if($value != '')
			{
				$this->Add_Attribute($company_id, $application_id, $value, $flag, $agent_id);
			}
			//use key to turn off (delete) all attributes for field
			else
			{
				$this->Remove_Attribute($company_id, $application_id, $key, $flag);
			}
		}
	}
	//end mantis:4646

	/**
	 *	This is used to remove an attribute.
	 *
	 *	Parms
	 *	       $app_id - This is the application id
	 *	       $column - This is the field
	 */
	private function Remove_Attribute($company_id, $id, $column, $flag, $table_name = 'application')
	{
		$query = "
                 DELETE FROM  application_field
                 WHERE	company_id = {$company_id}
				 AND	table_row_id = {$id}
                 AND	column_name = '{$column}'
				 AND	table_name = '{$table_name}'
				 AND	application_field_attribute_id = (select application_field_attribute_id from application_field_attribute where field_name = '{$flag}')";

		return $this->db->exec($query);
	}

	function Mark_All($company_id, $application_id, $flag, $agent_id)
	{
		$columns = array('name',
						 'street',
						 'customer_email',
						 'phone_home',
						 'phone_cell',
						 'phone_work');
	
		return $this->Add_Many_Columns($company_id, $application_id, $columns, $flag, $agent_id);
	}

}

?>
