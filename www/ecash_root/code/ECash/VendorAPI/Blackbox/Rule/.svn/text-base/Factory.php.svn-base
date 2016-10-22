<?php

class ECash_VendorAPI_Blackbox_Rule_Factory extends VendorAPI_Blackbox_Rule_Factory
{
	protected function getDataXRuleObservers()
	{
		$observers = array(
			new ECash_VendorAPI_Blackbox_DataX_BureauInquiryObserver(
				$this->driver->getFactory(),
				$this->config->call_context,
				$this->config->persistor,
				$this->driver->getInquiryClient()
			)
		);

		$class_name = strtoupper($this->driver->getEnterprise())
			.'_VendorAPI_Blackbox_DataX_AdverseActionObserver';

		if (class_exists($class_name))
		{
			$observers[] = new $class_name(
				$this->config->campaign,
				$this->driver->getStatProClient()
			);
		}

		$class_name = strtoupper($this->driver->getEnterprise())
			.'_VendorAPI_Blackbox_DataX_AutoFundObserver';

		if (class_exists($class_name))
		{
			$observers[] = new $class_name(
				$this->config->campaign,
				$this->driver->getStatProClient()
			);
		}

		return $observers;
	}
	
	/**
	 * Returns all the other miscellaneous rules we feel it necessary to run
	 * @return Blackbox_IRule
	 */
	public function getRuleCollection(ECash_CustomerHistory $customer_history)
	{
		if ($this->config->blackbox_mode == VendorAPI_Blackbox_Config::MODE_BROKER)
		{
			return new VendorAPI_Blackbox_Rule_PurchasedLeads(
				$this->config->event_log, 
				new VendorAPI_PurchasedLeadStore_Memcache(
					$this->driver->getEnterprise(), 
					$this->driver->getMemcachePool()
				), 
				NULL, 
				NULL, 
				$this->config->company, 
				'2 minute', 
				1
			);
		}
	}
}

?>
