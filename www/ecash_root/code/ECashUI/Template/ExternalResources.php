<?php

/**
 * A helper to provide caching based on name support for loading external 
 * resources.
 *
 * @package ECashUI.Template
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_Template_ExternalResources
{
	/**
	 * @var string
	 */
	protected $release_version;
	
	/**
	 * Creates a new external resource helper.
	 *
	 * @param string $release_version
	 */
	public function __construct($release_version)
	{
		$this->release_version = $release_version;
	}
	
	/**
	 * Adds a style sheet to the template at the current location.
	 *
	 * @param string $path url to the stylesheet.
	 */
	public function addStyleSheet($path)
	{
		echo '<link ' 
			. 'rel="stylesheet" '
			. 'type="text/css" '
			. 'href="' . $path . '?version=' . $this->release_version . '" '
			. "/>\n";
	}
		
	/**
	 * Adds an external javascript file to the template at the current location.
	 *
	 * @param string $path url to the stylesheet.
	 */
	public function addJavaScript($path)
	{
		echo '<script ' 
			. 'type="text/javascript" '
			. 'src="' . $path . '?version=' . $this->release_version . '" '
			. "></script>\n";
	}
}
?>