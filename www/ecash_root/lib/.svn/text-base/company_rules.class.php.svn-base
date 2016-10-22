<?php
/**
 * Company_Rules
 * Simplified wrapper class for Business_Rules
 *
 * Created on Jan 2, 2007
 *
 * @package eCash
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

class Company_Rules extends ECash_BusinessRules {

	static public $set_name = "Company Level";

	static protected $br_obj;
	static protected $config_chain;

	protected $loan_type_id;
	protected $rule_set_id;

	static public function Factory(DB_Database_1 $db = NULL)
	{
		if ($db === NULL)
		{
			$db = ECash::getMasterDb();
		}

		return new Company_Rules($db);
	}


	static public function Singleton($company_id = NULL, DB_Database_1 $db = NULL)
	{
		if (!self::$br_obj)
		{
			self::$br_obj = self::Factory($db);
			self::$br_obj->Set_Company($company_id);
		}

		return self::$br_obj;
	}

	static public function Get_Config($rule_name)
	{
		return self::Singleton()->Get_Config_Value($rule_name);
	}

	static public function Set_Config($rule_name, $new_value, $display_name = NULL, $rule_options = NULL)
	{
		return self::Singleton()->Set_Config_Value($rule_name, $new_value, $display_name, $rule_options);
	}

	static public function Standardize($rule_name)
	{
		return trim(preg_replace("/\W+/", "_" ,strtolower($rule_name))," \t\n\r\0\x0B_");
	}

	public function Set_Company($company_id = NULL)
	{
		if($company_id == NULL) 
		{
			$this->company_id = ECash::getCompany()->company_id;
		} 
		else 
		{
			$this->company_id = $company_id;
		}

	}

	public function Get_Company()
	{
		return $this->company_id;
	}

	public function Get_Config_Value($rule_name)
	{
 		if (!self::$config_chain)
 		{
			foreach($this->Get_Loan_Types($this->Get_Company()) as $index => $row)
			{
				if($row->name == self::$set_name)
				{
					$this->loan_type_id = $row->loan_type_id;
					$this->rule_set_id = $this->Get_Current_Rule_Set_Id($this->loan_type_id);
					self::$config_chain = $this->Get_Rule_Set_Tree($this->rule_set_id);
					//self::$config_chain = $this->Get_Latest_Rule_Set( $row->loan_type_id );
					break;
				}
			}
		}
		
		if(empty(self::$config_chain[$rule_name]) && empty(self::$config_chain[preg_replace("/\W/","_",strtolower($rule_name))])) 
		{
			$t_rule = preg_replace("/\W/","_", strtoupper($rule_name));

			try
			{
			$retval = ECash::getConfig()->$t_rule;
			return $retval;

			}
			catch(Exception $e)
			{
			}
		}

		return (self::$config_chain[$rule_name]) ? self::$config_chain[$rule_name] : self::$config_chain[preg_replace("/\W/","_",strtolower($rule_name))];

	}

	public function Set_Config_Value($rule_name, $new_value, $display_name = NULL, $rule_options = NULL)
	{
		if(preg_match("/\W/",$rule_name)) 
		{
			$display_name = $rule_name;
			$rule_name = trim(preg_replace("/\W+/", "_" ,strtolower($display_name))," \t\n\r\0\x0B_");
		}

		$curr_val = $this->Get_Config_Value($rule_name);
		if (!in_array($rule_name, array_keys(self::$config_chain)))
		{
			return $this->New_Config_Value($rule_name, $new_value, $display_name, $rule_options);
		}

		$query = "
			UPDATE
				rule_set_component,
				rule_component,
				rule_component_parm,
				rule_set_component_parm_value
			SET
				rule_set_component_parm_value.parm_value  = ?
			WHERE
				rule_set_component.rule_component_id = rule_component.rule_component_id
				AND rule_set_component.rule_set_id = ?
				AND rule_component.rule_component_id = rule_set_component_parm_value.rule_component_id
				AND rule_component_parm.rule_component_parm_id = rule_set_component_parm_value.rule_component_parm_id
				AND rule_set_component_parm_value.rule_set_id = rule_set_component.rule_set_id
				AND rule_component.name_short = ?
		";
		$this->db->queryPrepared($query, array($new_value, $this->rule_set_id, $rule_name));
	}

	public function New_Config_Value($rule_name, $rule_value, $display_name = NULL, $rule_options = NULL)
	{
		if(preg_match("/\W/",$rule_name)) 
		{
			$display_name = $rule_name;
			$rule_name = trim(preg_replace("/\W+/", "_" ,strtolower($display_name))," \t\n\r\0\x0B_");
		}

		$curr_val = $this->Get_Config_Value($rule_name);
		if(in_array($rule_name, array_keys(self::$config_chain))) 
		{
			return $this->Set_Config_Value($rule_name, $rule_value, $display_name, $rule_options);
		}

		if(!$display_name) $display_name = $rule_name;

		if(is_Array($rule_options)) $rule_options = implode(", ", $rule_options);
		try {
			$this->db->beginTransaction();

			$new_component_query =  "
				INSERT INTO rule_component (active_status, name, name_short, grandfathering_enabled)
				VALUES ('active', '{$display_name}', '{$rule_name}', 'no')
			";

			$this->db->exec($new_component_query);
			$component_id = $this->db->lastInsertId();

			$next_in_seq_query = "
			select max(sequence_no) + 1 as next_seq from rule_set_component where rule_set_id = {$this->rule_set_id}
			";
			$next_in_seq = $this->db->querySingleValue($next_in_seq_query);

			$component_seq_query = "
				INSERT INTO rule_set_component (active_status, rule_set_id, rule_component_id, sequence_no)
				VALUES ('active', {$this->rule_set_id}, {$component_id}, {$next_in_seq} )
				";
			$this->db->exec($component_seq_query);

			$param_query = "
			INSERT INTO rule_component_parm (active_status, rule_component_id, parm_name, sequence_no, display_name,
											 parm_type, user_configurable, input_type, presentation_type, value_label,
											 enum_values)
			VALUES ('active',{$component_id},'{$rule_name}',1,'{$display_name}',
					'string','yes','select','scalar','none',
					'{$rule_options}')
				";
			$this->db->exec($param_query);
			$param_id = $this->db->lastInsertId();

			$value_query = "
			INSERT INTO rule_set_component_parm_value (agent_id, rule_set_id, rule_component_id, rule_component_parm_id, parm_value)
			VALUES (0,{$this->rule_set_id},{$component_id},{$param_id},'{$rule_value}')
			";

			$this->db->exec($value_query);

			$this->db->commit();

		} 
		catch (Exception $e) 
		{
			$this->db->rollBack();
			throw $e;
		}

	}
	
}
