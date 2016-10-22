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

require_once eCash_Document_DIR . "/Document.class.php";
require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');

class eCash_Document_ApplicationData {

	static public function Get_Email(Server $server, $application_id)
	{
		$db = ECash::getMasterDb();
		$email = '';

		$app_query = "
			SELECT
				email
			FROM
				application
			WHERE
				application_id = {$application_id}"; //"

		//eCash_Document::Log()->write($query, LOG_DEBUG);

		$query_obj = $db->query($app_query);

		if ($row_obj = $query_obj->fetch(PDO::FETCH_OBJ))
		{
			$email .= $row_obj->email;
		}

		return $email;
	}

	

	static public function Get_History(Server $server, $application_id, $event = NULL)
	{
		$app_docs = array();
		
		$where = array(
			"application_id" => $application_id,
			"company_id" => ECash::getCompany()->company_id,
		);
		
		if (!empty($event)) $where["document_event_type"] = $event;

		$ecash_factory = ECash::getFactory();
		$agent_ref = $ecash_factory->getReferenceList("Agent");
		$doc_list_ref = $ecash_factory->getReferenceModel("DocumentListRef");
		$document_model = $ecash_factory->getModel("Document");
		
		$documents = $document_model->loadAllBy($where);
		foreach ($documents as $document)
		{
			$doc_list_ref->loadByKey($document->document_list_id);
			$data = new StdClass();
			foreach ($document->getColumns() as $column)
			{
				$data->{$column} = $document->{$column};
			}
			
			$data->name = $doc_list_ref->name_short;
			$data->description = $doc_list_ref->name;
			$data->required = $doc_list_ref->required;
			$data->send_method = $doc_list_ref->send_method;
			$data->event_type = $document->document_event_type;
			
			$data->login = (isset($agent_ref->{$document->agent_id}))
				? $agent_ref->{$document->agent_id}
				: 'unknown';
			if (empty($document->document_method)) $document->document_method = $document->document_method_legacy;
			$data->xfer_date = date('m-d-Y H:i', $document->date_created);
			$data->alt_xfer_date = date('Y-m-d', $document->date_modified);
			$app_docs[$data->date_created] = $data;
		}
		
		ksort($app_docs);
		$sorted_docs = array_values($app_docs);
		
		return $sorted_docs;

	}


	static private function Is_In_Prefund_Status($application_id)
	{
		$status = Fetch_Application_Status($application_id);
		return (($status['level1'] == "prospect") || 	($status['level2'] == "applicant") ||
				($status['level1'] == "applicant") || ($status['status'] == "funding_failed"));
	}

	static private function Has_Active_Schedule(Server $server, $application_id)
	{
		$sql = "
			SELECT COUNT(*) as 'count'
			FROM event_schedule
			WHERE application_id = {$application_id}
			AND event_status = 'scheduled'"; //"


		//eCash_Document::Log()->write($sql, LOG_DEBUG);
		$db = ECash::getMasterDb();

		$result = $db->query($sql);
		$count = $result->fetch(PDO::FETCH_OBJ)->count;
		return ($count != 0);
	}


	static private function Get_Business_Rules(Server $server, $application_id)
	{
		$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
		$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($application_id);
		return $business_rules->Get_Rule_Set_Tree($rule_set_id);
	}

	static public function Format_Money($value, $default = NULL)
	{
		if ($value && (ctype_digit( (string) $value) || is_numeric($value)))
		{
			return money_format('%.2n', (float) $value);
		}
		elseif ($value && preg_match('/\$\d+\.\d{2}/',$value))
		{
			return $value;

		}
		elseif (!$value && $default != NULL)
		{
			return self::Format_Money($default);
		}
		else
		{
			return money_format('%.2n', (float) 0);
		}
	}

	/**
		Calculate From Monthly Net
		Stole this function from Qualify 2.  There's no reason to be calling Qualify 2 to perform a simple calculation
		like this.
    	@param $pay_span array Payment Span
    	@param $pay string Payment
    	@return $monthly_net array Monthly Span, FALSE on failure
    */
	static public function Calculate_Monthly_Net($pay_span, $pay)
	{

		$paycheck = FALSE;

		switch (strtoupper($pay_span))
		{

			case 'WEEKLY':
                $paycheck = round(($pay * 12) / 52);
                break;
            case 'BI_WEEKLY':
                $paycheck = round(($pay * 12) / 26);
                break;
            case 'TWICE_MONTHLY':
                $paycheck = round($pay / 2);
                break;
            case 'MONTHLY':
                $paycheck = round($pay);
                break;
			default:
				throw new Exception("Invalid pay span, or monthly net pay is zero.");
		}

		return $paycheck;

	}


	// taken from qualify.2, which was flawed due to a calculated finance charge vs. an explicit one used here
	static public function Calc_APR($payoff_date, $fund_date, $loan_amount, $finance_charge)
	{
		$days = round((strtotime($payoff_date) - strtotime($fund_date)) / 86400 );
		$days = ($days >= 1) ? $days : 1;

		if ($loan_amount > 0)
		{
			return round( (($finance_charge / (float) $loan_amount / $days) * 365 * 100), 2);
		}
		else
		{
			return 0;
		}

	}
}

?>
