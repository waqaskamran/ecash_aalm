<?php

/**
 * Returns / Corrections File Parser for EPP / BillingTree
 *
 */
class ECash_ACHReport_EPP_ReturnsCorrectionsParser extends ECash_ACHReport_IMPACT_ReturnsCorrectionsParser
{
	/**
	 * Currently we do not know what they will actually return, so the recipient_id may not be correctly
	 * mapped to the right value.
	 *
	 * @var array
	 */
	protected $return_file_format = array(
		'merchant_id',				// Account ID
		'recipient_id', 			// Import ID
		'trans_type',				// Transaction Type
		'description',				// Description
		'batch_id',					// Account Batch ID
		'AccountId',				// Consumer In-House ID
		'effective_entry_date', 	// Submit Date
		'ConsumerFirstName',		// Customer's First Name
		'ConsumerLastName',			// Customer's Last Name
		'CheckReference',			// Check Reference
		'CheckNumber',				// Check Number
		'ReturnDate',				// Return Date
		'ABA', 						// Customer's ABA
		'AccountNumber', 			// Customer's Bank Account #
		'StatusDescription',		// Status Description
		'reason_code', 				// Reason Code (*)
		'return_reason',			// Return Reason
		'debit_amount',				// Debit Amount
		'credit_amount',			// Credit Amount
		'corrected_info' 			// Addenda Info (*)
	);

}
?>
