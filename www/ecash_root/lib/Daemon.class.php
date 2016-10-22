<?php
/**
 * <CLASSNAME>
 * <DESCRIPTION>
 *
 * Created on Mar 23, 2007
 *
 * @package <PACKAGE>
 * @category <CATEGORY>
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

declare (ticks=1);

class eCash_Daemon
{
	const LOOPLIMIT = 100;
	
	private $children = array();
	private $dead_children  = array();
	private $pid;
	private $caught_signal;
	
	private $debug_level = 0;
	
	private $spawn_command;
	private $spawn_count;
	
	static public $default_process_descriptors = array ( 0 => array("pipe", "r"),  // stdin 
														 1 => array("pipe", "w"),  // stdout 
														 2 => array("pipe", "w")   // stderr 
														 ); 
														 
	static public function Execute($command, $count = 1)
	{
		$proc = new eCash_Daemon;
		$proc->declareHandlers();
		$proc->spawn($command, $count);
		$proc->process();
	}
	
	public function __construct($debug = 0)
	{
		$this->pid = posix_getpid();
		$this->debug_level = $debug;
	}
	
	public function __destruct()
	{
		$this->handleSignal(SIGTERM);
	}
	
	public function declareHandlers()
	{
		pcntl_signal (SIGINT, array($this, 'handleSignal'));
		pcntl_signal (SIGTERM, array($this, 'handleSignal'));
		pcntl_signal (SIGHUP, array($this, 'handleSignal'));
		pcntl_signal (SIGCHLD, array($this, 'handleSignal'));
		
		register_shutdown_function(array($this, '__destruct'));
		
	}
	
	public function report($line, $debug = 0)
	{
		if ($debug <= $this->debug_level) 
		{
			echo date("Y-m-d H:i:s") . "." . substr(fmod(microtime(true), mktime()), 2, 3) ." [{$this->pid}] $line\n";
		}
	}
	
	public function handleSignal($signal)
	{
		$this->report("Process caught signal {$signal}");
		
		$this->caught_signal = $signal;
		switch ($this->caught_signal) 
		{
			case SIGHUP:
				$this->caught_signal = SIGTERM;
				$this->signalChildren();
				break;
				
			case SIGCHLD:
				$this->spawn($this->spawn_command, $this->spawn_count);
				break;
				
			case SIGTERM:
			case SIGINT:
			case SIGKILL:
				$this->spawn_count = 0;
				$this->signalChildren();
				exit;
		}
				
	}
	
	protected function signalChild($pid, $signal)
	{
		if (!posix_kill($pid, $signal)) 
		{
			$this->report("Failed to send signal {$signal} to process {$pid}");
		}
		$this->report("Process {$pid} sent signal {$signal}");
	
	}
	
	public function signalChildren ()
	{
		$this->report("Sending all children signal {$this->caught_signal}");		
		
		foreach (array_keys($this->children) as $pid) 
		{
			$this->signalChild($pid, $this->caught_signal);
		}	

		$this->updateChildren();
		
	}

	public function updateChildren ()
	{
		
		foreach ($this->children as $pid => &$info) 
		{
			if ( is_resource($info['resource']) ) 
			{

				$this->children[$pid]['info'] = proc_get_status($info['resource']);
				
				if ($this->children[$pid]['info']['running'] == FALSE) 
				{
					$this->report("Process {$pid} no longer running. Closing resource handle.");
					proc_close($info['resource']);
					$this->dead_children[$pid] = $this->children[$pid]['info'];
					unset($this->children[$pid]);
				}
				
			} 
			else 
			{
				
				$this->report("Process Resource for PID {$pid} is lost.");
				
				// if the resource doesn't exists, it's probably dead and i want to be sure
				$qpid = pcntl_waitpid($pid, $status, WNOHANG);
				switch ($qpid) 
				{
					case -1:
						$this->report("Process error thrown for PID {$pid}.");

					case 0:	
						$this->signalChild($pid, SIGKILL);
						
					default:
						$this->dead_children[$pid] = $pid;
						unset($this->children[$pid]);
				}
			}
			usleep(1000);
		}
		
		if (count($this->children) <  $this->spawn_count) 
		{
			$this->spawn($this->spawn_command, $this->spawn_count);
		}
	
	}

	public function spawn($command, $count = 1, $proc_descriptors = NULL)
	{

		if(!$this->spawn_command) 
		{
			$this->report( "Command set to: '{$command}'" );
			$this->spawn_command = $command;
		}
		
		if(!$this->spawn_count) 
		{
			$this->report( "Number of processes to spawn: {$count}" );
			$this->spawn_count = $count;
		}
		
		if (!$proc_descriptors) 
		{
			$proc_descriptors = self::$default_process_descriptors;
		}
		
		for ( $i = 0 ; count($this->children) < $this->spawn_count ;  $i++) 
		{
			$pipe = array();
			
			$pres = proc_open ($this->spawn_command, $proc_descriptors, $pipe);
			foreach ($pipe as &$pip) 
			{
				$this->allpipes[] =& $pip;
			}

			$pinfo = proc_get_status($pres);
			
			$this->children[$pinfo['pid']] = array ("resource" 	=> $pres,
													"info"		=> $pinfo,
													"pipes"		=> &$pipe
													);
													
			$this->report("PID Created: {$pinfo['pid']}. Command: '{$command}'");													
													
		}
		
		if ($i) $this->report( $i . " Processes Created");															
		
		return count($this->children);
		
	}
	
	public function process()
	{
		do {
			$this->updateChildren();
			usleep(250000);
			
		} while (count($this->children));
	}
	
}
