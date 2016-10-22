<?php
interface ECash_ACHReport_EPP_IReturnConfig extends ECash_ACHReport_IConfig
{	
	public function getEPPReturnsParser();
	
	public function getEPPReturnsFileName();
	
	public function getEPPReturnsTransport();
	
	public function getEPPReturnsServer();
		
	public function getEPPReturnsLogin();
	
	public function getEPPReturnsPass();
	
	public function getEPPReturnsPort();


}


?>
