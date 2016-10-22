<?php

require_once (LIB_DIR . 'common_functions.php');
require_once(ECASH_COMMON_DIR . 'ECashApplication.php');
require_once(ECASH_COMMON_DIR . 'Fraud/FraudRule.php');
require_once(ECASH_COMMON_DIR . 'Fraud/FraudCondition.php');

class Application_Fraud_Query
{

	private $server;
        private $companies;
        private $db;
        
        private static $fraud_fields = "
                                        application_id,
                                        ap.company_id,
                                        (case
                                                when c.name_short = 'd1' then '5FC'
                                                when c.name_short = 'pcl' then 'OCC'
                                                when c.name_short = 'ca' then 'AML'
                                                else upper(c.name_short)
                                        end) as display_short,                                  
                                        asf.level0_name as status_long,
                                        asf.level0 as status,
                                        asf.level1,
                                        name_first,
                                        name_middle,
                                        name_last,
                                        phone_home,
                                        phone_cell,
                                        phone_fax,
                                        street,
                                        unit,
                                        city,
                                        state,
                                        zip_code,
                                        ssn,
                                        legal_id_number,
                                        email,
                                        bank_name,
                                        bank_account,
                                        bank_account_type,
                                        bank_aba,
                                        employer_name,
                                        phone_work,
                                        phone_work_ext,
                                        income_monthly,
                                        job_title,
                                        income_direct_deposit
                                        ";
        
        public function __construct(Server $server)
        {
                $this->server = $server;
		$this->companies = $this->server->company_list;
		$this->db = ECash::getMasterDb();
	}

	public function Insert_Rule(FraudRule $rule)
	{
		//confirmed should always be off (0) on a new rule
		$insert_rule = "insert into fraud_rule
				  (date_created, date_modified, modified_agent_id, created_agent_id, active, exp_date, rule_type, confirmed, name, comments)
				  values
				  (now(), now(), {$this->server->agent_id}, {$this->server->agent_id}, {$rule->IsActive}, {$rule->ExpDate}, '" . mysql_escape_string($rule->RuleType) . "', 0, '" . mysql_escape_string($rule->Name) . "', '" . mysql_escape_string($rule->Comments) . "')
			";

		$this->db->query($insert_rule);
		$fraud_rule_id = $this->db->lastInsertId();

		$conditions = $rule->getConditions();
		//get an existing (or the next) prototype_id
		$prototype_id = $this->Get_Prototype_ID($conditions);

		$insert_conditions = "insert into fraud_condition
			(fraud_rule_id, field_name, field_comparison, prototype_id, field_value)
			values
			";

		$values = array();
		foreach($conditions as $condition)
		{
			$values[] = "({$fraud_rule_id}, '{$condition->FieldName}','{$condition->FieldComparison}',{$prototype_id}, '{$condition->FieldValue}')";
		}

		$insert_conditions .= join(",\n", $values);
		//echo "<!-- {$insert_conditions} -->\n";

		$this->db->query($insert_conditions);

		return $fraud_rule_id;		
	}
	
	public function Update_Rule(FraudRule $rule)
	{
		$update = "update fraud_rule
					set active = {$rule->IsActive},
					exp_date = {$rule->ExpDate},
					modified_agent_id = {$this->server->agent_id}
					where fraud_rule_id = {$rule->FraudRuleID}";
		
		return $this->db->query($update);
	}

	public function Confirm_Rule(FraudRule $rule)
	{
		//this is the only way to confirm a rule
		$update = "update fraud_rule
					set confirmed = {$rule->IsConfirmed},
					modified_agent_id = {$this->server->agent_id}
					where fraud_rule_id = {$rule->FraudRuleID}";
		
		return $this->db->query($update);
		
	}

