<?php
/**
php data_fix_insert_subsection.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');

$db = ECash::getMasterDb();

//$insert_under = 'application';
$insert_under = 'transactions';

	//foreach (array('internal','customer_service','account_mgmt','verification','underwriting') as $section)
	//foreach (array('verification','underwriting') as $section)
	foreach (array('internal','customer_service','account_mgmt') as $section)
	{
		$query = 
		"
			select 
				section_id,
				level
			from 
				section 
			where
				system_id = 3
			and
				active_status = 'active'
			and 
				name = '{$section}'
		";
		$result = $db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$trans_section_parent_id = intval($row['section_id']);
		$level = intval($row['level']);
		$trans_level = $level + 1;
		$new_level = $level + 2;

		$query = 
		"
			select 
				section_id
			from 
				section 
			where
				system_id = 3
			and
				active_status = 'active'
			and 
				name = '{$insert_under}'
			and
				section_parent_id = {$trans_section_parent_id}
		";
		$result = $db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$new_section_parent_id = intval($row['section_id']);


		$query = 
		"
			select 
				sequence_no 
			from
				section 
			where
				section_parent_id = {$new_section_parent_id} 
			and
				system_id = 3 
			and
				active_status = 'active' 
			and
				level = {$new_level} 
			order by sequence_no desc 
			limit 1
		";
		$result = $db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$sequence_no = intval($row['sequence_no']);
		$new_sequence = $sequence_no + 5;

		echo $new_section_parent_id, " ", $new_sequence, " ", $new_level, "\n";


		$query = 
		"
			insert ignore into
				section
			set
				date_created = now(),
				active_status = 'active',
				system_id = 3,
				-- name = 'designate_ach_provider',
				-- description = 'Designate ACH Provider',
				name = 'schedule_reattempt',
				description = 'Schedule Reattempt',
				section_parent_id = {$new_section_parent_id},
				sequence_no = {$new_sequence},
				level = {$new_level},
				read_only_option = 1
		";
		$db->Query($query);
	}

?>
