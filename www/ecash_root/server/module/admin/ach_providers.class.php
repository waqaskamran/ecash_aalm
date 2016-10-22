<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';
require_once SQL_LIB_DIR . 'util.func.php';

class AchProviders
{
        private $transport;
        private $request;
        private $server;

        public function __construct(Server $server, $request)
        {
                $this->server = $server;
                $this->transport = ECash::getTransport();
		$this->request = $request;
        }

        public function Display()
        {
                $data['ach_providers'] = $this->fetchAchProviders();
		$data['states'] = $this->fetchStates();
                $data['app_statuses'] = $this->fetchAppStatuses();
                $data['abas'] = $this->fetchABAs();
                ECash::getTransport()->Set_Data($data);

                return TRUE;
        }

	public function addAchProvider()
	{
		$agent_id = intval(ECash::getAgent()->getAgentId());

		$ach_provider_name = trim($this->request->ach_provider_name);
		$ach_provider_name = preg_replace('/\s+/', ' ', $ach_provider_name);
		$ach_provider_name = str_replace(array("'","`"), "", $ach_provider_name);

		$ach_provider_status = $this->request->ach_provider_status;
		$ach_batch_type = $this->request->ach_provider_batch_type;
		$credit_percentage = $this->request->ach_provider_credit;
		$debit_percentage = $this->request->ach_provider_debit;
		$ach_batch_time = $this->request->ach_batch_time;

		$ach_provider_name_short = str_replace(" ", "_", $ach_provider_name);
		$ach_provider_name_short = str_replace(array(" ","'","`"), "", $ach_provider_name_short);
		$ach_provider_name_short = strtolower($ach_provider_name_short);
		$ach_provider_name_short = substr($ach_provider_name_short, 0, 25);

		$flag_name = "Flag to know if application is assigned to " . $ach_provider_name;

		$state_array = $this->request->ach_provider_states;
		$state_string = implode(",", $this->request->ach_provider_states);

		if (
			(!empty($ach_provider_status))
			&& (!empty($ach_provider_name))
			&& (!empty($ach_provider_name_short))
		)
		{
			$db = ECash::getMasterDb();
			
			//ACH Provider
			$sql = "
				INSERT IGNORE INTO
					ach_provider
				SET
					date_created = NOW(),
					date_modified = NOW(),
					active_status = '{$ach_provider_status}',
					name = '{$ach_provider_name}',
					name_short = '{$ach_provider_name_short}',
					agent_id = {$agent_id}
			";
			$db->query($sql);

			$query = "
				SELECT
					ach_provider_id
				FROM
					ach_provider
				WHERE
					name_short = '{$ach_provider_name_short}'
			";
			$result = $db->query($query);
			$row = $result->fetch(PDO::FETCH_OBJ);
			$ach_provider_id = intval($row->ach_provider_id);

			//ACH Provider Config
			//ACH Batch Type
			$sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_batch_type',
					config_value = '{$ach_batch_type}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);

			//credit_percentage
			$sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'credit_percentage',
					config_value = '{$credit_percentage}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);

			//debit_percentage
			$sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'debit_percentage',
					config_value = '{$debit_percentage}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);
                        
                        ///////////////////////Blocked Fund Dates
                        if (
                                (!empty($this->request->ach_provider_fund_date_start))
                                || (!empty($this->request->ach_provider_fund_date_end))
                        )
                        {
                                if (empty($this->request->ach_provider_fund_date_start))
                                {
                                        $ach_provider_fund_date_start = "1970-01-01";
                                }
                                else
                                {
                                        $ach_provider_fund_date_start = date("Y-m-d", strtotime($this->request->ach_provider_fund_date_start));
                                }
                                
                                if (empty($this->request->ach_provider_fund_date_end))
                                {
                                        $ach_provider_fund_date_end = "2037-01-01";
                                }
                                else
                                {
                                        $ach_provider_fund_date_end = date("Y-m-d", strtotime($this->request->ach_provider_fund_date_end));
                                }
                                
                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_fund_dates_start',
					config_value = '{$ach_provider_fund_date_start}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);
                                
                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_fund_dates_end',
					config_value = '{$ach_provider_fund_date_end}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);
                        }
                        else
                        {
                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_fund_dates_start',
					config_value = '',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);

                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_fund_dates_end',
					config_value = '',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);
                        }
                        
