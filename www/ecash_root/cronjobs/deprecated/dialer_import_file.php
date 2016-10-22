<?php
/*  Usage example:  php -f dailer_import_file.php 

    This utility creates a tab delimited list of customers that have recently paid off
    their accounts to be imported into an auto-dialer.  After the list is created, it
    is then emailed as an attachment to CLK for processing.

    It should be run sometime around close of business on Fridays.

    Before using this utility, verify all of the defines below.
*/

require_once('../www/config.php');
include_once('/virtualhosts/lib/mime_mail.1.php');

function Main()
{	
	global $server;
	$company_id = $server->company_id;
	$log    = $server->log;

	try
	{
		$db = ECash::getMasterDb();
		$statuses = Get_Statuses($db);
	}
	catch (Exception $e)
	{
		$log->Write("Dialer Report: Could not get mysql.");
	}

	define("RECIPIENT", 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net'); //mantis:7147
	//define("RECIPIENT", 'brian.ronald@sellingsource.com');
	//define("RECIPIENT", 'alexander.lyakhov@sellingsource.com');

	echo "Creating Dialer Import File...\n";
	try
	{
		$report = Fetch_Dialer_Import_Data($db, 7500, $company_id); //mantis:6095 - $company_id
	}
	catch (Exception $e)
	{
		$log->Write("Dialer Report: Could not fetch dialer import data.");
	}

	$report_name = "Dialer Import File for " . strtoupper($server->company) . " " . date("m-d-Y");
	$report_filename = "Dialer_Import_File_" . strtoupper($server->company) . "_" . date("m-d-Y") . ".txt";

	$attachment = new stdClass();
	$attachment->contents = Format_Report($report);
	$attachment->filename = $report_filename;

	echo "Emailing File..\n";
	try
	{
		Email_Report(RECIPIENT, "reports@nowhere.com", $report_name, "Open the attached file(s) with CSV File.", $attachment);
	}
	catch (Exception $e)
	{
		$log->Write("Dialer Report: Could not emailed report.");
	}
}

function Email_Report($to, $from, $subject, $body, $attachment)
{
	$mailer = new mime_mail();

	$mailer->to = $to;
	$mailer->from = $from;
	$mailer->subject = $subject;
	$mailer->body = $body;

	$mailer->add_attachment($attachment->contents, basename($attachment->filename));

	$message = $mailer->get_mail();

	$mailer->send();
}

// Grabs each row from the report and tab delimits them, then ends it with a linefeed.
// The result is a big string of the output.
function Format_Report($report)
{
	$output = "";	
	foreach($report as $row)
	{
		$line = "";
		foreach($row as $column)
		{
			$line .= "$column \t";
		}
		$line = rtrim($line, "\t");
		$output .= $line . "\n";
	}
	return($output);
}

//mantis:7148 - switched to customer_id from ssn
function Fetch_Dialer_Import_Data($db, $limit = 500, $company_id) //mantis:6095 - remove 3
{
	require_once(SQL_LIB_DIR.'fetch_status_map.func.php');
	$status_map = Fetch_Status_Map();
	$inactive_array = array(Search_Status_Map('paid::customer::*root', $status_map));
	$inactive_paid = implode(",", $inactive_array);

	$sql_limit = $limit + 7000; //mantis:7147
	
	$query = "
            SELECT  
                    a.application_id as `App ID`,
                    a.customer_id as `Cust ID`,
                    a.name_last as `Last Name`,
                    a.name_first as `First Name`,
                    IFNULL(a.name_middle, '') as `Middle Initial`,
                    a.street as `Home Street Address`,
                    IFNULL(a.unit,'') as `Home Unit`,
                    a.city as `Home City`,
                    a.county as `Home County`,
                    a.state as `Home State`,
                    a.zip_code as `Home Zip`,
                    a.phone_home as `Home Phone`,
                    IFNULL(a.phone_cell,'') as `Cell Phone`,
                    a.phone_work as `Employer Phone`,
                    (SELECT  rscpv.parm_value
                     FROM    rule_set_component rsc, 
                             rule_component rc, 
                             rule_component_parm rcp, 
                             rule_set_component_parm_value rscpv
                     WHERE   rsc.rule_set_id = a.rule_set_id
                     AND     rc.rule_component_id = rsc.rule_component_id
                     AND     rcp.rule_component_id = rsc.rule_component_id
                     AND     rscpv.rule_set_id = rsc.rule_set_id
                     AND     rscpv.rule_component_id = rsc.rule_component_id
                     AND     rscpv.rule_component_parm_id = rcp.rule_component_parm_id
                     AND     rc.name_short = 'max_react_loan_amount'
                     AND     rcp.parm_name <= a.income_monthly
                     ORDER BY CAST(rcp.parm_name AS UNSIGNED) DESC
                     LIMIT 1) as `Max Next Loan Amount`, 
            	     a.date_application_status_set as `Last Payoff Date` 
		
		FROM  
			application a 
		
		LEFT JOIN application_field af ON af.table_row_id = a.application_id 
		
		WHERE 
			a.application_status_id IN ('{$inactive_paid}') 
		AND a.state NOT IN ('GA','KS','WV') 
		AND a.company_id = {$company_id} 
		AND (af.application_field_attribute_id != 2 OR af.application_field_attribute_id IS NULL) 
		AND (
			NOT EXISTS (SELECT 1 FROM do_not_loan_flag dnl WHERE dnl.ssn = a.ssn) 
			OR EXISTS (SELECT ovr.ssn FROM do_not_loan_flag_override ovr WHERE ovr.ssn = a.ssn AND ovr.company_id=a.company_id)
		    ) 
		AND a.income_monthly >= 800  
		AND DATE_SUB(CURDATE(),INTERVAL 7 DAY) >= a.date_application_status_set 
		ORDER BY a.date_application_status_set DESC
		LIMIT {$sql_limit}";

	// Changed: DATE_SUB(a.date_application_status_set, INTERVAL -7 DAY) <= CURDATE()
	
	try
	{
		$result = $db->query($query);
	}
	catch (Exception $e)
	{
		$log->Write("Dialer Report: Could not get result of query.");
	}

	$report = array();
	$included_apps = array();
	while($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		if(! in_array($row['Cust ID'], $included_apps))
		{
			if(! Check_For_Other_Apps($db, $company_id, $row['App ID'], $row['Cust ID']))
			{
				$included_apps[] = $row['Cust ID'];
				$report[] = $row;
			}
		}
		
		if(count($report) >= $limit)
			break;
	}
	//echo "Size: " . count($report) . "\n";
	return($report);
}

//mantis:7148 - switched to customer_id from ssn
// Returns true if there is another app with the same Customer ID whose status is in
// the exclude list.
function Check_For_Other_Apps($db, $company_id, $application_id, $customer_id)
{
	global $statuses;
	
	// Application Statuses to Exclude - If any other apps exist for the 
	// app in question and they match these statuses, we don't want them.
	$exclude_list = array(	'new::collections::customer::*root',
							'dequeued::contact::collections::customer::*root',
							'queued::contact::collections::customer::*root',
							'follow_up::contact::collections::customer::*root',
							'past_due::servicing::customer::*root',
							'current::arrangements::collections::customer::*root',
							'pending::external_collections::*root',
							'sent::external_collections::*root',
							'recovered::external_collections::*root',
							'unverified::bankruptcy::collections::customer::*root',
							'verified::bankruptcy::collections::customer::*root',
							'ready::quickcheck::collections::customer::*root',
							'sent::quickcheck::collections::customer::*root',
							'approved::servicing::customer::*root',
							'active::servicing::customer::*root',
							//'denied::applicant::*root',
							'queued::underwriting::applicant::*root',
							'dequeued::underwriting::applicant::*root',
							'queued::verification::applicant::*root',
							'dequeued::verification::applicant::*root');
	
	$sql = "
	SELECT * 
	FROM application 
	WHERE customer_id = {$customer_id}
	AND application_id != {$application_id} 
	AND company_id = {$company_id}";
	
	$result = $db->query($sql);
	
	if($result->rowCount() == 0)
		return false;
	
	while($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$status_id = $row['application_status_id'];
		$status_chain = $statuses[$status_id]['chain'];
		
		if(in_array($status_chain, $exclude_list))
		{
			return true;		
		}
	}
	return false;
}

// Pulls all the leaf node statuses and data that may be useful
function Get_Statuses($db)
{
	$statuses = array();
	$query = "
		SELECT  ass.application_status_id, 
				ass.name, 
				ass.name_short,
				asf.level0, asf.level1, asf.level2, asf.level3, asf.level4
		FROM application_status ass
		LEFT JOIN application_status_flat AS asf ON (ass.application_status_id = asf.application_status_id)
		WHERE ass.application_status_id NOT IN
			(   SELECT application_status_parent_id 
				FROM application_status
				WHERE active_status = 'active'
				AND application_status_parent_id IS NOT NULL  )
				AND ass.active_status = 'active'
		ORDER BY name";
		
	$result = $db->query($query);
	while($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$chain = $row->level0;
		if($row->level1 != null) { $chain .= "::" . $row->level1; }
		if($row->level2 != null) { $chain .= "::" . $row->level2; }
		if($row->level3 != null) { $chain .= "::" . $row->level3; }
		if($row->level4 != null) { $chain .= "::" . $row->level4; }

		$statuses[$row->application_status_id]['id'] = $row->application_status_id;
		$statuses[$row->application_status_id]['name_short'] = $row->name_short;
		$statuses[$row->application_status_id]['name'] = $row->name;
		$statuses[$row->application_status_id]['chain'] = $chain;
	}
	return $statuses;
}


?>
