<?php
require_once(SERVER_CODE_DIR . "bureau_query.class.php");
require_once(SERVER_CODE_DIR . "loan_data.class.php");
require_once(SQL_LIB_DIR . "loan_actions.func.php");
require_once(SERVER_CODE_DIR . "paydate_handler.class.php");

function Main()
{
	global $server;
	//1. get apps that are confirmed or approved
	//2. check if they have a loan document
	//3. check bureau inquiry for A4 value
	//4. move to prefund and build a schedule
	$company = ECash::getFactory()->getModel('Company');
	$company->loadBy(array('name_short' => strtolower(ECash::getConfig()->COMPANY_NAME_SHORT))); 

	$server->company_id = $company->company_id;
	$company_id = $company->company_id;
	$Loan_Doc_Template = 'Loan Document';
	
	//asm 87
	$supplemental_name = array("supplemental security income","ssi","s s i","s.s.i","social security ssi");

	$retirement_name = array("social security","soc. sec.","social security benefits","social security retirement","ssa","ssd","ssdi",
				 "ss","s s","s/s","s s a","s s d","s s d i","s s disability","s.s.d","s.s.d.","s.s.d.i","s.s.d.i.",
				 "s.s.","s.s.a.","s.s benefits","s.s. disability","ss income","s s income","s.s. income");
			
	$supplemental_retirement_name = array_merge($supplemental_name,$retirement_name);
	////

	// Confirmed 9, ('queued::servicing::customer::*root') - does not exist
	$status_list = "
		('queued::verification::applicant::*root'), ('queued::servicing::customer::*root')
	";
	
	//determine document_list_id of Loan Document
	$document_list_model = ECash::getFactory()->getModel('DocumentList');
    $document_list_model->loadBy(array('name_short' => 'Loan Document', 'company_id' => $company_id));
    $document_list_id = $document_list_model->document_list_id;

	/*
	$mssql_query = "
		DECLARE @STATUS_LIST table_type_varchar256;
		INSERT INTO @STATUS_LIST VALUES {$status_list};
		EXEC sp_commercial_authoritative_for_status @STATUS_LIST
	";
	$app_service_result = ECash::getAppSvcDB()->query($mssql_query);
	*/
	$db = ECash::getMasterDb();
	$mssql_query = "
		SELECT ap.application_id
		FROM application AS ap
		JOIN application_status_flat AS asf ON (asf.application_status_id = ap.application_status_id)
		WHERE asf.level0 IN ('queued','dequeued')
		AND asf.level1 IN ('verification','underwriting')
		AND ap.company_id = ".$company_id.";";
	$result_mssql_query = $db->query($mssql_query);

	$bureau_query = new Bureau_Query(ECash::getMasterdb(), ECash::getlog());
	$loan_data = new Loan_Data($server);

	$fund = FALSE;
	$autofund_eligible = FALSE;
	//while ($row = $app_service_result->fetch(DB_IStatement_1::FETCH_OBJ))
	while ($row = $result_mssql_query->fetch(PDO::FETCH_OBJ))
	{
		$app_id = $row->application_id;
		$app = ECash::getApplicationById($app_id);
		if(!$app->exists())
			continue;
		//Do not auto-fund if a VERIFY loan action exist
		$flags = $app->getFlags();
		if($flags->get('autofund_overridden')) continue;

		$sql = "
			SELECT lah.loan_action_history_id
			FROM loan_action_history AS lah
			JOIN loan_actions AS la ON (la.loan_action_id = lah.loan_action_id)
			WHERE lah.application_id = {$app_id}
			-- AND lah.date_created > DATE_SUB(DATE(NOW()), INTERVAL 1 DAY)
			AND
			(
				(la.type='PRESCRIPTION' AND la.name_short LIKE 'VERIFY%')
				OR
				la.type='CS_VERIFY'
			)
			ORDER BY lah.loan_action_history_id DESC
			LIMIT 1
		";
		$result1 = $db->query($sql);
		$row1 = $result1->fetch(PDO::FETCH_OBJ);
		if (isset($row1->loan_action_history_id) && ($row1->loan_action_history_id > 0))
		{
			$app->getComments()->add('Contains VERIFY loan action. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());

			if(!$flags->get('autofund_overridden'))
			{
				$flags->set('autofund_overridden');
			}
		
			continue;
		}

		//Income Frequency
		if ($app->income_frequency == "twice_monthly")
		{
			if (
				(!($app->day_of_month_1 == 15 && in_array($app->day_of_month_2, array(28,29,30,31,32))))
				&&
				(!($app->day_of_month_1 == 1 && $app->day_of_month_2 ==15))
				&&
				(!($app->day_of_month_1 == 5 && $app->day_of_month_2 ==20))
				&&
				(!($app->day_of_month_1 == 7 && $app->day_of_month_2 ==22))
			)
			{
				$loan_action_id = Get_Loan_Action_ID('VERIFY_INCOME_FREQUENCY_CS');
				$app_status = $app->getStatus();
				Insert_Loan_Action($app_id, $loan_action_id, $app_status->getApplicationStatus(), ECash::getAgent()->getAgentId());
				
				continue;
			}
		}
		
		//Email
		$email = strtolower($app->email);

		if (
			(strpos($email, "@charter.net") !== FALSE)
			||
			(strpos($email, "@comcast.com") !== FALSE)
			||
			(strpos($email, "@aol.com") !== FALSE)
		)
		{
			$loan_action_needed = TRUE;
		}
		else
		{
			if (
				strpos($email, "@") !== FALSE
				&&
				(
					strpos($email, ".com") !== FALSE
				       || strpos($email, ".org") !== FALSE
				       || strpos($email, ".net") !== FALSE
				       || strpos($email, ".int") !== FALSE
				       || strpos($email, ".edu") !== FALSE
				       || strpos($email, ".gov") !== FALSE
				       || strpos($email, ".mil") !== FALSE
				       || strpos($email, ".us") !== FALSE
				)
			)
			{
				$loan_action_needed = FALSE;
			}
			else
			{
				$loan_action_needed = TRUE;
			}
		}
		
		if ($loan_action_needed)
		{
			$loan_action_id = Get_Loan_Action_ID('VERIFY_EMAIL');
			$app_status = $app->getStatus();
			Insert_Loan_Action($app_id, $loan_action_id, $app_status->getApplicationStatus(), ECash::getAgent()->getAgentId());
			
			continue;
		}
		
		//asm 87, Social Security income frequency
		if ($app->income_source == "benefits")
		{
			$employer_name = strtolower($app->employer_name);
			
			if (
				in_array($employer_name, $supplemental_retirement_name)
				||  (strpos($employer_name, "social security") !== FALSE)
			)
			{
				$dob_last_two = substr($app->dob,8);

				if (
					(
						$app->income_frequency != "monthly"
					)
					||
					(
						!in_array($app->paydate_model, array('dm','wdw'))
					)
					||
					(
						($app->paydate_model == 'wdw') && ($app->day_of_week != 'wed')
					)
					|| //SSI, both SSI and Retirement/Disability
					(
						($app->paydate_model == 'dm')
						&&
						(
							!in_array($app->day_of_month_1, array(1,3))
						)
					)
					|| //Retirement/Disability
					(
						($app->paydate_model == 'wdw') && ($app->day_of_week == 'wed')
						&&
						(
							(
								$dob_last_two < "11"
								&& $app->week_1 != 2
							)
							||
							(
								$dob_last_two > "10"
								&& $dob_last_two < "21"
								&& $app->week_1 != 3
							)
							||
							(
								$dob_last_two > "20"
								&& $app->week_1 != 4
							)
						)
					)
				)
				{
					$loan_action_id = Get_Loan_Action_ID('VERIFY_SSA_INCOME_FREQ_CS');
					$app_status = $app->getStatus();
					Insert_Loan_Action($app_id, $loan_action_id, $app_status->getApplicationStatus(), ECash::getAgent()->getAgentId());
					
					continue;
				}
			}
		}

		// Denied
		$denied = FALSE;
		$status_history = $app->getStatusHistory();
		foreach($status_history as $rec)
		{
			if($rec->applicationStatus == 'denied::applicant::*root')
			{
				$denied = TRUE;
				break;
			}
		}
		if ($denied)
		{
			$loan_action_id = Get_Loan_Action_ID('VERIFY_DENIED_STATUS_HISTORY');
			$app_status = $app->getStatus();
			Insert_Loan_Action($app_id, $loan_action_id, $app_status->getApplicationStatus(), ECash::getAgent()->getAgentId());
			continue;
		}
		
		// Due Date falls on Pay Dates
		$duedate_falls_on_paydate = FALSE;
		$date_first_payment = $app->date_first_payment;
		$date_first_payment = date("Y-m-d", $date_first_payment);

		$pdh = new Paydate_Handler();	
		$app_row = new stdclass();
		$columns = $app->getColumns();
		foreach($columns as $column)
		{
			$app_row->$column = $app->$column;
		}
		$pdh->Get_Model($app_row);
		$paydates = $pdh->Get_Paydates($app_row->model, 'Y-m-d');
		$paydates = $paydates["alt_paydates"];

		if (in_array($date_first_payment, $paydates))
		{
			$duedate_falls_on_paydate = TRUE;
		}

		if (!$duedate_falls_on_paydate)
		{
			$loan_action_id = Get_Loan_Action_ID('VERIFY_DUEDATE_PAYDATE_CS');
			$app_status = $app->getStatus();
			Insert_Loan_Action($app_id, $loan_action_id, $app_status->getApplicationStatus(), ECash::getAgent()->getAgentId());
			continue;	
		}
		/////////////////
		//$docs = $app->getDocuments()->getSentandRecieved();
		$fund = false;
		$loan_doc_found = FALSE;

                $document_model = ECash::getFactory()->getModel('Document');
                $document_array = $document_model->loadAllBy(array('application_id' => $app_id,
                'document_list_id' => $document_list_id,
                'document_method' => 'olp',
                'document_event_type' => 'sent',));
                if ($document_array->count() > 0)
                        $loan_doc_found = TRUE;
		//foreach($docs as $doc)
		//{
			//$doc_name = $doc->getName();
			
			//if($doc_name == $Loan_Doc_Template)
			if ($loan_doc_found)
			{
				//$loan_doc_found = TRUE;	
				$inquiry_packages = $bureau_query->getData($app_id, $server->company_id);
				$autofund_eligible = false;
				if(count($inquiry_packages)) {
					/**
					 * We retrieve packages Newest to Oldest, so stop on the first match
					 */
					foreach($inquiry_packages as $package) {
						switch ($package->name_short) {
							case "datax":
								$dataxResponse = new ECash_DataX_Responses_Perf();
								break;
							case "factortrust":
								$dataxResponse = new ECash_FactorTrust_Responses_Perf();
								break;
							case "clarity":
								$dataxResponse = new ECash_Clarity_Responses_Perf();
								break;
						}
						$dataxResponse->parseXML($package->received_package);
						$autofund_eligible = $dataxResponse->getAutoFundDecision();
					}
				}
				//check if is a react and previous app wasn't in a collections status
				if($app->is_react == 'yes')
				{
					ECash::getlog()->Write('Check React ' . $app_id );
					$customer = ECash_Customer::getBySSN(ECash::getMasterDB(), $app->ssn, $app->company_id);

					$application_list = $customer->getApplications();
					$autofund_eligible = TRUE;
					$made_arrangement = 0;
					$loans_made = 0;
					$loans_paid = 0;
					foreach($application_list as $customer_app)
					{

						if ($app_id != $customer_app->application_id){
							$loans_made += $customer_app->fund_actual;
							$ap_sched = $customer_app->getSchedule();
							$balance_info = $ap_sched->getBalanceInformation();
							$loans_paid += $balance_info->total_paid;
							if ($customer_app->getStatus()->getApplicationStatus() == 'refi::servicing::customer::*root')
							{
								ECash::getlog()->Write('Parent application had in refi status ' . $customer_app->application_id);
								$app->getComments()->add('Parent application in refi status. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
								if(!$flags->get('autofund_overridden'))
								{
									$flags->set('autofund_overridden');
								}
								$autofund_eligible = FALSE;	
								break;
							}
							$status_history = $customer_app->getStatusHistory();
							ECash::getlog()->Write('Pulling parent app ' . $customer_app->application_id );

							foreach($status_history as $name => $entry)
							{
								//check status history 
								if (($entry->applicationStatus == 'pending::external_collections::*root') ||
								    ($entry->level1 == 'Bankruptcy') || ($entry->level0 == 'Bankruptcy')) 
								{
									ECash::getlog()->Write('Parent application had a collections or bankrupcy status ' . $customer_app->application_id);
									$app->getComments()->add('Parent application had a collections or bankruptcy status. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
									if(!$flags->get('autofund_overridden'))
									{
										$flags->set('autofund_overridden');
									}
									$autofund_eligible = FALSE;	
									break;
								}
								if ($entry->applicationStatus == 'current::arrangements::collections::customer::*root')
								{
									$made_arrangement++;
								}
							}
							$old_flags = $customer_app->getFlags();
							if($old_flags->get('has_fatal_ach_failure'))
							{	
								ECash::getlog()->Write('Parent application had a fatal ACH ' . $customer_app->application_id);
								$app->getComments()->add('Parent application had a fatal ACH. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
								if(!$flags->get('autofund_overridden'))
								{
									$flags->set('autofund_overridden');
								}
								$autofund_eligible = FALSE;	
								break;
							}
							if($old_flags->get('has_fatal_card_failure'))
							{
								ECash::getlog()->Write('Parent application had a fatal card ' . $customer_app->application_id);
								$app->getComments()->add('Parent application had a fatal card. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
								if(!$flags->get('autofund_overridden'))
								{
									$flags->set('autofund_overridden');
								}
								$autofund_eligible = FALSE;	
								break;
							}
							if($old_flags->get('bad_info'))
							{
								ECash::getlog()->Write('Parent application had a bad info ' . $customer_app->application_id);
								$app->getComments()->add('Parent application had a bad info. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
								if(!$flags->get('autofund_overridden'))
								{
									$flags->set('autofund_overridden');
								}
								$autofund_eligible = FALSE;	
								break;
							}
							$old_contact_flags = $customer_app->getContactFlags()->getAll();
							foreach($old_contact_flags as $old_contact_flag)
							{
								if($old_contact_flags->field_name == 'bad_info')
								{
									ECash::getlog()->Write('Parent application had a bad info ' . $customer_app->application_id);
									$app->getComments()->add('Parent application had a bad info. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
									if(!$flags->get('autofund_overridden'))
									{
										$flags->set('autofund_overridden');
									}
									$autofund_eligible = FALSE;	
									break;
								}
							}
							if (!$autofund_eligible) break;
						}
					}
					if ($made_arrangement > 1) 
					{
						ECash::getlog()->Write('Parent applications had exessive made arrangements. '.$app_id );
						$app->getComments()->add('Parent applications had excessive made arrangements. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
						if(!$flags->get('autofund_overridden'))
						{
							$flags->set('autofund_overridden');
						}
						$autofund_eligible = FALSE;	
					}
					if (($loans_paid - $loans_made) < 100){
						ECash::getlog()->Write('Amount paid on previous applications does not exceed loan amounts by $100. '.$app_id );
						$app->getComments()->add('Amount paid on previous applications does not exceed loan amounts by $100.  Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
                                                if(!$flags->get('autofund_overridden')){
							$flags->set('autofund_overridden');
						}
						$autofund_eligible = FALSE;
					}
				}
				/*
				if($autofund_eligible)
				{
					$fund = true;
				}
				*/
				//break;
			}
		//}

		if($fund)
		{
			ECash::getlog()->Write('Auto Funding App ' . $app_id );
			try {
				$return = $loan_data->Fund($app_id, 'Fund');
			}
			catch(Exception $e)
			{
				ECash::getlog()->Write('Exception thrown attempting to auto fund ' . $app_id . ' Exception: ' . $e->getMessage());
			}
			$app->getComments()->add('Application was Auto Funded', ECash::getAgent()->getAgentId());

		}
		else
		{
			if (!$loan_doc_found)
			{
				$app->getComments()->add('Application has no Loan Document. Routed to Verification queue for manual funding.', ECash::getAgent()->getAgentId());
				if(!$flags->get('autofund_overridden'))
				{
					$flags->set('autofund_overridden');
				}
			}
		}
	}

	/*
	//make sure no prefund apps are in a queue, this happens because the vapi scrubbers could run after the autofund happens,
	//so the cfe events for confirmed run after the funding happens
	$status_list = "
		('approved::servicing::customer::*root')
	";

	$mssql_query = "
		DECLARE @STATUS_LIST table_type_varchar256;
		INSERT INTO @STATUS_LIST VALUES {$status_list};
		EXEC sp_commercial_authoritative_for_status @STATUS_LIST
	";

	$app_service_result = ECash::getAppSvcDB()->query($mssql_query);
	$queue_manager = ECash::getFactory()->getQueueManager();
	while ($row = $app_service_result->fetch(DB_IStatement_1::FETCH_OBJ))
	{
		$app_id = $row->application_id;
		$queue_item = new ECash_Queues_BasicQueueItem($app_id);
		$queue_manager->getQueueGroup('automated')->remove($queue_item);
	}
	*/
}

?>
