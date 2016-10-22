<?php
/**
 * <CLASSNAME>
 * <DESCRIPTION>
 *
 * Created on Mar 1, 2007
 *
 * @package <PACKAGE>
 * @category <CATEGORY>
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

class eCash_Application_FieldAttribute
{
	protected $db;
	
	public function __construct(DB_Database_1 $db)
	{
		$this->db = $db;
	}
	
	/*
	 * $application_id  This is the application id.
	 */
	public function Get_Attributes($application_id, $company_id = NULL)
	{
		$query = "
			SELECT
				af.column_name,
				afa.field_name
			FROM application_field af
				INNER JOIN application_field_attribute afa
					ON (afa.application_field_attribute_id = af.application_field_attribute_id)
			WHERE
				table_name = 'application'
				AND table_row_id = ?
				{$company_where}
		";
		$args = array($application_id);
		
		if ($company_id !== NULL)
		{
			$query .= "AND company_id = ?";
			$args[] = $company_id;
		}
		
		$st = $this->db->queryPrepared($query, $args);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	private function Add_Many_Attributes($company_id, $id, $columns, $flag, $table_name = 'application')
	{
		$args = array();
		
		foreach($columns as $column_name)
		{
			$insert[] = "
				(NOW(), NOW(), {$company_id}, {$id}, '{$table_name}', ?,
					(select application_field_attribute_id from application_field_attribute where field_name = '{$flag}')
				)
			";
			$args[] = $column_name;
		}
		
		$query = "
			INSERT INTO application_field
			(
				date_modified,
				date_created,
				company_id,
				table_row_id,
				table_name,
				column_name,
				application_field_attribute_id
			)
			VALUES ".implode(", ", $insert)."
			ON DUPLICATE KEY UPDATE date_modified=now()
		";
		$this->db->queryPrepared($query, $args);
		return TRUE;
	}
	
	/**
	 * $id:  This is the primary key for $table_name (usually $application_id).
	 * $column_name:  This is the column name.
	 * $flag:  Values include (but are not limited to) 'best_contact', 'do_not_contact',
	 *			'do_not_market', 'bad_info', 'high_risk', 'fraud'.
	 * $table_name: The name of the table to associate this attribute to.
	 */
	private function Add_Attribute($company_id, $id, $column_name, $flag, $table_name =' application')
	{
		$column_name = trim(strtolower($column_name));	
		$flag = trim(strtolower($flag));
		
		return $this->Add_Many_Attributes($company_id, $id, array($column_name), $flag, $table_name);
	}

	//mantis:4646
	function Change_Attribute($company_id, $application_id, $flag, $column_array)
	{
		$add = array();
		
		foreach($column_array as $key => $value)
		{
			if ($value != '')
			{
				// use value to turn on (insert) attribute
				$add[] = $value;
			}
			else
			{
				// use key to turn off (delete) all attributes for field
				$this->Remove_Attribute($company_id, $application_id, $key, $flag);
			}
		}
		
		if (count($add))
		{
			$this->Add_Many_Attributes($company_id, $application_id, $add, $flag);
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
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			DELETE FROM  application_field
			WHERE	company_id = ?
				AND	table_row_id = ?
				AND	column_name = ?
				AND	table_name = ?
				AND	application_field_attribute_id = (
					SELECT application_field_attribute_id 
					FROM application_field_attribute 
					WHERE field_name = ?
				)
		";
		$this->db->queryPrepared($query, array($company_id, $id, $column, $table_name, $flag));
		return TRUE;
	}

	function Mark_All($company_id, $application_id, $flag)
	{
		$columns = array(
			'name',
			'street',
			'customer_email',
			'phone_home',
			'phone_cell',
			'phone_work'
		);
		return $this->Add_Many_Attributes($company_id, $application_id, $columns, $flag);
	}

}
