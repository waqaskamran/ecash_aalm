<?php
/**
 * @package PBX
 *
 * @author Zurab Davitiani <zurab.davitiani@telewebmarketing.com>
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Feb 6, 2007
 *
 * @version $Revision$
 */

require_once LIB_DIR . '/phpagi/phpagi-asmanager.php';

require_once LIB_DIR . "/Event/Event.class.php";

class PBX_Socket_Exception extends Exception { }

class eCash_PBX_AsteriskAGI extends AGI_AsteriskManager
{
	protected $event_class;
	
	public function log($message, $level=1)
	{
		eCash_PBX::Log()->write($message);
	}   

	public function isSocketLive()
	{	
		if ( is_resource($this->socket) ) 
		{
			$md = stream_get_meta_data($this->socket);
//var_dump($md)			;
			if (
//				$md['stream_type'] == 'socket' &&
				$md['timed_out'] !== true &&
				$md['eof'] !== true
				) {
					return true;
				}
		}
		
		return false;
		
	}
	
	public function getSocket()
	{
		if (!$this->isSocketLive()) 
		{
			@fclose($this->socket);
			if ($this->connect() !== true) 
			{
				throw new Exception(__METHOD__ . " Error: Unable to connect to Asterisk server");
			}
		}
		
		return $this->socket;
		
	}
	
	public function resetSocket()
	{
		@fclose($this->socket);
		return $this->getSocket();
	}
	
    function Events($eventmask, $handler)
    {
    	if ( is_object($handler) ) 
		{
    		$this->set_event_class($handler);
    	}

    	return parent::Events($eventmask);

    }

	public function send_request($action, $parameters = array())
    {
    	$req = "Action: $action\r\n";
    	
    	foreach($parameters as $var => $val) 
		{
    		$req .= "$var: $val\r\n";
    	}
    	$req .= "\r\n";
    	
    	fwrite($this->getSocket(), $req);
      
    	$this->log(__FILE__.":".__LINE__.":".__METHOD__."()\n---\n{$req}\n---\n");
      
    	return $this->wait_response(60);
    }

    function set_event_class(eCash_PBX_Asterisk $pba)
    {
    	$this->event_class = $pba;
    }

    function process_event($parameters)
    {
    	$this->log(__METHOD__ . "() Event: " . $parameters['Event']);		
    	
    	if(!$this->event_class || !($this->event_class instanceof eCash_PBX_Asterisk)) 
		{
    		$this->log(__METHOD__ . " Notice: Event Class not set, falling back to default behavior.");		
    		return parent::process_event($parameters);
    	}

    	$this->event_class->handleEvent($parameters);    	
      
    }
    
    function wait_response($socket_timeout = NULL)
    {
    	if ($socket_timeout) stream_set_timeout($this->socket, $socket_timeout);
    	
    	do
    	{
    		$type = NULL;
    		$parameters = array();
    		
    		$buffer = trim(fgets($this->socket, 4096));
    		$info = stream_get_meta_data($this->socket);
    		
    		while(!feof($this->socket) && !$info['timed_out'] && $buffer != '')
    		{
    			$a = strpos($buffer, ':');
    			if($a)
    			{
    				if(!count($parameters)) // first line in a response?
    				{
    					$type = strtolower(substr($buffer, 0, $a));
    					if(substr($buffer, $a + 2) == 'Follows')
    					{
    						// A follows response means there is a miltiline field that follows.
    						$parameters['data'] = '';
    						$buff = fgets($this->socket, 4096);
    						while(substr($buff, 0, 6) != '--END ')
    						{
    							$parameters['data'] .= $buff;
    							$buff = fgets($this->socket, 4096);
    						}
    					}
    				}

    				// store parameter in $parameters
    				$parameters[substr($buffer, 0, $a)] = substr($buffer, $a + 2);
    			}
    			$buffer = trim(fgets($this->socket, 4096));
    			$info = stream_get_meta_data($this->socket);
    			
    		}

    		if ($buffer) $this->log(__FILE__.":".__LINE__.":".__METHOD__."()\n---\n{$buffer}\n---\n");
        
    		// process response
    		switch($type)
    		{
    			case '': // timeout occured
    				throw new PBX_Socket_Exception();
    				break;
    				
   				case 'event':
   					$this->process_event($parameters);
   					break;
   					
   				case 'response':
   					break;
   					
   				default:
   					$this->log('Unhandled response packet from Manager: ' . print_r($parameters, true));
   					break;
    		}
    		
    	} 
		while($type != 'response');
    	
    	return $parameters;
    	
    }

}

