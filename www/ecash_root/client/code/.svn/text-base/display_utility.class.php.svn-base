<?php


class Display_Utility
{
	/**
	 * Efficient token replacement method
	 *
	 * @param string $subject
	 * @param array $token_data
	 * @return string
	 */
	public static function Token_Replace($subject, array $token_data)
	{
		// Find all the tokens in the subject
		$match_count = preg_match_all('/%%%(.*?)%%%/', $subject, $matches);
		
		if ($match_count > 0)
		{
			foreach ($matches[1] as $token_name)
			{
				// $source_data = strstr($token_name, '_edit', true); // Use this in php 6 instead of the substr
				if (strlen($token_name) > 5 && substr($token_name, -5) == '_edit')
				{
					$source_data = substr($token_name, 0, -5);
					
					if (isset($token_data['saved_error_data']) && array_key_exists($source_data, $token_data['saved_error_data']))
					{
						$subject = str_replace("%%%$token_name%%%", $token_data['saved_error_data']->{$source_data}, $subject);
					}
					//edit token
					else if (array_key_exists($source_data, $token_data))
					{
						$subject = str_replace("%%%$token_name%%%", stripslashes(htmlspecialchars((string)$token_data[$source_data])), $subject);
					}
				}
				else if (array_key_exists($token_name, $token_data))
				{
					$subject = str_replace("%%%$token_name%%%", stripslashes((string)$token_data[$token_name]), $subject);
				}
		    }
		}
		return $subject;
	}
}
