<?php
/**
 * @package rpc
 */

/**
 * ECash_RPC_HTTP
 * HTTP Authentication frontend for ECash_RPC
 *
 * Created on Feb 2, 2007
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */
class ECash_RPC_HTTP 
{
	public static $realm = ECash_RPC::SystemName;
	private static $ver = "1.0";

	static public function Error($msg = '500 Internal Error')
	{
		ECash_RPC::Log()->write(__METHOD__ . " Error: {$msg}");
		ECash_RPC::Log()->write("Backtrace\n---\n" . var_export(debug_backtrace(),true) . "\n---\n");
		
		header("WWW-Authenticate: Basic realm=\"" . self::$realm . "\"");
		header("HTTP/{$ver} {$msg}");
		exit;					
	}
	
	static public function Code($code = 500)
	{
		switch ($code) {
			case 400:
				self::BadRequest();
			case 401:
				self::Unauthorized();
			case 403:
				self::Forbidden();
			case 404:
				self::NotFound();
			case 500:
			default:
				self::InternalError();
		}
	}
	
	static public function Unauthorized()
	{
		@session_destroy();
		self::Error("401 Unauthorized");
	}
	
	static public function Forbidden()
	{
		@session_destroy();
		self::Error("403 Forbidden");		
	}
	
	static public function BadRequest()
	{
		self::Error("400 Bad request");		
	}
	
	static public function NotFound()
	{
		self::Error("404 Not Found");		
	}
	
	static public function InternalError()
	{
		self::Error("500 Internal Error");		
	}
	
}