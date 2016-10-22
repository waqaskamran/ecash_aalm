<?php
	require_once(dirname(__FILE__) . '/../www/config.php');
	require_once('general_exception.1.php');
	require_once('/virtualhosts/lib5/prpc/client.php');
	
	if(empty($argv[1]))
	{
		die("Usage: {$argv[0]} <company_short>\n");
	}
	else
	{
		$company = $argv[1];
	}
	
	main($company);
	
	function main($company)
	{	
		$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
		$base_config = CUSTOMER . '_Config_' . EXECUTION_MODE;
		$config_filename = CUST_DIR . "code/" . $enterprise_prefix . '/Config/' . $company . '.php';
		
		try
		{
			require_once($config_filename);
			$class_config = $company . '_CompanyConfig';
			ECash::setConfig(new $class_config(new $base_config()));
		}
		catch(Exception $e)
		{
			throw new Exception("Invalid company configuration class or company config file does not exist: $config_filename");
		}
		
		$url = ECash::getConfig()->CONDOR_SERVER;
		
		$prpc = new Prpc_Client($url);
		$condor_template_list = $prpc->Get_Template_Names();
		
		asort($condor_template_list);
		
	
		$db = ECash::getMasterDb();	
		$sql = "
			select dl.name
			from document_list as dl
			join company as c on c.company_id = dl.company_id
			where c.name_short = '{$company}'
			and dl.document_api = 'condor'
			and dl.active_status = 'active'
			order by dl.name
		";
		$result = $db->query($sql);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$ecash_template_list[] = $row->name;
		}
		
		$templates = array_merge($condor_template_list, $ecash_template_list);
		$templates = array_unique($templates);
		asort($templates);

		echo "Template Name                                    eCash  Condor\n";
		echo "----------------------------------------------------------------\n";

		$format = "%-50s         %-1s      %-1s\n";
		
		foreach($templates as $t)
		{ 
			$ecash  = false;	$ecash_label  = '';
			$condor = false;	$condor_label = '';
			
			if(in_array($t, $ecash_template_list))  $ecash  = true;
			if(in_array($t, $condor_template_list)) $condor = true;
			
			if($ecash === true && $condor === true)
			{
				$ecash_label  = color('X', '1;32');
				$condor_label = color('X', '1;32');
			}
			else if ($ecash === true)
			{
				$ecash_label = color('X', '1;31');
			}
			else
			{
				$condor_label = color('X', '1;31');
			}
			
			printf($format, color($t, 1), $ecash_label, $condor_label);
			
		}
	}

	function color($text, $num)
	{
		return "\x1B[{$num}m{$text}\x1B[0m";
	}
	
?>
