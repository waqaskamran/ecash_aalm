<?php
require_once(LIB_DIR . 'common_functions.php');
require_once(COMMON_LIB_DIR . 'applog.1.php');
require_once(COMMON_LIB_DIR .'security.6.php');
require_once( LIBOLUTION_DIR . "Mail/Trendex.1.php" );
require_once('session.9.php');

/**
 * eCash Server Class - This class is what handles all
 * requests, session creation and destruction, and security
 * validation.
 */
class Server
{
	static public $acl;

	/**
	 * I'm not sure if this is the place to do the MySQL var setting / or if these commands
	 * should even be run on the slave :-/ Move it if you'd like [JustinF]
	 * 
	 * Ok. Moved to the ECash_Config class where we grab the connection. [BrianR]  (BTW, I hate you, Justin.}
	 */
	public function __construct()
	{
	}

	/**
   * ACL accessor
   * @return ECash_ACL
   */
  static public function Get_ACL()
  {
	return self::$acl;
  }

  static public function Set_ACL($acl)
  {
	self::$acl = $acl;
  }

	// Security checks must be implemented in the subclass.
	protected function Security_Check () {
		return false;
	}

	// The basic processing that the class will do.
	protected function Execute_Processing() {
	}

	// This is the initiation point from the communications interface
	public function Process_Data()
	{
	}

	public function __toString()
	{
		return "The server object";
	}
}

?>
