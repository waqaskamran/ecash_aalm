<?php
/**
php data_fix_encrypt_decrypt_card.php "<string_to encrypt_decrypt>" <action_id>

SELECT * FROM card_info WHERE application_id=901396681;
*/

putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once "../www/config.php";
require_once(LIB_DIR . 'Payment_Card.class.php');
require_once('crypt.1.php');
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

if ($_SERVER['argc'] != 3)
{
echo "Usage: php data_fix_action.php <string_to encrypt_decrypt> <action_id from switch statement>, for example, data_fix_encrypt_decrypt_card.php \"GYFYIUIUO\" 2 (to decrypt).\n";
exit(1);
}

$string = $_SERVER['argv'][1];
$action = $_SERVER['argv'][2];

echo $string, " ", $action, "\n";

switch($action)
{
        case 1:
                //$result = Payment_Card::encrypt($string);

		//$crypt = new Crypt_1();
		$crypt = new Crypt_3();
		$result = $crypt->Encrypt($string);
                
		echo "Action: encrypt. Result: ", $result, "\n";
                break;
        case 2:
                //$result = Payment_Card::decrypt($string);
		
		//$crypt = new Crypt_1();
		$crypt = new Crypt_3();
		$result = $crypt->Decrypt($string);
                
		echo "Action: decrypt. Result: ", $result, "\n";
                break;

        default:
                echo "No valid action is specified.", "\n";
                break;
}

?>