class eCash_PBX_Asterisk 
{

	protected $username;
	protected $secret;
	protected $server;
	protected $port;
	const PBX_PORT = 5038;

	protected $pbx;
	protected $agi_id;
	
	static protected $agi = array();
	
	public function __construct(eCash_PBX $pbx)
	{

		$this->server 	= $pbx->getConfig("PBX Asterisk Host");
		$this->secret	= $pbx->getConfig("PBX Asterisk Password");
		$this->username	= $pbx->getConfig("PBX Asterisk Username");
		$this->port 	= $pbx->getConfig("PBX Asterisk Port");
						
		if (!$this->port) $this->port = self::PBX_PORT;
		
		$this->pbx = $pbx;
		
	}

	protected function getAgi()
	{
		if (!$this->agi_id || !(self::$agi[$this->agi_id] instanceof eCash_PBX_AsteriskAGI)) 
		{
			if(empty($this->server))
			{
				throw new Exception(__METHOD__ . " Error: Cannot contact PBX. The PBX Administration host is empty or undefined.");
			}
		
			$conf = get_object_vars($this);
			unset($conf['pbx']);
		
			$this->agi_id = md5(serialize($conf));
			
			self::$agi[$this->agi_id] = new eCash_PBX_AsteriskAGI(NULL, $conf);
			
		}
		
		return self::$agi[$this->agi_id];
		
	}
	
	protected function getSocket($events = 'off')
	{
		try 
		{
			$this->getAgi()->getSocket();

			$this->getAgi()->Events($events, $this);
		
			return $this->getAgi();
			
		} 
		catch (Exception $e) 
		{
			eCash_PBX::Log()->write(get_class($e) . " Thrown. " . $e->getFile() . ":" . $e->getLine() . ". Message: " . $e->getMessage());
			throw new Exception(__METHOD__ . " Error: Connection to PBX server {$this->server}:{$this->port} is lost or could not be established.");
		}
		
	}
	
	public function Dial($dial_number, $agent_extension, $dialer_caller_id, $agent_time_to_answer, $context, $priority, $contact_id = NULL)
	{

		if ($contact_id) eCash_PBX::registerContact($this->pbx->getServer(), $contact_id, $dial_number);
		
		$response = $this->getSocket()->Originate($this->formatExt($agent_extension), $dial_number, $context, $priority, null, null, $agent_time_to_answer, $dialer_caller_id);
		$this->getSocket()->disconnect();
		
		return $response;
		
	}

	public function formatExt($ext)
	{
		return "SIP/{$ext}";
	}
	
	public function readEvents()
	{
		try 
		{
			$this->getSocket('on');
			
		} 
		catch  (PBX_Socket_Exception $p) 
		{
			eCash_PBX::Log()->write(__METHOD__ . "(): Caught Socket Timeout Exception. Restarting event Reader");
			$this->getAgi()->resetSocket();
			
			$res = $this->getAgi()->Ping();
			eCash_PBX::Log()->write(__METHOD__ . "(): Successful Ping Response -> " . var_export($res,true));
			
		}
	}
	
	public function checkEventReader()
	{
		return $this->getAgi()->isSocketLive();
	}
	
	public function handleEvent($parameters = array())
	{
		$event_name = "ASTERISK_EVENT_" . strtoupper($parameters['Event']);

		$parameters['pbx'] = $this->pbx;
		$parameters['agi'] = $this->getAgi();
		
		eCash_Event::Trigger($this->pbx->getServer(), $event_name, NULL, $parameters);
		
	}
	
}

?>
