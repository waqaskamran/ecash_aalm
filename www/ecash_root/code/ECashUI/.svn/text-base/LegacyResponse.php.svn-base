<?php

class ECashUI_LegacyResponse implements Site_IResponse
{
	/**
	 * @var Display
	 */
	protected $display;

	/**
	 * @var ECash_Transport
	 */
	protected $transport;

	public function __construct(Display $display, ECash_Transport $transport)
	{
		$this->display = $display;
		$this->transport = $transport;
	}

	public function render()
	{
		$this->display->Do_Display($this->transport);
	}
}

?>