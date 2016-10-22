<?php
/*
 *  The New Coke of ECash Request
 */
class ECash_Request extends Object_1 implements IteratorAggregate
{
	const REQ_USER = 'User';

	public function __get($name)
	{
		if(isset($_REQUEST[$name]))
		{
			return $_REQUEST[$name];
		}
		return NULL;
	}


	public function __set($name, $value)
	{
		$_REQUEST[$name] = $value;
	}

    public function __isset($name)
    {
	return isset($_REQUEST[$name]);
    }

    public function __unset($name)
    {
	unset($_REQUEST[$name]);		
    }

	public function __toString()
	{
		return print_r($_REQUEST, TRUE);
	}

	public function setUser($user)
	{
		$_REQUEST[self::REQ_USER] = $user;		
	}

	public function getUser()
	{
		return $_REQUEST[self::REQ_USER];
	}
	
	public function getIterator()
	{
		return new ArrayIterator($_REQUEST);
	}
	

}

?>