                        //Blocked Eventdays, Weekdays
                        $weekdays_string = implode(",", $this->request->ach_provider_event_date_weekdays);
			$sql = "
				INSERT IGNORE INTO
				 	ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_event_dates_weekdays',
					config_value = '{$weekdays_string}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);
                        
                        //Blocked Eventdays, Monthdays
                        $monthdays_string = implode(",", $this->request->ach_provider_event_date_monthdays);
			$sql = "
				INSERT IGNORE INTO
				 	ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_event_dates_monthdays',
					config_value = '{$monthdays_string}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);
                        
                        ///////////////////////Blocked Event Dates
                        if (
                                (!empty($this->request->ach_provider_event_date_start))
                                || (!empty($this->request->ach_provider_event_date_end))
                        )
                        {
                                if (empty($this->request->ach_provider_event_date_start))
                                {
                                        $ach_provider_event_date_start = date("Y-m-d", time());
                                }
                                else
                                {
                                        $ach_provider_event_date_start = date("Y-m-d", strtotime($this->request->ach_provider_event_date_start));
                                }
                                
                                if (empty($this->request->ach_provider_event_date_end))
                                {
                                        $ach_provider_event_date_end = "2037-01-01";
                                }
                                else
                                {
                                        $ach_provider_event_date_end = date("Y-m-d", strtotime($this->request->ach_provider_event_date_end));
                                }
                                
                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_event_dates_start',
					config_value = '{$ach_provider_event_date_start}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);
                                
                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_event_dates_end',
					config_value = '{$ach_provider_event_date_end}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);
                        }
                        else
                        {
                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_event_dates_start',
					config_value = '',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);
                                
