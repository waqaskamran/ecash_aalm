<?php

require_once dirname(realpath(__FILE__)) . '/../www/config.php';

function Main()
{
	$factory = ECash::getFactory();
	$db =  $factory->getDb();
	
	$flag_list = $factory->getReferenceList('FlagType');
	$flag_id = $flag_list->toId('has_fatal_ach_failure');
	$status_list = $factory->getReferenceList('ApplicationStatusFlat');
	$active_status_id = $status_list->toId('active::servicing::customer::*root');
	
	
	//get applications
	$sql = "			SELECT 
    app.application_id,
    app.bank_aba,
    app.bank_account,
    ach.bank_aba,
    ach.bank_account,
    flag.flag_type_id,

    achrc.name_short,
    achrc.name,
    achrc.is_fatal
FROM
    application app
LEFT JOIN
    application_flag flag ON app.application_id = flag.application_id
JOIN 
    ach ON (ach.application_id = app.application_id
			AND ach.ach_status = 'returned'
			AND ach.bank_aba = app.bank_aba
			AND ach.bank_account = app.bank_account
			)
JOIN
   ach_return_code achrc ON achrc.ach_return_code_id = ach.ach_return_code_id 
WHERE
    achrc.is_fatal = 'yes'
AND
    (flag.flag_type_id IS NULL OR flag.flag_type_id != {$flag_id})

GROUP BY app.application_id
LIMIT 6500
;

	";
	$result = $db->query($sql);
	//die("Found " . $result->rowCount() . " rows\n");
	$i = 0;
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		
		//get their next paydate (next paydate after their last scheduled date if they have stuff scheduled for the future)
		
		$application_id = $row['application_id'];
		$application = ECash::getApplicationById($application_id);
		$flags = $application->getFlags();
		if(!$flags->get('has_fatal_ach_failure'))
		{
			$i++;
			echo "\nAdding fatal ach flag for {$row['application_id']} ";
			$flags->set('has_fatal_ach_failure');
		}
		
		
	}
	echo "\nAdded {$i} fatal ACH flags\n\n";
}


Main();

?>