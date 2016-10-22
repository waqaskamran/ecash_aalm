<?php

/**
 * A legacy response to allow setting the content on the constructor.
 * 
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @deprecated 
 */
class ECashUI_Response_Legacy extends ECashUI_Response_Templated implements Site_IResponse 
{
	/**
	 * @var string
	 */
	protected $content;
	
	/**
	 * Creates a new legacy response with the given content
	 *
	 * @param string $content
	 */
	public function __construct($content)
	{
		$this->content = $content;
	}
	
	/**
	 * Writes the response to stdout or the browser.
	 * 
	 * This method can also be used to perform any 'end of process' processing.
	 * (eg: logging, timers, etc.)
	 */
	public function render()
	{
		echo $this->content;
	}
}

?>