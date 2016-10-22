<?php

require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');

class API_Bureau_Record implements Module_Interface
{
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
        $this->permissions = array(
            array('loan_servicing'),
            array('funding'),
            array('collections'),
            array('fraud'),
        );
	}
	
	public function get_permissions()
	{
		return $this->permissions; 
	}

	public function Main() 
	{
		$input = $this->request->params[0];
		$input = json_decode($input);
		ECash::getLog('debug')->Write(var_export($input,true));

		switch ($input->action)
		{
			case 'get_records':
				try {
					
					// Just an outline of the data we want to display
					$application_id = $input->application_id;
					$list = ECash::getFactory()->getModel('BureauInquiryList');
					$list->loadByApplicationID($application_id);
					ECash::getLog('debug')->Write("Found Application ID: $application_id");
					ECash::getLog('debug')->Write("Found " . count($list) . " records");
					
					$bureau_records = array();
					foreach($list as $record_model)
					{
						ECash::getLog('debug')->Write("Adding Record ID: " . $record_model->bureau_inquiry_id);

						$record = array(
								'inquiry_id'  => $record_model->bureau_inquiry_id,
								'inquiry_date'       => $record_model->date_created,
								'inquiry_type'       => $record_model->inquiry_type,
//								'global_decision'    => 'Y',
								'global_decision'    => $record_model->getGlobalDecision(),
								'agent'			 	=> 'B. User',
								'outcome'            => $record_model->outcome,
								'trace_data'         => $record_model->trace_info,
								'error_code'         => '',
								);
						$bureau_records[] = $record;
					}

					ECash::getLog('debug')->Write("Added " . count($bureau_records) . " records");
					return $bureau_records;
				}
				catch (Exception $e)
				{
					ECash::getLog('debug')->Write($e->getMessage());
					return array('error' => 'Could not load card information');
				}
				break;
			case 'idv_recheck':
				break;
			default:
				throw new Exception("Unknown action {$input->action}");
		}

		return $data;
	}

	// Should look for a failure, error code, etc.
	// Returns array('Status' => '', 'Message' => '')
	function getStatus($xml, $call_type = NULL)
	{
		
	}

	// Assuming it didn't fail, attempts to look at the global decision
	// in the XML and returns a Yes or No
	function getGlobalDecision($xml, $call_type = NULL)
	{
		
	}
}

?>
