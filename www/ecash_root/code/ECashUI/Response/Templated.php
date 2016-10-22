<?php

/**
 * An extension of the templated for response for ecash implementing token providers.
 *
 * @package ECashUI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashUI_Response_Templated extends Site_Response_Templated
{
	/**
	 * @var ECashUI_ITokenProvider
	 */
	protected $token_provider;
	
	public function setTokenProvider(ECashUI_ITokenProvider $provider)
	{
		$this->token_provider = $provider;
	}
	
	/**
	 * Retrieves a token value.
	 *
	 * @param string $name
	 * @return mixed
	 * @throws InvalidArgumentException when a token does not exist
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->tokens))
		{
			return $this->tokens[$name];
		}
		elseif (isset($this->token_provider) && $this->token_provider->tokenExists($name))
		{
			return $this->token_provider->getToken($name);
		}
		else
		{
			throw new InvalidArgumentException('Invalid token, ' . $name);
		}
	}

	/**
	 * Determines if a token is set.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		if (array_key_exists($name, $this->tokens))
		{
			return TRUE;
		}
		elseif (isset($this->token_provider) && $this->token_provider->tokenExists($name))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}

?>