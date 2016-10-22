<?php
/* InactivateExpiredCards.php
 *
 * This currently just checks for cards which have been inactive for 90 days
 */
require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');

class ECash_NightlyEvent_InactivateExpiredCards extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'delinquent_full_pull';
	protected $timer_name = 'Inactivate_Expired_Cards';
	protected $process_log_name = 'inactivate_expired_cards';
	protected $use_transaction = FALSE;

	public function __construct()
	{
		$this->classname = __CLASS__;

		parent::__construct();
	}

	public function run()
	{
		// Sets up the Applog, any other pre-requisites in the parent
		parent::run();

		$this->Inactivate_Expired_Cards($this->start_date, $this->end_date);
	}

	private function Inactivate_Expired_Cards($start_date, $end_date)
	{
		// We only want to run on the first day of the month
		if (date('d') != '1')
			return TRUE;

		$db = ECash::getMasterDb();
        $holidays  = Fetch_Holiday_List();
        $pdc       = new Pay_Date_Calc_3($holidays);
        $biz_rules = new ECash_BusinessRulesCache($db);

        $loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
        $rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
        $rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		// This gets all applications that are in a specific terminating status, or have been inactived > $days
		$query = "
			SELECT
				card_info_id,
				application_id
			FROM
				card_info ci
			WHERE DATEDIFF(NOW(),
						   ci.expiration_date
						  ) >= 90
			
		";

		$result = $db->Query($query);

		$card_ids = array();

		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$this->log->Write("Card ID {$row->card_info_id} has been expired for 3 months. Inactivating");

			$card = ECash::getFactory()->getModel('CardInfo');
			$card->loadBy(array('card_info_id' => $row->card_info_id));
			$card->active_status = 'inactive';
			$card->save();

			$card_action = ECash::getFactory()->getModel('CardAction');
			$card_action->loadBy(array('name_short' => 'inactivate'));

			$card_action_history = ECash::getFactory()->getModel('CardActionHistory');
			$card_action_history->date_created   = time();
			$card_action_history->card_action_id = $card_action->card_action_id;
			$card_action_history->card_info_id   = $row->card_info_id;
			$card_action_history->application_id = $row->application_id;
			$card_action_history->agent_id       = ECash::getAgent()->getModel()->agent_id;

			$card_action_history->save();
		}

		return TRUE;
	}
}

?>
