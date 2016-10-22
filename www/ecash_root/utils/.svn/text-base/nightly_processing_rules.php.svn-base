<?php

include_once '../www/config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

$rule_component_array = array(
  	array('Resolve Flash Report','resolve_flash_report'),
  	array('Resolve DDA History Report','resolve_dda_history_report'),
  	array('Resolve Payments Due Report','resolve_payments_due_report'),
  	array('Resolve Open Advances Report','resolve_open_advances_report'),
  	array('Nightly Transactions Update','nightly_transactions_update'),
  	array('Resolve Past Due to Active','resolve_past_due_to_active'),
  	array('Resolve Collections New to Active','resolve_collections_new_to_act'),
  	array('Move Bankruptcy to Collections','move_bankruptcy_to_collections'),
  	array('Set Completed Accounts to Inactive','completed_accounts_to_inactive'),
  	array('Set QC Returns to 2nd Tier','set_qc_to_2nd_tier'),
  	array('Expire Watched Accounts','expire_watched_accounts'),
  	array('Reschedule Held Apps','reschedule_held_apps'),
  	array('Move Dequeued Collections to QC Ready','deq_coll_to_qc_ready'),
  	array('Complete Agent Affiliation Expiration Actions', 'cmp_aff_exp_actions'),
);

$rule_component_parm_array = array(
	array('Sunday','This rule determines which days a given task will run in the Nightly cron.'),
	array('Monday','This rule determines which days a given task will run in the Nightly cron.'),
	array('Tuesday','This rule determines which days a given task will run in the Nightly cron.'),
	array('Wednesday','This rule determines which days a given task will run in the Nightly cron.'),
	array('Thursday','This rule determines which days a given task will run in the Nightly cron.'),
	array('Friday','This rule determines which days a given task will run in the Nightly cron.'),
	array('Saturday','This rule determines which days a given task will run in the Nightly cron.'),
	array('Holidays','This rule determines whether or not a given task will run the night before a holiday.')
);

$seven_day_array = array(
	'resolve_flash_report',
	'resolve_dda_history_report',
	'resolve_payments_due_report',
	'resolve_open_advances_report',
	'nightly_transactions_update',
	'cmp_aff_exp_actions',
);

if ($_SERVER['argc'] < 2) {
	die ("Usage: load_nightly_processing_rules.php <company_id>");
}
$company_id = $_SERVER['argv'][1];
settype($company_id, 'int');

//Insert Loan Type
$mysqli->autocommit(FALSE);
$mysqli->query("
INSERT INTO `loan_type` 
  VALUES 
	(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active',$company_id, NULL,'Offline Processing Rules','offline_processing')
;");
echo("
INSERT INTO `loan_type` 
  VALUES 
	(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active',$company_id, NULL,'Offline Processing Rules','offline_processing')
;");

$loan_type_id = $mysqli->insert_id;

//Insert rule set
$mysqli->query("
INSERT INTO `rule_set`
  VALUES
	(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'active', NULL, 'Nightly Task Schedule', $loan_type_id, CURRENT_TIMESTAMP)
;");
echo("
INSERT INTO `rule_set`
  VALUES
	(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'active', NULL, 'Nightly Task Schedule', $loan_type_id, CURRENT_TIMESTAMP)
;");

$rule_set_id = $mysqli->insert_id;

foreach ($rule_component_array as $cr_index => $row) {
	$index = $cr_index + 1;
	$mysqli->query("
INSERT INTO `rule_component` 
  VALUES 
  	(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active', NULL,'{$mysqli->escape_string($row[0])}','{$mysqli->escape_string($row[1])}','no')
;");
	
	echo("
INSERT INTO `rule_component` 
  VALUES 
  	(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active', NULL,'{$mysqli->escape_string($row[0])}','{$mysqli->escape_string($row[1])}','no')
;");
		
	$rule_component_id = $mysqli->insert_id;

	//Insert Rule Set Component
	$mysqli->query("
INSERT INTO `rule_set_component`
  VALUES
	(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'active', $rule_set_id, $rule_component_id, $index)
;");

	echo("
INSERT INTO `rule_set_component`
  VALUES
	(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'active', $rule_set_id, $rule_component_id, $index)
;");
	
	$rule_set_component_id = $mysqli->insert_id;
	
	foreach ($rule_component_parm_array as $cpr_index => $component_parm_row) {
		$index = $cpr_index + 1;
		$mysqli->query("
INSERT INTO `rule_component_parm` 
  VALUES 
	(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active',NULL,$rule_component_id,'{$mysqli->escape_string($component_parm_row[0])}',NULL,$index,'{$mysqli->escape_string($component_parm_row[0])}','{$mysqli->escape_string($component_parm_row[1])}','string','no','select','array','Run Task',NULL,NULL,NULL,NULL,NULL,NULL,'Yes, No',NULL)
;");
		echo("
INSERT INTO `rule_component_parm` 
  VALUES 
	(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active',NULL,$rule_component_id,'{$mysqli->escape_string($component_parm_row[0])}',NULL,$index,'{$mysqli->escape_string($component_parm_row[0])}','{$mysqli->escape_string($component_parm_row[1])}','string','no','select','array','Run Task',NULL,NULL,NULL,NULL,NULL,NULL,'Yes, No',NULL)
;");

		$rule_component_parm_id = $mysqli->insert_id;
		
		//Insert Rule Set Component Parameter Value
		$value = in_array($row[1], $seven_day_array);
		
		if (!$value && in_array($component_parm_row[0], array('Friday', 'Saturday', 'Holidays'))) {
			$value = 'No';
		} else {
			$value = 'Yes';
		}
		
		$mysqli->query("
INSERT INTO `rule_set_component_parm_value`
  VALUES
	(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0, $rule_set_id, $rule_component_id, $rule_component_parm_id, '$value')
;");
		echo("
INSERT INTO `rule_set_component_parm_value`
  VALUES
	(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0, $rule_set_id, $rule_component_id, $rule_component_parm_id, '$value')
;");
	}
}

$mysqli->commit();
?>
