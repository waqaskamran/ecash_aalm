<?php

class ECashCra_Driver_Commercial_DataBuilder implements IObservable_1 
{
	protected $observers = array();
	/**
	 * Attach an observer that wishes to begin receiving notifications
	 * @param Delegate_1 $d
	 */
	public function attachObserver(Delegate_1 $d)
	{
		$this->observers[] = $d;
	}

	/**
	 * Detach an observer that no longer wishes to receive notifications
	 * @param Delegate_1 $d
	 */
	public function detachObserver(Delegate_1 $d)
	{
		$index = array_search($d, $this->observers, true);
		
		if ($index !== FALSE)
		{
			unset($this->observers[$index]);
		}
	}
	
	protected function detachAllObservers()
	{
		$this->observers = array();
	}
		
	public function getApplicationData(DB_IStatement_1 $statement)
	{
		$applications = array();
		while ($db_row = $statement->fetch(PDO::FETCH_ASSOC))
		{
			$application = new ECashCra_Data_Application($db_row);
			$application->setPersonal(
				new ECashCra_Data_Personal($db_row)
			);
			$application->setEmployer(
				new ECashCra_Data_Employer($db_row)
			);
			
			$this->notifyNewApplication($application, $db_row);
			$applications[] = $application;
		}

		$this->detachAllObservers();
		return $applications;
	}
	
	public function getPaymentData(DB_IStatement_1 $statement)
	{
		$payments = array();
		while ($db_row = $statement->fetch(PDO::FETCH_ASSOC))
		{
			$payment = new ECashCra_Data_Payment($db_row);
			$application = new ECashCra_Data_Application($db_row);
			$application->setPersonal(
				new ECashCra_Data_Personal($db_row)
			);
			$application->setEmployer(
				new ECashCra_Data_Employer($db_row)
			);
			
			$payment->setApplication($application);
			
			$this->notifyNewPayment($payment, $db_row);
			$payments[] = $payment;
		}
		
		$this->detachAllObservers();
		return $payments;
	}
	
	protected function notifyNewApplication(ECashCra_Data_Application $application, array $db_row)
	{
		foreach ($this->observers as $delegate)
		{
			/* @var $delegate Delegate_1 */
			$delegate->invokeArray(array($application, $db_row));
		}
	}
	
	
	protected function notifyNewPayment(ECashCra_Data_Payment $payment, array $db_row)
	{
		foreach ($this->observers as $delegate)
		{
			/* @var $delegate Delegate_1 */
			$delegate->invokeArray(array($payment, $db_row));
		}
	}
}

?>