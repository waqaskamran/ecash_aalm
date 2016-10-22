<?php
/**
php data_fix_convert_to_crypt3.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once "../www/config.php";
require_once(LIB_DIR . 'Payment_Card.class.php');
require_once('crypt.1.php');
require_once('crypt.3.php');

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

$sql = "
SELECT application_id,card_number,cardholder_name
FROM card_info 
-- WHERE active_status='active'
-- AND application_id=901670005
ORDER BY date_modified
";

$bad_card_numbers_array = array();
$bad_names_array = array();

$app_count = 0;
//echo "'$sql'\n\n";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
    	$app_count++;
    	$application_id = $row->application_id;
	$card_number_crypted1 = $row->card_number;
	$cardholder_name_crypted1 = $row->cardholder_name;

	$crypt1 = new Crypt_1();
	$card_number = $crypt1->Decrypt($card_number_crypted1);
	$cardholder_name = $crypt1->Decrypt($cardholder_name_crypted1);

	if (preg_match('/[^0-9]/', $card_number))
	{
		$bad_card_numbers_array[$application_id] = $card_number;
	}

	if (!preg_match('/[^\W_ ] /', $cardholder_name))
	{
		$bad_names_array[$application_id] = $cardholder_name;
	}

	$crypt3 = new Crypt_3();
	$card_number_crypted3 = $crypt3->Encrypt($card_number);
	$cardholder_name_crypted3 = $crypt3->Encrypt($cardholder_name);

    echo $application_id, 
    	" card_number_crypted1: ", $card_number_crypted1, 
    	" holder_crypted1: ", $cardholder_name_crypted1,
    	" card_number: ", $card_number,
   	" holder: ", $cardholder_name,
	" card_number_crypted3: ", $card_number_crypted3,
	" holder_crypted3: ", $cardholder_name_crypted3,
    "\n";

    $sql1 = "
    	UPDATE card_info
	SET card_number='{$card_number_crypted3}',
	cardholder_name='{$cardholder_name_crypted3}'
	WHERE application_id={$application_id}
    ";
    //$db->query($sql1);
}

echo $app_count, " apps\n";

//var_dump($bad_card_numbers_array);
//var_dump($bad_names_array);
?>

