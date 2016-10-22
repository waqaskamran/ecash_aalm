
<?php


// usage: php test-intercept.php

define ("ACH_REPORT_URL", 'https://www.intercepteft.com/getintercepteftreport.icp');

$batch_login = "usfast natlm"; // set to valid login in real script
$batch_pass = "offv5hyj"; // set to valid password in real script
$report_type = "RET";

$sdate = time();
$edate = $sdate;
$start_date = date("Ymd",$sdate);
print "start date is $start_date\n";

// Intercept contact:
// tech support Brian x 249
// 800 378 3328 
// programmer Chad x322
// chad@intercepteft.com

$company_id="200078432";
define("BATCH_KEY","rc");

// simple test of Intercept report download

	$post_vars = array	(
					 'url' 		=>	ACH_REPORT_URL,
					 'fields'	=>	array	(
					 'login' => $batch_login, 
					 'pass'	=> $batch_pass, 
					 'report' => $report_type, 
					 'format' => "CSV",
					 'sdate' => date("Ymd", strtotime($start_date)),
					 'edate' => date("Ymd", strtotime($start_date)),
					 'compid'=> $company_id)
	 );

		// For testing purposes
		
		$post_vars['fields']['source'] = BATCH_KEY;
		$iter=0;

		// Make up to 5 attempts to get a response from Intercept's server
		do
		{
			print "iter $iter\n";
			$iter++;
			$response = HTTP_Post($post_vars);
			if ( !$response['received'] )
			{
				sleep(5);
			}
		} while (!$response['received'] && $iter < 5);
	
		if (!$response["received"])
		{
			echo("ACH $report_type report: No response from " . $post_vars['url']);
		}
		else
		{
			// Update request parms and response into ach_report table
			echo "Received response\n";
			echo $response['received'];
		}

	
		
	function HTTP_Post ($post_vars)
	{
		$return_val = array();
		
		$curl = curl_init($post_vars['url']);

		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_vars['fields']);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

		try
		{
			$result = curl_exec($curl);
		}
		catch(Exception $e)
			{
				echo ("ACH: cURL had a problem connecting or transferring data to the remote server.");
			}

		$return_val["sent"]	= $post_vars['fields'];
		$return_val["received"]	= $result;
		return $return_val;
	}
	

	
?>