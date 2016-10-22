<?php

/**
 * This script is used for testing the email queue. It will submit multiple
 * emails to Condor for import be the eCash 'populate_email_queue' cronjob.
 *
 */

set_include_path( get_include_path() . ':/virtualhosts/lib5:/virtualhosts/lib');
require('prpc/client.php');

// declare random data
$messages = array(
						array(
						      'sender'  => 'gordy@yahoo.com',
					       	  'subject' => 'Second Round Email Testing',
						      'data'    => 'This email is for testing new queue functionality.',
						      'attach'  => "more text here"
							 ),
						array(
						      'sender'  => 'chris@yahoo.com',
					       	  'subject' => 'Even More Email Testing',
						      'data'    => "That's right, yet another email test.",
						      'attach'  => "other text here from chris"
							 ),
						array(
						      'sender'  => 'teddy@aol.com',
					       	  'subject' => 'Email Queue Testing 2',
						      'data'    => "This one should have an attachment. If it doesn't, the problem is with the test insert script.",
						      'attach'  => "some text here from teddy"
							 ),
						array(
						      'sender'  => 'vern@comcast.net',
					       	  'subject' => 'Testing email queue again',
						      'data'    => 'This is yet another test.',
						      'attach'  => "more text here from vern"
							 ),
						array(
						      'sender'  => 'ace@hotmail.com',
					       	  'subject' => 'More Testing',
						      'data'    => 'Enjoy your testing.',
						      'attach'  => "testing text here"
							 )
					   );
					   
$test_addresses = array(
	'customerservice@multiloansource.net',
	'collections@multiloansource.net',
);

// instantiate the prpc_client
$condor = new PRPC_Client('prpc://multiloansource:mlsc0nd0r@rc.condor.4.edataserver.com/condor_api.php');

$successful = 0;

foreach ($test_addresses as $recipient)
{
	echo "Sending test e-mails for $recipient\n";
	// add [number_specified] incoming emails 
	for ($x=0; $x<count($messages); $x++)
	{
		$type = 'text/html';
		$sender = $messages[$x]['sender'];
		$subject = $messages[$x]['subject'];
		$data = $messages[$x]['data'];
	
		$new_archive_id = $condor->Incoming_Document('EMAIL',$sender,$recipient,$type,$data,0,$subject);
	
		if(!is_numeric($new_archive_id))
		{
			echo("There was an error inserting email number " . ($x+1) . "\n");
		}
		else
		{
			// for testing attachments
			if (array_key_exists('attach', $messages[$x]))
			{
				$txt = $messages[$x]['attach'];
				$condor->Create_As_Email_Attachment($new_archive_id, 'text/rtf', $txt, 'attachment.rtf');
			}
			
			echo "new_archive_id: $new_archive_id\n";
			++$successful;
		}
	}
	echo "Finished with e-mails for $recipient\n";
}

// print the result
echo $successful . " emails added successfully. Finished.\n";

?>
