<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';
require_once SQL_LIB_DIR . 'util.func.php';
require_once('RegExpTranslator.class.php');

class GlobalCampaignRules
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
                $data['global_campaign_rules'] = $this->fetchGlobalCampaignRules();
                ECash::getTransport()->Set_Data($data);

                return TRUE;
        }

	public function addGlobalCampaignRule()
	{
		
	}

	public function editGlobalCampaignRule()
	{
		//Requested values
		$request_age = $this->request->global_campaign_rule_age;
		$request_military = $this->request->global_campaign_rule_military;
		$request_income_monthly = $this->request->global_campaign_rule_income_monthly;
		$request_bank_aba = $this->request->global_campaign_rule_aba;
		$request_street = $this->request->global_campaign_rule_street;

		//Translated values
		$age = array_search($request_age, RegExpTranslator::$exclude_min);
		$military = array_search($request_military, RegExpTranslator::$exclude_boolean);
		$income_monthly = array_search($request_income_monthly, RegExpTranslator::$exclude_min);
		$bank_aba = $request_bank_aba;
		$street = array_search($request_street, RegExpTranslator::$max_characters);

		//DB special chars
		$income_monthly = str_replace("\\", "\\\\", $income_monthly);
		$street = str_replace("\\", "\\\\", $street);

		//Final value
		$value_array = array();
		$value_array[] = $age;
		$value_array[] = $military;
		$value_array[] = $income_monthly;
		$value_array[] = $bank_aba;
		$value_array[] = $street;

		$value = implode(";", $value_array);
		
		//UPDATE
		$db = ECash::getMasterDb();

		$query = "
		SELECT
		slv.value_id
		FROM suppression_lists AS sl
		JOIN suppression_list_revisions AS slr USING (list_id)
		JOIN suppression_list_revision_values AS slrv USING (list_id,revision_id)
		JOIN suppression_list_values AS slv USING (value_id)
		WHERE sl.name = 'MLS - Global Multiple Exclusion'
		";
		$st = $db->query($query);
		$value_id = intval($st->fetch(PDO::FETCH_OBJ)->value_id);

		$query = "
		UPDATE
			suppression_list_values
		SET
			value = '{$value}'
		WHERE
			value_id = {$value_id}
		";
		$st = $db->query($query);

		//Log
		$agent_id = ECash::getAgent()->getAgentId();
		$log = ECash::getLog('admin');
		$log->Write("[Agent:{$agent_id}] Edited Global Campaign Rules. Age: {$request_age}, Military: {$request_military}, Income Monthly: {$request_income_monthly}, Street: {$request_street}.");
	}

	public function fetchGlobalCampaignRules()
	{
		static $global_campaign_rule_list;

		if(empty($global_campaign_rule_list))
		{
			$query = "
				SELECT
					sl.field_name,
					slv.value AS combined_global_campaign_rule
				FROM suppression_lists AS sl
				JOIN suppression_list_revisions AS slr USING (list_id)
				JOIN suppression_list_revision_values AS slrv USING (list_id,revision_id)
				JOIN suppression_list_values AS slv USING (value_id)
				WHERE sl.name = 'MLS - Global Multiple Exclusion'
			";

			$db = ECash::getMasterDb();
			$st = $db->query($query);
			$global_campaign_rule_list = $st->fetchAll(PDO::FETCH_ASSOC);
		}

		$return_array = array();

		foreach ($global_campaign_rule_list as $global_campaign_rule_list_current)
		{
			$field_name = $global_campaign_rule_list_current["field_name"];
			$field_name_array = explode(";", $field_name);

			$combined_global_campaign_rule = $global_campaign_rule_list_current["combined_global_campaign_rule"];
			$value_array = explode(";", $combined_global_campaign_rule);

			$combined_array = array_combine($field_name_array, $value_array);
			foreach ($combined_array as $field => $value)
			{
				if (in_array($field, array("age","income_monthly")))
				{
					$value = RegExpTranslator::$exclude_min[$value];
					$combined_array[$field] = $value;
				}
				else if ($field == "military")
				{
					$value = RegExpTranslator::$exclude_boolean[$value];
					$combined_array[$field] = $value;
				}
				else if ($field == "street")
				{
					$value = RegExpTranslator::$max_characters[$value];
					$combined_array[$field] = $value;
				}
			}

			$return_array[] = $combined_array;
		}
		
		return $return_array;
	}
}

?>
