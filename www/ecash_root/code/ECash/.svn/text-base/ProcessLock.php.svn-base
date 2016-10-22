<?php

/**
 * A very generic process locking class based on Stephan's work in Condor
 *
 * It makes more sense to extend this class if you need to perform actions based on the 
 */
class ECash_ProcessLock
{
	private $lock_filename;

	private $unlockable = false;
	
	const LOCK_EMPTY = 0;
	const LOCK_STALE = 1;
	const LOCK_VALID = 2;
	
	/**
	 * Returns the name of the lock file
	 *
	 * @return string
	 */
	private function getlockFilename()
	{
		if(! empty($this->lock_filename))
		{
			return $this->lock_filename;
		}
		else
		{
			throw new Exception("No lockfile filename has been set!");
		}
	}

	/**
	 * Set the name of the lock file
	 *
	 * @param unknown_type $lock_filename
	 */
	public function setlockFilename($lock_filename)
	{
		$this->lock_filename = $lock_filename;
	}	

	/**
	 * Checks the current lock states and process statuses
	 * 
	 * - If the lock file doesn't exist, we create one
	 * - If the lock file exists but is stale, remove the 
	 *   stale lock file and create a new one.
	 * 
	 * You can use the $lock_status to determine what needs to be done if you're
	 * using things like process_logs, such as in the case of a stale lock where
	 * you need to do some sort of cleanup.
	 *
	 * @param $lock_status - Will be one of LOCK_EMPTY, LOCK_STALE, or LOCK_VALID
	 * @return boolean
	 */
	public function isLocked(&$lock_status)
	{
		$lock_status = $this->getLockState();

		switch($lock_status)
		{
			case self::LOCK_EMPTY;
				$this->lock();
				return FALSE;
				break;

			case self::LOCK_STALE;
				$this->unlock();
				$this->lock();
				return FALSE;
				break;

			case self::LOCK_VALID;
				return TRUE;
				break;
		}
	}

	/**
	 * Are things locked?
	 *
	 * @return  self::LOCK_EMPTY = 0;
	 *			self::LOCK_STALE = 1;
	 *			self::LOCK_VALID = 2;
	 */
	private function getLockState()
	{
		$file = $this->getlockFilename();
		// If the file does not exist, return FALSE
		if (file_exists($file) == FALSE)
		return self::LOCK_EMPTY;

		/**
		 * This method isn't the safest as it will ONLY work
		 * on a Linux system, but it's reliable.
		 */
		$lockpid = file_get_contents($file);
		if(file_exists("/proc/$lockpid/"))
		{
			return self::LOCK_VALID;
		}
		else
		{
			return self::LOCK_STALE;
		}
	}

	/**
	 * Sets the lock if possible
	 */
	private function lock()
	{
		$this->unlockable = true;
		$file = $this->getlockFilename();
		if(!file_exists($file))
		{
			//If we lock things, make sure we unlock if
			//the script exits.
			//register_shutdown_function(array($this,'unlock'));
			file_put_contents($file, getmypid());
		}
		else
		{
			// Check if the process is still alive
			$lockpid = file_get_contents($file);
			
			$running = posix_kill($lockpid, 0);
			
			if (posix_get_last_error() == 1)
				$running = TRUE;
		
			if ($running == FALSE)
			{
				// Stale lockfile
				//register_shutdown_function(array($this,'unlock'));
				file_put_contents($file, getmypid());
			}
		}
	}	

	/**
	 * Removes the lock file
	 */
	private function unlock()
	{
		$this->unlockable = false;
		$file = $this->getlockFilename();
		if(file_exists($file))
		{
			unlink($file);
		}
	}
	
	public function __destruct()
	{
		if($this->unlockable)
		{
			$this->unlock();
		}
	}

}
