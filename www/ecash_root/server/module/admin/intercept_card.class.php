<?php
/**
 * @package admin
 */

require_once SERVER_CODE_DIR.'module_interface.iface.php';
require_once(LIB_DIR. "common_functions.php");

class Intercept_Card
{
	private $transport;
	private $request;
	private $server;
	private $db;

	public function __construct(Server $server, $request)
	{
		$this->server = $server;
		$this->transport = ECash::getTransport();
		$this->db = ECash::getMasterDb();	
	}

	public function Display()
	{
		$card_array = Fetch_Intercept_Card($this->server->company_id);

		$data['intercept_card_values'] = $card_array;
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

	public function Update_Intercept_Card($card_values_string)
	{
		$card_array = array();
		$m = 0;
		
		for ($i='A'; $i <= 'J'; ++$i)
		{
			$coulmn_array = array();
			for ($j = 1; $j <= 5; $j++)
			{
				$coulmn_array[$j] = substr($card_values_string, $m++, 1);
			}
			$card_array[$i] = $coulmn_array;
		}
		
		$query = "
	            		-- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
	            		UPDATE 
					intercept_login
				SET 
					active_status = 'inactive'
				WHERE 	
					company_id = {$this->server->company_id}
				  AND
					active_status = 'active'
	            	";
		$this->db->Query($query);


		$query = "
	            		-- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
	            		INSERT INTO intercept_login
							(
							date_created,
							company_id,
							active_status,
							intercept_serialized
							)
						VALUES 
							(
							now(),
							{$this->server->company_id}, 
							'active',
							'" . serialize($card_array) . "'
							)
	            	";

		return $this->db->Query($query);
	}
}

?>
