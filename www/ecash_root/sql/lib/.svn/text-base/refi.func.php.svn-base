<?php

require_once(LIB_DIR . 'common_functions.php');
require_once(SERVER_CODE_DIR . 	"schedule_event.class.php");
require_once(SQL_LIB_DIR . "util.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php"); //mantis:4454
require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");
require_once(LIB_DIR . "AmountAllocationCalculator.php");
require_once(LIB_DIR . "PaymentArrangementException.php");
require_once(SQL_LIB_DIR . 'agent_affiliation.func.php');
require_once(ECASH_COMMON_DIR . "ecash_api/interest_calculator.class.php");

/**
 * Used to process the post refi loan 
 *
 * It transfers and settles the amounts from the previous loans
 *
 * @param integer	$application_id
 */
    function Handle_Refi_Applications($application_id)
    {
//error_log('Gathering refis in: '.__METHOD__.' || '.__FILE__);
        $this_app = ECash::getApplicationById($application_id);
        
        $ssn = $this_app->ssn;
        $customer = ECash::getFactory()->getCustomerBySSN($ssn, $this_app->company_id);
        $applications = $customer->getApplications();
        
        $today = date("Y-m-d");
        
        $total_prnc = 0.0;
        $total_fees = 0.0;
        $e = array();
        $en = array();
        $account = new stdClass();
        foreach($applications as $app)
        {
            $status = $app->getStatus();
            if($status->level0 == 'refi'){
                //array_push($refi_aps,$app->application_id);
//error_log('Found refi ap: '.$app->application_id);
                $balance_info = Fetch_Balance_Information($app->application_id);

		$account->event_amount_type = 'service_charge';
	        $account->amount = -$balance_info->service_charge_balance;
		$account->num_reattempt = 0;

//error_log('Old balance: '.print_r($balance_info,true));
                // first remove last interest accured.
                $e = Schedule_Event::MakeEvent($today, $today, array($account), 'adjustment_internal','Drop Interest for Refi', 'scheduled','manual');                
//error_log(' old interest removed event: '.print_r($e,true));
                Post_Event($app->application_id, $e);

                // and any fees,
                if ($balance_info->fee_balance != 0)
                {
                    $account->event_amount_type = 'fee';
                    $account->amount = -$balance_info->fee_balance;
		    $account->num_reattempt = 0;

                    $e = Schedule_Event::MakeEvent($today, $today, array($account), 'adjustment_internal','Drop Fees for Refi', 'scheduled','manual');
		    Post_Event($app->application_id, $e);
                }
		if ($b->type == 'converted_service_chg_bal') return 1;
                
                // get and sum balance amount from old application and add it to pricipal
                $total_prnc += $balance_info->principal_balance;
             	$account->event_amount_type = 'principal';
	        $account->amount = -$balance_info->principal_balance;
		$account->num_reattempt = 0;

                $e = Schedule_Event::MakeEvent($today, $today, array($account), 'refi_foward','Transfer Balance to Refi', 'scheduled','manual');     
//error_log(' transferring pricipal from old loan: '.print_r($e,true));

                Post_Event($app->application_id, $e);

                //remove old scheduled events
                Remove_Unregistered_Events_From_Schedule($app->application_id);
                
                // mark old application as paid
                Update_Status(NULL,$app->application_id,array("paid","customer","*root"));
//error_log('Updating old refi app complete.');
            }
        }

        if ($total_prnc > 0) // refis exists, lets set the new balance
        {
	    $account->event_amount_type = 'principal';
	    $account->amount = $total_prnc;
	    $account->num_reattempt = 0;

            $en = Schedule_Event::MakeEvent($today, $today, array($account), 'converted_principal_bal','Transfer Balance to Refi', 'scheduled','manual');                
//error_log(' transfered balance: '.print_r($e,true));
	    Post_Event($application_id, $en);
        }
        
        return $total;
    }

?>
