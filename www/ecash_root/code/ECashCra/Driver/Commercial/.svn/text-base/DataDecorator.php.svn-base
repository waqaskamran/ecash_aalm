<?php

class ECashCra_Driver_Commercial_DataDecorator
{
	protected static $registry;
	
	protected $object_hash;
	
	public function __construct($object)
	{
		$this->object_hash = spl_object_hash($object);
		
		if (!isset(self::$registry))
		{
			self::$registry = array();
		}
		
		if (!isset(self::$registry[$this->object_hash]))
		{
			self::$registry[$this->object_hash] = array();
		}
	}
	
	public function __get($property)
	{
		if (!$this->propertyExists($property))
		{
			throw new InvalidPropertyException_1($property);
		}
		
		return self::$registry[$this->object_hash][$property];
	}
	
	public function __set($property, $value)
	{
		self::$registry[$this->object_hash][$property] = $value;
	}
	
	public function __isset($property)
	{
		return $this->propertyExists($property);
	}
	
	protected function propertyExists($property)
	{
		return array_key_exists($property, self::$registry[$this->object_hash]);
	}
}

?>