<?php
require_once(ECASH_COMMON_DIR . 'nada/NADA.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
	
class API_NADA implements Module_Interface
{
	private $db;
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
		$this->db = ECash::getMasterDb();
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

		switch ($input->action)
		{
			case 'nada_info':
				$nada = new NADA_API($this->db);
				
				switch ($input->function)
				{
				    case 'getMakes':
					    $data = $nada->getMakes($input->makes_year);
					    $data = (array)$data;
						break;
					case 'getSeries':
					    $data = $nada->getSeries($input->makes_year, $input->make);
					    $data = (array)$data;
						break;
					case 'getBodies':
					    $data = $nada->getBodies($input->bodies_year, $input->bodies_make, $input->bodies_series);
					    $data = (array)$data;
						break;
					case 'getValue':
					    $data = $nada->getValue($input->value_year, $input->value_make, $input->value_series, $input->value_body, NULL, 'L', $input->value_state);
						break;
					case 'getValueFromVIN':
					    $data = $nada->getVehicleByVin($input->value_vin, NULL, 'L', $input->value_state);
						break;
					default:
						throw new Exception("Unknown nada_info function {$input->function}");
				}
				break;
			default:
				throw new Exception("Unknown action {$input->action}");
		}
		return $data;
	}
}

?>
