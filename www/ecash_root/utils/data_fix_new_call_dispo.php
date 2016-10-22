<?php
/**
php data_fix_new_call_dispo.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');

$db = ECash::getMasterDb();

$new_dispo_array = array('promise_to_pay_ach','id_theft_2','w_eu_contact_no_work_2','verif_emp_2');

	foreach ($new_dispo_array as $new_dispo)
	{
		$query = 
		"
			select loan_action_id
			from loan_actions
			where name_short = '{$new_dispo}'
			and status = 'ACTIVE'
		";
		$result = $db->Query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);
		$loan_action_id = intval($row->loan_action_id);

		echo "loan_action_id: ", $loan_action_id, "\n";

		////////////////////////////////////////
		$query1 = "
		SELECT loan_action_section_id
		FROM loan_action_section
		WHERE name_short NOT LIKE '%INCOMING'
		ORDER BY name_short
		";
		$result1 = $db->Query($query1);
		while ($row1 = $result1->fetch(PDO::FETCH_OBJ))
		{
			$loan_action_section_id = intval($row1->loan_action_section_id);

			echo "loan_action_section_id: ", $loan_action_section_id, "\n";

			$query3 = "
				insert ignore into
				loan_action_section_relation
				set
				date_modified = NOW(),
				date_created = NOW(),
				loan_action_id = {$loan_action_id},
				loan_action_section_id = {$loan_action_section_id}
			 ";
			 $db->Query($query3);
		}
	}

?>
