<?php

require_once('DisplayMessages.class.php');

/**
 * Match error keys with their counter-part message. Also parse the message to insert values.
 *
 * @package Library
 * @subpackage DisplayMessage
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 * @version $Revision$
 */
class DisplayMessage
{
	/**
	 * Return a message based on the code and any parameters passed in.
	 *
	 * @param array $key The internal key of the message to use.
	 * @param mixed $display_information[],... Any number of variables to insert into the message.
	 * @return string
	 */
	public static function get($key)
	{
		$message = DisplayMessages::$messages;

		// Get any arguments to be passed to the error
		$information = func_get_args();
		array_shift($information);

		// Make sure we use a valid error message, or default to an unknown error.
		foreach ($key as $k)
		{
			if (!empty($message[$k]))
			{
				$message = $message[$k];
			}
		}

		if (!empty($message) && is_string($message))
		{
			// Parse the message if needed.
			if (!empty($information))
			{
				$message = vsprintf($message, $information);
			}
		}
		else
		{
			trigger_error('An unknown error has occured while retriving a message', E_USER_NOTICE);
			$message = 'The programmer misspelled or forgot to add a message. There '
				. 'was a message instead of this one, but we don\'t know what it was about.';
		}

		return $message;
	}

	/**
	 * Sends only javascript group of messages
	 *
	 * @return string JSON string of javascript messages
	 */
	public static function getJavascriptCodes()
	{
		$javascript_messages = DisplayMessages::$messages['javascript'];
		foreach ($javascript_messages as $parent_key => $messages)
		{
			foreach ($messages as $key => $message)
			{
				$javascript_messages[$parent_key][$key] = str_replace('\n', "\n", $message);
			}
		}

		return json_encode($javascript_messages);
	}
}

?>
