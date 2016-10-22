<?php
/* PurgeInactiveCards.php
 *
 * This currently just checks for cards which have been inactive for 90 days
 * and cards which are in specific statuses, and it DELETES them.
 */
require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');

class ECash_NightlyEvent_PurgeInactiveCards extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'delinquent_full_pull';
	protected $timer_name = 'Purge_Inactive_Cards';
	protected $process_log_name = 'purge_inactive_cards';
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

		$this->Purge_Inactive_Cards($this->start_date, $this->end_date);
	}

	private function Purge_Inactive_Cards($start_date, $end_date)
	{
		$db = ECash::getMasterDb();
        $holidays  = Fetch_Holiday_List();
        $pdc       = new Pay_Date_Calc_3($holidays);
        $biz_rules = new ECash_BusinessRulesCache($db);

        $loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
        $rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
        $rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		$days = ((!empty($rules['card_rules']['purge_inactive_days']))) ? $rules['card_rules']['purge_inactive_days'] : 90;

		// This gets all applications that are in a specific terminating status, or have been inactived > $days
		$query = "
                        SELECT
                                card_info_id,
                                ci.application_id
                        FROM
                                card_info ci
                        JOIN
                                application app ON (ci.application_id = app.application_id)
                        JOIN
                                application_status ass ON (app.application_status_id = ass.application_status_id)
                        WHERE
                                (
                                    /* 90 days after inactivation event, purge the card */
                                    DATEDIFF(NOW(),
                                    (
                                        SELECT
                                            MAX(cah.date_created)
                                        FROM
                                            card_action_history cah
                                        JOIN
                                            card_action ca ON (ca.card_action_id = cah.card_action_id)
                                        WHERE
                                            ca.name_short='inactivate'
                                        AND
                                            cah.card_info_id = ci.card_info_id
                                     )) > {$days}
                                 )
                                 OR
                                 (
                                     /* Or if the application is in one of the following statuses for 90+ days */
                                     ass.name IN ('Withdrawn',
                                                  'Confirm Declined',
                                                  'Disagree',
                                                  'Declined',
                                                  'Denied',
                                                  'Inactive (Paid)',
                                                  'Inactive (Recovered)',
                                                  'Chargeoff',
                                                  'Bankruptcy Verified',
                                                  'Deceased Verified',
                                                  'Inactive (Settled)',
                                                  'Second Tier (Sent)',
                                                  'Write Off',
                                                  'Fraud Verified')
                                     AND
                                        /* most likely won't be perfect, but suitable for this */
                                        DATEDIFF(NOW(),app.date_application_status_set) > {$days}


                                 )
		";

		$result = $db->Query($query);

		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$this->log->Write("Card ID {$row->card_info_id} has been inactivated for over {$days} days, or the application {$row->application_id} is in a terminating status. Purging");
		
			$query = "DELETE FROM card_info WHERE card_info_id = '{$row->card_info_id}';";
	
			$dresult = $db->Query($query);
		}

		return TRUE;
	}
}

?>
