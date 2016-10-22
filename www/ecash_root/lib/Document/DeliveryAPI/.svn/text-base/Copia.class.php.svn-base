<?php
/**
 * @package Documents
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Sep 13, 2006
 *
 * @version $Revision$
 */

//require_once("qualify.1.php");
//require_once("qualify.2.php");
require_once("xmlrpc.1.php");
require_once(SQL_LIB_DIR . "/fetch_campaign_info.func.php");
require_once(SQL_LIB_DIR . "/application.func.php");

class eCash_Document_DeliveryAPI_Copia {

	static public function Receive(Server $server, $document_list, $request)
	{

		try 
		{
			$orequest = $request;

			$_SESSION['current_app']->dnis = $request->dnis;
			$_SESSION['current_app']->tiff = $request->tiff;

			if(!isset($request->document_list)) 
			{
				throw new Exception("* No document is selected");
			}

//			if (isset($request->document_list) && count($request->document_list) > 1) {
//				throw new Exception("* Select 1 document at a time");
//			}

			if(!is_numeric($request->dnis) || !is_numeric($request->tiff)) 
			
			{
				ECash::getTransport()->Set_Data($_SESSION['current_app']);
				throw new Exception("* Fields must be numeric");
			}

			if(strlen($request->dnis) != 4) 
			{
				ECash::getTransport()->Set_Data($_SESSION['current_app']);
				throw new Exception("* DNIS must be exactly 4 digits");
			}

			if(!self::Validate_Tiff($request->tiff, $request->dnis)) 
			{
				throw new Exception("* Document not found");
			}

			eCash_Document::$message = "Document(s) found";

			foreach($document_list as $document) 
			{

//				if (strtolower($document->name) == "other" && strlen($request->docname) < 1) {
				$otherdoc = "docname_" . strtolower($document->name);
				if (preg_match("/^other/", strtolower($document->name)) && strlen($request->$otherdoc) < 1) 
				{
					throw new Exception("* Enter another name");
				} 
				elseif (preg_match("/^other/", strtolower($document->name))  ) 
				{
					$request->destination['name'] = $request->$otherdoc;
				}

				$request->method = "fax";
				$request->document_event_type = "received";
				$request->document_id_ext = "{$request->dnis},{$request->tiff}"; //"

				eCash_Document::Log_Document($server, $document, (array) $request);

			}

			$_SESSION['current_app']->tiff_message .= ", marked received";

		}  
		catch (Exception $e) 
		{
			if (preg_match("/^\*\s/",$e->getMessage())) 
			{

				eCash_Document::Log()->write($e->getMessage(), LOG_ERROR);		
		
				eCash_Document::$message = "<font color=\"red\"><b>" . str_replace("* ","",$e->getMessage()) . "</b></font>";
				
			} 
			else 
			{
				throw $e;

			}
		}
	}

