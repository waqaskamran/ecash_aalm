#!/usr/bin/php
<?php
/*
 * This script will view a customer's schedule or optionally view the
 * results of an Analyze_Schedule() as 'status'.
 *
 * This utility uses the current configuration based on the BASE_DIR/www/config.php file.
 *
 * Before using this utility, verify all of the defines below.  Only a few precautions
 * are made to verify data is not modified on the wrong servers.
 */


require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/clk/");
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(LIB_DIR . 'common_functions.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');

if($argc > 2) { $application_id = $argv[2]; $process = strtolower($argv[1]);}
else Usage($argv);

if(in_array($process, array('status','schedule','verify')))
{
        $schedule = Fetch_Schedule($application_id);

        switch($process) {
                case 'status':
                        echo "Dumping Status\n";
                        $status = Analyze_Schedule($schedule,false);
                        echo var_dump($status) . "\n";
                        break;
                case 'verify':
                        echo "Dumping Verified Status\n";
                        $status = Analyze_Schedule($schedule,true);
                        echo var_dump($status) . "\n";
                        break;
                case 'schedule':
                        echo "Dumping Schedule\n";
                        echo var_dump($schedule) . "\n";
                        break;
        }
}
else
{
        Usage($argv);
}

exit;

function Usage($argv)
{
        echo "Usage: {$argv[0]} [schedule|status|verify] [application_id]\n";
        echo " - schedule = The customer's full schedule.\n";
        echo " - status   = The results of Analyze_Schedule()\n";
        echo " - verify   = The results of Analyze_Schedule() with the verify option.\n\n";
        exit;
}
