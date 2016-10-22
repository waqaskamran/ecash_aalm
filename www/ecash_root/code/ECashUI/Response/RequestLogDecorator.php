<?php

class ECashUI_Response_RequestLogDecorator implements Site_IResponse 
{
	/**
	 * @var ECash_RequestTimer
	 */
	protected $timer;
	
	/**
	 * @var Site_IResponse
	 */
	protected $response;
	
	public function __construct(Site_IResponse $response, ECash_RequestTimer $timer)
	{
		$this->response = $response;
		$this->timer = $timer;
	}
	
	/**
	 * Writes the response to stdout or the browser.
	 * 
	 * This method can also be used to perform any 'end of process' processing.
	 * (eg: logging, timers, etc.)
	 */
	public function render()
	{
		$this->response->render();
		$this->timer->stop();
	}
}

?>