<?php

$target_co_id = 7;//aml

require_once('/virtualhosts/lib/mysqli.1.php');
$db = new MySQLi_1('db1.clkonline.com', 'test_ecash', '3c4shl1n3', 'ldb',13306);

$db->Start_Transaction();
// First clear out the old permissions and access groups for the target company
$result = $db->Query("select access_group_id from access_group where system_id = 3 and company_id <> 3 and company_id = {$target_co_id}");
while ($row = $result->Fetch_Object_Row()) {
	$q = "delete from acl where access_group_id = {$row->access_group_id} and company_id = {$target_co_id}"; echo $q ."\n"; $db->Query($q);
	$q = "delete from agent_access_group where access_group_id = {$row->access_group_id} and company_id = {$target_co_id}"; echo $q ."\n"; 	$db->Query($q);
	$q = "delete from access_group where access_group_id = {$row->access_group_id} and company_id = {$target_co_id}"; echo $q . "\n"; $db->Query($q);
	$q = "delete from access_group_control_option where access_group_id = {$row->access_group_id}"; echo $q."\n"; $db->Query($q);
}

// Now determine the correct memberships and copy them

// source access groups
$source_groups = array();
$r = $db->Query('select * from access_group where company_id = 3 and system_id = 3');
while ($row = $r->Fetch_Object_Row()) {

	// Make the new access group for the target company
	$q = "insert into access_group(date_created,company_id,system_id,name) values (current_timestamp(),{$target_co_id},3,'{$row->name}')"; echo $q."\n";
	$db->Query($q);
	$agid = $db->Insert_Id();
	
	// Copy agent_access_group associations
	$q = "select * from agent_access_group where company_id = 3 and access_group_id = {$row->access_group_id}"; echo $q."\n";
	$aag_result = $db->Query($q);
	while ($aag_row = $aag_result->Fetch_Object_Row()) {
		$q = "insert into agent_access_group(date_created,company_id,agent_id,access_group_id) values (current_timestamp(),{$target_co_id},{$aag_row->agent_id},{$agid})";
		echo $q."\n";
		$db->Query($q);
	}

	// Now copy acl associations
	$q = "select * from acl where company_id = 3 and access_group_id = {$row->access_group_id}"; echo $q."\n";
	$sec_result = $db->Query($q);
	while ($sec_row = $sec_result->Fetch_Object_Row()) {
		$q = "insert into acl(date_created, company_id,access_group_id,section_id,read_only) values (current_timestamp(),{$target_co_id},{$agid},{$sec_row->section_id},{$sec_row->read_only})";
		echo $q."\n";
		$db->Query($q);
	}

	// Finally do control options
	$q = "select * from access_group_control_option where access_group_id = {$row->access_group_id}"; echo $q."\n";
	$agco_result = $db->Query($q);
	while ($agco_row = $agco_result->Fetch_Object_Row()) {
		$q = "insert into access_group_control_option(date_created,access_group_id,control_option_id) values (current_timestamp(), {$agid}, {$agco_row->control_option_id})";
		echo $q."\n";
		$db->Query($q);
	}
}

// All done!
$db->Commit();
?>