<?php

/**
 * Provides a json response based on a given variable
 * 
 * @author Asa Ayers <Asa.Ayers@sellingsource.com>
 * @package Site
 */
class ECashUI_Response_Redirect implements Site_IResponse
{
protected $url;
	
	public function __construct($url)
	{
		$this->url = $url;
	}
	/**
	 * Writes the response to stdout or the browser.
	 * 
	 * Does Nothing
	 */
	public function render()
	{
		header("Location: {$this->url}");
	}
}

?>