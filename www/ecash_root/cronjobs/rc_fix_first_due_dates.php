<?php

require_once(SQL_LIB_DIR . "util.func.php");
require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");

function main()
{
	global $server;

	$holidays = Fetch_Holiday_List();
	$pdc = new Pay_Date_Calc_3($holidays);

	echo "Processing Pre-Fund Apps\n";
	$apps = Fetch_Prefund_Apps($server->company_id);

	$ten_days = strtotime("+10 Days");

	foreach($apps as $a)
	{
		echo "Application: {$a->application_id} ";
		if(strtotime($a->date_first_payment) > $ten_days)
		{
			echo " .. {$a->date_first_payment} already > 10 days, skipping\n";
			continue;
		}
		else
		{
			// Get what would be the most recent last paydate
			$date_first_payment = Get_Date_First_Payment($pdc, $a->application_id);

			if($date_first_payment)
			{
				echo "{$a->date_first_payment} not within grace period or in the past, updating to $date_first_payment\n";
				Update_Date_First_Payment($a->application_id, $date_first_payment);
			}
			else
			{
				echo "Could not generate a date!\n";
			}
		}
	}
}				

function Fetch_Prefund_Apps($company_id)
{
	$db = ECash::getMasterDB();
	
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT  application_id, date_first_payment, last_paydate
					FROM    application
					WHERE   application_status_id IN (8,9,10,11,13,153,154,14,155,5,11)
					AND     company_id = {$company_id} ";
        
        $result = $db->query($query);
        while($row = $result->fetch(PDO::FETCH_OBJ))
        {
                $apps[] = $row;
        }

        return $apps;
}

function Get_Date_First_Payment($pdc, $application_id)
{
        $data = Get_Transactional_Data($application_id);
        $dates = $pdc->Calculate_Pay_Dates($data->info->paydate_model, $data->info->model,
                                           $data->info->direct_deposit, 48, date('m/d/y', time()));

        // Just grab the first date that is in the future.
        foreach($dates as $date)
        {
                $ten_days = strtotime("+10 days", time());

                if(strtotime($date) > $ten_days)
                {
                        return $date;
                        break;
                }
        }
}

function Update_Date_First_Payment($application_id, $date_first_payment)
{
	$db = ECash::getMasterDB();	
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			UPDATE  application
			SET     date_first_payment = '{$date_first_payment}'
			WHERE   application_id = $application_id ";
	$result = $db->query($query);
}

