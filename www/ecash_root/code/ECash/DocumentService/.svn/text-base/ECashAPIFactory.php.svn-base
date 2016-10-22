<?php
class ECash_DocumentService_ECashAPIFactory implements ECash_Service_DocumentService_IECashAPIFactory
{
	private $db;
	private $company;

	public function __construct(DB_IConnection_1 $db, ECash_Models_Company $company) {
		$this->db = $db;
		$this->company = $company;
	}

	/**
	 * @see ECash_Service_Loan_API#getEcashApi2
	 * @param int $application_id
	 * @return eCash_API_2
	 */
	public function createECashAPI($application_id)
	{
		require_once(ECASH_COMMON_DIR . "ecash_api/ecash_api.2.php");

		$api = NULL;
		try
		{
			$api = eCash_API_2::Get_eCash_API($this->company->name_short, $this->db, $application_id, $this->company->company_id);
		}
		catch (Exception $e)
		{
			$log = ECash::getLog();
			$log->write("Unable to get eCash_API_2 instance: " . $e->getMessage());
			throw $e;
		}
		return $api;
	}
}