	public function Save_Proposition($rule_id, $question, $description, $quantify, $file_name, $file_size, $file_type, $attachment)
	{

		$query = "
			insert into fraud_proposition
			(date_created, fraud_rule_id, agent_id, question, description, quantify, file_name, file_size, file_type, attachment)
			values
			(now(), {$rule_id}, {$this->server->agent_id}, '{$question}', '{$description}', '{$quantify}', '{$file_name}', {$file_size}, '{$file_type}', '{$attachment}')
			on duplicate key update
				date_modified = now(),
				agent_id = {$this->server->agent_id},
				question = '{$question}',
				description = '{$description}',
				quantify = '{$quantify}',
				file_name = '{$file_name}',
				file_size = {$file_size},
				file_type = '{$file_type}',
				attachment = '{$attachment}'
		";

		$this->db->query($query);

		return $this->db->lastInsertId();		
	}
	
	public function Get_Rules($type, $active = NULL, $confirmed = NULL)
	{
		$rule_names = array();
	
		$active_sql = '';
		$confirmed_sql = '';
		
		if($active !== NULL)
		{
			$active_sql = "AND fr.active = {$active}";
		}

		if($confirmed !== NULL)
		{
			$confirmed_sql = "AND fr.confirmed = {$confirmed}";
		}
		
		$select = "
			select
				fr.fraud_rule_id,
				(case
				    when fr.active = 0 and fr.confirmed = 0 then concat('* ', fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))
					when fr.active = 0 and fr.confirmed = 1 then concat('*(c) ', fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))
					when fr.active = 1 and fr.confirmed = 1 then concat('(c) ', fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))					
					else concat(fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))
				end) as name
			from
				fraud_rule fr
			left join fraud_proposition fp on (fp.fraud_rule_id = fr.fraud_rule_id)
			where fr.rule_type = '{$type}'
			{$active_sql}
			{$confirmed_sql}
      		order by fr.name
			";

		$result = $this->db->query($select);
		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$rule_names[$row->fraud_rule_id] = $row->name;
		}

		return $rule_names;
	}

	public function Get_Expiring_Rules($date_start, $date_end)
	{
		
		$select = "
			select
				fr.fraud_rule_id,
				if(fr.rule_type = 'FRAUD', 'Fraud', 'High Risk') rule_type,
				fr.name,
				date_format(from_unixtime(fr.exp_date), '%Y/%m/%d') exp_date
			from
				fraud_rule fr
			where fr.active = 1
			and fr.exp_date between unix_timestamp('{$date_start}') and unix_timestamp('{$date_end}')

			UNION
				
			select
				fr.fraud_rule_id,
				if(fr.rule_type = 'FRAUD', 'Fraud', 'High Risk') rule_type,
				fr.name,
				date_format(from_unixtime(fr.exp_date), '%Y/%m/%d') exp_date
			from
				fraud_rule fr
			where fr.active = 1
			and fr.exp_date < now()
      		order by exp_date desc, rule_type, name asc
			limit ". $this->max_display_rows;

		//echo "<!-- {$select} -->";
		
		$result = $this->db->query($select);

		//this stuff is for reporting so put it in an 'all' company
		$rules = array('All' => array());
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$rules['All'][] = $row;
		}

		return $rules;		
	}
	
	public function Get_Rule_And_Conditions($rule_id)
	{
		$rules = array();
		
		$select = "
			select
				r.fraud_rule_id,
				r.date_modified,
				r.date_created,
				concat(fram.name_first, ' ', fram.name_last) modified_agent_name,
				concat(frac.name_first, ' ', frac.name_last) created_agent_name,
				r.active,
				from_unixtime(r.exp_date) exp_date,
				r.rule_type,
				r.confirmed,
				r.name,
				r.comments,
				date_format(fp.date_created, '%Y/%m/%d') prop_date_created,
				fp.fraud_proposition_id,
				concat(fpa.name_first, ' ', fpa.name_last) prop_agent_name,
				fp.question,
				fp.description,
				fp.quantify,
				fp.file_name,
				fp.file_size,
				fp.file_type,
				cond.fraud_condition_id,
				cond.field_name,
				cond.field_comparison,
				cond.field_value,
				cond.prototype_id
			from
				fraud_condition cond
			inner join fraud_rule r on (r.fraud_rule_id = cond.fraud_rule_id)
			inner join agent frac on (frac.agent_id = r.created_agent_id)
			inner join agent fram on (fram.agent_id = r.modified_agent_id)
			left join fraud_proposition fp on (fp.fraud_rule_id = r.fraud_rule_id)
			left join agent fpa on (fp.agent_id = fpa.agent_id)
			where r.fraud_rule_id = {$rule_id}
      		order by r.name, r.fraud_rule_id, cond.fraud_condition_id
			";

		$result = $this->db->query($select);

		$rule = NULL;
		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if($rule == NULL)
			{
				$rule = new FraudRule($row->fraud_rule_id,
									  $row->date_modified,
									  $row->date_created,
									  $row->active,
									  $row->exp_date,
									  $row->rule_type,
									  $row->confirmed,
									  $row->name,
									  $row->comments);

				//these are for display only
				$rule->setModifiedAgentName($row->modified_agent_name);
				$rule->setCreatedAgentName($row->created_agent_name);

				$rule->setProposition(new FraudProposition($row->fraud_proposition_id,
														   $row->prop_date_created,
														   $row->prop_agent_name,
														   $row->question,
														   $row->description,
														   $row->quantify,
														   $row->file_name,
														   $row->file_size,
														   $row->file_type));
			}

			$rule->addCondition(new FraudCondition($row->field_name, $row->field_comparison, $row->field_value));

		}

		//echo "<!-- ", print_r($rule, TRUE), " -->";
		return $rule;		
	}

	public function Add_Rules_To_App($application_id, $rule_ids)
	{
		$insert = "insert ignore into fraud_application
				  (fraud_rule_id, application_id)
				  values
				  ";

		$values = array();
		foreach($rule_ids as $fraud_rule_id)
		{
			$values[] = "({$fraud_rule_id}, {$application_id})";
		}

		if(!empty($values))
		{
			$query = $insert . join(",\n", $values);
			//echo "<!-- {$query} -->";
			return $this->db->query($query);
		}
	}

	public function Remove_Rule_Type_From_App($application_id, $type)
	{
		$query = "delete fa from fraud_application fa
					inner join fraud_rule fr on (fr.fraud_rule_id = fa.fraud_rule_id)
					where application_id = {$application_id}
					and fr.rule_type = '{$type}'";
		
		return $this->db->query($query);
	}

	public function Get_Rule_Names($application_ids)
	{
		$query = "select
					fa.application_id,
					fr.name
					from fraud_rule fr
					inner join fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
					where fa.application_id in (" . join(",", $application_ids) . ")
					order by fa.application_id
			";

		$result = $this->db->query($query);
		
		$rules = array();		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if(empty($rules[$row->application_id]))
			{
				$rules[$row->application_id] = $row->name;
			}
			else
			{
				$rules[$row->application_id] .= ";" . $row->name;				
			}
		}
		return $rules;
	}

	//ALL == ALL: verification, underwriting, fraud, high_risk
	//FRAUD == fraud
	//RISK == high_risk
	public function Get_Queue_Apps($type = 'ALL', $conditions = NULL)
	{
		$statii = array('ALL' => array('verification', 'underwriting', 'fraud', 'high_risk', 'withdrawn'),
						 FraudRule::RULE_TYPE_FRAUD => array('fraud'),
						 FraudRule::RULE_TYPE_RISK => array('high_risk'));

		$where = "";
		if($conditions)
		{
			$where = $this->Get_Rule_Where($conditions);
		}
		
		$queries = array();
		foreach($this->companies as $company_id => $company_info)
		{
			if($company_id < 100) //this is a fantastic hack to skip the "UFC - Archive" company -- THANKS DOUG!
                        {
                                $select_start = "
                                        SELECT
                                        " . self::$fraud_fields . "
                                        FROM
                         application ap
                    INNER JOIN application_status_flat asf on (asf.application_status_id = ap.application_status_id)
                                        INNER JOIN company c on (c.company_id = ap.company_id)
                    LEFT JOIN card ON card.login_id = ap.login_id
                    WHERE ap.company_id = {$company_id}
						and
						(";
				
				$select_end = "
						)
                        AND card.card_number IS NULL
						{$where}
					";				

				//include 
				if(!Has_Batch_Closed($company_id))
				{
					$queries[] = $select_start .
						" asf.level1 = 'approved' AND asf.level2 = 'servicing' AND asf.level3='customer' AND asf.level4='*root' " .
						$select_end;
				}

				foreach($statii[$type] as $status)
				{
					$queries[] = $select_start . $this->Get_Queue_Search($status) . $select_end;
				}
			}
		}

		$big_query = join("\nUNION\n", $queries);

		//echo "<!-- {$big_query} -->";

		$result = $this->db->query($big_query);

		$apps = array();
		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$app = new ECashApplication();
			foreach($row as $column => $value)
			{
				$app->$column = $value;
			}
			
			$apps[] = $app;
		}

		//print_r($apps); exit;
		
		//echo "<!-- retrieved ", count($apps), " apps for rule comparison -->\n";
                return $apps;           
        }

        public function Get_Fraud_App($application_id)
        {
                $select = "
                    SELECT
                         " . self::$fraud_fields . "
                    FROM
                         application ap
                    INNER JOIN application_status_flat asf on (asf.application_status_id = ap.application_status_id)
                    INNER JOIN company c on (c.company_id = ap.company_id)
                    WHERE ap.application_id = {$application_id}";

                //echo "<-- {$select} -->";
                $result = $this->db->query($select);
                $row = $result->fetch(PDO::FETCH_OBJ);
                $app = new ECashApplication();
                foreach($row as $column => $value)
                {
                        $app->$column = $value;
                }
                return $app;            
        }

        private function Get_Queue_Search($type)
        {
                return " asf.level1 = '{$type}' AND asf.level2='applicant' AND asf.level3='*root' ";
	}

   	private function Get_Rule_Where($conditions)
	{
		$where = "";
		foreach($conditions as $condition)
		{
			$where .= "and ap.".$condition->getFieldName()." ";
			$where .= $condition->formatSearch();
			$where .= "\n";
		}
		return $where;
	}
	

	private function Get_Prototype_ID($conditions)
	{
		$count = count($conditions);
		
		$select = "
				select c0.prototype_id
				from fraud_condition c0";

		$where = "
				where c0.field_name = '{$conditions[0]->FieldName}' 
				and c0.field_comparison = '{$conditions[0]->FieldComparison}'
				group by prototype_id";
		
		for($i = 1; $i < count($conditions); $i++)
		{
			$select .= "
					join
					(
						select prototype_id
						from fraud_condition
						where field_name = '{$conditions[$i]->FieldName}'
						and field_comparison = '{$conditions[$i]->FieldComparison}'
						group by prototype_id
					) c{$i} on (c{$i}.prototype_id = c0.prototype_id)";
		}
		
		$select .= "
				join
				(
					select prototype_id
					from
					(
						select prototype_id, field_name, field_comparison 
						from fraud_condition
						group by prototype_id, field_name, field_comparison
					) cnt_grp
					group by prototype_id
					having count(*) = {$count}
				) cnt on (cnt.prototype_id = c0.prototype_id)";

		$select .= $where;

		//echo $select;

		$result = $this->db->query($select);
		$row = $result->fetch(PDO::FETCH_OBJ);

                if(empty($row))
                {
                        $query = "select ifnull(max(prototype_id)+1, 1) as prototype_id from fraud_condition";
                        $result = $this->db->query($query);
                        $row = $result->fetch(PDO::FETCH_OBJ);
                }

		return $row->prototype_id;
	}
}

?>
