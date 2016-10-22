<?php
/**
 * This class is used to display black box underwriting inquiry rules for the admin GUI.
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");
require_once(COMMON_LIB_DIR . "ecash_admin_resources.php");

//ecash module
class Display_View extends Admin_Parent
{
	private $company_name;
	private $uw_rule_set;
	private $campaign_groups;
	private $campaign_pubs;
	private $campaigns;
	private $inquiries;
	private $providers;
	private $stores;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();

		$this->company_name = $returned_data->company_name;
		$this->uw_rule_set = $returned_data->uw_rule_set;
		$this->campaign_groups = $returned_data->campaign_groups;
		$this->campaign_pubs = $returned_data->campaign_pubs;
		$this->campaigns = $returned_data->campaigns;
		$this->inquiries = $returned_data->inquiries;
		$this->providers = $returned_data->providers;
		$this->stores = $returned_data->stores;
	}

	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();

		// campaign -> uw id lists
		$id = 0;
		$fields->uw_rule_set = "[";
		foreach($this->uw_rule_set as $uw_rule)
		{
			$fields->uw_rule_set .= "\t\t\n{"
				. "id:'" . $id . "', "
				. "group_id:'" . $uw_rule->campaign_group_id . "', "
				. "publisher_id:'" . $uw_rule->campaign_publisher_id . "', "
				. "campaign_id:'" . $uw_rule->campaign_id . "', " 
				. "inquiry_id:'" . $uw_rule->uw_inquiry_id . "', " 
				. "provider_id:'" . $uw_rule->uw_provider_id
				. "'},";

			$id++;
		}
		$fields->uw_rule_set .= "[";

		// campaign groups
		$fields->campaign_groups = "[";
		foreach($this->campaign_groups as $group)
		{
			$fields->campaign_groups .= "\t\t\n{"
				. "id:'" . $group->campaign_group_id . "', "
				. "name:'" . $group->campaign_group . "', "
				. "risk:'" . $group->campaign_group_risk . "'},";
		}
		$fields->campaign_groups .= "]";

		// campaign publishers
		$fields->campaign_pubs .= "[";
		foreach($this->campaign_pubs as $pubs)
		{
			$fields->campaign_pubs .= "\t\t\n{"
				. "id:'" . $pubs->campaign_publisher_id . "', "
				. "name:'" . $pubs->campaign_publisher_name . "'},";
		}
		$fields->campaign_pubs .= "]";

		// campaigns
		$fields->campaigns .= "[";
		foreach($this->campaigns as $camps)
		{
			$fields->campaigns .= "\t\t\n{"
				. "id:'" . $camps->campaign_id . "', "
				. "name:'" . $camps->campaign_name . "', "
				. "publisher:'" . $camps->campaign_publisher_name . "', "
				. "group:'" . $camps->campaign_group . "', "
				. "risk:'" . $camps->campaign_group_risk . "', "
				. "source:'" . $camps->income_source . "', "
				. "frequency:'" . $camps->income_frequency . "', "
				. "count:'" . $camps->count . "'},";
		}
		$fields->campaigns .= "]";

		// inquiries
		$fields->inquiries .= "[";
		foreach($this->inquiries as $inqs)
		{
			$fields->inquiries .= "\t\t\n{"
				. "id:'" . $inqs->uw_inquiry_id . "', "
				. "name:'" . $inqs->uw_inquiry_name . "', "
				. "provider:'" . $inqs->uw_provider_name . "', "
				. "store_id:'" . $inqs->uw_store_id . "', "
				. "store:'" . $inqs->store_id . "', "
				. "username:'" . $inqs->username . "'},";
		}
		$fields->inquiries .= "]";

		// underwriter provider
		$fields->providers .= "[";
		foreach($this->providers as $provider)
		{
			$fields->providers .= "\t\t\n{"
				. "id:'" . $provider->uw_provider_id . "', "
				. "name:'" . $provider->uw_provider_name . "', "
				. "name_short:'" . $provider->name_short . "'},";
		}
		$fields->providers .= "]";

		// underwriter stores
		$fields->stores .= "[";
		foreach($this->stores as $store)
		{
			$fields->stores .= "\t\t\n{"
				. "id:'" . $store->uw_store_id . "', "
				. "store_id:'" . $store->store_id . "', "
				. "group_id:'" . $store->store_group_id . "', "
				. "merchant:'" . $store->merchant . "', "
				. "username:'" . $store->username . "', "
				. "provider_id:'" . $store->uw_provider_id . "'},";
		}
		$fields->stores .= "]";

		$js = new Form(ECASH_WWW_DIR.'js/underwriting_rules.js');

		return parent::Get_Header() . $js->As_String($fields);
	}

	public function Get_Module_HTML()
	{
		$fields = new stdClass();

		// campaign groups
		$id = 0;
		$fields->campaign_groups = "";
		$fields->campaign_groups_ary = "[";
		$campaign_groups_lookup = array();
		foreach($this->campaign_groups as $group)
		{
			$fields->campaign_groups .= "<option value='" . $id . "'>" . $group->campaign_group . "</option>";
			$fields->campaign_group_ids .= "<option value='" . $group->campaign_group_id . "'>" . $group->campaign_group . "</option>";
			$fields->campaign_groups_ary .= "\t\t\n{"
				. "id:'" . $group->campaign_group_id . "', "
				. "name:'" . $group->campaign_group . "', "
				. "risk:'" . $group->campaign_group_risk . "'},";
			$campaign_groups_lookup[$group->campaign_group_id] = $id++;
		}
		$fields->campaign_groups_ary .= "]";

		// campaign publishers
		$id = 0;
		$fields->campaign_pubs = "";
		$fields->campaign_pubs_ary = "[";
		$campaign_pubs_lookup = array();
		foreach($this->campaign_pubs as $pubs)
		{
			$fields->campaign_pubs .= "<option value='" . $id . "'>" . $pubs->campaign_publisher_name . "</option>";
			$fields->campaign_pub_ids .= "<option value='" . $pubs->campaign_publisher_id . "'>" . $pubs->campaign_publisher_name . "</option>";
			$fields->campaign_pubs_ary .= "\t\t\n{"
				. "id:'" . $pubs->campaign_publisher_id . "', "
				. "name:'" . $pubs->campaign_publisher_name . "'},";
			$campaign_pubs_lookup[$pubs->campaign_publisher_id] = $id++;
		}
		$fields->campaign_pubs_ary .= "]";

		// campaigns
		$id = 0;
		$fields->campaigns = "";
		$fields->campaigns_ary = "[";
		$campaign_lookup = array();
		foreach($this->campaigns as $camps)
		{
			$fields->campaigns .= "<option value='" . $id . "'>" . $camps->campaign_name . "</option>";
			$fields->campaign_ids .= "<option value='" . $camps->campaign_id . "'>" . $camps->campaign_name . "</option>";
			$fields->campaigns_ary .= "\t\t\n{"
				. "id:'" . $camps->campaign_id . "', "
				. "name:'" . $camps->campaign_name . "', "
				. "publisher:'" . $camps->campaign_publisher_name . "', "
				. "publisher_lookup_id:'" . $campaign_pubs_lookup[$camps->campaign_publisher_id] . "', "
				. "publisher_id:'" . $camps->campaign_publisher_id . "', "
				. "group:'" . $camps->campaign_group . "', "
				. "group_lookup_id:'" . $campaign_groups_lookup[$camps->campaign_group_id] . "', "
				. "group_id:'" . $camps->campaign_group_id . "', "
				. "risk:'" . $camps->campaign_group_risk . "', "
				. "source:'" . $camps->income_source . "', "
				. "frequency:'" . $camps->income_frequency . "', "
				. "count:'" . $camps->count . "'},";
			$campaign_lookup[$camps->campaign_id] = $id++;
		}
		$fields->campaigns_ary .= "]";

		// uw providers
		$id = 0;
		$fields->providers = "";
		$fields->providers_ary = "[";
		$provider_lookup = array();
		foreach($this->providers as $provider)
		{
			$fields->providers .= "<option value='" . $id . "'>" . $provider->uw_provider_name . "</option>";
			$fields->provider_ids .= "<option value='" . $provider->uw_provider_id . "'>" . $provider->uw_provider_name . "</option>";
			$fields->providers_ary .= "\t\t\n{"
				. "id:'" . $provider->uw_provider_id . "', "
				. "name:'" . $provider->uw_provider_name . "', "
				. "name_short:'" . $provider->name_short . "'},";
			$provider_lookup[$provider->uw_provider_id] = $id++;
		}
		$fields->providers_ary .= "]";

		// underwriter stores
		$id = 0;
		$fields->stores = "";
		$fields->stores_ary = "[";
		$store_lookup = array();
		foreach($this->stores as $store)
		{
			$fields->stores .= "<option value='" . $id . "'>" . $store->store_id . " / " . $store->username. "</option>";
			$fields->store_ids .= "<option value='" . $store->uw_store_id . "'>" . $store->store_id . " / " . $store->username. "</option>";
			$fields->stores_ary .= "\t\t\n{"
				. "id:'" . $store->uw_store_id . "', "
				. "store_id:'" . $store->store_id . "', "
				. "group_id:'" . $store->store_group_id . "', "
				. "merchant:'" . $store->merchant . "', "
				. "username:'" . $store->username . "', "
				. "provider_name:'" . $store->uw_provider_name . "', "
				. "provider_id:'" . $store->uw_provider_id . "',"
				. "provider_lookup_id:'" . $provider_lookup[$store->uw_provider_id] . "'},";
			$store_lookup[$store->uw_store_id] = $id++;
		}
		$fields->stores_ary .= "]";


		// uw inquiry
		$id = 0;
		$fields->inquiries = "";
		$fields->inquiries_ary = "[";
		$inquiry_lookup = array();
		foreach($this->inquiries as $inqs)
		{
			$fields->inquiries .= "<option value='" . $id . "'>" . $inqs->uw_inquiry_name . "</option>";
			$fields->inquiry_ids .= "<option value='" . $inqs->uw_inquiry_id . "'>" . $inqs->uw_inquiry_name . "</option>";
			$fields->inquiries_ary .= "\t\t\n{"
				. "id:'" . $inqs->uw_inquiry_id . "', "
				. "name:'" . $inqs->uw_inquiry_name . "', "
				. "provider:'" . $inqs->uw_provider_name . "', "
				. "provider_lookup_id:'" . $provider_lookup[$inqs->uw_provider_id] . "', "
				. "provider_id:'" . $inqs->uw_provider_id . "', "
				. "store_lookup_id:'" . $store_lookup[$inqs->uw_store_id] . "', "
				. "store_id:'" . $inqs->uw_store_id . "', "
				. "store:'" . $inqs->store_id . "', "
				. "username:'" . $inqs->username . "'},";
			$inquiry_lookup[$inqs->uw_inquiry_id] = $id++;
		}
		$fields->inquiries_ary .= "]";

		$id = 0;
		$fields->uw_rule_set = "";
		$fields->uw_rule_set_ary = "[";
		foreach($this->uw_rule_set as $uw_rule)
		{
			$fields->uw_rule_set .=  "<tr>"
					. "<td class='ci_campaign_col' >" .
						$uw_rule->campaign_name
					. "</td>" 
					. "<td class='ci_publisher_col' >" .
						$uw_rule->campaign_publisher_name
					. "</td>"
					. "<td class='ci_group_col' >" .
						$uw_rule->campaign_group
					. "</td>"
					. "<td class='ci_risk_col' >" .
						$uw_rule->campaign_group_risk
					. "</td>"
					. "<td class='ci_source_col' >" .
						$uw_rule->income_source
					. "</td>"
					. "<td class='ci_frequency_col' >" .
						$uw_rule->income_frequency
					. "</td>"
					. "<td class='ci_provider_col' >" .
						$uw_rule->uw_provider_name
					. "</td>"
					. "<td class='ci_inquiry_col' >" .
						$uw_rule->uw_inquiry_name
					. "</td>"
					. "<td class='ci_percent_col' >" .
						round($uw_rule->percentage) ."%"
					. "</td>"
					. "<td class='ci_count_col' >" .
						"(".$uw_rule->count.")"
					. "</td>"
					. "<td class='ci_edit_col' >" 
						. "<button onclick='edit_campaign_inquiry("
						. $uw_rule->campaign_inquiry_id .","
						. $campaign_lookup[$uw_rule->campaign_id] .","
						. $inquiry_lookup[$uw_rule->uw_inquiry_id]  .","
						. $uw_rule->count 
						. ")'> EDIT </button>"
					. "</td>"
				. "</tr>";

				$fields->uw_rule_set_ary .= "\t\t\n{"
					. "id:'" . $id . "', "
					. "campaign_id:'" . $uw_rule->campaign_id . "', " 
					. "inquiry_id:'" . $uw_rule->uw_inquiry_id . "', " 
					. "campaign_lookup_id:'" . $campaign_lookup[$uw_rule->campaign_id] . "', " 
					. "inquiry_lookp_id:'" . $inquiry_lookup[$uw_rule->uw_inquiry_id] . "', " 
					. "count:'" . $uw_rule->count . "', " 
					. "},";
			$id++;
		}
		$fields->uw_rule_set_ary .= "]";

		$fields->company_name = $this->company_name;

		$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/underwriting_rules.html");

		return $form->As_String($fields);
	}
}

?>
