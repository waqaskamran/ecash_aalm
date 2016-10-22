<?php

class Payment_Types
{
	private $transport;
	private $tags;
	private $request;

	/**
	 *
	 */
	public function __construct(Server $server, $request)
	{
		$this->request = $request;
		$this->transport = ECash::getTransport();

		$this->_Fetch_Data();
	}

	public function Display()
	{
		return $this->_Fetch_Data();
	}

	public function Get_Loan_Types()
	{
		$lts = ECash::getFactory()->getModel('LoanTypeList');
		$lts->loadBusinessLoanTypes();
		
		$loan_types = array();

		foreach ($lts as $loan_type)
		{
			$loan_types[] = array('loan_type_id' => $loan_type->loan_type_id,
								  'name'         => $loan_type->name, 
								  'name_short'   => $loan_type->name_short, 
								  'company_id'   => $loan_type->company_id);
		}

		return $loan_types;
	}

	public function Get_Payment_Types()
	{
		$pts = ECash::getFactory()->getModel('PaymentTypeList');
		$pts->loadAll();
		
		$payment_types = array();
		
		foreach ($pts as $payment_type)
		{
			$payment_types[] = array('payment_type_id' => $payment_type->payment_type_id,
									 'name'            => $payment_type->name,
									 'name_short'      => $payment_type->name_short);

		}

		return $payment_types;
	}


	private function _Fetch_Data($extra_data = array())
	{
		$data['loan_types']    = $this->Get_Loan_Types();
		$data['payment_types'] = $this->Get_Payment_Types();
		
		
		$data['msg'] = '';

		ECash::getTransport()->Set_Data(array_merge($data, $extra_data));

		return TRUE;
	}
}

?>
