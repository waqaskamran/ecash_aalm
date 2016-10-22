<?php


require_once(SERVER_CODE_DIR . 'module_interface.iface.php');

class API_Payment_Arrangement implements Module_Interface
{
	private $permissions;

	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
		$this->permissions = array(
			array('loan_servicing', 'customer_service',  'transactions', 'payment_arrangement'),
			array('loan_servicing', 'account_mgmt', 'transactions', 'payment_arrangement'),
			array('collections', 'internal', 'transactions', 'payment_arrangement'),
			array('fraud', 'watch', 'transactions', 'payment_arrangement'),
		);
	}

	public function get_permissions()
	{
		return $this->permissions; 
	}
	
	public function Main() 
	{
		$input = $this->request->params[0];

		switch ($input->action)
		{
			case 'fetch':
			$app_id = $input->application_id;	
			return $this->get_arrangements($app_id);	
			break;
			default:
				throw new Exception("Unknown action {$input->action}");
		}

	}
	
	private function get_arrangements($app_id)
	{
		$schedule = Fetch_Schedule($app_id);
		$result = array();
		foreach ($schedule as $e)
		{
			if (($e->context === 'arrangement' || $e->context == 'partial') && ($e->status === 'scheduled' || $e->status === 'registered')) 
			{			
			  $result[]=array('type' => $e->type,'date' => date('m/d/Y',strtotime($e->date_effective)),'amount' => abs($e->principal + $e->service_charge + $e->fee), 'status' => $e->status);

			}	
		}
		return $result;
	}
}
?>