                                $sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_event_dates_end',
					config_value = '',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
                                ";
                                $db->query($sql);
                        }
                        
                        //New/React
                        $ach_batch_new_react = $this->request->ach_batch_new_react;
			$sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_new_react',
					config_value = '{$ach_batch_new_react}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);
                        
                        //Blocked Failures
                        $failures_string = implode(",", $this->request->ach_provider_failures);
			$sql = "
				INSERT IGNORE INTO
				 	ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_failures',
					config_value = '{$failures_string}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);

			//ach_batch_time
			$sql = "
				INSERT IGNORE INTO
					ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_batch_time',
					config_value = '{$ach_batch_time}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);

			//Flag Type
			$sql = "
				INSERT IGNORE INTO
					flag_type
				SET
					date_modified=NOW(),
					date_created=NOW(),
					active_status='active',
					name='{$flag_name}',
					name_short='{$ach_provider_name_short}'
			";
			$db->query($sql);

			//Blacklisted States
			$sql = "
				INSERT IGNORE INTO
				 	ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_states',
					config_value = '{$state_string}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);
                        
                        //Blocked App Statuses
                        $app_statuses_array = $this->request->ach_provider_app_statuses;
                        $app_status_string = implode(",", $app_statuses_array);
                        
                        $sql = "
				INSERT IGNORE INTO
				 	ach_provider_config
				SET
					ach_provider_id = {$ach_provider_id},
					config_key = 'ach_app_statuses',
					config_value = '{$app_status_string}',
					date_modified = NOW(),
					date_created = NOW(),
					agent_id = {$agent_id}
			";
			$db->query($sql);
                        
                        ///////////////////////Blocked ABAs
                        // Add (block) ABA
                        $block_aba = trim($this->request->ach_provider_add_aba);
                        if (!empty($block_aba) && $block_aba != "")
                        {
                                $query = "
                                        SELECT
                                                ach_provider_bank_aba_id,
                                                active_status
                                        FROM
                                                ach_provider_bank_aba
                                        WHERE
                                                ach_provider_id = {$ach_provider_id}
                                        AND
                                                bank_aba = '{$block_aba}'
                                ";
                                $result = $db->query($query);
                                $row = $result->fetch(PDO::FETCH_OBJ);
                                $ach_provider_bank_aba_id = intval($row->ach_provider_bank_aba_id);
                                
                                if (empty($ach_provider_bank_aba_id))
                                {
                                        $sql = "
                                        INSERT IGNORE INTO
                                                ach_provider_bank_aba
                                        SET
                                                date_modified = NOW(),
                                                date_created = NOW(),
                                                ach_provider_id = {$ach_provider_id},
                                                bank_aba = '{$block_aba}',
                                                active_status='active',
                                                agent_id = {$agent_id}
                                        ";
                                        $db->query($sql);    
                                }
                                else
                                {
                                        $sql = "
                                        UPDATE
                                                ach_provider_bank_aba
                                        SET
                                                active_status='active'
                                        WHERE
                                                ach_provider_bank_aba_id = {$ach_provider_bank_aba_id}
                                        ";
                                        $db->query($sql);
                                }
                        }

			//Log
			$log = ECash::getLog('ach');
			$log->Write("[Agent:{$agent_id}] Added ACH Provider. Name: {$this->request->ach_provider_name}, Status: {$ach_provider_status}, ACH Batch Type: {$ach_batch_type}, Credit: {$credit_percentage}, Debit: {$debit_percentage}, Fund Dates Start: {$ach_provider_fund_date_start}, Fund Dates End: {$ach_provider_fund_date_end}, Event Dates Weekdays: {$weekdays_string}, Event Dates Monthdays: {$monthdays_string}, Event Dates Start: {$ach_provider_event_date_start}, Event Dates End: {$ach_provider_event_date_end}, States: {$state_string}, App Statuses: {$app_status_string}, Block ABA: {$block_aba}, New React: {$ach_batch_new_react}, Failures: {$failures_string}, ACH Batch Time: {$ach_batch_time}.");
		}
	}

	public function editAchProvider()
	{
		$agent_id = intval(ECash::getAgent()->getAgentId());
		$ach_provider_id = intval($this->request->ach_provider_id);

		$ach_provider_name = trim($this->request->ach_provider_name);
		$ach_provider_name = preg_replace('/\s+/', ' ', $ach_provider_name);
		$ach_provider_name = str_replace(array("'","`"), "", $ach_provider_name);

		$ach_provider_status = $this->request->ach_provider_status;
		$ach_batch_type = $this->request->ach_provider_batch_type;
		$credit_percentage = $this->request->ach_provider_credit;
		$debit_percentage = $this->request->ach_provider_debit;
		$ach_batch_time = $this->request->ach_batch_time;

		$state_array = $this->request->ach_provider_states;
		$state_string = implode(",", $this->request->ach_provider_states);

		if (
			(!empty($ach_provider_id))
			&& (!empty($ach_provider_name))
		)
		{
			 $db = ECash::getMasterDb();

			//Name
			$sql = "
			 	UPDATE
					ach_provider
				SET
					name = '{$ach_provider_name}',
					active_status = '{$ach_provider_status}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
			";
			$db->query($sql);

			//ACH Batch Type
			$sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$ach_batch_type}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_batch_type'
			";
			$db->query($sql);

			// Credit %
			$sql = "
			 	UPDATE
					ach_provider_config
				SET
					config_value = '{$credit_percentage}',
					agent_id = {$agent_id}
				WHERE
				        ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'credit_percentage'
			";
			$db->query($sql);

			//Debit %
			$sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$debit_percentage}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'debit_percentage'
			";
			$db->query($sql);

                        ///////////////////////Blocked Fund Dates
                        if (
                                (!empty($this->request->ach_provider_fund_date_start))
                                || (!empty($this->request->ach_provider_fund_date_end))
                        )
                        {
                                if (empty($this->request->ach_provider_fund_date_start))
                                {
                                        $ach_provider_fund_date_start = "1970-01-01";
                                }
                                else
                                {
                                        $ach_provider_fund_date_start = date("Y-m-d", strtotime($this->request->ach_provider_fund_date_start));
                                }
                                
                                if (empty($this->request->ach_provider_fund_date_end))
                                {
                                        $ach_provider_fund_date_end = "2037-01-01";
                                }
                                else
                                {
                                        $ach_provider_fund_date_end = date("Y-m-d", strtotime($this->request->ach_provider_fund_date_end));
                                }
                                
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$ach_provider_fund_date_start}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_fund_dates_start'
                                ";
                                $db->query($sql);
                                
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$ach_provider_fund_date_end}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_fund_dates_end'
                                ";
                                $db->query($sql);
                        }
                        else
                        {
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_fund_dates_start'
                                ";
                                $db->query($sql);
                                
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_fund_dates_end'
                                ";
                                $db->query($sql);
                        }
                        
                        //Blocked Event Dates, Weekdays
                        $weekdays_string = implode(",", $this->request->ach_provider_event_date_weekdays);
			$sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$weekdays_string}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_event_dates_weekdays'
			";
			$db->query($sql);
                        
                        //Blocked Event Dates, Monthdays
                        $monthdays_string = implode(",", $this->request->ach_provider_event_date_monthdays);
			$sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$monthdays_string}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_event_dates_monthdays'
			";
			$db->query($sql);
                        
                        ///////////////////////Blocked Event Dates
                        if (
                                (!empty($this->request->ach_provider_event_date_start))
                                || (!empty($this->request->ach_provider_event_date_end))
                        )
                        {
                                if (empty($this->request->ach_provider_event_date_start))
                                {
                                        $ach_provider_event_date_start = date("Y-m-d", time());
                                }
                                else
                                {
                                        $ach_provider_event_date_start = date("Y-m-d", strtotime($this->request->ach_provider_event_date_start));
                                }
                                
                                if (empty($this->request->ach_provider_event_date_end))
                                {
                                        $ach_provider_event_date_end = "2037-01-01";
                                }
                                else
                                {
                                        $ach_provider_event_date_end = date("Y-m-d", strtotime($this->request->ach_provider_event_date_end));
                                }
                                
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$ach_provider_event_date_start}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_event_dates_start'
                                ";
                                $db->query($sql);
                                
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$ach_provider_event_date_end}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_event_dates_end'
                                ";
                                $db->query($sql);
                        }
                        else
                        {
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_event_dates_start'
                                ";
                                $db->query($sql);
                                
                                $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_event_dates_end'
                                ";
                                $db->query($sql);
                        }
                        
                        ///////////////////////Blocked ABAs
                        // Add (block) ABA
                        $block_aba = trim($this->request->ach_provider_add_aba);
                        if (!empty($block_aba) && $block_aba != "")
                        {
                                $query = "
                                        SELECT
                                                ach_provider_bank_aba_id,
                                                active_status
                                        FROM
                                                ach_provider_bank_aba
                                        WHERE
                                                ach_provider_id = {$ach_provider_id}
                                        AND
                                                bank_aba = '{$block_aba}'
                                ";
                                $result = $db->query($query);
                                $row = $result->fetch(PDO::FETCH_OBJ);
                                $ach_provider_bank_aba_id = intval($row->ach_provider_bank_aba_id);
                                
                                if (empty($ach_provider_bank_aba_id))
                                {
                                        $sql = "
                                        INSERT IGNORE INTO
                                                ach_provider_bank_aba
                                        SET
                                                date_modified = NOW(),
                                                date_created = NOW(),
                                                ach_provider_id = {$ach_provider_id},
                                                bank_aba = '{$block_aba}',
                                                active_status='active',
                                                agent_id = {$agent_id}
                                        ";
                                        $db->query($sql);    
                                }
                                else
                                {
                                        $sql = "
                                        UPDATE
                                                ach_provider_bank_aba
                                        SET
                                                active_status='active'
                                        WHERE
                                                ach_provider_bank_aba_id = {$ach_provider_bank_aba_id}
                                        ";
                                        $db->query($sql);
                                }
                        }
                        
                        //Delete (unblock) ABAs
                        if (count($this->request->ach_provider_delete_abas) > 0)
                        {
                                $unblock_abas_string = implode(",", $this->request->ach_provider_delete_abas);
                                foreach ($this->request->ach_provider_delete_abas as $unblock_aba)
                                {
                                        $sql = "
                                        UPDATE
                                                ach_provider_bank_aba
                                        SET
                                                active_status='inactive'
                                        WHERE
                                                ach_provider_id = {$ach_provider_id}
                                        AND
                                                bank_aba = '{$unblock_aba}'
                                        ";
                                        $db->query($sql);   
                                }
                        }
                        //////////////////////////////
                        
                        //New React
                        $ach_batch_new_react = $this->request->ach_batch_new_react;
			$sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$ach_batch_new_react}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_new_react'
			";
			$db->query($sql);
                        
                        //Blocked Failures
                        $failures_string = implode(",", $this->request->ach_provider_failures);
                        $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$failures_string}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_failures'
			";
			$db->query($sql);
                        
			//ACH Batch Time
			$sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$ach_batch_time}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_batch_time'
			";
			$db->query($sql);

			//Blocked States
			$sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$state_string}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_states'
			";
			$db->query($sql);
                        
                        // Blocked App Statuses
                        $app_status_array = $this->request->ach_provider_app_statuses;
                        $app_status_string = implode(",", $app_status_array);
                        
                        $sql = "
				UPDATE
					ach_provider_config
				SET
					config_value = '{$app_status_string}',
					agent_id = {$agent_id}
				WHERE
					ach_provider_id = {$ach_provider_id}
				AND
					config_key = 'ach_app_statuses'
			";
			$db->query($sql);

			//Log
			$log = ECash::getLog('ach');
			$log->Write("[Agent:{$agent_id}] Edited ACH Provider. Name: {$this->request->ach_provider_name}, Status: {$ach_provider_status}, ACH Batch Type: {$ach_batch_type}, Credit: {$credit_percentage}, Debit: {$debit_percentage}, Fund Dates Start: {$ach_provider_fund_date_start}, Fund Dates End: {$ach_provider_fund_date_end}, Event Dates Weekdays: {$weekdays_string}, Event Dates Monthdays: {$monthdays_string}, Event Dates Start: {$ach_provider_event_date_start}, Event Dates End: {$ach_provider_event_date_end}, States: {$state_string}, App Statuses: {$app_status_string}, Block ABA: {$block_aba}, Unblock ABAs: {$unblock_abas_string}, New React: {$ach_batch_new_react}, Failures: {$failures_string}, ACH Batch Time: {$ach_batch_time}.");
		}
	}

	public function fetchAchProviders()
	{
		static $ach_provider_list;

		if(empty($ach_provider_list))
		{
			$query = "
				SELECT
					pr.date_created,
					pr.ach_provider_id,
					pr.name_short,
					pr.name,
					pr.active_status,
                                        pc_batch_type.config_value AS ach_batch_type,
					pc_c.config_value AS credit_percentage,
					pc_d.config_value AS debit_percentage,
                                        pc_fund_dates_start.config_value AS ach_fund_dates_start,
                                        pc_fund_dates_end.config_value AS ach_fund_dates_end,
					pc_states.config_value AS states,
                                        pc_app_statuses.config_value AS app_statuses,
					pc_ach_time.config_value AS ach_batch_time,
                                        pc_event_dates_weekdays.config_value AS ach_event_dates_weekdays,
                                        pc_event_dates_monthdays.config_value AS ach_event_dates_monthdays,
                                        pc_event_dates_start.config_value AS ach_event_dates_start,
                                        pc_event_dates_end.config_value AS ach_event_dates_end,
                                        pc_new_react.config_value AS ach_new_react,
                                        pc_failures.config_value AS failures
				FROM
					ach_provider AS pr
				LEFT JOIN
					ach_provider_config AS pc_c ON (pc_c.ach_provider_id = pr.ach_provider_id
					AND pc_c.config_key = 'credit_percentage')
				LEFT JOIN
					ach_provider_config AS pc_d ON (pc_d.ach_provider_id = pr.ach_provider_id
					AND pc_d.config_key = 'debit_percentage')
				LEFT JOIN
					ach_provider_config AS pc_ach_time ON (pc_ach_time.ach_provider_id = pr.ach_provider_id
					AND pc_ach_time.config_key = 'ach_batch_time')
                                LEFT JOIN
					ach_provider_config AS pc_fund_dates_start ON (pc_fund_dates_start.ach_provider_id = pr.ach_provider_id
					AND pc_fund_dates_start.config_key = 'ach_fund_dates_start')
                                LEFT JOIN
					ach_provider_config AS pc_fund_dates_end ON (pc_fund_dates_end.ach_provider_id = pr.ach_provider_id
					AND pc_fund_dates_end.config_key = 'ach_fund_dates_end')
				LEFT JOIN
					ach_provider_config AS pc_states ON (pc_states.ach_provider_id = pr.ach_provider_id
					AND pc_states.config_key = 'ach_states')
                                LEFT JOIN
					ach_provider_config AS pc_app_statuses ON (pc_app_statuses.ach_provider_id = pr.ach_provider_id
					AND pc_app_statuses.config_key = 'ach_app_statuses')
				LEFT JOIN
					ach_provider_config AS pc_batch_type ON (pc_batch_type.ach_provider_id = pr.ach_provider_id
					AND pc_batch_type.config_key = 'ach_batch_type')
                                LEFT JOIN
					ach_provider_config AS pc_event_dates_weekdays ON (pc_event_dates_weekdays.ach_provider_id = pr.ach_provider_id
					AND pc_event_dates_weekdays.config_key = 'ach_event_dates_weekdays')
                                LEFT JOIN
					ach_provider_config AS pc_event_dates_monthdays ON (pc_event_dates_monthdays.ach_provider_id = pr.ach_provider_id
					AND pc_event_dates_monthdays.config_key = 'ach_event_dates_monthdays')
                                LEFT JOIN
					ach_provider_config AS pc_event_dates_start ON (pc_event_dates_start.ach_provider_id = pr.ach_provider_id
					AND pc_event_dates_start.config_key = 'ach_event_dates_start')
                                LEFT JOIN
					ach_provider_config AS pc_event_dates_end ON (pc_event_dates_end.ach_provider_id = pr.ach_provider_id
					AND pc_event_dates_end.config_key = 'ach_event_dates_end')
                                LEFT JOIN
					ach_provider_config AS pc_new_react ON (pc_new_react.ach_provider_id = pr.ach_provider_id
					AND pc_new_react.config_key = 'ach_new_react')
                                LEFT JOIN
					ach_provider_config AS pc_failures ON (pc_failures.ach_provider_id = pr.ach_provider_id
					AND pc_failures.config_key = 'ach_failures')
				ORDER BY pr.ach_provider_id
			";

			$db = ECash::getMasterDb();
			$st = $db->query($query);
			$ach_provider_list = $st->fetchAll(PDO::FETCH_OBJ);
		}

		return $ach_provider_list;
	}

	public function fetchStates()
	{
		static $state_list;
		if(empty($state_list))
		{
			$query = "
				SELECT DISTINCT state FROM zip_tz ORDER BY state
			";
			$db = ECash::getMasterDb();
			$st = $db->query($query);
			$state_list = $st->fetchAll(PDO::FETCH_OBJ);
		}

		return $state_list;
	}
        
        public function fetchAppStatuses()
	{
		static $app_status_list;
		if(empty($app_status_list))
		{
			$query = "
				SELECT
                                        application_status_id,
                                        name
                                FROM
                                        application_status
                                WHERE
                                        active_status = 'active'
				AND
					application_status_parent_id IS NOT NULL
                                ORDER BY
                                        name
			";
			$db = ECash::getMasterDb();
			$st = $db->query($query);
			$app_status_list = $st->fetchAll(PDO::FETCH_OBJ);
		}

		return $app_status_list;
	}
        
        public function fetchABAs()
	{
		$providers = array();
		$pr_model = ECash::getFactory()->getModel('AchProvider');
		$pr_array = $pr_model->loadAllBy(array('active_status' => 'active',));
		foreach ($pr_array as $pr)
		{
			$providers[] = $pr->ach_provider_id;
		}

		$combine_array = array();
		foreach ($providers as $ach_provider_id)
		{
			$aba_list = array();

			$pr_aba_model = ECash::getFactory()->getModel('AchProviderBankAba');
			$pr_aba_array = $pr_aba_model->loadAllBy(array('active_status' => 'active',
									'ach_provider_id' => $ach_provider_id,
			));
			foreach ($pr_aba_array as $aba)
			{
				$aba_list[] = $aba->bank_aba;
			}

			$combine_array[$ach_provider_id] = $aba_list;
		}
		
		return $combine_array;
	}
}

?>
