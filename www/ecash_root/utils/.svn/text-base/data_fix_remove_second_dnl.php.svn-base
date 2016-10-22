<?php
/**
php data_fix_remove_second_dnl.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once "../www/config.php";
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(CUSTOMER_LIB."failure_dfa.php");
require_once(SERVER_CODE_DIR . 'comment.class.php');

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

echo "Started...\n";

$ssn_array = array();
$dnl_flag_id_remove_array = array();

                $sql = "
                	SELECT ssn, count(*) AS number_of_active
			FROM do_not_loan_flag
			WHERE active_status = 'active'
			GROUP BY ssn
			HAVING number_of_active > 1
		";
                $result = $db->query($sql);
                while ($row = $result->fetch(PDO::FETCH_OBJ))
                {
                	$ssn = $row->ssn;
                        //echo $ssn, "\n";
			if (!empty($ssn) || $ssn != "" || $ssn != '') $ssn_array[] = $ssn;
                }

		//var_dump($ssn_array);

		$row = $result = $sql = $ssn = NULL;

		foreach ($ssn_array as $ssn)
		{
			$sql = "
				SELECT dnl_flag_id
				FROM do_not_loan_flag
				WHERE ssn = '{$ssn}'
				ORDER BY dnl_flag_id ASC
				LIMIT 1
			";
			$result = $db->query($sql);
			$row = $result->fetch(PDO::FETCH_OBJ);
			$dnl_flag_id_remove = intval($row->dnl_flag_id);
			//echo $dnl_flag_id_remove, "\n";
			$dnl_flag_id_remove_array[] = $dnl_flag_id_remove;
		}

		//var_dump($dnl_flag_id_remove_array);

		$dnl_flag_id_remove = $row = $result = $sql = $ssn = NULL;

		foreach ($dnl_flag_id_remove_array as $dnl_flag_id_remove)
		{
			echo $dnl_flag_id_remove, "\n";
			$sql = "
				DELETE FROM do_not_loan_flag
				WHERE dnl_flag_id = $dnl_flag_id_remove
			";
			//$db->query($sql);
		}
?>
