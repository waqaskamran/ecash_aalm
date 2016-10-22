<?php
/*
* ECash_RPC_Tokens
* Used to retrieve database token list for OLP to create documents
*
*/
require_once LIB_DIR . "/common_functions.php";
require_once SQL_LIB_DIR . "/scheduling.func.php";

require_once LIB_DIR . "/RPC/RPC.class.php";
require_once eCash_RPC_DIR . "/SOAP.class.php";

class ECash_RPC_Tokens implements eCash_iWSDL
{

	
	public static function getRPCMethods()
	{
		//Define the list of methods.
		$methods = array();
		
		
		$methods[] = (object) array("name" => "getTokensByLoanType",
								 	"args" => array ( (object) array (	"name" => "loan_type_id",
								 						  				"type" => "string"),
								 					),
								 	"response" => (object) array ("type" => "array")
								 	);
			 									 	
		
		return $methods;
		
	}
	/*
		returns all database tokens for a given loan type id
		
		@param int $loan_type_id

		@return array 
	*/
	public function getTokensByLoanType($loan_type_id)
	{
		$loan_type_model = Ecash::getFactory()->getModel('LoanType');
		if($loan_type_model->loadBy(array('loan_type_id' => $loan_type_id)))
		{
			$token_manager = ECash::getFactory()->getTokenManager();
			$db_tokens = $token_manager->getTokensByLoanTypeId($loan_type_model->company_id, $loan_type_id);
			$tokens = array();
			foreach($db_tokens as $token_name => $token)
			{
				$tokens[$token_name] = $token->getValue();

			}
			return $tokens;
		}
		//loan type does not exist
		else
		{
			throw new InvalidArgumentException('Invalid Loan Type');
		}
		

	}
	
	protected function getDB()
	{
		$db = ECash::getMasterDb();
		return $db;
	}

	
	
	
	
	
}
