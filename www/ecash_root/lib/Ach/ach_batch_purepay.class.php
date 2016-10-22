<?php

/**
 * Processor specific ACH_Batch class extension
 * 
 * 	Allows for processor specific adjustments to batch format.
 *
 */
class ACH_Batch_Purepay extends ACH_Batch_Teledraft 
{
	
	public function Get_Remote_Filename()
	{
		$transport_type   = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$transport_url    = ECash::getConfig()->ACH_BATCH_URL;
		$client_id = ECash::getConfig()->CLIENT_ID;
		$cybr_location_id = ECash::getConfig()->CYBR_LOCATION_ID;
		$batch_id = $this->ach_batch_id;
		// If we're using SFTP, we need to specify the whole path including a filename
			if(in_array($transport_type, array('SFTP', 'SFTP_AGEAN', 'FTP', 'FTPS'))) {
				// This needs to be modified based on the company
				$filename = "$transport_url/".date('Ymd').'_'.$cybr_location_id."_".$batch_id . ".csv"; 
			} else {
				$filename = $transport_url;
			}
			
			return $filename;
	}

}
?>