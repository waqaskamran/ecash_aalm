<?php

class TwoCrypt implements Security_ICrypt_1
{
	protected $host;
	protected $user;
	protected $pass;

	protected $url;

	protected $crypt;
	protected $rpc;


	public function __construct($server_key_id, $ref, $key)
	{
		/* Check for prerequisite config file variables */
		if (!isset(ECash::getConfig()->ENCRYPTION_SERVER_USER))
			throw new Exception('ENCRYPTION_SERVER_USER not set in config file');

		if (!isset(ECash::getConfig()->ENCRYPTION_SERVER_PASS))
			throw new Exception('ENCRYPTION_SERVER_PASS not set in config file');

		if (!isset(ECash::getConfig()->ENCRYPTION_SERVER_HOST))
			throw new Exception('ENCRYPTION_SERVER_HOST not set in config file');

		if (!is_array($ref)) $ref = array('ref' => $ref);

		$this->user = ECash::getConfig()->ENCRYPTION_SERVER_USER;
		$this->pass = ECash::getConfig()->ENCRYPTION_SERVER_PASS;
		$this->host = ECash::getConfig()->ENCRYPTION_SERVER_HOST;

		$this->url = 'http://' . urlencode($this->user).':'.urlencode($this->pass).'@'.$this->host.'/'.urlencode($server_key_id).'?'.http_build_query($ref);

		$this->crypt = new Security_Crypt_1($key, Security_Crypt_1::CIPHER_256_BIT);
		$this->rpc   = new Rpc_Client_1($this->url);
	}

	public function encrypt($data)
	{
		$data = $this->crypt->encrypt($data);
		return base64_encode($this->rpc->encrypt($data));
	}

	public function decrypt($data)
	{
		$data = $this->rpc->decrypt(base64_decode($data));
		return $this->crypt->decrypt($data);
	}
}

?>
