<?php

/*
 * This returns true if any field is marked do_not_loan, otherwise
 * it returns false.
 *
 * @parm $application  This is the application id
 *
 * @return $result  This is true if any field is marked do not loan.
 */
function Is_Do_Not_Loan_Set($application_id, $company_id, $table_name='application')
{
		$app_column_model = ECash::getFactory()->getModel('ApplicationColumn');
		return $app_column_model->loadBy(array('company_id' => $company_id, 'application_id' => $application_id, 
							'table_name' => $table_name, 'do_not_loan' => 'on'));
}

?>
