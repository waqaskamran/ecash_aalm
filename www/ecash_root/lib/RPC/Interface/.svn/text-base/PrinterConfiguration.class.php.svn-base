<?php
/**
 * eCash_Printer_Configration
 * - Simple class to retrieve the current queue to printer configuration
 */

require_once eCash_RPC_DIR . "/SOAP.class.php"; 

class eCash_RPC_Interface_PrinterConfiguration
{
	
	public function __construct()
	{
		eCash_RPC::Log()->write(__METHOD__ . "() Called");
	}
	
	public function reflect($val)
	{
		eCash_RPC::Log()->write(__METHOD__ . "() Called: Param: {$val}");
		return $val;
	}

	public function Get_Printer_Configurations()
	{
		$db = ECash::getMasterDb();

		$printer_configs = array();

		$query = "
			SELECT
				c.name_short as company,
				p.printer_id,
				p.printer_name,
				p.printer_host,
				p.queue_name,
				p.document_highwater_id 
			FROM
				printer AS p
			JOIN company AS c USING (company_id)
			WHERE
				active_status = 'active' 
			";

		$result = $db->query($query);
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}
}
