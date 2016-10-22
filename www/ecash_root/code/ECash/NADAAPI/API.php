<?php
/**
 * NADA SOAP API
 * 
 * SOAP wrapper for NADA lookup
 * 
 * 
 * @author toddh, alexr
 */
require_once(ECASH_COMMON_DIR."/nada/NADA.php");


/**
 * Transport object that passes vehical information through soap
 */
Class Vehicle
{
	public $make;
	public $model;
	public $series;
	public $body;
	public $vin;
	public $vicYear; 
	public $value;
	
	/**
	 * This constructor takes an stdClass representation of a vehical
	 * and converts it to the dto object that needs to get passed
	 */
	public function __construct($objVehicle)
	{
		if ($objVehicle != NULL)
		{
			if (isset($objVehicle->make))
			{
				$this->make = $objVehicle->make;
			}
			
			if (isset($objVehicle->model))
			{
				$this->model = $objVehicle->model;
			}
			
			if (isset($objVehicle->series))
			{
				$this->series = $objVehicle->series;
			}
			if (isset($objVehicle->body))
			{
				$this->body = $objVehicle->body;
			}
			if (isset($objVehicle->vin))
			{
				$this->vin = $objVehicle->vin;
			}
			if (isset($objVehicle->vic_year))
			{
				$this->vicYear = $objVehicle->vic_year;
			}
			if (isset($objVehicle->value))
			{
				$this->value = $objVehicle->value;
			}
		}		
	}
}

/**
 * This class exposes the NASA service from olp to the
 * NADA SOAP API
 */
Class ECash_NADAAPI_API
{
	protected $db;
	protected $nada;

	public function __construct($db)
	{
		$this->db = $db;
		$this->nada = new NADA_API($this->db);
	}

	/**
	 * Gets a Vehicle's information based on the 17 digit VIN# (actually 9 of the first 10 digits of it)
	 *
	 * @param string $vin the 17 digit VIN# (or just the first 10 digits)
	 * @param string $region the 1-2 digit region id code (optional)
	 * @param char $valuetype the single character value type to get the value for ('L'oan,'R'etail,'T'rade-in) (optional)
	 * 
	 * @return array with the vehicle's description, as well as value
	 * 
	 */
        public function getVehicleByVin($vin, $regionid, $valuetype, $state_code)
        {
			//This is because SOAP keeps passing all the blank variables as empty strings
			$regionid = $regionid == '' ? '01' : $regionid;
			$valuetype = $valuetype == '' ? 'L' : $valuetyp;
			$state_code = $state_code == '' ? NULL : $state_code;
			
			$objVehicle = $this->nada->getVehicleByVin($vin,$regionid,$valuetype,$state_code);
			
			$vehicle = new Vehicle($objVehicle);	
			$vehicle->vin = $vin;		
			
			return $vehicle;
		}

	/**
	 * Gets the value for a vehicle from description values
	 *
	 * @param string $make The description of the make of the vehicle
	 * @param string $region the 1-2 digit region id code (optional)
	 * @param char $valuetype the single character value type to get the value for ('L'oan,'R'etail,'T'rade-in) (optional)
	 * 
	 * @return array with the vehicle's description, as well as value
	 * 
	 */
	public function getValueFromDescription($make, $model, $series, $body, $year)
	{
		return $this->nada->getValueFromDescription($make, $model, $series, $body, $year);
	}
}
