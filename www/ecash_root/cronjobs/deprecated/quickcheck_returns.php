<?php
/*
 *  Fetches Quick Check Returns File
 *  NOTICE:  This is the OLD style return file that we no long use!
 *			 This file is deprecated and should not be used anymore.  It is
 *			 here purely for reference.
 *
 */
function Fetch_QC_Returns_File($server, $date, $overwrite = true)
{
	$log = $server->log;

	defined("QC_LIVE_YEAR")         || define( "QC_LIVE_YEAR",         2005 );
	defined("QC_LIVE_MONTH")        || define( "QC_LIVE_MONTH",        12 );
	defined("QC_LIVE_DAY")          || define( "QC_LIVE_DAY",          01 );

	if(! $date) { $date = date(Ymd); }

	$_BATCH_XEQ_MODE = strtoupper(EXECUTION_MODE);

	// Check mode
	if( in_array($_BATCH_XEQ_MODE, array('LOCAL','RC')) )
	{
		$url       = Quick_Checks::TEST_RETURN_URL;
		$host      = Quick_Checks::TEST_RETURN_HOST;
		$user      = Quick_Checks::TEST_RETURN_USER;
		$pass      = Quick_Checks::TEST_RETURN_PASS;
	}
	elseif( $_BATCH_XEQ_MODE === 'LIVE' )
	{
		$url       = Quick_Checks::PROD_RETURN_URL;
		$host      = Quick_Checks::PROD_RETURN_HOST;
		$user      = Quick_Checks::PROD_RETURN_USER;
		$pass      = Quick_Checks::PROD_RETURN_PASS;
	}
	else
	{
		echo("Unrecognized mode: {$_BATCH_XEQ_MODE}.");
		$log->Write("Fetch QC Returns: Unrecognized mode: {$_BATCH_XEQ_MODE}.");
		return false;
	}

	$company = $server->company;
	$return_code = ECash::getConfig()->QC_RETURN_CODE;

	// Check date
	if( strlen($date) == 10 )
	{
		$year  = substr($date,0,4);
		$month = substr($date,5,2);
		$day   = substr($date,8,2);
	}
	elseif( strlen($date) == 8 )
	{
		$year  = substr($date,0,4);
		$month = substr($date,4,2);
		$day   = substr($date,6,2);
	}
	else
	{
		$year  = -1;
		$month = -1;
		$day   = -1;
	}

	if( ! checkdate($month,$day,$year) || mktime(0,0,0,$month,$day,$year) < mktime(0,0,0,QC_LIVE_MONTH,QC_LIVE_DAY,QC_LIVE_YEAR) )
	{
		$log->Write( "Fetch QC Returns File: Invalid date supplied - {$date}." );
		return false;
	}

	$file_path = QC_RETURN_FILE_DIR . "{$company}/";
	$filename  = $return_code . '-' . $date . '.ddl';
	$local_filename = $file_path . $filename;

	// Does the local file exist?
	$local = file_exists($local_filename);

	// If the local file does not already exist
	// or
	// been asked to overwrite it
	if( ! $local || ($local && $overwrite === true) )
	{
		if( ! is_dir($file_path) )
		{
			mkdir($file_path, 0755, true);
		}

		$connection = ftp_ssl_connect($host, 21);
		$login_result = ftp_login($connection, $user, $pass);
		ftp_pasv($connection, TRUE);
		// Unfortunately, there's no easy way to tell if the file exists.
		$dir_contents = ftp_nlist($connection, "."); // Get contents of current directory
		if(in_array($filename, $dir_contents)) // Make sure the file is there before trying to retrieve it
		{
			if(ftp_get($connection, $local_filename, $filename, FTP_ASCII))
			{
				echo "QC: Succesfully retrieved QuickChecks Return File : $local_filename\n";
				$log->Write("QC: Succesfully retrieved QuickChecks Return File : $local_filename");
				ftp_close($connection);
			}
		}
		else
		{
			// didnt find it
			$log->Write("QC: Unable to retrieve return file : $filename.");
			echo "QC: Unable to retrieve return file : $filename\n";
			ftp_close($connection);
			return false;
		}
	}
	else
	{
		// Already have this file
		$log->Write("Quickchecks file $local_filename exists and overwrite was option wasn't set.");
		return false;
	}

	// Ok, all done
	return $local_filename;
}



function Main()
{
	require_once(LIB_DIR . "/Config.class.php");
	require_once(SERVER_MODULE_DIR."collections/quick_checks.class.php");

	global $server;
	global $_BATCH_XEQ_MODE;

	try 
	{
		
		$qc       = new Quick_Checks($server);

		$today = date("Y-m-d");

		$qc_fetch_timer           = "({$today}) Fetch_Quickcheck_Returns_Files";
		$qc_returns_timer         = "({$today}) Process_Quickcheck_Returns";


		// Fetch quick check returns files
		if( defined("PHP_512_CLI_PATH") && strlen(PHP_512_CLI_PATH) > 0 )
		{
			$target_date = date('Ymd', strtotime("-2 days"));

			$server->timer->Timer_Start($qc_fetch_timer);
			$return_file = Fetch_QC_Returns_File($server, $target_date, $overwrite = true);
			$server->timer->Timer_Stop($qc_fetch_timer);

			// Now process the returns that came in today
			if($return_file != false)
			{
				$server->timer->Timer_Start($qc_returns_timer);
				$qc->Process_Return_File($return_file);
				$server->timer->Timer_Stop($qc_returns_timer);
			}
		}
		else
		{
			ECash::getLog()->Write(__FILE__.": Unable to fetch quickchecks returns file, php 5.1.2+ cli not found.", LOG_ALERT);
		}
	} 
	catch (Exception $e) 
	{
		$error_message = $e->getMessage();
		ECash::getLog()->Write("QC Returns Error: $error_message");

		/* If there is an error, try sending it to the NOTIFICATION_ERROR_RECIPIENTS.
		 * If we forgot to define it, and the EXECUTION_MODE is Live, then
		 * email the right TSS people.  This is done so that for RC environments
		 * the NOTIFICATION_ERROR_RECIPIENTS can be defined for whomever is testing
		 * but if it's not defined and we're not in the LIVE environment
		 * nothing happens.
		 */
		if(ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS != NULL) 
		{
			$recipients = ECash::getConfig()->NOTIFICATION_ERROR_RECIPIENTS;
		} 
		else if (EXECUTION_MODE == 'LIVE') 
		{
			$recipients = 'rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net';
		}

		if (!empty($recipients))
		{
			$body = "An ERROR has occured with the QC Return - EXECUTION MODE:  " . EXECUTION_MODE . "\n\n";
			$body .= "Error Message: \n$error_message\n\n";
			$body .= "Trace: \n" . $e->getTraceAsString() . "\n\n";

			require_once(LIB_DIR . '/Mail.class.php');
			eCash_Mail::sendExceptionMessage($recipients, $body);
		}

	}

}

?>
