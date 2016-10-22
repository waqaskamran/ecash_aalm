<?php

/**
 * Transport information between classes. Rather than set one global variable, this gets passed
 * between calling and called classes.
 *
 * @package Library
 * @subpackage Transport
 */
class Transport
{
	public $company;
	public $company_id;
	public $company_list;
	public $agent_id;
	public $login;
	public $user_acl;
	public $page_array;
	public $new_app_url;
	public $pbx_enabled;

	/**
	 * This could be either an object (we can only hope an stdClass) or an array. It's a mystery.
	 * @var mixed
	 */
	private $data;

	/**
	 * Keep track of the current level in the page_array.
	 * @var integer
	 */
	private $current_level;

	/**
	 * An array of errors that we are keeping tabs on.
	 * @var array
	 */
	private $errors;

	/**
	 * Set our default object property values.
	 *
	 * @todo Why is this done here and not next to the property declaration?
	 */
	public function __construct()
	{
		$this->page_array = array();
		$this->user_acl = array();
		$this->company_list = array();
		// (Un)comment this if switching between fixed/unfixed versions.
		$this->current_level = 0;
// 		$this->current_level = NULL;
		$this->errors = array();
	}

	/**
	 * Overrides any previously set page levels with an indeterminate number of parameters.
	 *
	 * @param string $level,... Name of the page level to add
	 */
	public function Set_Levels()
	{
		$this->page_array = func_get_args();
	}

	/**
	 * Each call will return the next page level.
	 *
	 * This does not do what might be expected. In reality it returns the CURRENT level, then sets
	 * the internal pointer to the next one. If you were to run Get_Current_Level immediately after
	 * this function, it wouldn't return the same thing.
	 *
	 * @return mixed Null if there is no current level, or String of level.
	 * @todo FIX ME! (See description)
	 */
	public function Get_Next_Level()
	{
		// (Un)comment this if switching between fixed/unfixed versions.
// 		if (is_null($this->current_level))
// 		{
// 			$this->current_level = 0;
// 		}
// 		else
// 		{
// 			$this->current_level++;
// 		}

		$next_level = $this->Get_Current_Level();

// 		// (Un)comment this if switching between fixed/unfixed versions.
		if ($next_level)
		{
			$this->current_level++;
		}

		return $next_level;
	}

	/**
	 * Returns the current page level.
	 *
	 * @return mixed Null if there is no current level, or String of level.
	 */
	public function Get_Current_Level()
	{
		$current_level = NULL;

// 		if (is_null($this->current_level))
// 		{
// 			$this->current_level = 0;
// 		}

		if (isset($this->page_array[$this->current_level]))
		{
			$current_level = $this->page_array[$this->current_level];
		}

		return $current_level;
	}

	/**
	 * Add any number of levels to the page
	 *
	 * @param string $level,... Name of the page level to add
	 */
	public function Add_Levels()
	{
		$parameters = func_get_args();
		$this->page_array = array_merge($this->page_array, $parameters);
	}

	/**
	 * Add an error message to the stack.
	 *
	 * @param string $error_message Message of the error.
	 * @param string $field name of the field that the error message should be inserted into.
	 */
	public function Add_Error($error_message, $field = NULL)
	{
		if (is_null($field))
		{
			$this->errors[] = $error_message;
		}
		else
		{
			$this->errors[$field] = $error_message;
		}
	}

	/**
	 * Return the errors array.
	 *
	 * @return array An array of errors.
	 */
	public function Get_Errors()
	{
		return $this->errors;
	}

	/**
	 * Set or Merge the data object.
	 *
	 * This function confuses the internal handling of the data. Most things expect this to be an
	 * object, but it's possible for this to be an array. This should be standarized to one or the
	 * other, but not both.
	 *
	 * @param mixed $data An array or object of data.
	 */
	public function Set_Data($data)
	{
		// Check data type to ensure we don't overwrite
		// the transport object by acceident
		if(! is_object($data) && ! is_array($data)) throw new Exception("Passing invalid data: " . var_export($data, true));
			
//		if (EXECUTION_MODE == 'LOCAL' && !is_object($data)) throw new Exception("Passing non-object data: " . var_export($data, true));

		if (is_object($data) && is_object($this->data))
		{
			$this->data = (object) array_merge((array) $this->data, (array) $data);
		}
		elseif (is_array($data) && is_array($this->data))
		{
			$this->data = array_merge($this->data, $data);
		}
		else
		{
			$this->data = $data;
		}
	}

	/**
	 * Return the internal data object/array.
	 *
	 * @return mixed The data object/array.
	 * @todo This really needs formalized as either an object or an array; not either.
	 */
	public function Get_Data()
	{
		return $this->data;
	}

	/**
	 * Convert this object to a human-readable string. This makes for easy debugging.
	 *
	 * @return string The string of this object, enclosed in HTML pre tags.
	 */
	public function __toString()
	{
		$string = "<pre>";
		$string .= "page_array: " . To_String($this->page_array);
		$string .= "data: " . To_String($this->data);
		$string .= "user_acl: " . To_String($this->user_acl);
		$string .= "company_list: " . To_String($this->company_list);
		$string .= "company_id: {$this->company_id}";
		$string .= "</pre>";
		return $string;
	}

	/**
	 * Clone the data object if we get cloned.
	 */
	public function __clone()
	{
		if (isset($this->data) && is_object($this->data))
		{
			$this->data = clone $this->data;
		}
	}
}

?>
