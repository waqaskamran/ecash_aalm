<?php

require_once(SERVER_CODE_DIR . "master_module.class.php");
require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once("acl.3.php");

// loan servicing module
//class Module implements Module_Interface
class Module extends Master_Module
{
	protected $loan_servicing;
	protected $edit;
	protected $search;

	const DEFAULT_MODE = 'view_queues';

	public function __construct(Server $server, $request, $module_name)
	{
        parent::__construct($server, $request, $module_name); 
		
	}

	public function Main()
	{
		switch($this->request->action)
		{
			default:
				$this->getQueues();
				$this->Add_Current_Level();
				ECash::getTransport()->Add_Levels('main_screen','main_screen');
		}
				

		
		return;
	}

	protected function getQueues()
	{
		$data = ECash::getTransport()->Get_Data();
		$queues = array(
			'verification_pending' => array(
				'name' => 'Verification Pending',
				'module' => 'funding',
				'mode' => 'verification',
				'apps' => array(
					0 => array(
						'application_id' 	=> 54002805 ,
						'status'			=> 'pending',
						'queue_time'		=> '08/21/2007 10:00AM',
						'name'				=> 'name!',
						'module'			=> 'funding',
						
							),
					1 => array(
						'application_id' 	=> 54001659 ,
						'status'			=> 'Confirmed',
						'queue_time'		=> '08/20/2007 10:11PM',
						'name'				=> 'Sharonda Lingham',
						'module'			=> 'funding',
						
							),
					2 => array(
						'application_id' 	=> 54001710 ,
						'status'			=> 'Pre-Fund',
						'queue_time'		=> '08/14/2007 8:12AM',
						'name'				=> 'William Crawford',
						'module'			=> 'funding',
						)
				)
			),
			'additional_verification' => array(
				'name' => 'Additional Verification',
				'module' => 'funding',
				'mode' => 'verification',
				'apps' => array(
					0 => array(
						'application_id' 	=> 54002805 ,
						'status'			=> 'pending',
						'queue_time'		=> '08/21/2007 10:00AM',
						'name'				=> 'name!',
						'module'			=> 'funding',
						
							),
					1 => array(
						'application_id' 	=> 54001659 ,
						'status'			=> 'Confirmed',
						'queue_time'		=> '08/20/2007 10:11PM',
						'name'				=> 'Sharonda Lingham',
						'module'			=> 'funding',
						
							),
					2 => array(
						'application_id' 	=> 54001710 ,
						'status'			=> 'Pre-Fund',
						'queue_time'		=> '08/14/2007 8:12AM',
						'name'				=> 'William Crawford',
						'module'			=> 'funding',
						)
				)
			),'underwriting' => array(
				'name' => 'Underwriting Queue',
				'module' => 'funding',
				'mode' => 'verification',
				'apps' => array(
					0 => array(
						'application_id' 	=> 54002805 ,
						'status'			=> 'pending',
						'queue_time'		=> '08/21/2007 10:00AM',
						'name'				=> 'name!',
						'module'			=> 'funding',
						
							),
					1 => array(
						'application_id' 	=> 54001659 ,
						'status'			=> 'Confirmed',
						'queue_time'		=> '08/20/2007 10:11PM',
						'name'				=> 'Sharonda Lingham',
						'module'			=> 'funding',
						
							),
					2 => array(
						'application_id' 	=> 54001710 ,
						'status'			=> 'Pre-Fund',
						'queue_time'		=> '08/14/2007 8:12AM',
						'name'				=> 'William Crawford',
						'module'			=> 'funding',
						)
				)
			),
			'empty_queue' => array(
				'name'	=>	'Empty Queue',
				'module' =>	'loan_servicing',
				'mode'	=>	'customer_service',
				'apps'	=>	array()
				
			)
		);
		$data->queues=$queues;
		$data = ECash::getTransport()->Set_Data($data);
	}
	//populate the queues at some point?
	protected function populateQueues()
	{
		
	}
}
?>