	static public function Send(Server $server, $data, $document_list, $send_type = "email", $destination_override = NULL)
	{
		$result = array();

		if(!$data || !$document_list) 
		{
			eCash_Document::Log()->write(__METHOD__ . " Failed at line " . __LINE__, LOG_WARNING);
//			return self::applyResultToAllDocs(&$result, $document_list, "status", "failed");
		}
//		self::applyResultToAllDocs(&$result, $document_list, "application_id", $data->application_id);
		
		$send_obj = self::Map_Data($server, $data);
//var_dump($send_obj)		;
		$send_obj->copia->full_name = $send_obj->applicant->full_name;
		$destination['name'] = $send_obj->copia->full_name;

		switch(true) 
		{
			/**
			 * TODO: This case routes all faxes from non LIVE environments to 
			 * the DOCUMENT_TEST_FAX number. This is here because Parallel was
			 * sending faxes to customers. Agents are entering fax numbers that
			 * are not associated with the apps, so there's no way to identify
			 * a 'test' number. This should be replaced by something
			 * that allows for easier RC and Local testing.
			 */
			case (strtolower($send_type) == "fax" && EXECUTION_MODE != 'LIVE') :
				$destination_override = ECash::getConfig()->DOCUMENT_TEST_FAX;
				$send_obj->copia->fax_phone = $destination_override;
				$destination['destination'] = $destination_override;
				break;

			case (strtolower($send_type) == "fax" && $destination_override != NULL) :
				$send_obj->copia->fax_phone = $destination_override;
				$destination['destination'] = $destination_override;
				break;

			case strtolower($send_type) == "fax"  :
				$send_obj->copia->fax_phone = $send_obj->applicant->personal->fax_phone;
				$destination['destination'] = $send_obj->copia->fax_phone;
				break;

			// RC environment check.. ugly hack
			case EXECUTION_MODE != 'LIVE' && $destination_override != NULL && $send_obj->applicant->personal->email == $destination_override && stripos($send_obj->applicant->personal->email,"sellingsource") === FALSE :
				$destination_override = 'ecash3drive@gmail.com';
				
			case $destination_override != NULL :
				$send_obj->copia->email_address = $destination_override;
				$destination['destination'] = $destination_override;
				break;

			// RC environment check.. ugly hack
			case EXECUTION_MODE != 'LIVE' &&  stripos($send_obj->applicant->personal->email,"sellingsource") === FALSE:
				$send_obj->copia->email_address = 'ecash3drive@gmail.com';
				$destination['destination'] = 'ecash3drive@gmail.com';
				break;
				
			default:
				$send_obj->copia->email_address = $send_obj->applicant->personal->email;
				$destination['destination'] = $send_obj->copia->email_address;

		}
//		self::applyResultToAllDocs(&$result, $document_list, "destination", $destination);

		switch (true) 
		{
			case strtolower($send_type) == "fax" && isset($send_obj->copia->fax_phone) :
			case isset($send_obj->copia->email_address):
				break;
			default:
				eCash_Document::Log()->write(__METHOD__ . " Failed at line " . __LINE__, LOG_WARNING);

				eCash_Document::Log()->Write(__METHOD__ . " Result: " . var_export($result, true));
		
				return self::applyResultToAllDocs(&$result, $document_list, "status", "failed");
		}

		foreach($document_list as $doc) 
		{

			//hack
			$send_result = "";
			$res_check = array();
			
			if(isset($send_obj->send_document_list)) unset($send_obj->send_document_list);
			
			/**
			 * newer model
			 */
			if(is_array($doc->bodyparts) && count($doc->bodyparts) > 0) 
			{

				if(!is_object(current($doc->bodyparts))) 
				{
					$doc->bodyparts = eCash_Document::singleton($server,$_REQUEST)->Get_Documents($doc->bodyparts);
				}

				foreach ($doc->bodyparts as $subpart) 
				{			
					$result[$subpart->document_list_id] = array("status" => &$send_result,
																"application_id" => $data->application_id,
																"destination" => $destination,
																"method" => strtolower($send_type),
																"document" => (array) $subpart
																);
							
					if($subpart->esig_capable == 'yes') 
					{
						self::sendEsigDocsEmail($server, $data, $send_obj->copia->email_address);

						$result[$subpart->document_list_id]['status'] = 'sent';
						$result[$subpart->document_list_id]['document_id'] = eCash_Document::Log_Document($server,$subpart,$result[$subpart->document_list_id]);
						break;

					} 
					else 
					{
						$name = $subpart->name . ".rtf";
						$send_obj->send_document_list->{$name} = strtoupper($send_type);
						$res_check[] = $subpart->document_list_id;
					}
				}

			}

			$result[$doc->document_list_id] = array("status" => &$send_result,
													"application_id" => $data->application_id,
													"destination" => $destination,
													"method" => strtolower($send_type),
													"document" => (array) $doc
													);

			switch ($doc->esig_capable) 
			{
				case "yes":
					self::sendEsigDocsEmail($server, $data, $send_obj->copia->email_address);

					$result[$doc->document_list_id]['status'] = 'sent';
					$result[$doc->document_list_id]['document_id'] = eCash_Document::Log_Document($server,$doc,$result[$doc->document_list_id]);
					break;

				case "no":
				default:
					$name = $doc->name . ".rtf";
					$send_obj->send_document_list->{$name} = strtoupper($send_type);
					$res_check[] = $doc->document_list_id;
			}
			
			if(!isset($send_obj->send_document_list) && !isset($ole)) 
			{
				eCash_Document::Log()->write(__METHOD__ . " Failed at line " . __LINE__, LOG_WARNING);
				$send_result = 'failed';

			} 
			elseif (!isset($send_obj->send_document_list)) 
			{
				eCash_Document::Log()->write(__METHOD__ . " may have succeeded at line " . __LINE__, LOG_WARNING);

				eCash_Document::Log()->Write(__METHOD__ . " Result: " . var_export($result, true));
		
				return $result;
				
			} 
			else 
			{

				$send_obj->applicant->personal->doc_send_method = strtoupper($send_type);
				$send_obj->document_extension = ".rtf";

//		var_dump($send_obj); var_dump($result); die;
		
				$xmlrpc_envelope = new stdClass ();
				$xmlrpc_envelope->passed_data = base64_encode (serialize ($send_obj));

				$copia_host = ECash::getConfig()->COPIA_HOST;
				$copia_port = ECash::getConfig()->COPIA_PORT;
				$copia_path = ECash::getConfig()->COPIA_PATH;
				
				$xres = Xmlrpc_Request ($copia_host, $copia_port, $copia_path, "Send_Document", $xmlrpc_envelope);

				$send_result = (Error_2::Error_Test ($xres, FALSE)) ? "failed" : "sent";
			
			}
//			var_dump($result); die;
		
			eCash_Document::Log()->write(__METHOD__ . " may have succeeded at line " . __LINE__, LOG_WARNING);

			foreach($res_check as $did) 
			{
				$result[$did]['document_id'] = eCash_Document::Log_Document($server,(object) $result[$did]['document'],$result[$did]);
			}
			
		}	

		eCash_Document::Log()->Write(__METHOD__ . " Result: " . var_export($result, true));
		
		return $result;

	}

