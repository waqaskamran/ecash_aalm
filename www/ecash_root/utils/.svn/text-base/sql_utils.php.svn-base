<?

class SQL_Utils
{
	/**
	 * Function to turn prepared mysql statements into regular ones
	 *
	 * @param mixed $tokens
	 * @param string $query
	 * @return string
	 */
	public static function prepared2Direct($tokens=array(),$query='')
	{
		// Break up the prepared statement query into pieces
		// but we still need to maintain the original '?' to perform a replace
		$placeholder = "!@#$"; 
		$query = str_replace('?',"?$placeholder",$query);	
		$query_elements = explode($placeholder,$query);	
			
		
		// Replace all the ?'s with the token elements
		foreach($query_elements as $index => $element)
		{
			//This substring search is necessary in the case where the prepared query ends with ?
			if(strchr($element,'?'))
			{
				if(!isset($tokens[$index]))
					throw new Exception("Not enough token elements");
				
				$new_query .= str_replace('?',
					"'" . mysql_escape_string($tokens[$index]) . "'",
					$element);
			}
		}
		
		return $new_query;
	}
}

?>