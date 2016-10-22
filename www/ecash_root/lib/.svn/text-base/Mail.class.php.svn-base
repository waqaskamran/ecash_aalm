<?php
/**
 * Provide a simplified interface to the TrendEx Mail Client interface, or any other mail system.
 *
 * @package Library
 *
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @copyright Copyright &copy; 2007 The Selling Source, Inc.
 * @created 2007-04-24
 *
 * @version $Revision$
 */

/**
 * Attachment array:
 *
 * array(
 * 	'method' => 'ATTACH',
 * 	'filename' => 'llama.txt',
 * 	'mime_type' => 'text/plain',
 * 	'file_data' => gzcompress('llama'),
 * 	'file_data_size' => strlen('llama'),
 * ),
 *
 * $file = file_get_contents('/root/GarrisonCap.jpg');
 * array(
 * 	'method' => 'EMBED',
 * 	'filename' => 'GarrisonCap.jpg',
 * 	'mime_type' => 'image/jpeg',
 * 	'file_data' => gzcompress($file),
 * 	'file_data_size' => strlen($file),
 * ),
 */

class eCash_Mail
{
	/**
	 * Instance of the TrendEx Mail Client object
	 * @var object
	 */
	public static $tx;

	/**
	 * Template key to template id associative array.
	 *
	 * Templates that have not been approved by the TrendEx company owner don't have a template
	 * key assigned to them, so we would normally have to refer to them by number. This, however,
	 * would be a pain to go back and change the implemented name/id in the code, so instead this
	 * will automatically convert the template_key to the template_id.
	 *
	 * @var array
	 */
	public static $unapproved_templates = array(
		'ECASH_ESIG_LOAN_DOCS' => 5332,
		'ECASH_COMMENT' => 5349,
		'ECASH_REACT_EMAIL_OFFER' => 5371,
		'OLP_PAPERLESS_I_AGREE' => 5427,
		'OLP_PAPERLESS_I_AGREE_500FC' => 5428,
		'OLP_PAPERLESS_I_AGREE_AL' => 5429,
		'OLP_PAPERLESS_I_AGREE_OCC' => 5430,
		'OLP_PAPERLESS_I_AGREE_UCL' => 5431,
		'OLP_PAPERLESS_I_AGREE_UFC' => 5432,
		'FBOD_CERTEGY_BATCH'		=> 7098,
		'ADVANTAGE_ACH_BATCH_CONFIRMATION_v4' => 9847);

	/**
	 * Templates to add fake marketing data to.
	 *
	 * This should only be used for templates that have not yet been approved as TRANSACT by
	 * TrendEx, and thus still require tracking information for an unsubscribe link.
	 *
	 * @var array
	 */
	public static $fake_marketing = array(
		'ADVANTAGE_ACH_BATCH_CONFIRMATION_v4',
		'ECASH_COMMENT',
		'ECASH_ESIG_LOAN_DOCS',
		'ECASH_REACT_EMAIL_OFFER',
		'OLP_PAPERLESS_I_AGREE',
		'OLP_PAPERLESS_I_AGREE_500FC',
		'OLP_PAPERLESS_I_AGREE_AL',
		'OLP_PAPERLESS_I_AGREE_OCC',
		'OLP_PAPERLESS_I_AGREE_UCL',
		'OLP_PAPERLESS_I_AGREE_UFC');