	static private function applyResultToAllDocs(&$result, $document_list, $result_name, $result_value)
	{
		if(is_array($document_list)) 
		{
			foreach($document_list as $doc) 
			{
				$result[$doc->document_list_id][$result_name] = $result_value;
			}
		} 
		else 
		{
			$result[(int) $document_list][$result_name] = $result_value;
		}

		return $result;

	}

	static private function sendEsigDocsEmail(Server $server, $data, $email_override=NULL)
	{
		$template = 'ECASH_ESIG_LOAN_DOCS';
		$tokens = self::prepareDataForEmail($server, $data, $email_override);
		$recipients = $tokens['email_primary'];

		require_once(LIB_DIR . '/Mail.class.php');
		$response = eCash_Mail::sendMessage($template, $recipients, $tokens);

		if (!$response)
		{
			throw new Exception(__METHOD__ . ' Error: Bad response from eCash_Mail -  email not sent');
		}
	}

	static private function prepareDataForEmail(Server $server, $data, $email_override = NULL)
	{
		// Terrible hack to get ESig to work
		$newapp = $server->new_app_url;
		$esig_site = split('\?', $newapp);
		$site_name = str_replace('http://', '', $esig_site[0]);

		$holidays = Fetch_Holiday_List();
		$paydate_obj = new Pay_Date_Calc_3($holidays);

		$login_hash = md5($data->application_id . '24hrloans');
		if ($site_name == 'ecashapp.com')
		{
			$esig_link = $esig_site[0] . '/?application_id=' . urlencode(base64_encode($data->application_id)) . "&page=ent_cs_login&login={$login_hash}&ecvt&ecash_confirm=1";
		}
		else
		{
			$esig_link = $esig_site[0] . '/?page=ecash_sign_docs&application_id=' . urlencode(base64_encode($data->application_id)) . "&login={$login_hash}&ecvt";
		}

        $fund_date2 = strtotime(str_replace('-', '/', $data->date_fund_estimated));
        $i = 1;

        while (1)
        {
			$stamp = strtotime("+{$i} day", $fund_date2);
        	if ($paydate_obj->Is_Weekend($stamp) || $paydate_obj->Is_Holiday($stamp))
        	{
				$i++;
        	}
        	else 
        	{
        		break;
        	}
        }

		return array(
			'email_primary' => (($email_override != NULL) ?  $email_override : $data->customer_email),
			'name_view' => $server->company_list[$server->company_id]['name'],
			'name' => strtoupper($data->name_first) . ' ' . strtoupper($data->name_last),
			'email_primary_name' => strtoupper($data->name_first) . ' ' . strtoupper($data->name_last),
			'application_id' => $data->application_id,
			'username' => $data->login_id,
			'password' => $data->decrypt_pass,
			'esig_link' => $esig_link,
			'estimated_fund_date_1' => str_replace('-', '/', $data->date_fund_estimated),
			'estimated_fund_date_2' => date('m/d/Y', $stamp),
			'site_name' => $site_name);
	}
	
