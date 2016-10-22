<?php
// Testing constant to restrict data to 5 rows
//defined( "ARTIFICIAL_LIMIT" ) || define("ARTIFICIAL_LIMIT", "LIMIT 36" );

require_once( COMMON_LIB_DIR    . "general_exception.1.php" );
require_once( SERVER_MODULE_DIR . "collections/pdf.class.php" );
// Get common functions
require_once(LIB_DIR . "common_functions.php");
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "util.func.php");

/**
 * Handles everything involved with quick checks
 *   builds flat file
 *   sends file to bank
 *   deals with returns
 *   pdf checks
 *    680: Returns
 *   1124: PDF
 *   1570: Electronic
 *   2104: Info
 *   2330: Utility Methods
 * @author William Finn
 * @author Brian Ronald
 * @author Mike Lively
 * 
 * @todo   check that ecld_id in transaction_register is used when able
 * @todo   Process_Returns && Process_Return_File is duplicated code
 * @todo   Returns processings:  per app transaction/rollback/commit/log errors, continue processing
 * @todo   Implement Business rules for quickchecks (Maximum times quickchecked, catasrophic return codes, 60 day window, etc)
 * @todo   see if catasrophic return codes are/should be implemented
 * @todo   Better checking that the file transfer was successful in Process_Quick_Checks
 */
class Quick_Checks
{
	/* ***************** *\
	|* General constants *|
	\* ***************** */
	// Process steps
	const STEP_PDF              = 'qc_pdf';
	const STEP_DEPOSIT          = 'qc_build_deposit';
	const STEP_SEND             = 'qc_send_deposit';
	const STEP_STATUS           = 'qc_status';
	const STEP_RETURN           = 'qc_return';
	const STEP_RETURN_FILE      = 'qc_upload';

	// Process states
	const STATE_START           = 'started';
	const STATE_STOP            = 'completed';
	const STATE_FAIL            = 'failed';

	// Maximum number of times 1 app can be quick checked
	// Business rule... maybe should get from rules object?
	// If so, need to also check grandfathering and all that
	const QUICK_CHECK_LIMIT     = 2;

	// tmp dir to store files
	const TMP_DIR               = '/tmp/eCash3.5/qc';

	/* ***************** *\
	|* Deposit constants *|
	\* ***************** */

	// File deposit Return codes
	const DEPOSIT_SUCCESS        = 0;
	const DEPOSIT_FAIL_DUPLICATE = 1;
	const DEPOSIT_FAIL_NO_DATA   = 2;

	/*
	 * PDF constants
	 */
	// Where is the micr font?
	public static $DIR_FONT      = "collections/fonts/";

	// maximum # of checks in 1 pdf file
	const MAX_PDF_BUNDLE        = 250;
	const FONT_SIZE_LARGE       = 14;
	const FONT_SIZE_NORMAL      = 9;
	const FONT_SIZE_SMALL       = 8;
	const FONT_SIZE_TITLE       = 12;
	const FONT_SIZE_MICR        = 18;

	/*
	 * electronic constants
	 */
	// Are we generating test or production files?
	const TEST                  = 'T';
	const PRODUCTION            = 'P';
	const VERIFYPEER       = 0;

	// Maximum number of checks in one bundle
	const MAX_CHECKS_IN_BUNDLE  = 1000;

	// end of line
	const RS                    = "\n";

	// Other Info
	const COUNTRY_CODE           = 'US';
	const BLANK                  = '';
	const US_BANK_ROUTING_NUMBER = '091901707';

	const IMM_DESTINATION_ROUTING_NUMBER = Quick_Checks::US_BANK_ROUTING_NUMBER;
	const INSTITUTION_ROUTING_NUMBER     = Quick_Checks::US_BANK_ROUTING_NUMBER;
	const DESTINATION_ROUTING_NUMBER     = Quick_Checks::US_BANK_ROUTING_NUMBER;
	const IMM_ORIGIN_ROUTING_NUMBER      = Quick_Checks::US_BANK_ROUTING_NUMBER;
	const ORIGIN_ROUTING_NUMBER          = Quick_Checks::US_BANK_ROUTING_NUMBER;
	const DESTINATION_NAME               = 'US Bank';
	const ORIGIN_NAME                    = 'US Bank';
	const DUNS_NUMBER                    = '248940025';

	// Record types
	const FILE_HEADER           = '_01';
	const CASH_LETTER_HEADER    = '_10';
	const BUNDLE_HEADER         = '_20';
	const CHECK_DETAIL          = '_25';
	const CHECK_DETAIL_B        = '_27';
	const USER_UNPARSED_MICR    = '_60';
	const USER_RECORD           = '_64';
	const USER_DEPOSIT          = '_64-1';
	const USER_TRANSACTION      = '_64-3';
	const USER_ITEM             = '_64-4';
	const USER_MICR             = '_64-5';
	const USER_FIELD_1          = '_64-8-1';
	const USER_FIELD_2          = '_64-8-2';
	const BUNDLE_CONTROL        = '_70';
	const CASH_LETTER_CONTROL   = '_90';
	const FILE_CONTROL          = '_99';

	// Data validation expressions
	// Similar to what is in the docs provided by US Bank
	// However, their docs are shit.  These are my reasonable interpretations
	const TYPE_O                = '%^.*$%';
	const TYPE_A                = '%^[A-Za-z ]*$%';
	const TYPE_N                = '%^[0-9]*$%';
	const TYPE_B                = '%^ *$%';
	const TYPE_S                = '%^[\x21-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f]*$%';
	const TYPE_AN               = '%^[A-Za-z0-9]*$%';
	const TYPE_ANB              = '%^[A-Za-z0-9{}\-]*$%';
	const TYPE_ANS              = '%^[\x21-\x7f]*$%';
	const TYPE_NS               = '%^[\x21-\x40\x5b-\x60\x7b-\x7f]*$%';
	const TYPE_NBD              = '%^[0-9\- ]*$%';
	const TYPE_ND               = '%^[0-9\-]*$%';
	const TYPE_NSN              = '%^-?[0-9]*$%';
	const TYPE_ANC              = '%^(<?!,)(,?[A-Za-z0-9]+)*$%';
	// MICR defines
	const MICR_NBSM             = '%^[0-9* ]*$%';
	const MICR_NBSMD            = '%^[0-9*\- ]*$%';
	const MICR_NBSMU            = '%^[0-9/$#*\- ]*$%';
	const MICR_NBSMOS           = '%^[0-9*\-/ ]*$%';
	// Not in spec, defined by Mehul Patel of US Bank 9/15/05
	const TYPE_NB               = '%^[0-9 ]*$%';

	// To access $record_definitions
	const REC_SIZE              = 0;
	const REC_TYPE              = 1;
	const REC_VALUE             = 2;

	/* ********************* *\
	|*   Returns constants   *|
	\* ********************* */
	// Grab local return files (ie, from ps8), or remote (direct from US Bank's server)
	const LOCAL_RETURN_FILES    = true;

	/* ********************* *\
	|* File status constants *|
	\* ********************* */
	// File status responses
	const STATUS_PROCESSED      = 'processed';
	const STATUS_RESEND         = 'resend';
	const STATUS_ERROR          = 'error';
	const STATUS_SOAP_ERROR     = 'soap_error';
	const STATUS_NO_STATUS      = 'no_data';

	/**
	 * Execution mode to use (local || rc) == test, live == production)
	 * @var    string
	 * @access private
	 */
	private $execution_mode;

	/**
	 * current process
	 * @var    string
	 * @access private
	 */
	public $process;

	/**
	 * list of valid states for the process_log
	 * @var    array
	 * @access private
	 */
	private $process_states = array(Quick_Checks::STATE_START, Quick_Checks::STATE_STOP, Quick_Checks::STATE_FAIL);

	/**
	 * list of valid steps for the process_log
	 * @var    array
	 * @access private
	 */
	private $process_steps  = array(Quick_Checks::STEP_DEPOSIT,
	                                Quick_Checks::STEP_STATUS,
	                                Quick_Checks::STEP_RETURN,
	                                Quick_Checks::STEP_PDF,
	                                Quick_Checks::STEP_RETURN_FILE);

	/**
	 * Proper names of the record types
	 * have to use _ at start because some versions of php will convert to integer
	 * when used as an array key in array() function, Bug #35509
	 * @var array
	 * @access    private
	 */
	private $reverse_map = array( Quick_Checks::FILE_HEADER         => '_01',
	                              Quick_Checks::CASH_LETTER_HEADER  => '_10',
	                              Quick_Checks::BUNDLE_HEADER       => '_20',
	                              Quick_Checks::CHECK_DETAIL        => '_25',
	                              Quick_Checks::CHECK_DETAIL_B      => '_27',
	                              Quick_Checks::USER_UNPARSED_MICR	=> '_60',
	                              Quick_Checks::USER_DEPOSIT        => '_64-1',
	                              Quick_Checks::USER_TRANSACTION    => '_64-3',
	                              Quick_Checks::USER_ITEM           => '_64-4',
	                              Quick_Checks::USER_MICR           => '_64-5',
	                              Quick_Checks::USER_FIELD_1        => '_64-8-1',
	                              Quick_Checks::USER_FIELD_2        => '_64-8-2',
	                              Quick_Checks::BUNDLE_CONTROL      => '_70',
	                              Quick_Checks::CASH_LETTER_CONTROL => '_90',
	                              Quick_Checks::FILE_CONTROL        => '_99'
	                            );

