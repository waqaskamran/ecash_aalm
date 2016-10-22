<?php
/**
php withdrawn_to_cancel.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');

$db = ECash::getSlaveDb();
//$company_id = ECash::getCompany()->company_id;

$sql = "
SELECT distinct ap.application_id
from application ap
join transaction_register tr using (application_id)
where ap.application_status_id in (19,109)
and ap.application_status_id not in (194)
and tr.transaction_type_id=37
and tr.transaction_status='complete'
ORDER BY tr.date_created desc
";

$app_count = 0;
//echo "'$sql'\n\n";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_ASSOC))
{
    $app_count++;
    $application_id = $row['application_id'];
    Update_Status(NULL, $application_id, 'canceled::servicing::customer::*root');
    echo $application_id, "\n";
}

echo $app_count, " apps\n";

?>