	static public function Map_Data(Server $server, $data)
	{
		$object = (object) array();

		$object->applicant->bank_info->application_id 	= $data->application_id;
		$object->applicant->bank_info->bank_name 		= $data->bank_name;
		$object->applicant->bank_info->account_number 	= $data->bank_account;
		$object->applicant->bank_info->routing_number 	= $data->bank_aba;
		$object->applicant->bank_info->check_number 	= ""; //$data->check_number
		$object->applicant->bank_info->direct_deposit 	= ($data->income_direct_deposit == "yes") ? "TRUE" : "FALSE";

		$object->applicant->employment->application_id 	= $data->application_id;
		$object->applicant->employment->employer 		= $data->employer_name;
		$object->applicant->employment->address_id 		= "";
		$object->applicant->employment->work_phone 		= $data->phone_work;
		$object->applicant->employment->work_ext 		= $data->phone_work_ext;
		$object->applicant->employment->title 			= $data->job_title;
		$object->applicant->employment->shift 			= $data->shift;
		$object->applicant->employment->date_of_hire 	= $data->date_hire_day . "-" . $data->date_hire_month . "-" . $data->date_hire_year;
		$object->applicant->employment->income_type 	= $data->income_source;
		$object->applicant->employment->dohy 			= $data->date_hire_year;
		$object->applicant->employment->dohm 			= $data->date_hire_month;
		$object->applicant->employment->dohd 			= $data->date_hire_day;
		$object->applicant->employment->length 			= "";

		$object->applicant->lengthofemployment = "";

		$object->applicant->income->application_id 		= $data->application_id;
		$object->applicant->income->modified_date 		= "";
		$object->applicant->income->net_pay 			= number_format($data->income_monthly, 0, '.', '');
		$object->applicant->income->pay_frequency 		= $data->income_frequency;
		$object->applicant->income->paid_on_day_1 		= "";
		$object->applicant->income->paid_on_day_2 		= "";
		$object->applicant->income->pay_date_1 			= $data->paydate_0;
		$object->applicant->income->pay_date_2 			= $data->paydate_1;
		$object->applicant->income->pay_date_3 			= $data->paydate_2;
		$object->applicant->income->pay_date_4 			= $data->paydate_3;
		$object->applicant->income->monthly_net_pay 	= number_format($data->income_monthly, 0, '.', '');
		$object->applicant->income->pd1m 				= $data->income_date_one_month;
		$object->applicant->income->pd1d 				= $data->income_date_one_day;
		$object->applicant->income->pd1y 				= $data->income_date_one_year;
		$object->applicant->income->pd2m 				= $data->income_date_two_month;
		$object->applicant->income->pd2d 				= $data->income_date_two_day;
		$object->applicant->income->pd2y 				= $data->income_date_two_year;

		$object->applicant->residence->application_id 		= $data->application_id;
		$object->applicant->residence->residence_type 		= $data->tenancy_type;
		$object->applicant->residence->length_of_residence 	= ""; //$data->length_of_residence;
		$object->applicant->residence->address_1 			= ucwords($data->street);
		$object->applicant->residence->address1 			= ucwords($data->street);
		$object->applicant->residence->apartment 			= $data->unit;
		$object->applicant->residence->city 				= ucwords($data->city);
		$object->applicant->residence->state 				= strtoupper($data->state);
		$object->applicant->residence->zip 					= $data->zip;
		$object->applicant->residence->address_2 			= "";

		$object->applicant->personal->application_id 	= $data->application_id;
		$object->applicant->personal->modified_date 	= "";
		$object->applicant->personal->first_name 		= trim($data->name_first);
		$object->applicant->personal->middle_name 		= trim($data->name_middle);
		$object->applicant->personal->last_name 		= trim($data->name_last);
		$object->applicant->personal->home_phone 		= $data->phone_home;
		$object->applicant->personal->cell_phone 		= $data->phone_cell;
		$object->applicant->personal->fax_phone 		= $data->phone_fax;
		$object->applicant->personal->email 			= $data->customer_email;
		$object->applicant->personal->alt_email 		= "";
		$object->applicant->personal->date_of_birth 	= $data->dob;
		$object->applicant->personal->contact_id_1 		= "";
		$object->applicant->personal->contact_id_2 		= "";

		preg_match('/(\d{3})(\d{2})(\d{4})/', $data->ssn, $ssn_matches);
		$object->applicant->personal->social_security_number 	= $ssn_matches[1] . "-" . $ssn_matches[2] . "-" . $ssn_matches[3];
		$object->applicant->personal->drivers_license_number 	= $data->legal_id_number;
		$object->applicant->personal->best_call_time 			= $data->call_time_pref;

		$object->applicant->personal->dobm 					= $data->dob_month;
		$object->applicant->personal->dobd 					= $data->dob_day;
		$object->applicant->personal->doby 					= $data->dob_year;
		$object->applicant->personal->social_security_1 	= $ssn_matches[1];
		$object->applicant->personal->social_security_2 	= $ssn_matches[2];
		$object->applicant->personal->social_security_3 	= $ssn_matches[3];

		$date_fund_estimated = $data->date_fund_estimated_month . '-' . $data->date_fund_estimated_day . '-' . $data->date_fund_estimated_year;
		$object->applicant->loan_note->application_id 		= $data->application_id;
		$object->applicant->loan_note->modified_date		= "";
		$object->applicant->loan_note->estimated_fund_date 	= $date_fund_estimated;
		$object->applicant->loan_note->fund_amount 			= self::Format_Money($data->current_principal_payoff_amount, $data->fund_amount); // Curr if exists, else from DB
		$object->applicant->loan_note->num_payments 		= "";
		$object->applicant->loan_note->apr 					= ($data->current_apr) ? number_format($data->current_apr, 2, '.', '') . '%' : number_format($data->apr, 2, '.', '') . '%' ; // Curr if exists, else from DB

		//$object->applicant->loan_note->finance_charge 			= number_format($data->finance_charge, 2, '.', '');
//		$object->applicant->loan_note->finance_charge 				= isset($data->next_service_charge_amount) ? "$". number_format($data->next_service_charge_amount, 2, '.', '') : "";
		$object->applicant->loan_note->finance_charge 				= self::Format_Money($data->current_service_charge, $data->finance_charge); // Curr if exists, else from DB
		
		//$object->applicant->loan_note->total_payments 			= number_format($data->payment_total , 2, '.', '');
		$object->applicant->loan_note->total_payments 				= self::Format_Money($data->current_payoff_amount, $data->payment_total); // Curr if exists, else from DB

		//$object->applicant->loan_note->estimated_payoff_date 		= $data->date_first_payment;
		$object->applicant->loan_note->estimated_payoff_date 		= ($data->current_due_date) ? $data->current_due_date : $data->date_first_payment ; // Curr if exists, else from DB

		//$object->applicant->loan_note->pay_down_finance_charge 	= isset($data->pay_down_finance_charge) ? $data->pay_down_finance_charge : "";
		$object->applicant->loan_note->pay_down_finance_charge 		= self::Format_Money($data->current_service_charge);

		//$object->applicant->loan_note->pay_down_amount_due 		= isset($data->pay_down_amount_due) ? $data->pay_down_amount_due : "";
		$object->applicant->loan_note->pay_down_amount_due 			= self::Format_Money($data->current_principal);

		//$object->applicant->loan_note->pay_down_total 			= isset($data->pay_down_total) ? $data->pay_down_total : "";
//		$object->applicant->loan_note->pay_down_total 				= isset($data->current_principal) && isset($data->current_service_charge) ? "$". number_format($data->current_principal + $data->current_service_charge,2,'.','') : "$0.00";
		$object->applicant->loan_note->pay_down_total 				= self::Format_Money($data->current_total_due);
		
		//$object->applicant->loan_note->pay_down_next_finance_charge = isset($data->pay_down_next_finance_charge) ? $data->pay_down_next_finance_charge : "";
		$object->applicant->loan_note->pay_down_next_finance_charge = self::Format_Money($data->next_total_due);

		// add this one
		$object->applicant->loan_note->next_due_date 				= ($data->next_due_date) ? $data->next_due_date : "Not Scheduled";

		$object->applicant->loan_note->next_business_day 			= isset($data->next_business_day) ? $data->next_business_day : "DD/MM/YYYY";



		$object->applicant->loan_note->fund_date 			= $date_fund_estimated;
		$object->applicant->loan_note->actual_fund_date 	= $date_fund_estimated;
//		$object->applicant->loan_note->payoff_date 			= $data->date_first_payment;
		$object->applicant->loan_note->payoff_date 			= isset($data->current_due_date)? $data->current_due_date : $data->date_first_payment;
		$object->applicant->loan_note->max_fund_amount 		= $object->applicant->loan_note->fund_amount;

		$object->applicant->loan_note->arrangement_amount	= self::Format_Money($data->past_arrangement_payment);
		$object->applicant->loan_note->arrangement_date		= $data->past_arrangement_due_date;
		$object->applicant->loan_note->payment_type			= $data->past_arrangement_type;

		$object->applicant->loan_note->login_id 			= isset($data->login_id) ? $data->login_id : "";
		$object->applicant->loan_note->agent_login 			= isset($data->agent_login) ? $data->agent_login : "";;
		$object->applicant->loan_note->reason_for_ach_return = isset($data->reason_for_ach_return) ? $data->reason_for_ach_return : "";


		$object->applicant->LoanBalance = $data->current_payoff_amount;  // This value is the total of the following outstanding values: Principal + Interests + Fees. This is used in the Collection Balance Letter and Customer Balance Letter. This value has not been retrieved from the database yet. [Sept 7, 2006]


		$object->applicant->loan_note->current_principal_payoff_amount = isset($data->current_principal_payoff_amount) ? "$". number_format($data->current_principal_payoff_amount, 2, '.','') : "$0.00";

		// Copia is deprecated, but just in case it does get called.
		// we don't want this method called.
		//$references = Fetch_References($data->application_id);

		$object->applicant->personal_contact = self::Format_Reference_Data($references);

		$company = strtoupper($server->company);

		$object->applicant->application_id = $company . ' - ' . $data->application_id;
		$object->applicant->application_prefix = $company;

		$ci = Fetch_Campaign_Info($data->application_id, $server->company_id);

		if (isset($ci->promo_id))
		{
			$object->applicant->promo_id = $ci->promo_id;
		}
		else
		{
			$object->applicant->promo_id = '';
		}

		$object->applicant->date = date("m/d/Y");
		$object->property = $company;
		$object->applicant->full_name	= ucwords(trim($data->name_first))." ".ucwords(trim($data->name_last));
		$object->applicant->agent_name	= ucwords($server->agent_name);
//var_dump($object);
		return $object;

	}