	/**
	 * Record definitions
	 *             record type                                    field #   size,  type,                     default value
	 * @var array
	 * @access    private
	 */
	private $record_definitions = array (
		//                                        
		// 01
		Quick_Checks::FILE_HEADER        => array( 1  => array(2,   Quick_Checks::TYPE_N,      '01'),
		                                           2  => array(2,   Quick_Checks::TYPE_N,      '04'),
		                                           3  => array(1,   Quick_Checks::TYPE_A,      Quick_Checks::BLANK),
		                                           4  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::IMM_DESTINATION_ROUTING_NUMBER),
		                                           5  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::IMM_ORIGIN_ROUTING_NUMBER),
		                                           6  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           7  => array(4,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           8  => array(1,   Quick_Checks::TYPE_A,      'N'),
		                                           9  => array(18,  Quick_Checks::TYPE_A,      Quick_Checks::DESTINATION_NAME),
		                                           10 => array(18,  Quick_Checks::TYPE_A,      Quick_Checks::ORIGIN_NAME),
		                                           11 => array(1,   Quick_Checks::TYPE_AN,     '1'),
		                                           12 => array(2,   Quick_Checks::TYPE_A,      Quick_Checks::COUNTRY_CODE),
		                                           13 => array(4,   Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           14 => array(1,   Quick_Checks::TYPE_B,      Quick_Checks::BLANK)
		                                        ),
		// 10
		Quick_Checks::CASH_LETTER_HEADER => array( 1  => array(2,   Quick_Checks::TYPE_N,      '10'),
		                                           2  => array(2,   Quick_Checks::TYPE_N,      '12'),
		                                           3  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::DESTINATION_ROUTING_NUMBER),
		                                           4  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::INSTITUTION_ROUTING_NUMBER),
		                                           5  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           6  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           7  => array(4,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           8  => array(1,   Quick_Checks::TYPE_A,      'E'),
		                                           9  => array(1,   Quick_Checks::TYPE_AN,     'G'),
		                                           10 => array(8,   Quick_Checks::TYPE_AN,     Quick_Checks::BLANK),
		                                           11 => array(14,  Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           12 => array(10,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           13 => array(1,   Quick_Checks::TYPE_AN,     'C'),
		                                           14 => array(2,   Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           15 => array(1,   Quick_Checks::TYPE_B,      Quick_Checks::BLANK)
		                                        ),
		// 20
		Quick_Checks::BUNDLE_HEADER      => array( 1  => array(2,   Quick_Checks::TYPE_N,      '20'),
		                                           2  => array(2,   Quick_Checks::TYPE_N,      '01'),
		                                           3  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::DESTINATION_ROUTING_NUMBER),
		                                           4  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::INSTITUTION_ROUTING_NUMBER),
		                                           5  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           6  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           7  => array(10,  Quick_Checks::TYPE_AN,     Quick_Checks::BLANK),
		                                           8  => array(4,   Quick_Checks::TYPE_NB,     Quick_Checks::BLANK),
		                                           9  => array(2,   Quick_Checks::TYPE_AN,     Quick_Checks::BLANK),
		                                           10 => array(9,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           11 => array(5,   Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           12 => array(12,  Quick_Checks::TYPE_B,      Quick_Checks::BLANK),
				),
		// 25
		Quick_Checks::CHECK_DETAIL       => array( 1  => array(2,   Quick_Checks::TYPE_N,      '25'),
		                                           2  => array(15,  Quick_Checks::MICR_NBSM,   Quick_Checks::BLANK),
		                                           3  => array(1,   Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           4  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::BLANK),
		                                           5  => array(20,  Quick_Checks::MICR_NBSMOS, Quick_Checks::BLANK),
		                                           6  => array(10,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           7  => array(15,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK),
		                                           8  => array(1,   Quick_Checks::TYPE_AN,     'G'),
		                                           9  => array(1,   Quick_Checks::TYPE_AN,     '0'),
		                                           10 => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           11 => array(2,   Quick_Checks::TYPE_N,      '1'),
		                                           12 => array(1,   Quick_Checks::TYPE_N,      '0'),
		                                           13 => array(1,   Quick_Checks::TYPE_AN,     'B'),
		                                           14 => array(1,   Quick_Checks::TYPE_B,      Quick_Checks::BLANK)
				),
		// 27
		Quick_Checks::CHECK_DETAIL_B     => array( 1  => array(2,   Quick_Checks::TYPE_N,      '27'),
		                                           2  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(15,  Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           4  => array(4,   Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           5  => array(5,   Quick_Checks::TYPE_B,      Quick_Checks::BLANK),
		                                           6  => array(15,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK),
		                                           7  => array(4,   Quick_Checks::TYPE_N,      '22'),
		                                           8  => array(22,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK)
				),
		// 60
		Quick_Checks::USER_UNPARSED_MICR => array( 1  => array(2,   Quick_Checks::TYPE_N,      '60'),
		                                           2  => array(70,  Quick_Checks::MICR_NBSMU,  Quick_Checks::BLANK),
		                                           3  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           4  => array(7,   Quick_Checks::TYPE_B,      Quick_Checks::BLANK)
				),
		// 64-1
		Quick_Checks::USER_DEPOSIT       => array( 1  => array(2,   Quick_Checks::TYPE_N,      '64'),
		                                           2  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(9,   Quick_Checks::TYPE_N,      Quick_Checks::DUNS_NUMBER),
		                                           4  => array(20,  Quick_Checks::TYPE_AN,     '0'),
		                                           5  => array(3,   Quick_Checks::TYPE_AN,     '1'),
		                                           6  => array(3,   Quick_Checks::TYPE_AN,     '1'),
		                                           7  => array(7,   Quick_Checks::TYPE_N,      '160'),
		                                           //  Mehul specified guid should be surrounded by {}, which are not allowed by TYPE_AN...
		                                           //				                                          8  => array(38,  Quick_Checks::TYPE_AN,     Quick_Checks::BLANK), // 1
		                                           8  => array(38,  Quick_Checks::TYPE_ANB,    Quick_Checks::BLANK), // 1
		                                           9  => array(30,  Quick_Checks::TYPE_A,      Quick_Checks::BLANK), // 2
		                                           10 => array(17,  Quick_Checks::TYPE_AN,     Quick_Checks::BLANK), // 3
		                                           11 => array(30,  Quick_Checks::TYPE_AN,     Quick_Checks::BLANK), // 4
		                                           12 => array(30,  Quick_Checks::TYPE_AN,     SOFTWARE_NAME),       // 5
		                                           13 => array(15,  Quick_Checks::TYPE_AN,     ECASH_VERSION)
				),
		// 64-3
		Quick_Checks::USER_TRANSACTION   => array( 1  => array(2,   Quick_Checks::TYPE_N,      '64'),
		                                           2  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(9,   Quick_Checks::TYPE_N,      Quick_Checks::DUNS_NUMBER),
		                                           4  => array(20,  Quick_Checks::TYPE_AN,     '0'),
		                                           5  => array(3,   Quick_Checks::TYPE_AN,     '3'),
		                                           6  => array(3,   Quick_Checks::TYPE_AN,     '1'),
		                                           7  => array(7,   Quick_Checks::TYPE_N,      '82'),
		                                           //  Mehul specified guid should be surrounded by {}, which are not allowed by TYPE_AN...
		                                           //				                                          8  => array(22,  Quick_Checks::TYPE_AN,     Quick_Checks::BLANK), // 1
		                                           8  => array(22,  Quick_Checks::TYPE_ANB,    Quick_Checks::BLANK), // 1
		                                           9  => array(30,  Quick_Checks::TYPE_A,      'POS'),               // 2
		                                           10 => array(30,  Quick_Checks::TYPE_AN,     Quick_Checks::BLANK)  // 3
				),
		// 64-4
		Quick_Checks::USER_ITEM          => array( 1  => array(2,   Quick_Checks::TYPE_N,      '64'),
		                                           2  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(9,   Quick_Checks::TYPE_N,      Quick_Checks::DUNS_NUMBER),
		                                           4  => array(20,  Quick_Checks::TYPE_AN,     '0'),
		                                           5  => array(3,   Quick_Checks::TYPE_AN,     '4'),
		                                           6  => array(3,   Quick_Checks::TYPE_AN,     '1'),
		                                           7  => array(7,   Quick_Checks::TYPE_N,      '59'),
		                                           8  => array(2,   Quick_Checks::TYPE_AN,     '01'),                // 1
		                                           9  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK), // 2
		                                           10 => array(4,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK), // 3
		                                           11 => array(3,   Quick_Checks::TYPE_A,      'ARC'),               // 4
		                                           12 => array(30,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK), // 5
		                                           13 => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK), // 6
		                                           14 => array(4,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK)  // 7
				),
		// 64-5
		Quick_Checks::USER_MICR          => array( 1  => array(2,   Quick_Checks::TYPE_N,      '64'),
		                                           2  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(9,   Quick_Checks::TYPE_N,      Quick_Checks::DUNS_NUMBER),
		                                           4  => array(20,  Quick_Checks::TYPE_AN,     '0'),
		                                           5  => array(3,   Quick_Checks::TYPE_AN,     '5'),
		                                           6  => array(3,   Quick_Checks::TYPE_AN,     '1'),
		                                           7  => array(7,   Quick_Checks::TYPE_N,      '169'),
		                                           8  => array(9,   Quick_Checks::TYPE_NBD,    Quick_Checks::BLANK), // 1
		                                           9  => array(30,  Quick_Checks::TYPE_NBD,    Quick_Checks::BLANK), // 2
		                                           10 => array(30,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK), // 3
		                                           11 => array(30,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK), // 4
		                                           12 => array(30,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK), // 5
		                                           13 => array(30,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK), // 6
		                                           14 => array(10,  Quick_Checks::TYPE_NB,     Quick_Checks::BLANK)  // 7
				),
		// 64-8-1
		Quick_Checks::USER_FIELD_1       => array( 1  => array(2,   Quick_Checks::TYPE_N,      '64'),
		                                           2  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(9,   Quick_Checks::TYPE_N,      Quick_Checks::DUNS_NUMBER),
		                                           4  => array(20,  Quick_Checks::TYPE_AN,     '0'),
		                                           5  => array(3,   Quick_Checks::TYPE_AN,     '8'),
		                                           6  => array(3,   Quick_Checks::TYPE_AN,     '1'),
		                                           7  => array(7,   Quick_Checks::TYPE_N,      '55'),
		                                           8  => array(2,   Quick_Checks::TYPE_N,      '01'),               // 1
		                                           9  => array(3,   Quick_Checks::TYPE_N,      '50'),               // 2
		                                           10 => array(50,  Quick_Checks::TYPE_O,      Quick_Checks::BLANK) // 3
				),
		// 64-8-2
		Quick_Checks::USER_FIELD_2       => array( 1  => array(2,   Quick_Checks::TYPE_N,      '64'),
		                                           2  => array(1,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(9,   Quick_Checks::TYPE_N,      Quick_Checks::DUNS_NUMBER),
		                                           4  => array(20,  Quick_Checks::TYPE_AN,     '0'),
		                                           5  => array(3,   Quick_Checks::TYPE_AN,     '8'),
		                                           6  => array(3,   Quick_Checks::TYPE_AN,     '1'),
		                                           7  => array(7,   Quick_Checks::TYPE_N,      '55'),
		                                           8  => array(2,   Quick_Checks::TYPE_N,      '02'),               // 1
		                                           9  => array(3,   Quick_Checks::TYPE_N,      '50'),               // 2
		                                           10 => array(50,  Quick_Checks::TYPE_O,      Quick_Checks::BLANK) // 3
				),
		// 70
		Quick_Checks::BUNDLE_CONTROL     => array( 1  => array(2,   Quick_Checks::TYPE_N,      '70'),
		                                           2  => array(4,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           3  => array(12,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           4  => array(12,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           5  => array(5,   Quick_Checks::TYPE_N,      '0'),
		                                           6  => array(12,  Quick_Checks::TYPE_B,      Quick_Checks::BLANK),
		                                           7  => array(8,   Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           8  => array(25,  Quick_Checks::TYPE_B,      Quick_Checks::BLANK)
				),
		// 90
		Quick_Checks::CASH_LETTER_CONTROL => array(1  => array(2,   Quick_Checks::TYPE_N,      '90'),
		                                           2  => array(6,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           3  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           4  => array(14,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           // Field Type specified does not allow data required, using our specification
		                                           //				                                          5  => array(9,   Quick_Checks::TYPE_A,      '0'),
		                                           5  => array(9,   Quick_Checks::TYPE_N,      '0'),
		                                           6  => array(18,  Quick_Checks::TYPE_A,      Quick_Checks::BLANK),
		                                           7  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           8  => array(6,   Quick_Checks::TYPE_AN,     Quick_Checks::BLANK),
		                                           9  => array(14,  Quick_Checks::TYPE_N,      '0')
				),
		// 99
		Quick_Checks::FILE_CONTROL       => array( 1  => array(2,   Quick_Checks::TYPE_N,      '99'),
		                                           2  => array(6,   Quick_Checks::TYPE_N,      '1'),
		                                           3  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           4  => array(8,   Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           5  => array(16,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           6  => array(14,  Quick_Checks::TYPE_ANS,    Quick_Checks::BLANK),
		                                           7  => array(10,  Quick_Checks::TYPE_N,      Quick_Checks::BLANK),
		                                           8  => array(16,  Quick_Checks::TYPE_N,      '0')
				)
	);

	/**
	 * Path to the wsdl file retrieved from us bank and saved locally
	 * @var    string
	 * @access private
	 */
	private $wsdl_path;

	/**
	 * URL of the https script
	 * @var    string
	 * @access private
	 */
	private $status_url;

	/**
	 * URL of passive ftps server
	 * @var    string
	 * @access private
	 */
	private $deposit_url;

	/**
	 * Port # of passive ftps server
	 * @var    string
	 * @access private
	 */
	private $deposit_port;

	/**
	 * URL of auth ssl ftps server
	 * @var    string
	 * @access private
	 */
	private $return_url;

	/**
	 * Username for the https connection
	 * @var    string
	 * @access private
	 */
	private $status_user;

	/**
	 * Password for the https connection
	 * @var    string
	 * @access private
	 */
	private $status_pass;

	/**
	 * username & password for the passive ftps connection (curl format)
	 * @var    string
	 * @access private
	 */
	private $deposit_userpass;

	/**
	 * username for the auth ssl ftps connection (curl format)
	 * @var    string
	 * @access private
	 */
	private $return_user;

	/**
	 * password for the auth ssl ftps connection (curl format)
	 * @var    string
	 * @access private
	 */
	private $return_pass;


	/**
	 * Should check certificate or not, test servrs have invalid certificate
	 * @var    string
	 * @access private
	 */
	private $verify_peer;

	/**
	 * applog
	 * @var    Applog
	 * @access private
	 */
	private $log;

	/**
	 * mysql connection
	 * @var    DB_Database_1
	 * @access private
	 */
	private $db;

	/**
	 * ID of company being processed
	 * @var    integer
	 * @access private
	 */
	private $company_id;

	/**
	 * Short name of the company being processed
	 * @var    string
	 * @access private
	 */
	private $company_short;

	/**
	 * Name of the return file
	 * @var    string
	 * @access private
	 */
	private $return_filename;

	/**
	 * Name of batch file created
	 * @var    string
	 * @access private
	 */
	private $deposit_filename;

	/**
	 * Contents of created file
	 * @var    string
	 * @access private
	 */
	private $deposit_file;

	/**
	 * Contents of the request file (status request xml)
	 * @var    string
	 * @access private
	 */
	private $status_file;

	/**
	 * Contents of return file
	 * @var    string
	 * @access private
	 */
	private $return_file;

	/**
	 * Date this batch is being processed
	 * @var    string  strtotime()
	 * @access private
	 */
	public $process_date;

	/**
	 * Client Identifier
	 * @var    string
	 * @access private
	 */
	private $client_id;

	/**
	 * ID of process_log entry this instance is using
	 * @var    integer
	 * @access private
	 */
	private $process_id;

	/**
	 * ID of ecld_return id after inserting file
	 * @var    integer
	 * @access private
	 */
	private $return_id;

	/**
	 * Data used to create the send file
	 * @var    array
	 * @access private
	 */
	private $deposit_data;

	/**
	 * The data ($depsoit_data) after it has been grouped into bundles
	 * @var    array
	 * @access private
	 */
	private $bundle_data;

	/**
	 * ecld_file_id returned from mysql after inserting file
	 * @var    integer
	 * @access private
	 */
	private $ecld_file_id;

	/**
	 * return_code company specific return code
	 * @var    integer
	 * @access private
	 */
	public $return_code;
	
	public $server;

	/**
	 * Constructor
	 * @throws General_Exception
	 * @param  Applog  $log
	 * @param  integer $company
	 * @access public
	 */
	public function __construct( Server $server )
	{
		$this->server        = $server;
		$this->log           = get_log('quickcheck');
		$this->server		 = $server;
		$this->db = ECash::getMasterDb();
		$this->company_id    = $server->company_id;
		$this->company_short = $server->company;
		
		$this->execution_mode   = ECash::getConfig()->QC_EXECUTION_MODE;

		$this->return_code = ECash::getConfig()->QC_RETURN_CODE;

		$this->deposit_url      = ECash::getConfig()->QC_DEPOSIT_URL;
		$this->deposit_userpass = ECash::getConfig()->QC_DEPOSIT_USER .
							  ':'.ECash::getConfig()->QC_DEPOSIT_PASS;
		$this->deposit_port		= ECash::getConfig()->QC_DEPOSIT_PORT;
		$this->return_url       = ECash::getConfig()->QC_RETURN_URL;
		$this->return_user      = ECash::getConfig()->QC_RETURN_USER;
		$this->return_pass      = ECash::getConfig()->QC_RETURN_PASS;
		$this->status_url       = ECash::getConfig()->QC_STATUS_URL;
		$this->status_user      = ECash::getConfig()->QC_STATUS_USER;
		$this->status_pass      = ECash::getConfig()->QC_STATUS_PASS;		
		$this->verify_peer      = self::VERIFYPEER;	
	
		self::$DIR_FONT = SERVER_MODULE_DIR . self::$DIR_FONT;
	}

	/* ***************************************** *\
	|*         Return Processing Methods         *|
	\* ***************************************** */

	/**
	 * Checks for available returns
	 * @param integer[optional] $date yyyymmdd
	 * @return boolean true
	 * @throws General_Exception
	 * @access public
	 * @todo   Logic to check if file has already been downloaded
	 */
	public function Process_Returns($date = null)
	{
		$this->process = self::STEP_RETURN;

		if( empty($date) )
			$date = date("Ymd");

		if( ! $this->okdate($date) )
			$this->Failure( 'Invalid process date requested of ' . __METHOD__ . '.', __LINE__  );

		$this->process_date = $date;

		// STEP_RETURN
		$this->Set_Process_State(self::STATE_START, $this->process_date);

		$start_date   = $this->Fetch_Last_Process_Date();
		$end_date     = $this->process_date;
		$current_date = $start_date;

		while( strtotime($current_date) <= strtotime($end_date) )
		{
			try
			{
				$this->return_filename = "{$this->return_code}-{$current_date}.ddl";

				// Check for return file
				if( $this->File_Transfer() === false )
				{
					$current_date = date("Ymd", strtotime("+1 day", strtotime($current_date)));
					continue;
				}

				// Save
				$this->return_id = $this->Save_Return_File();

				// Get the info from the file
				$returns = $this->Parse_Return_File();

				// Update ecld & register tables
				$this->Record_Returns($returns);

				// Done processing
				$this->Update_File_Status('processed');

				$this->log->Write( "Quick Check returns successfully processed for {$current_date}.", LOG_INFO );
			}
			catch( Exception $e )
			{
				$this->Failure( $e->getMessage(), __LINE__ );
			}

			$current_date = date("Ymd", strtotime("+1 day", strtotime($current_date)));
		}

		$this->Set_Process_State(self::STATE_STOP, $this->process_date);

		return true;
	}
	
	/**
	 * Special function to pull unmatched returns from the database for existing ecld_return entries.
	 *
	 * @param unknown_type $return_id
	 */
	public function Retroactive_Pull_Unmatched_Returns($return_id) 
	{
		$query = "
		SELECT
			return_file_content
		  FROM
		  	ecld_return
		  WHERE
		  	ecld_return_id = $return_id";
		
		$db = ECash::getMasterDb();
		$row = $db->querySingleRow($query);
	
		$this->return_file = $row->return_file_content;
		
		if (!preg_match("/^\<\?xml/", $this->return_file)) return;
				
		if(!$this->process_date = $this->Get_Process_Date_From_XML_File($this->return_file))
			$this->Failure( "Could not retrieve process date from file [{$file_path}].", LOG_INFO );

		$returns = $this->Parse_XML_Return_File();
		
		$check_query = "
			SELECT 
				COUNT(*) count
			  FROM ecld
			  WHERE 
			  	ecld_id = ?
			  	AND application_id = ?
			  	AND company_id = ?
			  	AND TRIM( LEADING '0' FROM bank_account) = ?";
		
		$results = array();
		foreach ($returns as $data)
		{
			$row = $result->querySingleRow(
				$check_query,
				array(
					$data['ecld_id'],
					$data['application_id'],
					$data['company_id'],
					$data['account_number']
					
				)
			);
			
			if ($row->count < 1)
			{
				$results[] = array(
					'aba' => $data['routing_number'],
					'account' => $data['account_number'],
					'return_code' => $data['return_code'],
					'ecld_id' => $data['ecld_id'],
					'app_id' => $data['company_id'],
				);				
			}
		}
		
		return $results;
	}

	/**
	 * Process an uploaded return file
	 * @param string $file_path location of the file to process
	 * @throws General_Exception
	 * @access public
	 * @todo   Transaction code
	 */
	public function Process_Return_File( $file_path )
	{
		// Don't allow things to time out because we often run long processing tasks
		set_time_limit(0);

		$this->process = self::STEP_RETURN_FILE;
		
		$this->return_file = file_get_contents($file_path);
		if($this->return_file === false)
			$this->Failure( "Could not open return file [{$file_path}].", LOG_INFO );
			
		if ($this->return_file == "") 
		{
			$this->Failure("Recieved a NULL file [{$file_path}].", LOG_INFO);	
		}

		if( preg_match("%[\\x00-\\x09\\x0E-\\x1F\\x7F]%", $this->return_file) )
			$this->Failure( "File contains binary characters [{$file_path}].", LOG_INFO );

		try
		{
			// Save the return file to the database
			$this->return_id = $this->Save_Return_File();

			// Determine the file format based on it's first line and then
			// use the proper parser.
			if(preg_match("/^\<\?xml/", $this->return_file))
			{
				$this->log->Write("QC: Processing XML Return File {$file_path}");

				if(!$this->process_date = $this->Get_Process_Date_From_XML_File($this->return_file))
					$this->Failure( "Could not retrieve process date from file [{$file_path}].", LOG_INFO );

				$this->Set_Process_State(self::STATE_START, $this->process_date);
				$returns = $this->Parse_XML_Return_File();
			}
			else if(preg_match("/^HD,/", $this->return_file))
			{
				$this->log->Write("QC: Processing Flat Return File {$file_path}");

				if(!$this->process_date = $this->Get_Process_Date_From_File($this->return_file))
					$this->Failure( "Could not retrieve process date from file [{$file_path}].", LOG_INFO );

				$this->Set_Process_State(self::STATE_START, $this->process_date);
				$returns = $this->Parse_Flat_Return_File();
			}
			else
			{
				$this->Failure( "Incorrect file format! [{$file_path}].", LOG_INFO );
			}

			// Update ecld & register tables
			$complete_success = $this->Record_Returns($returns);

			// Done processing
			$this->Update_File_Status('processed', self::STEP_RETURN_FILE);
		}
		catch( Exception $e )
		{
			$this->Failure( $e->getMessage(), $e->getLine() );
			$this->Set_Process_State(self::STATE_STOP, $this->process_date);
			$this->log->Write( "Quick Check return file [{$file_path}] processing failed.", LOG_INFO );
			return false;
		}

		$this->Set_Process_State(self::STATE_STOP, $this->process_date);
		$this->log->Write( "Quick Check return file [{$file_path}] successfully processed.", LOG_INFO );
		
		if (!$complete_success) 
		{
			$this->Failure("Not all returns successfully processed.", LOG_INFO);
		}

		return true;
	}

	/**
	 * Records returns in db (register, ecld)
	 * @param array $returns parsed return file info
	 * @throws Exception
	 * @access private
	 */
	private function Record_Returns($returns)
	{
		$error = false;
		foreach( $returns as $data )
		{
			try
			{
				if(!$this->db->InTransaction)
				{
					$this->db->beginTransaction();
					$commit = true;
				}
				else 
				{
					$commit = false;
				}
				$this->Update_Register($data);
				$this->Update_Ecld($data);
				
				if($commit)
				{
					$this->db->commit();
				}
				
				// Seperate logging of acceptable return codes, unknown return codes, 
				// and the P-R return code. I got rid of the nasty switch in doing so.
				// In the event that we get new acceptable return codes they will go 
				// into the array below.
				$acceptableReturnCodes = array(
					'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 
					'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 
					'W', '03', '04', '08', '12', '16', '17', 'P-N', 'P-E',
					'P-X', 'P-A', 'P-S', 'P-F', 'P-U'
				);
				
				if (! in_array(strtoupper($data['return_code']), $acceptableReturnCodes)) 
				{
					$this->log->Write( "Unrecognized qc return code [" . $data['return_code'] . "] for app " .
						$data['application_id'] . ", ecld_id: " . $data['ecld_id'], LOG_NOTICE );

				} 
				elseif($data['return_code'] == 'P-R') 
				{
					$this->log->Write( "qc return code [P-R] indicates action [" . $data['action_code'] .
						"] for app " . $data['application_id'] . ", ecld_id: " .
						$data['ecld_id'], LOG_NOTICE );
				}
			}
			catch( QC_Duplicate_Return_Exception $e )
			{
				$this->log->Write( "QC [Return File ID:{$this->return_id}][ECLD ID:{$data['ecld_id']} Could not process return for application_id " .
				                   $data['application_id'] . ", It has already been marked as returned" . ": " . $e->getMessage(), LOG_ALERT );
				if($commit) 
				{
					$this->db->rollBack();
				}
				continue;
			}
			catch( Exception $e )
			{
				$this->log->Write( "QC ecld_return_id [{$this->return_id}]. Could not process return for application_id " .
				                   $data['application_id'] . ", ecld_id " . $data['ecld_id'] . ": " . $e->getMessage(), LOG_ALERT );
				$this->db->rollback();
				
				$error = true;
				continue;
			}
			try 
			{
				// Setup the DFA...
				$this->log->Write("Rescheduling QC Applicant " . $data['application_id']);

				$parameters = new stdClass();
				$parameters->application_id = $data['application_id'];
				$parameters->returned_ecld_id = $data['ecld_id'];
				$parameters->return_amount = $data['return_amount'];
				$parameters->server = $this->server;


				if (!isset($dfas['failure'])) 
				{
					require_once(CUSTOMER_LIB."/failure_dfa.php");
					$dfa = new FailureDFA();
					$dfa->SetLog($this->log);
					$dfas['failure'] = $dfa;
				}
				else
				{
					$dfa = $dfas['failure'];
				}
				$dfa->run($parameters);	

			}
			catch( Exception $e )
			{
				$this->log->Write( "[ApplicationID:{$data['application_id']}] QC Rescheduling failed: {$e->getMessage()}");
				$this->log->Write( "[ApplicationID:{$data['application_id']}] Setting rescheduling standby.");

				Set_Standby($this->request->application_id, $this->company_id, 'reschedule');
				continue;
			}
		}
		
		return !$error;
	}

	/**
	 * updates the ecld table to reflect a return
	 * @param array $data data about the return
	 * @throws Exception
	 * @access private
	 */
	private function Update_Ecld($data)
	{
		// Never know... maybe the data returned from the bank has some garbage in it
		$aba         = $this->db->quote($data['routing_number']);
		$account     = $this->db->quote($data['account_number']);
		$return_code = $this->db->quote($data['return_code']);
		$ecld_id     = $this->db->quote($data['ecld_id']);
		$app_id      = $this->db->quote($data['application_id']);
		$company_id  = $this->db->quote($data['company_id']);

		$ecld_update_query = "
			UPDATE	ecld
			 SET	ecld_status        = 'returned',
					return_reason_code = {$return_code},
					ecld_return_id     = {$this->return_id}
			 WHERE	ecld_id            = {$ecld_id}
			  AND	application_id     = {$app_id}
			  AND	company_id         = {$company_id}
			  AND	TRIM( LEADING '0' FROM bank_account) = {$account} ";
		$st = $this->db->query($ecld_update_query);
		if ($st->rowCount() > 0)
		{
			$this->log->Write("ECLD [ID: {$ecld_id}] updated ".$st->rowCount()." rows.");
		}
			
		$this->log->Write("Updated ECLD [ID: {$ecld_id}] for Application [{$app_id}] with return code [{$return_code}]");
	}

	/**
	 * Updates the transaction_register entry for 1 app's quick check entry
	 * @param array $data app_id, routing_number, account_number
	 * @returns boolean success or failure
	 * @access private
	 */
	private function Update_Register($data)
	{
		// Again, data is coming from US Bank, gotta make sure there is no garbage
		$app_id  = $this->db->quote($data['application_id']);
		$ecld_id = $this->db->quote($data['ecld_id']);
		$company_id = $this->db->quote($data['company_id']);
		$tt_id   = $this->Get_Transaction_Type_Id($company_id);
		$agent_id = Fetch_Current_Agent();
		$transction_reg = array();

		$find_transction_reg_query = "
			SELECT 
				transaction_register_id
			FROM
				transaction_register
			 WHERE	application_id      = {$app_id}
			  AND	ecld_id             = {$ecld_id}
			  AND	company_id          = {$company_id}
			  AND	transaction_type_id = {$tt_id}
			  AND	transaction_status  = 'pending'											
		";
    	
    	$transction_reg = $this->db->querySingleColumn($find_transction_reg_query);
    	
    	for($i=0; $i<count($transction_reg); $i++)
    	{
    		Set_Loan_Snapshot($transction_reg[$i],"failed");
		}
		
		$register_update_query = "
			UPDATE	transaction_register
			 SET	transaction_status  = 'failed',
				modifying_agent_id  = '{$agent_id}'
			 WHERE	application_id      = {$app_id}
			  AND	ecld_id             = {$ecld_id}
			  AND	company_id          = {$company_id}
			  AND	transaction_type_id = {$tt_id}
			  AND	transaction_status  = 'pending'	";

		$st = $this->db->query($register_update_query);


		if ($st->rowCount() != 1)
		{
			throw new QC_Duplicate_Return_Exception("Transaction register UPDATE tried to affect " . $st->rowCount() . " rows with query:\n{$register_update_query}" );
		}
		
		$this->log->Write("Updated Register [Company:{$company_id}][ECLD ID:{$ecld_id}] for Application [{$app_id}] to failure");
	}

	/**
	 * Gets the process_date from the provided return file header record
	 * @param string $header the header record from the return file
	 * @returns timestamp
	 * @access private
	 */
	private function Get_Process_Date_From_File( $header )
	{
		$stringdate = substr($header, 13, 8);
		$year       = substr($stringdate, 0, 4);
		$month      = substr($stringdate, 4, 2);
		$day        = substr($stringdate, 6, 2);

		if( checkdate($month, $day, $year) )
			return $year . $month . $day;
		else
			return false;
	}

	private function Get_Process_Date_From_XML_File ( $xml )
	{
		$sxmlFile = new SimpleXMLElement($xml);
		
		$date = (string)$sxmlFile['CreateDate'];
		list($year, $month, $day) = explode("-", $date);

		if( checkdate($month, $day, $year) )
			return $year . $month . $day;
		else
			return false;

	}
	
	/**
	 * Saves the return file in the database
	 * @returns integer ecld_return_id
	 * @access private
	 */
	private function Save_Return_File()
	{
		$save_query = "
			INSERT INTO ecld_return
			 SET   date_created        = now(),
			       company_id          = ?,
			       return_file_content = ?,
			       return_status       = 'received'
		              ";

		$st = $this->db->query($save_query, array($this->company_id, $this->return_file));

		return $st->lastInsertId();
	}
	
	/**
	 * Parses a return file in xml format. Output is the same format as 
	 * property is available for this class when calling this function.
	 * 
	 * @return array An array containing transaction result information
	 */
	private function Parse_XML_Return_File()
	{
		require_once(SQL_LIB_DIR . "ownercode_company_id_map.func.php");
		$xml = $this->return_file;
		$sxmlFile = new SimpleXMLElement($xml);

		$return = array();
		
		$ownercode2company_id = ownercode_company_id_map($this->db);
		
		foreach ($sxmlFile->Member as $sxmlMember) 
		{
			foreach ($sxmlFile->xpath('/ReturnsFile/Member/Transaction') as $sxmlTransaction) 
			{
				$row = array();
				$row['return_code']    = (string)$sxmlTransaction->Check->Return['ReasonCode'];
				$row['return_amount']  = (string)$sxmlTransaction->Check->Return['ReturnAmount'];
				$row['action_code']    = '';
				$row['account_number'] = ltrim((string)$sxmlTransaction->Check['Account'], 0);
				$row['routing_number'] = (string)$sxmlTransaction->Check['RT'];
				$row['application_id'] = ltrim(substr((string)$sxmlTransaction['TRN'], -10), 0);
				$row['ecld_id']        = (string)$sxmlTransaction->Check['CheckNo'];
				$row['company_id']	   = $ownercode2company_id[(string)$sxmlMember['OwnerCode']];
				$return[] = $row;
			}
		}
		
		$numberOfRecords = (string)$sxmlFile->ReturnsFileSummaryRecord['TotalTransactionCount'];
		if($numberOfRecords != count($return) ) 
		{
			throw new Exception( "File claims " . $trailer['num_records'] . ", found " . count($return) . "." );
		}
		
		return $return;
	}

	/**
	 * Parses a return file received from US Bank
	 * @access private
	 * @todo test
	 */
	private function Parse_Flat_Return_File()
	{
		$file    = explode("\n", $this->return_file);
		$header  = array();
		$trailer = array();
		$return  = array();

		foreach( $file as $line )
		{
			$code = substr($line, 0, 2);

			switch(strtoupper($code))
			{
				case false:
					break;
				case 'HD': // Header record
					$header['sequence_number'] = rtrim(substr($line, 3,  9));
					$header['process_date']    = rtrim(substr($line, 13, 8));
					$header['client_id']       = rtrim(substr($line, 53, 30));
					break;
				case 'TL': // Trailer record
					$trailer['sequence_number'] = rtrim(substr($line, 3,  9));
					$trailer['process_date']    = rtrim(substr($line, 13, 8));
					$trailer['client_id']       = rtrim(substr($line, 53, 30));
					$trailer['num_records']     = ltrim(rtrim(substr($line, 84, 9)), '0');
					break;
				case 'P1': // Incoming Original Returns (Paper & ACH)
				case 'P2': // Incoming Second time paper returns
				case 'R1': // Incoming First ACH re-presentment returns
				case 'R2': // Incoming Second ACH re-presentment returns
				case 'S1': // Incoming First Service Fee returns
				case 'S2': // Incoming Second+ Service Fee returns
				case 'LS': // Incoming Late Service Fee returns
				case 'RC': // ACH Status changed to "cleared"
				case 'SC': // Service Fee status changed to "cleared", credit passed
				case 'OR': // Outgoing ACH
				case 'OS': // Outgoing SF
				case 'RR': // Return Item Reversal - Credited
				default:   // Detail record
					$row['return_code']    = rtrim(substr($line, 38, 3));
					$row['action_code']    = rtrim(substr($line, 42, 3));
					$row['return_amount']  = ltrim(substr($line, 61,12),0);
					$row['account_number'] = ltrim(rtrim(substr($line, 74, 16)), 0);
					$row['routing_number'] = rtrim(substr($line, 91, 9));
					$row['application_id'] = ltrim(substr(rtrim(substr($line, 336, 40)), -10), '0');
					$row['ecld_id']        = ltrim(rtrim(substr($line, 101, 8)), '0');
					$row['company_id']	   = $this->company_id;
					$return[] = $row;
					break;
			}
		}
		
		if( $header['sequence_number'] != $trailer['sequence_number'] )
		{
			throw new Exception( "File header sequence number [" . $header['sequence_number'] . "] does not mach trailer " .
			                     "record sequence number [" . $trailer['sequence_number'] . "] for '{$this->process_date}'." );
		}
		if( $header['process_date']    != $trailer['process_date'] )
		{
			throw new Exception( "File header process date [" . $header['sequence_number'] . "] does not mach trailer " .
			                     "record process date [" . $trailer['sequence_number'] . "] for '{$this->process_date}'." );
		}
		if( $header['client_id']       != $trailer['client_id'] )
		{
			throw new Exception( "File header client id [" . $header['sequence_number'] . "] does not mach trailer " .
			                     "record client id [" . $trailer['sequence_number'] . "] for '{$this->process_date}'." );
		}
		if( $trailer['num_records'] != count($return) )
			throw new Exception( "File claims " . $trailer['num_records'] . ", found " . count($return) . "." );

		return $return;
	}

/* ********************************** *\
|* PDF Quick Check Processing Methods *|
\* ********************************** */

	private function Get_PDF_File_Count()
	{
		$start = $this->process_date . "000000";
		$end   = $this->process_date . "235959";

		$count_query = "
			SELECT
				COUNT(*) AS count
			 FROM
			 	ecld_file
			 WHERE
			 	client_identifier = 'pdf'
			  AND	file_status IN ('created','downloaded')
			  AND	date_created BETWEEN '{$start}' AND '{$end}'
			  AND	company_id = {$this->company_id}
			";

		return $this->querySingleValue($count_query);
	}

	/**
	 * Processes 250 pending quick checks
	 * @throws General_Exception
	 */
	public function Process_PDF_Checks()
	{
		$this->process = self::STEP_PDF;

		$date = date("Ymd");

		$this->process_date     = $date;

		$this->Set_Process_State(self::STATE_START, $this->process_date);

		$this->client_id        = 'pdf';

		$pdf_count = $this->Get_PDF_File_Count();

		$this->db->beginTransaction();

		try
		{
			// Start by getting the data
			while( ($this->deposit_data = $this->Get_Deposit_Data(self::MAX_PDF_BUNDLE)) !== false )
			{
				$this->deposit_filename = $this->company_short . "-" . $this->process_date . "-" . ++$pdf_count . ".pdf";

				// Prepare ecld_file table for the file we're building
				$this->Init_Ecld_File_Table();

				$this->Prep_Apps();

				$this->deposit_file = $this->Generate_PDF($this->deposit_data);
				$this->Save_Deposit_File();
			}

			// No data, thats ok.  Exit gracefully
			if( $this->deposit_data === false )
			{
				$this->log->Write("Quick Check: No new transactions were located.", LOG_INFO);
				$this->Set_Process_State(self::STATE_STOP, $this->process_date);
				return array( 'status'    => self::DEPOSIT_FAIL_NO_DATA,
				              'batch_id'  => null, 
				              'client_id' => null
				            );
			}

		}
		catch( Exception $e )
		{
			$this->Failure($e, $e->getLine());
		}

		$this->db->commit();
		$this->Set_Process_State(self::STATE_STOP, $this->process_date);
		$this->log->Write( "Quick Check PDF Checks successfully generated for {$this->process_date}.", LOG_INFO );

		return array( 'status'    => self::DEPOSIT_SUCCESS,
		              'batch_id'  => $this->ecld_file_id,
			      'client_id' => $this->client_id);
	}

	/**
	 * Generates a single check in the pdf page
	 * Ugly?  yea.  copied from elsewhere, no time to clean it up
	 * @param Object $data One customer
	 */
	private function Generate_PDF_Page($pdf, $user_info)
	{
		$max_y = 792; // Top Edge = 792

		$micr_check_number_x   =  88;
		$micr_routing_number_x = 215;
		$micr_account_number_x = 322;
		$micr_y = 214; // Top MICR line Y coordinate

		$pdf->New_Page(612, 792);
		
		$split_date = array(substr($this->process_date, 4, 2),
		                    substr($this->process_date, 6, 2),
				    substr($this->process_date, 0, 4));
		$today = implode("/", $split_date);

		$pdf->ezSetY($max_y - 22);
		$text_options = array ( "left" => 103 );
		$pdf->ezText ($user_info->first_name."  ".$user_info->last_name, "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);
		$pdf->ezText ($user_info->address_1, "Helvetica" , self::FONT_SIZE_NORMAL, $text_options);
		$y = $pdf->ezText ($user_info->city.", ".$user_info->state." ".$user_info->zip, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);
		$phone = preg_replace ("/[^\d]/", "", $user_info->homephone);
		$phone = "(".substr($phone,0,3).") ".substr($phone,3,3)."-".substr($phone,6);
		$pdf->ezText ($phone, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);

		$pdf->ezSetY($max_y - 22);
		$text_options = array( "left" => 530 );
		$pdf->ezText( $user_info->cc_number, "Helvetica", self::FONT_SIZE_NORMAL, $text_options );

		$pdf->ezSetY($max_y - 51);
		$text_options = array ( "left" => 538 );
		$pdf->ezText ($today, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);

		$pdf->ezSetY($max_y - 71);
		$text_options = array ( "left" => 30 );
		$pdf->ezText ('Pay To The', "Helvetica", self::FONT_SIZE_TITLE, $text_options);
		$pdf->ezText ('Order Of', "Helvetica", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 82);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ($user_info->company_name , "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 82);
		$text_options = array ( "left" => 252 );
		$pdf->ezText ('1-877-674-0644', "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 82);
		$text_options = array ( "left" => 504 );
		$pdf->ezText ('******'.$user_info->ach_amount, "Helvetica-Bold", self::FONT_SIZE_LARGE, $text_options);

		$pdf->ezSetY($max_y - 105);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ('Pay Exactly******'.$user_info->ach_amount.' Dollars and Cents', "Helvetica-BoldOblique", self::FONT_SIZE_LARGE,
		$text_options);
		$pdf->line (382, $max_y - 116, 604, $max_y - 116, 1.5);

		$pdf->ezSetY($max_y - 124);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ($user_info->bank_name, "Helvetica", self::FONT_SIZE_NORMAL+1, $text_options);

		$pdf->ezSetY($max_y - 120);
		$text_options = array ( "left" => 400 );
		$pdf->ezText ('Payable in U.S. Funds', "Helvetica-Oblique", self::FONT_SIZE_SMALL, $text_options);

		$pdf->ezSetY($max_y - 140);
		$text_options = array ( "left" => 368 );
		$pdf->ezText ('SIGNATURE NOT REQUIRED', "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 153);
		$text_options = array ( "left" => 348 );
		$pdf->ezText ('Your depositor has authorized this payment to payee.', "Helvetica", self::FONT_SIZE_SMALL, $text_options);
		$pdf->ezText ('Payee to hold you harmless for payment of this document.', "Helvetica", self::FONT_SIZE_SMALL, $text_options);
		$pdf->ezText ('This document shall be deposited only to credit of payee.', "Helvetica", self::FONT_SIZE_SMALL, $text_options);
		$pdf->ezText ('Absense of endorsement is guaranteed by payee.', "Helvetica", self::FONT_SIZE_SMALL, $text_options);

		$pdf->ezSetY($max_y - 160);
		$text_options = array ( "left" => 30 );
		$pdf->ezText ('Memo', "Helvetica", self::FONT_SIZE_SMALL, $text_options);

		$pdf->ezSetY($pdf->Get_Y() - 5);
		$y = $pdf->ezText ('Telephone/FAX Check Payment', "Helvetica", self::FONT_SIZE_SMALL, $text_options);
		$pdf->ezText ('Customer authorization obtained', "Helvetica", self::FONT_SIZE_SMALL, $text_options);

		$pdf->ezSetY($y);
		$text_options = array ( "left" => 160 );
		$pdf->ezText ($today, "Helvetica", self::FONT_SIZE_SMALL, $text_options);

		// MICR Data
		$pdf->ezSetY($max_y - $micr_y);
		$text_options = array ( "left" => $micr_check_number_x, "embed" => 1 );
		$pdf->ezText( 'C' . $user_info->cc_number . 'C', "MICR", self::FONT_SIZE_MICR, $text_options );

		$pdf->ezSetY($max_y - $micr_y);
		$text_options = array ( "left" => $micr_routing_number_x, "embed" => 1  );
		$pdf->ezText( 'A'. $user_info->routing_number . 'A', "MICR", self::FONT_SIZE_MICR, $text_options);

		$pdf->ezSetY($max_y - $micr_y);
		$text_options = array ( "left" => $micr_account_number_x, "embed" => 1 );
		$pdf->ezText( $user_info->acctno . 'C', "MICR", self::FONT_SIZE_MICR, $text_options);

		$max_y = 790;
		$pdf->line (37, $max_y - 252, 45, $max_y - 252);
		$pdf->line (562, $max_y - 252, 570, $max_y - 252);

		$pdf->ezSetY($max_y - 270);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ($user_info->company_name , "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 270);
		$text_options = array ( "left" => 252 );
		$pdf->ezText ('1-877-674-0644', "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 270);
		$text_options = array ( "left" => 343 );
		$pdf->Set_Parameter('Underline','True');
		$pdf->ezText ('Client Copy', "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);
		$pdf->Set_Parameter('Underline','False');

		$pdf->ezSetY($max_y - 268);
		$text_options = array ( "left" => 530 );
		$pdf->ezText( $user_info->cc_number, "Helvetica", self::FONT_SIZE_NORMAL, $text_options );

		$pdf->ezSetY($max_y - 285);
		$text_options = array ( "left" => 344 );
		$pdf->ezText ("Check Authorization Notice", "Helvetica-Bold", self::FONT_SIZE_NORMAL+9, $text_options);

		$pdf->ezSetY($max_y - 305);
		$text_options = array ( "left" => 348, "spacing" => 1.2 );
		$pdf->ezText ('As per your authorization this check has been', "Helvetica", self::FONT_SIZE_SMALL, $text_options);
		$pdf->ezText ('deposited to make the payment you requested', "Helvetica", self::FONT_SIZE_SMALL, $text_options);

		$pdf->ezSetY($max_y - 313);
		$text_options = array ( "left" => 530 );
		$pdf->ezText ($today, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);

		$pdf->ezSetY($max_y - 320);
		$text_options = array ( "left" => 30 );
		$pdf->ezText ('Pay To The', "Helvetica", self::FONT_SIZE_TITLE, $text_options);
		$pdf->ezText ('Order Of', "Helvetica", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 334);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ($user_info->company_name , "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 334);
		$text_options = array ( "left" => 252 );
		$pdf->ezText ('1-877-674-0644', "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);
		$pdf->ezSetY($max_y - 358);

		$text_options = array ( "left" => 102 );
		$pdf->ezText ('Pay Exactly******'.$user_info->ach_amount.' Dollars and Cents', "Helvetica-BoldOblique", self::FONT_SIZE_LARGE,
		$text_options);
		$pdf->line (382, $max_y - 370, 604, $max_y - 370);

		$pdf->ezSetY($max_y - 392);
		$text_options = array ( "left" => 415 );
		$pdf->ezText ($user_info->bank_name, "Helvetica", self::FONT_SIZE_NORMAL+1, $text_options);

		$pdf->ezSetY($max_y - 392);
		$text_options = array ( "left" => 103 );
		$pdf->ezText ($user_info->first_name."  ".$user_info->last_name, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);
		$pdf->ezText ($user_info->address_1, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);
		$pdf->ezText ($user_info->city.", ".$user_info->state." ".$user_info->zip, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);
		$pdf->ezText ($phone, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);
		$text_options = array ( "left" => 117 );
		$pdf->ezText ($user_info->cc_number, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);

		$pdf->ezSetY($max_y - 442);
		$text_options = array ( "left" => 290 );
		$pdf->ezText ('MEMO', "Helvetica", self::FONT_SIZE_NORMAL+1, $text_options);

		$max_y = 765;
		$pdf->line (37, $max_y - 483, 45, $max_y - 483);
		$pdf->line (562, $max_y - 483, 570, $max_y - 483);

		$pdf->ezSetY($max_y - 515);
		$text_options = array ( "left" => 15 );
		$pdf->Set_Parameter('Underline','True');
		$pdf->ezText ('Payee Copy', "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);
		$pdf->Set_Parameter('Underline','False');

		$pdf->ezSetY($max_y - 516);
		$text_options = array ( "left" => 103 );
		$pdf->ezText ($user_info->first_name."  ".$user_info->last_name, "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);
		$pdf->ezText ($user_info->address_1, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);
		$pdf->ezText ($user_info->city.", ".$user_info->state." ".$user_info->zip, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);
		$pdf->ezText ($phone, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);

		$pdf->ezSetY($max_y - 534);
		$text_options = array ( "left" => 349 );
		$pdf->ezText ('NON-NEGOTIABLE', "Helvetica-Bold", self::FONT_SIZE_TITLE+2, $text_options);

		$pdf->ezSetY($max_y - 521);
		$text_options = array ( "left" => 530 );
		$pdf->ezText( $user_info->cc_number, "Helvetica-Bold", self::FONT_SIZE_NORMAL, $text_options );

		$pdf->ezSetY($max_y - 546);
		$text_options = array ( "left" => 530 );
		$pdf->ezText ($today, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);

		$pdf->ezSetY($max_y - 563);
		$text_options = array ( "left" => 30 );
		$pdf->ezText ('Pay To The', "Helvetica", self::FONT_SIZE_TITLE, $text_options);
		$pdf->ezText ('Order Of', "Helvetica", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 577);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ($user_info->company_name, "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 577);
		$text_options = array ( "left" => 252 );
		$pdf->ezText ('1-877-674-0644', "Helvetica-Bold", self::FONT_SIZE_TITLE, $text_options);

		$pdf->ezSetY($max_y - 607);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ('Pay Exactly******'.$user_info->ach_amount.' Dollars and Cents', "Helvetica-BoldOblique", self::FONT_SIZE_LARGE,
		$text_options);
		$pdf->line (382, $max_y - 620, 604, $max_y - 620);

		$pdf->ezSetY($max_y - 628);
		$text_options = array ( "left" => 102 );
		$pdf->ezText ($user_info->bank_name, "Helvetica", self::FONT_SIZE_NORMAL+1, $text_options);

		$pdf->ezSetY($max_y - 664);
		$text_options = array ( "left" => 113 );
		$pdf->ezText ($user_info->cc_number, "Helvetica", self::FONT_SIZE_NORMAL, $text_options);

		// MICR Data
		$pdf->ezSetY($max_y - 710);
		$text_options = array ( "left" => $micr_check_number_x, "embed" => 1 );
		$pdf->ezText( 'C' . $user_info->cc_number . 'C', "MICR", self::FONT_SIZE_MICR, $text_options );

		$pdf->ezSetY($max_y - 710);
		$text_options = array ( "left" => $micr_routing_number_x, "embed" => 1  );
		$pdf->ezText( 'A'. $user_info->routing_number . 'A', "MICR", self::FONT_SIZE_MICR, $text_options);

		$pdf->ezSetY($max_y - 710);
		$text_options = array ( "left" => $micr_account_number_x, "embed" => 1 );
		$pdf->ezText( $user_info->acctno . 'C', "MICR", self::FONT_SIZE_MICR, $text_options);

		$pdf->End_Page();
	}

	/**
	 * Uses the junk provided by Rodric to generate a pdf of the data
	 * @throws General_Exception
	 * @param  array $data
	 * @access private
	 */
	private function Generate_PDF($data)
	{
		$pdf = new PDF_Document();
		$pdf->Set_Parameter( "FontOutline", "MICR=" . Quick_Checks::$DIR_FONT . "Micr.TTF" );
		$pdf->Set_Info("Title",  "PDF Quick Checks for {$this->process_date}" );
		$pdf->Set_Info("Author", "eCash 3.0" );

		$processed_count = 0;
		foreach( $data as $application_id => $check )
		{
			++$processed_count;
			$user_info = new stdClass();
			$user_info->first_name      = ucwords($check['name_first']);
			$user_info->last_name       = ucwords($check['name_last']);
			$user_info->address_1       = ucwords($check['address_1']);
			$user_info->city            = ucwords($check['city']);
			$user_info->county            = ucwords($check['county']);
			$user_info->state           = strtoupper($check['state']);
			$user_info->routing_number  = $check['bank_aba'];
			$user_info->acctno          = $check['bank_account'];
			$user_info->zip             = $check['zip'];
			$user_info->homephone       = $check['homephone'];
			$user_info->cc_number       = $check['quick_check_num'] . str_pad($check['ecld_id'], 9, "0", STR_PAD_LEFT);
			$user_info->bank_name       = ucwords($check['bank_name']);
			$user_info->ach_amount      = $check['amount'];
			$user_info->company_name    = $check['company_name'];

			$this->Generate_PDF_Page($pdf, $user_info);
		}

		$pdf->Close();

		return $pdf->Get_Buffer();
	}

	/**
	 * Generates necessary headers to download the pdf
	 * @access public
	 * @param  string $local Should the file be saved locally?
	 * @throws General_Exception
	 */
	public function Download_PDF($file_id, $local = false)
	{
		if( empty($file_id) || ! is_int($file_id) )
			$this->Failure( "Invalid parameter passed to " . __METHOD__ . ".", __LINE__ );

		$pdf_query = "
			SELECT
				ecld_file_content,
				remote_filename
			 FROM
			 	ecld_file
			 WHERE
			 	ecld_file_id = " . $this->db->quote($file_id);
	
		try
		{
			$pdf_result = $this->db->query($pdf_query);
			
			if ($pdf_result->rowCount() == 1)
			{
				$row = $pdf_result->fetch(PDO::FETCH_ASSOC);
	
				$filename = $row['remote_filename'];
				$filedata = $row['ecld_file_content'];
	
				$this->Update_File_Status('downloaded', self::STEP_DEPOSIT, $file_id);
			}
	
			if ($local === true && ! empty($filedata))
			{
				$fh = fopen($filename, "w");
				fputs($fh, $filedata);
				fclose($fh);
				$this->log->Write( "Quick Check PDF Checks downloaded.  file_id: {$file_id}.", LOG_INFO );
			}
			elseif( $local === false && ! empty($filedata) )
			{
				$data_length  = strlen($filedata);
				header( "Content-Type: application/pdf" );
				header( "Content-Disposition: attachment; filename=" . $filename );
				header( "Content-Length: $data_length" );
				echo $filedata;
				$this->log->Write( "Quick Check PDF Checks downloaded.  file_id: {$file_id}.", LOG_INFO );
			}			
		}
		catch( Exception $e )
		{
			$this->Failure( $e, __LINE__ );
		}



		return false;
	}

/* *************************************************** *\
|* Quick Check Processing Methods (electronic deposit) *|
\* *************************************************** */

	/**
	 * Processes quick checks for the day
	 * @throws General_Exception
	 * @return array  Info about the batch
	 * @access public
	 */
	public function Process_Quick_Checks()
	{	
		echo "OH NO NO!";DIE;
		$this->log->Write(__FILE__.": Executing quick checks processing. [Mode: {$_BATCH_XEQ_MODE}] [Company: {$this->company_short}]");

		$this->process = 'qc_build_deposit';
		$this->process_date = date("Ymd");

		$status = Check_Process_State($this->db, $this->company_id, 'qc_build_deposit', $this->process_date);
	
		if(($status === false) || ($status != 'completed'))
		{
			// Check to see if the previous process failed
			if(($status == 'failed') || ($status == 'started'))
			{
				$ecld_file_id = $this->Get_Ecld_File_Id($this->process_date);
				if(!empty($ecld_file_id))
				{
					$this->ecld_file_id = $ecld_file_id;
				}
			}
			
			$pid = Set_Process_Status($this->db, $this->company_id, 'qc_build_deposit', 'started');

			// Have we tried sending this before?
			$attempts = $this->Quick_Check_Sent($this->process_date);

			if( $attempts > 0 )
			{
				$attempts += 1;
				$ordinal = array('th','st','nd','rd','th','th','th','th','th','th');
				Set_Process_Status($this->db, $this->company_id, 'qc_build_deposit','failed', null, $pid);
				$this->log->Write( "QC: [ELEC]: " . $attempts . $ordinal[($attempts%10)] .
		        	           	" attempt to process Quick Checks for " . $this->process_date . ".", LOG_INFO );
				return array( 'status' => self::DEPOSIT_FAIL_DUPLICATE, 'batch_id'  => null,  'client_id' => null );
			}
			else
			{
				$this->log->Write( "QC: [ELEC]: 1st attempt to process Quick Checks for " . $this->process_date . ".", LOG_INFO );
			}

			try
			{
				Update_Progress('qc','Getting Deposit Data',5);
				// Start by getting the data
				$this->deposit_data = $this->Get_Deposit_Data();

				// No data, thats ok.  Exit gracefully
				if(($this->deposit_data === false) && (empty($ecld_file_id)))
				{
					$this->log->Write("QC: No new transactions were located.", LOG_INFO);
					Set_Process_Status($this->db, $this->company_id, 'qc_build_deposit','completed', null, $pid);
					return array( 'status' => self::DEPOSIT_FAIL_NO_DATA, 'batch_id' => null, 'client_id' => null);
				}

				Update_Progress('qc',"Found " . count($this->deposit_data) . " accounts", 10);
				
				// If the ecld_file_id is empty, this is our first run and we can create
				// the ecld_file entry, otherwise we need to grab the existing deposit data
				// and then merge it before we create the file.
				if(empty($ecld_file_id))
				{
					$this->log->Write("QC: Initing ecld_file entry");
					$this->Init_Ecld_File_Table();
				}
				else
				{
					$this->log->Write("QC: Found existing ecld_file_id: {$ecld_file_id}");
					$previous_data = $this->Get_Deposit_Data_From_Ecld_Table($ecld_file_id);
				}

				// Set status, make schedule & register items
				Update_Progress('qc',"Prepping Apps (Creating QC transactions, updating statuses)", 30);
				$this->Prep_Apps();

				// If we're recovering from a previous failure, merge it in with the current data.
				if(is_array($previous_data))
				{
					$this->deposit_data = array_merge($this->deposit_data, $previous_data);
				}

				// Get a guid(uuid) for the file
				$this->client_id = $this->Get_UUID();
				$this->deposit_filename = $this->client_id . ".dat";
				//$this->deposit_url .= "/{$this->deposit_filename}";

				// Create the file
					// Everything except the binary lengths
				Update_Progress('qc',"Building Deposit File", 80);
				$this->Build_Deposit_File();

				// Save it to the db
					// Saves the data without binary lengths
				$this->Save_Deposit_File();

				Set_Process_Status($this->db, $this->company_id, 'qc_build_deposit','completed', null, $pid);
				
			}
			catch( Exception $e )
			{
				$this->Failure($e, $e->getLine());
			}

		}
	}
	
	public function Send_Deposit_File($ecld_file_id = null)
	{
		$this->process_date = date("Ymd");

		try 
		{
			$status = Check_Process_State($this->db, $this->company_id, 'qc_send_deposit', $this->process_date);
			if(($status === false) || ($status != 'completed'))
			{
				if($ecld_file_id === null)
				{
					$ecld_file_id = $this->Get_Ecld_File_Id($this->process_date);
					if(!empty($ecld_file_id))
					{
						$this->ecld_file_id = $ecld_file_id;
					}
					else
					{
						Set_Process_Status($this->db, $this->company_id, 'qc_send_deposit', 'failed');
						$this->log->Write("QC: No ecld_file_id found!  No file to send!");
						return false;
					}
				}

				$this->process = self::STEP_SEND;
				$pid = Set_Process_Status($this->db, $this->company_id, 'qc_send_deposit', 'started');

				// The following function sets this->deposit_file and this->deposit_filename
				$this->Get_Deposit_File($ecld_file_id);

				// Send it out
				
				$this->deposit_url .= "/{$this->deposit_filename}";
				$this->deposit_file = $this->Add_Binary_Lengths($this->deposit_file);
				$this->deposit_filename = $this->Create_Temporary_File($this->deposit_filename, $this->deposit_file);

				Update_Progress('qc',"File_Name: {$this->deposit_filename}",88);
				Update_Progress('qc',"Transferring Batch File", 90);
				$response = $this->File_Transfer();
		
				// Check for reponse before updating file status
				if($response != false)
				{
					Set_Process_Status($this->db, $this->company_id, 'qc_send_deposit','completed', null, $pid);
					$this->log->Write( "QC: Quick Check Electronic Checks successfully processed for {$this->process_date}.", LOG_INFO );
					Update_Progress('qc',"Transfer Successful",100);
					
					// Mark the file as sent
					$this->Update_File_Status('sent', 'qc_send_deposit', $ecld_file_id);

					// Cleanup tmp file
					unlink($this->deposit_filename);

					return array( 'status'    => self::DEPOSIT_SUCCESS,
	    				          'batch_id'  => $this->ecld_file_id,
							      'client_id' => $this->client_id);
		
				}
				else
				{
					Set_Process_Status($this->db, $this->company_id, 'qc_send_deposit','failed', null, $pid);
					$this->Update_File_Status('failed', 'qc_send_deposit', $ecld_file_id);
					
					Update_Progress('qc',"Transfer Failed!  Please contact eCash Support!",999);

					$this->log->Write( "QC: Quick Check Electronic Checks failed for {$this->process_date}.", LOG_INFO );

					return array( 'status'    => self::DEPOSIT_FAIL_DUPLICATE,
		    			          'batch_id'  => $this->ecld_file_id,
							      'client_id' => $this->client_id);
				}
			}
			
		}
		catch( Exception $e )
		{
			$this->Failure($e, $e->getLine());
		}
	}


	/**
	 * Adds the line lengths as 4 byte big endian binary data to the beginning of each line
	 * @param   string $data The original data
	 * @returns string Modified file data
	 * @access privated
	 */
	private function Add_Binary_Lengths($data)
	{
		$x_data = explode("\n", $data);
		for( $x = 0 ; $x < count($x_data) ; ++$x )
		{
			$x_data[$x] = pack("N", strlen($x_data[$x])) . $x_data[$x];
		}
		return implode("", $x_data);
	}

	/**
	 * Saves the built file to the database
	 * @throws General_Exception
	 * @access private
	 */
	private function Save_Deposit_File()
	{
		$save_query = "
					UPDATE ecld_file
					 SET   ecld_file_content = ?,
					       remote_filename   = ?
					       client_identifier = ?
					 WHERE ecld_file_id      = ?
					  ";

		$st = $this->db->queryPrepared($save_query, array($this->deposit_filename, $this->client_id, $this->ecld_file_id));
	}
	
	/**
	 * Reads the built file from the database and sets up all
	 * the proper variables to match the original values
	 * @throws General_Exception
	 * @access private
	 */
	private function Get_Deposit_File($ecld_file_id)
	{
		$query = "
				   SELECT ecld_file_content, remote_filename, date_created FROM ecld_file
  				   WHERE ecld_file_id      = '{$ecld_file_id}'
			     ";
		
		if (!($row = $this->db->querySingleRow($query, NULL, PDO::FETCH_OBJ)))
		{
			throw new Exception("Could not retrieve ecld file : id {$ecld_file_id}");
		}
		
		$this->deposit_filename = $row->remote_filename;
		$this->client_id = preg_replace('/.dat/','', $this->deposit_filename);
		$this->deposit_file = $row->ecld_file_content;

		// Set the Process Date to that of the original Deposit
		$date = strtotime($row->date_created);
		$this->process_date = date("Ymd", $date);
	
		return(true);
	}

	/**
	 * Initializes ecld_file table entry
	 * @throws General_Exception
	 * @access private
	 */
	private function Init_Ecld_File_Table()
	{
		$init_ecld_file_query = "
			INSERT	INTO ecld_file
			 SET	date_created      = now(),
				company_id        = '{$this->company_id}',
				ecld_file_content = '',
				remote_filename   = '',
				file_status       = 'created',
				client_identifier = ''
			";

		$st = $this->db->query($init_ecld_file_query);
		$this->ecld_file_id = $st->lastInsertId();
	}

	/**
	 * @returns string
	 * @access  private
	 */
	private function Get_UUID()
	{
		return "{" . $this->db->querySingleValue("select uuid()") . "}";
	}

	/**
	 * Places data in bundles and gets total $$ amount in bundle
	 * @access private
	 */
	private function Prep_Bundles()
	{
		$bundle_rows       = 0;
		$num_bundles       = 0;
		$bundled           = array();

		foreach( $this->deposit_data as $deposit_line )
		{
			if( $bundle_rows === self::MAX_CHECKS_IN_BUNDLE )
			{
				$bundle_cash_total = 0;
				$bundle_rows = 0;
				++$num_bundles;
			}

			$bundled[$num_bundles][$bundle_rows] = $deposit_line;
			if( ! isset($bundled[$num_bundles]['total_amount']) )
				$bundled[$num_bundles]['total_amount'] = 0;
			$bundled[$num_bundles]['total_amount'] += $deposit_line['amount'];
			++$bundle_rows;
		}

		$this->bundle_data = $bundled;
	}

	/**
	 * Builds the file to be saved & sent
	 *   and puts it in a temp file
	 * @throws General_Exception
	 * @access private
	 */
	private function Build_Deposit_File()
	{
		$co_phone_number	= ECash::getConfig()->COMPANY_PHONE_NUMBER;
		$company_name 		= ECash::getConfig()->COMPANY_NAME;
		$qc_company 		= ECash::getConfig()->QC_COMPANY;
		$qoc 				= ECash::getConfig()->QC_OWNER_CODE;

		$total_checks  = 0;

		// Counts for header and footer records
		$total_records = 5;
		$total_check_amount = 0;

		// These are used quite a bit in this function
		$date_Hi = date("Hi");

		// Get the data into bundles
		$this->Prep_Bundles();

		// File headers
		$this->deposit_file  = $this->Build_Record( self::FILE_HEADER,        array(3  => $this->execution_mode,
		                                                                            6  => $this->process_date,
		                                                                            7  => $date_Hi) );
		$this->deposit_file .= $this->Build_Record( self::CASH_LETTER_HEADER, array(5  => $this->process_date,
		                                                                            6  => $this->process_date,
		                                                                            7  => $date_Hi) );
		$this->deposit_file .= $this->Build_Record( self::USER_DEPOSIT,       array(8  => $this->client_id,
		                                                                            9  => $qc_company,
		                                                                            10 => $qoc,
		                                                                            13 => (int)ECASH_VERSION));

		// this->Prep_Bundles() put data in groups of 1000
		for( $bundle_num = 0 ; $bundle_num < count($this->bundle_data) ; ++$bundle_num )
		{
			$current_bundle = $this->bundle_data[$bundle_num];

			$this->deposit_file .= $this->Build_Record( self::BUNDLE_HEADER,  array(5  => $this->process_date,
			                                                                        6  => $this->process_date) );

			$total_amount = $current_bundle['total_amount'];
			unset($current_bundle['total_amount']);

			for( $row_num = 0 ; $row_num < count($current_bundle) ; ++$row_num )
			{
				$current_row = $current_bundle[$row_num];
				// Build unparsed micr record per spec by US
				//$unparsed_micr = "#{$current_row['bank_aba']}# {$current_row['bank_account']}/{$current_row['ecld_id']}";
				// The first part of the record containing the Bank ABA
				$unparsed_micr_acct = "#{$current_row['bank_aba']}# ";
				// The ONUS Field, starting from the Account number to the Check Number.  Max length is 20.
				if (strlen($current_row['bank_account'].$current_row['ecld_id']) >= 20) 
				{
					$unparsed_micr_onus = "{$current_row['bank_account']}/";
					$unparsed_micr_onus_aux = "/{$current_row['ecld_id']}/";
				} 
				else 
				{
					$unparsed_micr_onus = "{$current_row['bank_account']}/{$current_row['ecld_id']}";
					$unparsed_micr_onus_aux = "";
				}
				$unparsed_micr = $unparsed_micr_onus_aux . $unparsed_micr_acct . $unparsed_micr_onus;
				
				if(strlen($unparsed_micr_onus) > 20)
				{
					// Should Email an exception, skip this record.  Actually, this should be done
					// sooner in the operation.  We may also want to look for apps with credit card
					// account numbers.
					//continue;
				}
				

				$this->deposit_file .= $this->Build_Record( self::USER_TRANSACTION, array(8  => $current_row['trn']) );
				$this->deposit_file .= $this->Build_Record( self::CHECK_DETAIL,     array(6  => ($current_row['amount']*100)) );
				++$total_checks;
				$this->deposit_file .= $this->Build_Record( self::CHECK_DETAIL_B,   array(8  => $current_row['trn']) );
				$this->deposit_file .= $this->Build_Record( self::USER_ITEM,        array(9  => $this->process_date,
				                                                                          10 => $date_Hi) );
				/* Added per conversaion with US Bank on 08-04-2006 */                                                                            
				$this->deposit_file .= $this->Build_Record( self::USER_UNPARSED_MICR, array( 2 => $unparsed_micr) ); // Added this here.

				/* Removed per conversation with US Bank on 08-04-2006 
				$this->deposit_file .= $this->Build_Record( self::USER_MICR,        array(8  => $current_row['bank_aba'],
				                                                                          9  => $current_row['bank_account'],
				                                                                          10 => $current_row['ecld_id'],
				                                                                          14 => ($current_row['amount']*100)) );
				*/
				$this->deposit_file .= $this->Build_Record( self::USER_FIELD_1,     array(10 => $company_name .
				                                                                                " " .
				                                                                                $co_phone_number) );
				$this->deposit_file .= $this->Build_Record( self::USER_FIELD_2,     array(10 => ($current_row['name_last'] .
				                                                                                 ', ' .
				                                                                                 $current_row['name_first'])) );
				// Assuming this is the # if records we added to the deposit file
				$total_records += 7;
			}

			$this->deposit_file .= $this->Build_Record( self::BUNDLE_CONTROL,       array(2  => count($current_bundle),
			                                                                              3  => ($total_amount*100)) );
			$total_records += 2;
			$total_check_amount += $total_amount;
		}

		$this->deposit_file .= $this->Build_Record( self::CASH_LETTER_CONTROL, array(2  => count($this->bundle_data),
		                                                                             3  => $total_checks,
		                                                                             4  => ($total_check_amount*100)) );
		$this->deposit_file .= $this->Build_Record( self::FILE_CONTROL,        array(3  => $total_records,
		                                                                             4  => $total_checks,
		                                                                             5  => ($total_check_amount*100)) );
		$this->deposit_file = substr($this->deposit_file, 0, -1);

		if( file_exists(self::TMP_DIR . "/{$this->deposit_filename}") )
			throw new Exception( "File {$this->deposit_filename} already exists." );

		// Ensure we have some place to put the temp file
		if( ! is_dir(self::TMP_DIR) )
			mkdir(self::TMP_DIR, 0700, true);

			// We're creating a temp file, but have no binary lengths... What gives?
		// Make the temp file
		$fh = fopen(self::TMP_DIR . "/{$this->deposit_filename}", "wb");
		fwrite($fh, $this->deposit_file);
		fclose($fh);
	}

	public function ReCreate_Deposit_File($ecld_file_id)
	{
		$this->deposit_data = $this->Get_Deposit_Data_From_Ecld_Table($ecld_file_id);
		$this->client_id = $this->Get_UUID();
		$this->deposit_filename = $this->client_id . ".dat";
		$this->process_date = date("Ymd");
		$this->Build_Deposit_File();
		$this->Save_Deposit_File();
		$this->deposit_file = $this->Add_Binary_Lengths($this->deposit_file);
		$this->deposit_filename = $this->Create_Temporary_File($this->deposit_filename, $this->deposit_file);
	}
	
	/**
	 * Builds 1 record for the qc file
	 * @throws General_Exception
	 * @param  integer $record_type
	 * @param  array   $data  1 row of data
	 * @return string  1 record
	 * @access private
	 */
	private function Build_Record( $record_type, $data )
	{
		if( ! isset($this->record_definitions[$record_type]) )
			throw new Exception( "Invalid record type: '{$record_type}'." );

		$record = "";

		foreach( $this->record_definitions[$record_type] as $field => $attributes )
		{
			if( isset($data[$field]) )
			{
				$record .= $this->Set_Field_Content($record_type, $field, $data[$field]);
			}
			else
			{
				$record .= $this->Set_Field_Content($record_type, $field, $attributes[self::REC_VALUE]);
			}
		}

		$record .= self::RS;

		return $record;
	}

	/**
	 * Checks data validity and pads data as necessary
	 * @throws General_Exception
	 * @param  integer $record_type
	 * @param  integer $field_number
	 * @param  string  $value
	 * @return string  Formatted field
	 * @access private
	 */
	private function Set_Field_Content( $record_type, $field_number, $value)
	{
		$field_def = $this->record_definitions[$record_type][$field_number];

		if( ! preg_match($field_def[self::REC_TYPE], $value) )
		{
			throw new Exception("Invalid data for field {$field_number} [$value], of record type " . $this->reverse_map[$record_type] . '.');
		}

		if( strlen($value) > $field_def[self::REC_SIZE] )
		{
			throw new Exception("Invalid field size for field {$field_number}, of record type " . $this->reverse_map[$record_type] . '.');
		}

		$value = str_pad( $value, $field_def[self::REC_SIZE], ' ', STR_PAD_RIGHT );

		return $value;
	}

/* ***************************************** *\
|*            File Status Methods            *|
\* ***************************************** */

	/**
	 * Checks the status on a previous submission
	 * @param  integer[optional]  $date  strtotime()
	 * @throws General_Exception
	 * @return integer $return IN (STATUS_SOAP_ERROR, STATUS_NO_STATUS, STATUS_PROCESSED, STATUS_RESEND, STATUS_ERROR)
	 * @access public
	 * @todo everything
	 */
	public function Check_Status($date = null)
	{
		if( empty($date) )
			$date = date("Ymd");

		if( ! $this->okdate($date) )
			$this->Failure( 'Invalid process date requested of ' . __METHOD__ . '.', __LINE__  );

		$this->process_date = $date;

		$this->process = Quick_Checks::STEP_STATUS;

		if( $this->Quick_Check_Sent($this->process_date) === 0 )
			return self::STATUS_NO_STATUS;

		// What was this date's client_id?
		$this->client_id = $this->Get_Client_Id();

		// Build Status Request File
		$this->Build_Status_Request();

		// Get the wsdl and stick it in a file
		$this->wsdl_path = $this->Fetch_WSDL();

		// Request status
		$response = trim(ltrim($this->File_Transfer()));

		unlink($this->wsdl_path);

		// Parse the response
		return $this->Parse_Status_Response($response);
	}

	/**
	 * Fetches the US Bank wsdl
	 * @access private
	 * @returns string location of tmp wsdl file
	 */
	private function Fetch_WSDL()
	{
		// IIS being used at US Bank for this too
		// IIS doesn't close ssl connections cleanly, causing php to generate warnings
		$wsdl = @file_get_contents($this->status_url);
		return $this->Create_Temporary_File('US_Bank.wsdl', $wsdl);
	}

	/**
	 * Parses the xml response for the status request and returns the response code
	 * @throws General_Exception
	 * @param string the response text
	 * @return integer $return IN (STATUS_SOAP_ERROR, STATUS_NO_STATUS, STATUS_PROCESSED, STATUS_RESEND, STATUS_ERROR)
	 * @access private
	 */
	private function Parse_Status_Response($response)
	{
		if( empty($response) )
			throw new Exception( "Empty response received for client_id {$this->client_id}." );
		elseif( preg_match( '%<([a-z]+:)*faultcode[^>]*>([^<]+)</faultcode>\s*<([a-z]+:)*faultstring[^>]*>([^<]+)</faultstring>%i',
		                    $response, $parsed ) )
		{
			$this->status_error_faultcode   = $parsed[2];
			$this->status_error_faultstring = $parsed[4];
			$this->status_error_response    = $response;
			$this->log->Write( "Quick Check Status:  Error received checking status of client_id {$this->client_id}.\n" .
			                   "Code: {$this->status_error_faultcode}\nString: {$this->status_error_faultstring}", LOG_ERR );
			return self::STATUS_SOAP_ERROR;
		}
		elseif( ! preg_match( "%<state>[^<]*</state>%i", $response ) )
		{
			throw new Exception("Malformed response received for client_id {$this->client_id}.\nResponse <<<EOS\n{$response}\nEOS;\n");
		}

		preg_match( "%<state>([^<]+)</state>%i", $response, $status );

		switch(strtolower($status[1]))
		{
			case 'processed':
				return self::STATUS_PROCESSED;
			case 'resend':
				return self::STATUS_RESEND;
			case 'error':
				return self::STATUS_ERROR;
			default:
				throw new Exception( "Status response contained an unrecognized state '{$status[1][0]}' for client_id {$this->client_id}." );
		}
	}

	/**
	 * Checks if a file was sent out on the specified date and returns the client_id
	 * @throws General_Exception
	 * @return string
	 * @access private
	 */
	private function Get_Client_Id()
	{
		$start_date = $this->process_date . '000000';
		$end_date   = $this->process_date . '235959';

		$status_query = "
			SELECT client_identifier
			 FROM  ecld_file
			 WHERE date_created BETWEEN '{$start_date}'
			                        AND '{$end_date}'
			";

		$st = $this->db->query($status_query);

		switch($st->rowCount())
		{
			case 1:
				return $st->fetchColumn(0);
			case 0:
				throw new Exception( "Deposit file sent for " . $this->process_date . ", but no ecld_file entry found!" );
			default:
				throw new Exception( "More than 1 ecld_file entry found for " . $this->process_date . '.' );
				break;
		}
	}

	/**
	 * Builds the status request file
	 * @todo everything
	 */
	private function Build_Status_Request()
	{
	        $this->status_file = <<<EOS
<DEPOSITSTATUSREQUEST>
        <BASICAUTHENTICATION>
                <USERNAME>{$this->status_user}</USERNAME>
                <PASSWORD>{$this->status_pass}</PASSWORD>
        </BASICAUTHENTICATION>
        <DEPOSITID>{$this->client_id}</DEPOSITID>
</DEPOSITSTATUSREQUEST>
EOS;
	}

/* ***************************************** *\
|*               Info Methods                *|
\* ***************************************** */

	/**
	 * Gets the # of apps waiting to be processed
	 * @returns integer
	 * @throws General_Exception
	 * @access public
	 */
	public function Get_Pending_Count()
	{
		$qc = new ECash_ExternalBatches_QuickCheckBatch($this->db);
		$qc->preprocess();
		$qc->process();
		$count = $qc->getAppCount();
		$balance = $qc->getBalance();

		// QC Ready Status ID
		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$status_id = $status_list->toId('ready::quickcheck::collections::customer::*root');

		$pending_query = "
			SELECT
				COUNT(*)                AS count,
				SUM((ls.principal_pending + ls.service_charge_pending + ls.fee_pending))		AS total
			 FROM
			 	company                 AS c,
				loan_type               AS lt,
				application             AS a
			 LEFT JOIN
                (SELECT
					ea.application_id,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short = 'principal', ea.amount, 0)) principal_pending,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short = 'service_charge', ea.amount, 0)) service_charge_pending,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short = 'fee', ea.amount, 0)) fee_pending,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short <> 'irrecoverable', ea.amount, 0)) total_pending
				  FROM
					event_amount ea
          JOIN application a USING (application_id)
          JOIN event_amount_type eat USING (event_amount_type_id)
          JOIN transaction_register tr USING (transaction_register_id)
				  WHERE a.application_status_id = {$status_id}
				  GROUP BY ea.application_id
				) AS ls ON (ls.application_id = a.application_id)
			 WHERE
				a.company_id = {$this->company_id}
			  AND	a.company_id             = c.company_id
			  AND	lt.loan_type_id          = a.loan_type_id
			  AND	lt.name_short = 'standard'
			  AND	ls.total_pending > 0
			  AND	a.application_status_id = {$status_id}
			  AND	a.is_watched = 'no'
			  AND   NOT EXISTS
			  		( -- Exclude Apps with Scheduled or Pending items in their schedule
	                	SELECT 'X'
                		FROM event_schedule AS es
                            LEFT JOIN transaction_register AS tr using (event_schedule_id)
	                	WHERE es.application_id = a.application_id
    	            	AND   (tr.transaction_status = 'pending' OR  es.event_status = 'scheduled')
        	       	)";
		$retval = array();
		try
		{
			$pending_result = $this->db->query($pending_query);
		
			if ($pending_result->rowCount() > 0)
			{
				$pending_row    = $pending_result->fetch(PDO::FETCH_OBJ);
				$retval['count']    = number_format($pending_row->count);
				$retval['total']    = "\\$" . number_format($pending_row->total, 2);
			}
			else
			{
				$retval['count']    = 0;
				$retval['total']    = 0;
			}
		}
		catch( Exception $e )
		{
			$this->Failure($e, $e->getLine());
		}
		$retval['count'] = $count;
		$retval['total'] = $balance;
		return $retval;
	}

	/**
	 * Gets info about 1 day's pdf batch
	 * @param string $date yyyymmdd
	 * @returns array
	 * @throws Exception
	 */
	public function Get_PDF_Batch_Info( $date )
	{
		if( empty($date) )
			$date = date("Ymd");

		if( ! $this->okdate($date) )
			$this->Failure( 'Invalid process date requested of ' . __METHOD__ . '.', __LINE__  );

		$start = $date . "000000";
		$end   = $date . "235959";

		$pdf_query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				ecld_file_id,
				(SELECT COUNT(*)
				 FROM  	ecld AS ecld
				 WHERE 	ecld.ecld_file_id = file.ecld_file_id
				) AS count,
				(SELECT SUM(amount)
				 FROM 	ecld AS ecld
				 WHERE  ecld.ecld_file_id = file.ecld_file_id
				) AS total,
				file.file_status   AS status,
				file.date_modified AS last_action,
                IF(file.client_identifier = 'pdf', 'PDF', 'Electronic') AS type
			 FROM
				ecld_file AS file
			 WHERE
				file.date_created BETWEEN '{$start}' AND '{$end}'
			 -- AND	file.client_identifier = 'pdf'
			";

		//echo "<pre>\n" . str_replace("\t", "        ", $pdf_query) . "\n</pre>\n";
		//exit;

		$data = array();
		
		try 
		{
			$pdf_result = $this->db->Query($pdf_query);
			
			if ($pdf_result->rowCount() < 1)
			{
				return $data;
			}
		
			while ($row = $pdf_result->fetch(PDO::FETCH_ASSOC))
			{
				$row_data['ecld_file_id'] = (int)$row['ecld_file_id'];
				$row_data['count']        = (int)$row['count'];
				$row_data['total']        = "\\$" . number_format((int)$row['total'],2);
				$row_data['status']       = ucfirst($row['status']);
				$row_data['last_action']  = $row['last_action'];
				
				$data[] = $row_data;
			}
		}
		catch( Exception $e )
		{
			$this->Failure( $e, __LINE__ );
		}

		return $data;
	} 
	
	
	/**
	 * Gets info about all batches processed during the time period
	 * Consolidates pdf batches done in same day to 1 row
	 * @param string $start_date yyyymmdd
	 * @param string $end_date   yyyymmdd
	 * @returns array
	 * @throws General_Exception
	 */
	public function Get_Batch_Info( $start_date, $end_date )
	{
		$start_year  = substr($start_date, 0, 4);
		$start_month = substr($start_date, 4, 2);
		$start_day   = substr($start_date, 6, 2);
		$end_year    = substr($end_date,   0, 4);
		$end_month   = substr($end_date,   4, 2);
		$end_day     = substr($end_date,   6, 2);

		if( ! checkdate($start_month, $start_day, $start_year) ||
		    ! checkdate($end_month,   $end_day,   $end_year) ||
		    mktime(0, 0, 0, $start_month, $start_day, $start_year) > mktime(0, 0, 0, $end_month, $end_day, $end_year) )
		{
			$this->Failure( "Invalid date passed to " . __METHOD__ . ".", __LINE__ );
		}

		$start = $start_date . "000000";
		$end   = $end_date   . "235959";

		$batch_query = "
			SELECT
				ecld_file_id                                            AS id,
				date_format(file.date_created, '%m/%d/%Y')              AS date,
				IF(file.client_identifier = 'pdf', 'PDF', 'Electronic') AS type,
				(SELECT COUNT(*)
				  FROM	ecld AS ecld
				  WHERE ecld.ecld_file_id = file.ecld_file_id
				)                                                       AS count,
				(SELECT SUM(amount)
				 FROM 	ecld AS ecld
				 WHERE  ecld.ecld_file_id = file.ecld_file_id
				) AS total,
				file.file_status                                        AS status,
				date_format(file.date_modified, '%m/%d/%Y') AS last_action
			FROM
				ecld_file AS file
			WHERE
				file.date_created BETWEEN '{$start}' AND '{$end}' AND
				company_id = {$this->company_id}
			ORDER BY file.date_created DESC
			";

		$data = array();

		try
		{
			$batch_result = $this->db->query($batch_query);
			if ($batch_result->rowCount() < 1)
			{
				return $data;
			}
	
			while ($row = $batch_result->fetch(PDO::FETCH_ASSOC))
			{
				$date = $row['date'];
				$pdf  = (strtolower($row['type']) == 'pdf' ? true : false);
	
				if( $pdf )
				{
					if( isset($data[$date]) )
					{
						$data[$date]['count'] += $row['count'];
						$data[$date]['total'] += $row['total'];
						// So the user will know if any files in the pdf batch have not yet been downloaded
						if( $data[$date]['status'] === 'downloaded' )
							$data[$date]['status'] = $row['status'];
						elseif( $data[$date]['status'] === 'created' && $row['status'] === 'failed' )
							$data[$date]['status'] = $row['status'];
					}
					else
						$data[$date] = $row;
				}
				else
				{
					$data[] = $row;
				}
			}
				
		}
		catch( Exception $e )
		{
			$this->Failure( $e, __LINE__ );
		}


		return $data;
	}

/* ***************************************** *\
|*          General/Utility Methods          *|
\* ***************************************** */

	/**
	 * Counts how many quick checks have been sent for one application
	 * @param integer $application_id
	 * @returns integer # of quickchecks sent
	 * @throws Exception
	 * @access private
	 */
	private function Count_QC_Sent( $application_id, $company_id = null )
	{
		if (!isset($company_id)) 
		{
			$company_id = $this->company_id;
		}
		$tt_id = $this->Get_Transaction_Type_Id($company_id);
		
		$sent_query = "
			SELECT
				COUNT(*) AS count
			 FROM
			 	transaction_register
			 WHERE
			 	application_id = {$application_id}
			  AND	company_id     = {$company_id}
			  AND	transaction_type_id = {$tt_id}
			";

		return $this->db->querySingleValue($sent_query);
	}

	/**
	 * Finds out when a process was last run
	 * @param string[optional] $process
	 * @returns string date YYYY-mm-dd
	 */
	private function Fetch_Last_Process_Date( $process = null )
	{
		if( empty($process) )
		{
			if( empty($this->process) )
				$process = self::STEP_RETURN;
			else
				$process = $this->process;
		}

		$process = $this->db->quote($process);

		$last_run_query = "
			SELECT
				DATE_FORMAT(MAX(business_day),'%Y%m%d') AS last_run_date
			 FROM
			 	process_log
			 WHERE
			 	step       = '{$process}'
			  AND	state      = 'completed'
			  AND	company_id = {$this->company_id}
			";

		$last_run_result = $this->db->query($last_run_query);
		$row = $last_run_result->fetch(PDO::FETCH_ASSOC);

		if( ! empty($row['last_run_date']) )
			return $row['last_run_date'];
		else
			return '19700101';
	}

	/**
	 * Checks if the date passed is legal
	 * @param string $date yyyymmdd || yyyy-mm-dd
	 * @returns boolean
	 */
	private function okdate( $date )
	{
		if( strlen($date) == 10 )
		{
			$year  = (int)substr($date, 0, 4);
			$month = (int)substr($date, 5, 2);
			$day   = (int)substr($date, 8, 2);
		}
		elseif( strlen($date) == 8 )
		{
			$year  = (int)substr($date, 0, 4);
			$month = (int)substr($date, 4, 2);
			$day   = (int)substr($date, 6, 2);
		}
		else
		{
			$year  = -1;
			$month = -1;
			$day   = -1;
		}

		return checkdate($month, $day, $year);
	}

	/**
	 * Creates and populates the temporary file
	 * @param string $filename
	 * @param string $contents
	 * @throws General Exception
	 * @return string full path to the file
	 */
	private function Create_Temporary_File($filename, $contents)
	{
		// Ensure we have some place to put the temp file
		if( ! is_dir(self::TMP_DIR) )
			mkdir(self::TMP_DIR, 0700, true);

		$fullpath = self::TMP_DIR . "/" . $filename;

		// Make the temp file
		$fh = fopen($fullpath, "wb");
		fwrite($fh, $contents);
		fclose($fh);

		return $fullpath;
	}

	/**
	 * Performs necessary actions for an individual app to be quickchecked (schedule, register, status)
	 * @throws General Exception
	 * @access private
	 */
	private function Prep_Apps()
	{
		if(is_array($this->deposit_data))
		{
		$remaining_deposit_data = array();
			$p = 1; // Progress
			$t = count($this->deposit_data); // Total Items
			foreach( $this->deposit_data as $app_id => $line )
			{
				// To show the percentage of completion
				$percentage = intval(($p/$t)*100);
				if(($percentage != $last) && (! ($percentage % 10))) 
				{
					// We start ourt at 30% and end at 80%
					$bar = 30 +  ($percentage/2);
					Update_Progress('qc',"$percentage% Prepping Completed", $bar);
					$last = $percentage;
				}
				$p++;

				try 
				{
					Update_Status($this->server, $app_id, "sent::quickcheck::collections::customer::*root");
	
					$this->db->beginTransaction();
					$this->deposit_data[$app_id]['event_schedule_id'] = $this->Insert_Schedule($app_id);
					$line['event_schedule_id'] = $this->deposit_data[$app_id]['event_schedule_id'];
					
					$total_amount = $this->Insert_Event_Amounts($app_id, $line['event_schedule_id'], $line);
					$line['amount'] = $total_amount;
					
					$this->deposit_data[$app_id]['ecld_id'] = $this->Fill_Ecld_Table($app_id, $line);
					$line['ecld_id']           = $this->deposit_data[$app_id]['ecld_id'];
				
					$this->deposit_data[$app_id]['register_id'] = $this->Insert_Register($app_id, $line['event_schedule_id'], $total_amount, $line['ecld_id']);
					$this->db->commit();
				}
				catch (Exception $e)
				{
					$this->log->Write("QCBATCH: Failed processing {$app_id}. Retrying.");
					$remaining_deposit_data[$app_id] =& $this->deposit_data[$app_id];
				}
			}
		}
		
		if (count($remaining_deposit_data))
		{
			$this->Retry_Prep_Apps($remaining_deposit_data, 1);
		}
	}
	
	private function Retry_Prep_Apps(&$remaining_deposit_data, $count)
	{
		$failed_deposit_data = array();
		if(is_array($remaining_deposit_data))
		{
			$p = 1; // Progress
			$t = count($remaining_deposit_data); // Total Items
			foreach($remaining_deposit_data as $app_id => $line )
			{
				// To show the percentage of completion
				$percentage = intval(($p/$t)*100);
				if(($percentage != $last) && (! ($percentage % 10))) 
				{
					// We start ourt at 30% and end at 80%
					Update_Progress('qc',"Retry {$count}: $percentage% Prepping Completed", 80);
					$last = $percentage;
				}
				$p++;

				try 
				{
					Update_Status($this->server, $app_id, "sent::quickcheck::collections::customer::*root");
	
					$this->db->beginTransaction();
					$this->deposit_data[$app_id]['event_schedule_id'] = $this->Insert_Schedule($app_id);
					$line['event_schedule_id'] = $this->deposit_data[$app_id]['event_schedule_id'];
					
					$total_amount = $this->Insert_Event_Amounts($app_id, $line['event_schedule_id'], $line);
					$line['amount'] = $total_amount;
					
					$this->deposit_data[$app_id]['ecld_id'] = $this->Fill_Ecld_Table($app_id, $line);
					$line['ecld_id']           = $this->deposit_data[$app_id]['ecld_id'];
				
					$this->deposit_data[$app_id]['register_id'] = $this->Insert_Register($app_id, $line['event_schedule_id'], $total_amount, $line['ecld_id']);
					$this->db->commit();
				}
				catch (Exception $e)
				{
					$this->log->Write("QCBATCH: Failed processing {$app_id}. Retrying.");
					$failed_deposit_data[$app_id] =& $this->deposit_data[$app_id];
				}
			}
		}
		
		if (count($failed_deposit_data))
		{
			$this->Retry_Prep_Apps($failed_deposit_data, $count + 1);
		}
	}

	/**
	 * Gets the data from the database to process
	 * @throws General_Exception
	 * @return mixed
	 * @param  integer $max_rows Normally only for pdf checks
	 * @access private
	 */
	private function Get_Deposit_Data($max_rows = null)
	{
		$this->log->Write("QC: Getting Deposit Data.");
		if( empty($max_rows) || ! is_int($max_rows) )
			$limit = "";
		else
			$limit = "LIMIT {$max_rows}";

		if( defined("ARTIFICIAL_LIMIT") )
		{
			$limit = ARTIFICIAL_LIMIT;
		}

		// QC Ready Status ID
		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$status_id = $status_list->toId('ready::quickcheck::collections::customer::*root');

		$deposit_query = "
			/* tmpnokill */
			SELECT
				a.application_id        	AS application_id,
				ls.principal_pending   		AS principal,
				ls.service_charge_pending   AS service_charge,
				ls.fee_pending    			AS fee,
				(ls.principal_pending + ls.service_charge_pending + ls.fee_pending) AS amount,
				TRIM(a.bank_aba)        AS bank_aba,
				TRIM(LEADING '0' FROM a.bank_account)    AS bank_account,
				a.name_last             AS name_last,
				a.name_first            AS name_first,
				a.street                AS address_1,
				a.city                  AS city,
				a.county                  AS county,
				a.state                 AS state,
				a.zip_code              AS zip,
				c.name                  AS company_name,
				a.phone_home            AS homephone,
				a.bank_name             AS bank_name,
				a.bank_account_type     AS bank_account_type,
				IFNULL(qc.count,0) + 1  AS quick_check_num
			 FROM
			 	company                 AS c,
				loan_type               AS lt,
				application             AS a
			 LEFT JOIN
				(SELECT	tr2.application_id   AS application_id,
					COUNT(*)             AS count
				  FROM	transaction_register AS tr2
					JOIN transaction_type     AS tt2 USING(transaction_type_id)
          JOIN application a USING (application_id)
				  WHERE a.application_status_id = {$status_id}
				   AND	tt2.clearing_type       = 'quickcheck'
				  GROUP BY application_id
				) AS qc ON qc.application_id = a.application_id
			 LEFT JOIN
                (SELECT
					ea.application_id,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short = 'principal', ea.amount, 0)) principal_pending,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short = 'service_charge', ea.amount, 0)) service_charge_pending,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short = 'fee', ea.amount, 0)) fee_pending,
					SUM(IF
					  ( tr.transaction_status <> 'failed'
					  	AND eat.name_short <> 'irrecoverable', ea.amount, 0)) total_pending
				  FROM
					event_amount ea
          JOIN application a USING (application_id)
					JOIN event_amount_type eat USING (event_amount_type_id)
          JOIN transaction_register tr USING (transaction_register_id)
				  WHERE a.application_status_id = {$status_id}
				  GROUP BY ea.application_id
				) AS ls ON (ls.application_id = a.application_id)
			 WHERE
				a.company_id = {$this->company_id}
			  AND	a.company_id             = c.company_id
			  AND	lt.loan_type_id          = a.loan_type_id
			  AND	lt.name_short = 'standard'
			  AND	ls.total_pending > 0
			  AND	a.application_status_id = {$status_id}
			  AND	a.is_watched = 'no'
			  AND   NOT EXISTS
			  		( -- Exclude Apps with Scheduled or Pending items in their schedule
	                	SELECT 'X'
                		FROM event_schedule AS es
                            LEFT JOIN transaction_register AS tr using (event_schedule_id)
	                	WHERE es.application_id = a.application_id
    	            	AND   (tr.transaction_status = 'pending' OR  es.event_status = 'scheduled')
        	       	)
			 ORDER BY application_id
			 {$limit}
			";

		$deposit_result = $this->db->query($deposit_query);

		$this->log->Write("QC: Deposit Data Retrieved.");
		
		while ($row = $deposit_result->fetch(PDO::FETCH_ASSOC))
		{
			foreach( $row as $key => $value )
			{
				if( ! is_string($key) )
					unset($row[$key]);
			}

			$application_id = $row['application_id'];
			unset($row['application_id']);

			$row['trn'] = str_pad(str_pad(date("zHis"), 11, '0'), 12, '1') . str_pad($application_id, 10, '0', STR_PAD_LEFT);
			$data[$application_id] = $row;
		}

		if (count($data)) 
		{
			return $data;
		} 
		else 
		{
			return false;
		}
	}

	/**
	 * Inserts the row data for this file into the ecld table and adds the ecld_id to the data row for later usage
	 * @throws General_Exception
	 * @param integer $application_id
	 * @param array   $data
	 * @access private
	 * @returns integer ecld_id
	 */
	private function Fill_Ecld_Table($application_id, $data)
	{
		$ecld_query = "
			INSERT INTO ecld
			 SET	date_created      = now(),
				company_id        = '{$this->company_id}',
				application_id    = '{$application_id}',
				event_schedule_id = '" . $data['event_schedule_id'] . "',
				business_date     = '{$this->process_date}',
				amount            = '" . $data['amount'] . "',
				bank_aba          = '" . $data['bank_aba'] . "',
				bank_account      = '" . $data['bank_account'] . "',
				bank_account_type = '" . $data['bank_account_type'] . "',
				ecld_status       = 'batched',
				ecld_file_id      = {$this->ecld_file_id},
				trans_ref_no      = '" . $data['trn'] . "'
			";

		$this->db->query($ecld_query);

		return $this->db->lastInsertId();
	}

	/**
	 * Inserts the event schedule row for 1 app
	 * @param integer $application_id
	 * @access private
	 * @returns integer event_schedule_id
	 * @throws General_Exception
	 */
	private function Insert_Schedule($application_id)
	{
		return Record_Schedule_Entry(
				$application_id, 'quickcheck', '', '', 
				date('Y-m-d', time()), date('Y-m-d', strtotime(" +60 days")), "", 'generated');
	}
	
	private function Insert_Event_Amounts($application_id, $schedule_id, $balance_info) {
		
		// Quickchecks should use the pending balance to determine the amount 
		// for the quick check. This prevents things like double pulls, etc.
		
		// For normal amounts we will actually use the not_reattempt columns. 
		// Otherwise we would be subtracting them later.
		$amount = array();
		$amount['principal'] = $balance_info['principal'];
		$amount['service_charge']= $balance_info['service_charge'];
		$amount['fee'] = $balance_info['fee'];
		
		$total_amount = 0;
		foreach ($amount as $type => $value) 
		{
			if ($value == 0) continue;
			Record_Event_Amount($application_id, $schedule_id, $type, -$value);
			$total_amount += $value;
		}
		
		return $total_amount;
	}

	/**
	 * Inserts the transaction register row for 1 app
	 * @param integer application_id
	 * @param integer event_schedule_id
	 * @access private
	 * @returns integer transaction_register_id
	 */
	private function Insert_Register($application_id, $event_schedule_id, $amount, $ecld_id)
	{
		$amount = -abs($amount);
		$tt_id = $this->Get_Transaction_Type_Id();
		$source_map = Get_Source_Map();
		$agent_id = Fetch_Current_Agent();

		$register_query = "
			INSERT INTO transaction_register
			  (date_created,        company_id,          application_id,     event_schedule_id,
			   ecld_id,             transaction_type_id, transaction_status, amount,
			   date_effective, source_id, modifying_agent_id)
			VALUES
			  (now(),               {$this->company_id}, {$application_id}, {$event_schedule_id},
			   {$ecld_id},          {$tt_id},            'pending',         {$amount},
			   ADDDATE(now(), INTERVAL 60 day), '{$source_map['ecashinternal']}', '{$agent_id}')
			";

		$register_result = $this->db->query($register_query);
		$register_id = $this->db->lastInsertId();
		
		Set_Loan_Snapshot($register_id,"pending");		
		
		$event_status_query = "
				UPDATE event_schedule
				  SET
				    event_status = 'registered'
				  WHERE 
				    event_schedule_id = {$event_schedule_id}
			";
		$this->db->exec($event_status_query);

		$event_amount_query = "
				UPDATE event_amount
				  SET
					transaction_register_id = {$register_id}
				  WHERE
					event_schedule_id = {$event_schedule_id}
			";
		$this->db->exec($event_amount_query);
		return $register_id;
	}

	/**
	 * Sets the file status for a file (when processing is done or failed)
	 * @param string $status
	 * @throws Exception
	 * @access private
	 */
	private function Update_File_Status($status, $process = null, $id = null)
	{
		if( empty($process) )
			$process = $this->process;

		switch( $process )
		{
			case self::STEP_RETURN:
			case self::STEP_RETURN_FILE:
				if( empty($id) )
					$id = $this->return_id;

				if( ! empty($id) )
				{
					$update_query = "
						UPDATE ecld_return
						 SET   return_status = '{$status}'
						 WHERE ecld_return_id = {$id}
						";
				}
				break;
			case self::STEP_SEND:
				if( empty($id) )
					$id = $this->ecld_file_id;

				if( ! empty($id) )
				{
					$update_query = "
						UPDATE ecld_file
						 SET   file_status = '{$status}'
						 WHERE ecld_file_id = '{$id}'
						";
				}
				break;
			// Nothing to update for these
			case self::STEP_PDF:
			case self::STEP_STATUS:
				break;
		}

		if( isset($update_query) )
			$update_result = $this->db->query($update_query);
			
	}

	/**
	 * Gets the transaction_type_id for a quickcheck
	 * @returns integer transaction_type_id
	 * @throws Exception
	 */
	private function Get_Transaction_Type_Id($company_id = null)
	{
		static $tt_id;
		
		if(! empty($tt_id)) 
		{
			return $tt_id;
		}
		
		if (!isset($company_id)) 
		{
			$company_id = $this->company_id;
		}
		
		$tt_query = "
			SELECT
				transaction_type_id
			 FROM
			 	transaction_type
			 WHERE
			 	company_id = {$company_id}
			  AND	name_short = 'quickcheck'
			";

		return $this->db->querySingleValue($tt_query);
	}

	/**
	 * Checks if a quick_check file has been sent on the specified date
	 * @throws General_Exception
	 * @param  integer[optional] strtotime()
	 * @return integer number of times quick checks were sent out on specified day
	 * @access private
	 */
	public function Quick_Check_Sent($date = null)
	{
		if( empty($date) )
			$date = date('Ymd');

		$check_query = "
			SELECT
				COUNT(*) as completed
			 FROM
			 	process_log
			 WHERE
			 	step         = '" . self::STEP_DEPOSIT . "'
			  AND	state        = '" . self::STATE_STOP . "'
			  AND	business_day = '{$date}'
			  AND	company_id   = '{$this->company_id}'
			";

		return $this->db->querySingleValue($check_query);
	}

	/**
	 * Sends a file to or gets a file from US Bank
	 * @throws General_Exception
	 * @param  array $post_vars
	 * @return mixed success/failure (+ possible error messages)
	 * @access private
	 */
	private function File_Transfer()
	{
		switch( $this->process )
		{
			// Status Check
			case self::STEP_STATUS:
				$soap = new SoapClient($this->wsdl_path);

				// soap errors should just get thrown out to the caller
				$message = $soap->GetDepositStatus(array('depositStatusRequestXML' => $this->status_file));
				$result  = $message->GetDepositStatusResult();
				break;
			// Deposit
			case self::STEP_SEND:
				// This is ugly... And a hack.  But it should allow us to send the file for now.
				$this->log->Write("Deposit File: {$this->deposit_filename}");
				
				$send_string = "sudo -u usbankqc scp {$this->deposit_filename} drop1.clkonline.com:.";
				$this->log->Write("QC: Send command: '{$send_string}'");

				$output = array();
				exec($send_string, $output, $return_value);

				if($return_value != 0)
				{
					foreach($output as $o)
					{
						$this->log->Write($o);
						Update_Progress('qc',$o, 999);
					}
					return false;
				} 
				else 
				{
					Update_Progress('qc','Send file to drop1.clkonline.com sucessfully!');
				}
				
				$curl_string  = 'sudo -u usbankqc ssh drop1.clkonline.com ';
				$curl_string .= '\'curl --ftp-ssl --url "' . escapeshellcmd($this->deposit_url) .'" -T ';
				$curl_string .=  '"' . escapeshellcmd($this->client_id) . '.dat" -u '. $this->deposit_userpass .' -k -v\'';
				$this->log->Write("QC: cURL command: '{$curl_string}'");
				
				$output = array();
				exec($curl_string, $output, $return_value);

				if($return_value != 0)
				{
					foreach($output as $o)
					{
						$this->log->Write($o);
						Update_Progress('qc',$o, 999);
					}
					return false;
				} 
				else 
				{
						Update_Progress('qc', "File transfer successful!");
					return true;
				}
				
				/*
				$curl = curl_init();
				$fh = fopen($this->deposit_filename, "r");
				$this->log->Write("Uploading Deposit to URL: {$this->deposit_url}"); 

				$this->log->Write("Deposit Filename: {$this->deposit_filename}"); 

				curl_setopt($curl, CURLOPT_URL,            $this->deposit_url);
				curl_setopt($curl, CURLOPT_USERPWD,        $this->deposit_userpass);
				curl_setopt($curl, CURLOPT_VERBOSE,        1);
				curl_setopt($curl, CURLOPT_TIMEOUT,        30);
				curl_setopt($curl, CURLOPT_FTP_USE_EPRT,   0);
				curl_setopt($curl, CURLOPT_FTP_SKIP_PASV_IP,   1);
				curl_setopt($curl, CURLOPT_FTP_USE_EPSV,   0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
				curl_setopt($curl, CURLOPT_INFILE,         $fh);
				curl_setopt($curl, CURLOPT_UPLOAD,         1);

				$result = curl_exec($curl);
				if($result === false)
				{
					$curl_error = curl_error($curl);
					$this->log->Write("cURL Error Response: $curl_error");
					Update_Progress('qc',"QC: Transfer Error: $curl_error", 999);
				}
				curl_close($curl);
				fclose($fh);
				
				*/
				break;
			// Returns
			case self::STEP_RETURN:
				if( Quick_Checks::LOCAL_RETURN_FILES === true )
				{
					$return_file_path = QC_RETURN_FILE_DIR . $this->company_short . "/";
					$return_file_full = $return_file_path . $this->return_filename;

					if( file_exists($return_file_full) )
					{
						$this->return_file = file_get_contents($return_file_full);
						$result = true;
					}
					else
					{
						$result = false;
					}
				}
				else
				{
					$return_full_url = "ftps://{$this->return_user}:{$this->return_pass}@{$this->return_url}/{$this->return_user}/{$this->return_filename}";

					if( file_exists($return_full_url) )
					{
						// IIS doesn't close ssl connections properly, must use @
						//   See php bug #23220
						$this->return_file = @file_get_contents($return_full_url);
						$result = true;
					}
					else
					{
						$result = false;
					}
				}

				break;
			default:
				throw new Exception( "Invalid transmission type ({$this->process}) passed to " . __METHOD__ . "." );
		}

		return $result;
	}

	/**
	 * Handles everything that needs to be done upon a failure
	 * @param mixed  $reason     Some text describing the error
	 * @param int    $error_type Log row type
	 * @throws General_Exception
	 */
	private function Failure($reason, $line, $error_type = null)
	{
		if( ! is_int($line) )
			$line = '???';

		if( empty($error_type) )
			$error_type = LOG_ERR;

		if ($this->db->InTransaction)
			$this->db->rollBack();

		if( ! empty($this->process) )
		{
			//$this->Set_Process_State(self::STATE_FAIL, $this->process_date);

			// mark ecld_file or ecld_return as failed if necessary
			$this->Update_File_Status('failed');
		}

		if( is_object($reason) && ($reason instanceof Exception) && ! ($reason instanceof General_Exception) )
			throw new General_Exception( "Quick Check Failure on line {$line}: " . $reason->getMessage(), $error_type );
		elseif( is_string($reason) )
			throw new General_Exception( "Quick Check Failure on line {$line}: {$reason}", $error_type );
		elseif( is_object($reason) && ($reason instanceof General_Exception) )
			throw $reason;
		else
			throw new General_Exception( "Quick Check Failure on line {$line}: {$reason}", $error_type );
	}
	
  /**
   * Fetches the ecld_file_id for the specified date or today if no date is specified
   *
   * @param string $date
   * @return string ecld_file_id or false if none can be found
   */
	public function Get_Ecld_File_Id ($date = null)
	{
		if( empty($date) )
			$date = date('Ymd');

		$query = "
			SELECT ecld_file_id
			FROM ecld_file
			WHERE (file_status = 'created' || file_status = 'failed')
			AND date_created BETWEEN {$date}000000 and {$date}235959
            ORDER BY date_created DESC
            LIMIT 1
			";
		
		return $this->db->querySingleValue($query);
	}
	
	public function Get_Deposit_Data_From_Ecld_Table($ecld_file_id)
	{
		$query = "
			SELECT  e.application_id,
                    e.amount,
                    e.bank_aba,
                    e.bank_account,
                    a.name_last,
                    a.name_first,
                    a.street as address_1,
                    a.city,
                    a.county,
                    a.state,
                    a.zip_code as zip,
                    c.name,
                    a.phone_home,
                    a.bank_name,
                    a.bank_account_type,
                    e.trans_ref_no as trn,
                    e.event_schedule_id,
                    e.ecld_id,
                    t.transaction_register_id as register_id
            FROM    application a,
                    company c,
                    ecld e
            LEFT JOIN transaction_register AS t ON t.event_schedule_id = e.event_schedule_id
			WHERE   e.ecld_file_id = {$ecld_file_id}
            AND     a.application_id = e.application_id
            AND     c.company_id = a.company_id
		";
		
		$list = array();
		
		$result = $this->db->query($query);
		
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$list[$row['application_id']] = $row;
		}
		return $list;
	}
	
	private function Set_Process_State( $state, $date )
	{
		if( ! empty($this->process) )
		$step = $this->process;
		else
		$step = self::STEP_DEPOSIT;

		$state         = strtolower(trim($state));

		if( ! in_array($state, $this->process_states) )
		throw new General_Exception("Quick Check Internal Failure: " . __METHOD__ . " called with invalid processing state ('{$state}').", LOG_ERR);

		if( ! in_array($step, $this->process_steps) )
		throw new General_Exception("Quick Check Internal Failure: " . __METHOD__ . " called with invalid step name ('{$step}').", LOG_ERR);

		if( empty($this->process_id) )
		{
			$process_query = "
                               INSERT  INTO process_log
                                SET    business_day = '{$date}',
                                       company_id   = '{$this->company_id}',
                                       step         = '{$step}',
                                       state        = '{$state}',
                                       date_started = now()
                                    ";
		}
		else
		{
			$process_query = "
                               UPDATE  process_log
                                SET    step           = '{$step}',
                                       state          = '{$state}'
                                WHERE  process_log_id = '{$this->process_id}'
                                    ";
		}

		//echo "process_query:\n";
		//echo str_replace("\t","",$process_query) . "\n";
		//exit;

		$process_result = $this->db->query($process_query);

		if( empty($this->process_id) )
		$this->process_id = $this->db->lastInsertId();
	}
}


/**
 * A generic Exception to catch in the case of a quickcheck being
 * return more than once.  We'll catch this exception and avoid
 * doing any processing on the return since it should have already
 * occurred.
 */
class QC_Duplicate_Return_Exception extends Exception
{
	public function __construct($message)
	{
		parent::__construct($message, 0);
	}
}
