<?php
try 
{
	define("WARN_THRESHOLD", 0.25);
	define("CRIT_THRESHOLD", 0.50);

	require_once(dirname(__FILE__)."/../www/config.php");

	$db = ECash::getMasterDb();

	$status_query = "
		SELECT application_status_id
		FROM application_status_flat
		WHERE (level2 = 'applicant')
		AND (level3 = '*root')
		AND (level1='underwriting' OR level1='verification')
	";
	$app_statuses = $db->querySingleColumn($status_query);

	$main_query = "
		SELECT c.name, c.name_short, a.company_id, a.is_react, count(*) as 'count'
		FROM application a, company c
		WHERE a.application_status_id IN (".implode(",",$app_statuses).") AND c.company_id = a.company_id
		AND c.company_id < 100
		GROUP BY a.company_id,a.is_react
	";
	$st = $db->query($query);

	$dists = array();
	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		if (!isset($dists[$row->name_short]))
		{
			$dists[$row->name_short] = array();
		}
		$dists[$row->name_short][$row->is_react] = intval($row->count);
	}

	$alerts = array();
	$max = 0.0;
	$min = 1.0;
	foreach ($dists as $company => $counts) 
	{
		if (isset($counts['yes'])) $total = $counts['yes'];
		else $counts['yes'] = 0;
		if (isset($counts['no'])) $total += $counts['no'];
		else $counts['no'] = 0;

		$ratio = floatval($counts['yes']) / floatval($total);
		if ($ratio > WARN_THRESHOLD) 
		{
			$alerts[$company] = $ratio;
			if ($ratio > $max) $max = $ratio;
			if ($ratio < $min) $min = $ratio;
		}
	}

	// Determine our return code
	if ($max >= CRIT_THRESHOLD) $return_code = 2;
	else $return_code = 1;

	$min = number_format($min * 100.0, 2);
	$max = number_format($max * 100.0, 2);

	// Determine what the message will say
	if (count($alerts) == count($dists)) 
	{
		// All companies have problematic ratios
		echo "React Ratio: All companies high (min:{$min}%)\n";
		exit($return_code);
	} 
	else if (count($alerts) > 1) 
	{ 
		// Some but not all have problematic ratios
		echo "React Ratio: Some companies high (min:{$min}%)\n";
		exit($return_code);
	} 
	else if (count($alerts) == 1) 
	{
		// Only one is problematic
		foreach ($alerts as $company => $ratio) 
		{
			$company = strtoupper($company);
			echo "React Ratio: {$company} is at {$max}%.\n";
			exit($return_code);
		}
	} 
	else 
	{
		// All quiet on the western front
		exit(0);
	}
} 
catch(Exception $e) 
{
	echo "React Ratio: Unknown error occurred.\n";
	exit(3);
}

?>
