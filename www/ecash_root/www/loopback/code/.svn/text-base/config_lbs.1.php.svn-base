<?PHP
/**
	ECASH - LBS                          
	@version: 1.0.0 , 2006-01-11 , PHP5/MySQL5
	@author: Nick White
*/

// Definitions
define('CORE_LIB','/virtualhosts/lib/');

// Switch Items
define('REC_RETURNS',0); 		//Number of records returned in the file default 0
define('REC_PERCENTAGE',3); 	        //Number of records returned by percent  default 100
define('REC_TYPE_FORCE','');		//Return code to force on all records returned
define('REC_ROLLBACK',4);		//Number of days to return from start date
define('REC_ACHID_FORCE','');		//ACHID to force into the file, only this id will return


// Isolation lists - these lists guarantee a return will not happen (negative) for the contained people,
//                   or that they WILL happen for the contained people                     
$positive_iso_list = array("CAMPBELL, MELANIE",
			   "DAWN, CANDACE",
			   "MORGAN, ALECIA",
			   "SKEET, YVONNE",
			   "MACDONALD, MEGAN",
			   "BARRON, SANDRA",
			   "HORTON, GRAHAM",
			   "SUSICE, BRENNA",
			   "WHITING, LISA",
			   "YU, JAMES");


$negative_iso_list = array("SMITH, NOBLE",
			   "BANKS, ALVITA",
			   "MCLAUGHLIN, KATHLEEN",
			   "WATKINS, JACKIE",
			   "UNVERSAW, KAROLYN",
			   "SMITH, DANIELLE",
			   "OMOTOSO, ABIMBOLA");

// Fatal/Non-fatal isolation lists - these will force a fatal return or not, depending
$fatal_iso_list = array("HORTON, GRAHAM", "SUSICE, BRENNA");
$non_fatal_iso_list = array("WHITING, LISA", "YU, JAMES");

// Flip them to make searching faster
$positive_iso_list = array_flip($positive_iso_list);
$negative_iso_list = array_flip($negative_iso_list);
$fatal_iso_list = array_flip($fatal_iso_list);
$non_fatal_iso_list = array_flip($non_fatal_iso_list);

// Required Core Library
require_once(CORE_LIB."applog.1.php");

// Required Local Library
require_once("upload_lbs.1.php");

class Config_Lbs_1
{
	/**
	 * @desc Creates the database connection and starts the Applog program.
	 * @return bool
	 * @param sql
	 * @param db 
	*/
	function __construct(&$sql, &$log, $location)
	{

		// Start logger
		try {
			$log = new Applog('ecash_lbs', 5000000, 20);
			//$sql = new 1("dev2.clkonline.com", "test_ecash", "3cash", "ach_loopback", 33306);
			$sql->disk_cache_enable("/dev/null");
		} catch (Exception $e) {
			$log->Write("Unable to open MYSQL connection:\n".print_r($e, true));
		}						
		return TRUE;
	}
}
?>