	static public function Fetch_Doc_List( $company_short )
	{

		$object = (object) array();
		$object->property = strtoupper($company_short);

		$xmlrpc_envelope = (object) array();
		$xmlrpc_envelope->passed_data = base64_encode (serialize ($object));

		$copia_host = ECash::getConfig()->COPIA_HOST;
		$copia_port = ECash::getConfig()->COPIA_PORT;
		$copia_path = ECash::getConfig()->COPIA_PATH;
				
		$result = Xmlrpc_Request ($copia_host, $copia_port, $copia_path, "Get_Document_List", $xmlrpc_envelope);

		//echo "<pre>".To_String($result)."</pre>";

		//don't ask my why this works and $result[0] doesn't
		list($key, $rpc_result) = each($result);

		// Pull the list from the response
		$doc_list = unserialize (base64_decode ($rpc_result));
		//echo "<pre>".To_String($doc_list)."</pre>";

		$doc_return = array();

		foreach($doc_list as $file)
		{
			$obj = (object) array();
			$obj->name = str_replace('.rtf','',$file);
			$obj->description = $obj->name;
			$obj->file = $file;
			$obj->required = 0;
			$doc_return[$obj->name] = $obj;
		}

		return $doc_return;

	}



	static public function Validate_Tiff ($tiff, $dnis)
	{
		// Call the copia server to find out which documents to look for
		$object = (object) array();
		$object->dnis = $dnis;
		$object->tiff = $tiff;

		//echo "<pre>Passing Object:".To_String($object)."</pre>";

		$xmlrpc_envelope = (object) array();
		$xmlrpc_envelope->passed_data = base64_encode (serialize ($object));

		$copia_host = ECash::getConfig()->COPIA_HOST;
		$copia_port = ECash::getConfig()->COPIA_PORT;
		$copia_path = ECash::getConfig()->COPIA_PATH;
				
		$result = Xmlrpc_Request ($copia_host, $copia_port, $copia_path, "Validate_Tiff", $xmlrpc_envelope);

		//echo "<pre>".To_String($result)."</pre>";

		//don't ask my why this works and $result[0] doesn't
		list($key, $rpc_result) = each($result);

		// Pull from the response
		return unserialize (base64_decode ($rpc_result));

	}

	static private function Format_Reference_Data($rows)
	{
		$contact_obj = new stdClass();
		$ref_num = 1;

		if(count($rows) > 0) 
		{
			foreach($rows as $ref) 
			{
				$name = "name_{$ref_num}";
				$phone = "phone_{$ref_num}";
				$relationship = "relationship_{$ref_num}"; //"

				$contact_obj->$name = $ref->name_full;
				$contact_obj->$phone = $ref->phone;
				$contact_obj->$relationship = $ref->relationship;
				$ref_num++;
			}
		}

		return $contact_obj;
	}

	static public function Format_Money($value, $default = NULL)
	{
		return eCash_Document_ApplicationData::Format_Money($value, $default);
	}	
	
}

?>
