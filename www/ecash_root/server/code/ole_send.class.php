<?php

require_once(LIB_DIR . '/Mail.class.php');

/**
 * @deprecated
 */
class OLE_Send
{
	private $log;
	private $ole_mail;
	
	public function __construct($log)
	{
		$this->log = $log;
	}

	public function Send_Email($template, $ole_data)
	{
		$mailing_id = NULL;

		try
		{
			$template = 'ECASH_ESIG_LOAN_DOCS';
			$recipient = $ole_data['email_primary'];
			$response = eCash_Mail::sendMessage($template, $recipient, $ole_data);
		}
		catch( Exception $e )
		{
			$this->log->Write("Could not connect to ole -  email not sent {$e} application_id: {$ole_data['application_id']}" , LOG_ERR);
		}

		// log if we don't get a response from ole
		if (!$mailing_id)
		{
			$this->log->Write( 'Bad response from eCash_Mail - email not sent ' . $e
										. ' application_id: ' . $data['application_id'] , LOG_ERR);
		}
		
		return $mailing_id;
	}

}

?>