	/**
	 * ADVANTAGE_ACH_BATCH_CONFIRMATION
	 *	wrapper for sendMessage that sends the ADVANTAGE_ACH_BATCH_CONFIRMATION message from TrendEx.
	 * 	It automatically gets the values for the E-mail if the values are not passed to it.
	 * @param array $recipients an array of E-mail addresses that the E-mail is going to 
	 * @param array $data array of tokens you want populated
	 */
	public static function ADVANTAGE_ACH_BATCH_CONFIRMATION($recipients=array(),$data=array())
	{
		//tokens = date, time, additional_msg
		$template = 'ADVANTAGE_ACH_BATCH_CONFIRMATION_v4';
		$template = 9847;
		
		
		//populate tokens
		$mail_data = array();
		
		$mail_data['CompanyName']        = $data['company_name'];
		$mail_data['ACHCompanyAcronym']  = $data['company_acro'];
		// Because I'm an idiot, and don't feel like bugging devin anymore
		$mail_data['CompanyAcronym']     = $data['company_acro'];
		$mail_data['BatchSendDate']      = $data['date'];

		$mail_data['CreditItemCount']    = $data['total_credits'];
		$mail_data['DebitItemCount']     = $data['total_debits'];

		$mail_data['CreditDollarAmount'] = $data['total_credits_amt'];
		$mail_data['DebitDollarAmount']  = $data['total_debits_amt'];

		// Administrative Contact
		$mail_data['AdminContactName']    = $data['admin_contact_n'];
		$mail_data['AdminContactPhone']   = $data['admin_contact_p'];
		$mail_data['AdminContactEmail']   = $data['admin_contact_e'];

		// Technical Contact
		$mail_data['TechContactName']    = $data['tech_contact_n'];
		$mail_data['TechContactPhone']   = $data['tech_contact_p'];
		$mail_data['TechContactEmail']   = $data['tech_contact_e'];

		$mail_data['EffectiveEntryDate'] = $data['effective_date'];
		$mail_data['BatchFileName']      = $data['remote_file'];

		self::sendMessage($template,$recipients,$mail_data,array(),FALSE);
	}

	
	/**
	 * FBOD_CERTEGY_BATCH
	 *	wrapper for sendMessage that sends the FBOD_CERTEGY_BATCH message from TrendEx.
	 * 	It automatically gets the values for the E-mail if the values are not passed to it.
	 * @param array $recipients an array of E-mail addresses that the E-mail is going to 
	 * @param array $data array of tokens you want populated
	 */
	public static function FBOD_CERTEGY_BATCH($recipients=array(),$data=array())
	{
		//tokens = date, time, additional_msg
		$template = 'FBOD_CERTEGY_BATCH';
		$template = 7098;
		
		$recipients = count($recipients)?$recipients:ECash::getConfig()->CERTEGY_NOTIFICATION_LIST;
		//populate tokens
		$mail_data = array();
		$mail_data['date'] = $data['date'] ? $data['date'] : date('m/d/Y');
		$mail_data['time'] = $data['time'] ? $data['time'] : date('H:i:s');
		$mail_data['name_first'] = $data['name_first'] ? $data['name_first'] : '';
		$mail_data['name_last'] = $data['name_last'] ? $data['name_last'] : '';
		$mail_data['additional_msg'] = $data['additional_msg'] ? $data['additional_msg'] : '';
		
		self::sendMessage($template,$recipients,$mail_data,array(),FALSE);
	}
	/**
	 * Called statically to send a message. Only the template name and recipient email is required.
	 *
	 * @param string $template Name of the email template
	 * @param string $recipients Email address of the recipient. Can be comma delimited for
	 *                           multiple recipients.
	 * @param array $tokens optional array of tokens to replace with values in template
	 *                      'track_key' should be added to this array when desired
	 * @param array $attachments optional array of attachment information to include
	 * @param array $suppression_list optional
	 * @return boolean True on success, False on error.
	 */
	public static function sendMessage($template, $recipients, $tokens=array(),
		$attachments=array(), $suppression_list=FALSE)
	{
		$status = true;

		// Grab a copy of the tx object if we don't already have one.
		if (empty(self::$tx))
		{
			require_once('tx/Mail/Client.php');
			self::$tx = new tx_Mail_Client(false);
		}

		// If the template hasn't been approved then it will think it is a marketing attempt.
		// We need to add some fake marketing data to these templates, at least until they've
		// been approved.
		if (in_array($template, array_keys(self::$unapproved_templates)))
		{
			$tokens['source'] = 'eCash_Mail';
			$tokens['signup_date'] = date('Y-m-d H:i:s');
			$tokens['ip_address'] = '127.0.0.1';
		}

		// Unapproved templates don't have a template name, but we can use their template id.
		if (isset(self::$unapproved_templates[$template]))
		{
			$template = self::$unapproved_templates[$template];
		}

		// Get the tracking key from the token array
		$tracking_key = (isset($tokens['track_key'])) ? $tokens['track_key'] : '';

		// The underlying framework doesn't support multiple recipients, so break it up here.
		$recipients = is_array($recipients)?$recipients:explode(',', $recipients);
		foreach ($recipients as $recipient)
		{
			$recipient = trim($recipient);
/*
			$res = self::$tx->sendMessage(
				'live',
				$template,
				$recipient,
				$tracking_key,
				$tokens,
				$attachments,
				$suppression_list);
*/
			/**
			 * TrendX will return a transaction id that can be used to track the
			 * individual e-mail.  If there is an error during the submission it will
			 * return FALSE.
			 */
			if(empty($res))
			{
				$status = false;
			}
		}

		return $status;
	}

	public static function sendExceptionMessage($recipients, $body, $subject = null) {
		$argc = func_num_args();
		$argv = func_get_args();

		$tokens = ($argc >= 4) ? $argv[3] : array();

		if(isset($subject))
			$tokens['subject'] = $subject;
		else
			$tokens['subject'] = 'Ecash Alert '. strtoupper($_SESSION['company']);

		$tokens['error'] = $body;

		$parameters = array(
			'ECASH_EXCEPTION',
			$recipients,
			$tokens);

		for ($i = 4; $i < $argc; $i++)
		{
			$parameters[] = $argv[$i];
		}
//error_log(print_r(debug_backtrace(),true));

/*
		//return call_user_func_array(array('eCash_Mail', 'sendMessage'), $parameters);
        // hack rsk hard application id
		$db = ECash::getMasterDb();
        $app = new ECash_Application($db, '900946464');
        
        $docs = $app->getDocuments();
        $template = 'EXCEPTION';

        $doc = $docs->create($template);
            
        $condor_server = ECash::getConfig()->CONDOR_SERVER;
        $this->prpc = new Prpc_Client($condor_server);

        if($doc) {
            $transport = new ECash_Documents_Email();
            $transport->setEmail("randy.klepetko@sbcglobal.net,brian.gillingham@gmail.com,rebel75cell@gmail.com");
            
            if(!$doc->send($transport, ECash::getAgent()->getAgentId()))
            {
                ECash::getLog('documents')->write("Send Result: " . 'Document Failed to Send' );
            }
            else
            {
                ECash::getLog('documents')->write("Send Result: " . 'Document Sent' );
            }
            
        }
        else
        {
            ECash::getLog('documents')->write("Send Result: " . 'Document Failed Creation' );
        }
*/
}
}

?>
