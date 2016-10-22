<?php
/**
 * Behavior related to Bureau Inquiries
 *
 * Used for sending inquiries to the app service
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */

class ECash_VendorAPI_BureauInquiry implements VendorAPI_IBureauInquiry
{

	/**
	 * Builds and sents data packet to the app service for bureau inquiry
	* @param VendorAPI_StateObject $state
	* @param ECash_WebService_InquiryClient $inquiry_client
	* @param $data 
	* @return boolean
	 */
	public function sendInquiryToAppService(VendorAPI_StateObject $state, $data, ECash_WebService_InquiryClient $inquiry_client, VendorAPI_TemporaryPersistor $persistor)
	{

		if ($bureau_data = $persistor->loadBy(ECash::getFactory()->getModel('BureauInquiry'), array()))
		{

		}
		elseif($bureau_data = $persistor->loadBy(ECash::getFactory()->getModel('BureauInquiryFailed'), array()))
		{

		}
		if (!empty($bureau_data))
		{
			if(ord(substr($bureau_data->received_package, 5, 1)) == '156')
			{
				$received_package = gzuncompress(substr($bureau_data->received_package, 4));
			}
			else
			{
				$received_package = $bureau_data->received_package;
			}
			if(ord(substr($bureau_data->sent_package, 5, 1)) == '156')
			{
				$sent_package = gzuncompress(substr($bureau_data->sent_package, 4));
			}
			else
			{
				$sent_package = $bureau_data->sent_package;
			}

			$datar = array();
			$datar['company_id'] = $bureau_data->company_id;
			$datar['external_id'] = $data['external_id'];
			$datar['application_id'] = $data['application_id'];
			$datar['bureau'] = 'datax';
			$datar['inquiry_type'] = $bureau_data->inquiry_type;
			$datar['outcome'] = $bureau_data->outcome;
			$datar['decision'] = $bureau_data->decision;
			$datar['error_condition'] = '';
			$datar['sent_package'] = utf8_encode($sent_package);
			$datar['receive_package'] = utf8_encode($received_package);
			$datar['trace_info'] = $bureau_data->trace_info;
			$datar['reason'] = $bureau_data->reason;
			$datar['timer'] = stripslashes($bureau_data->timer);

			return $inquiry_client->recordInquiry($datar);
		
		}
		return false;

	}
}

?>
