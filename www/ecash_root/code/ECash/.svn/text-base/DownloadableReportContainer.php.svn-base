<?php

/**
 * This was my quick solution for writing report data out to a temp file before 
 * sending it out to the browser for download.  The old method was to continially
 * contatenate a string variable which ate way too much memory. 
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */
class ECash_DownloadableReportContainer
{
	/**
	 * Some companies want the output in all upper case
	 * This variable is the flag for that
	 * @var bool
	 */
	private $use_upper_case = FALSE;
	
	/**
	 * File length
	 * @var int
	 */
	private $length;

	/**
	 * File handle
	 * @var file pointer resource
	 */
	private $fh;
	
	/**
	 * Flag to determine if it's safe to add data
	 * @var bool
	 */
	private $write_safe = TRUE;
	
	public function __construct()
	{
		if(! $this->fh = tmpfile())
		{
			throw new Exception("Could not create temporary file '{$this->filename}'!");
		}
	}

	/**
	 * Sets the configuration option to reformat data
	 * to use upper-case
	 */
	public function useUpper()
	{
		$this->use_lower_case = TRUE;
	}

	/**
	 * Appends the input to the temporary file.  Will rewrite to upper-case
	 * if the flag has been set to true.
	 * 
	 * @param $line - string
	 */
	public function add($line)
	{
		if(! $this->write_safe)
			throw new Exception("Can't add to " . __CLASS__ . " after we've already sent the data!\n");

		if($this->use_upper_case)
			$line = strtoupper($line);

		$this->length += strlen($line);

		fputs($this->fh, $line);
	}
	
	/**
	 * Returns the length of the data we've written thus far
	 * @return int
	 */
	public function getLength()
	{
		return $this->length;
	}
	
	/**
	 * Ouutputs our entire tempfile to the output buffer
	 */
	public function output()
	{
		// Flag to tell the add() function that we're
		// no longer accepting data.
		$this->write_safe = FALSE;

		// Rewind back to the start of the file pointer
		rewind($this->fh);
		
		// Spit out the contents
		fpassthru($this->fh);
		
		// Close the handle -- it will be automatically removed because
		// it was created with tmpfile()
		fclose($this->fh);
	}
}
