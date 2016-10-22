<?php

/**
 * Utility to copy the event/transaction relationships from one company
 * to another.  Be VERY careful with this utiility.  You should only use
 * it with new companies!
 *
 * THIS NEEDS TO BE RUN AS A CRON VIA ecash_exec.php
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */
function main()
{
	die(); // REMOVE ME TO MAKE ME RUN

	$new_company_id = 9;
	$source_company_id = 1;
	
	$event_transaction = ECash::getFactory()->getModel('EventTransactionList');
	$event_transaction->loadBy(array('company_id' => $source_company_id));
	
	foreach($event_transaction as $evt)
	{
		$new_evt = $evt->copy();
		$new_evt->company_id = $new_company_id;
		$new_evt->date_created = date('Y-m-d h:i:s');
		$new_evt->date_modified = date('Y-m-d h:i:s');
		
		if(empty($last_event_type) || $last_event_type != $evt->event_type_id)
		{
			$event = ECash::getFactory()->getModel('EventType');
			$event->loadBy(array('event_type_id' => $evt->event_type_id));
			$last_event_type = $event->event_type_id;
			$new_event = $event->copy();
			$new_event->company_id = $new_company_id;
			$new_event->date_created = date('Y-m-d h:i:s');
			$new_event->date_modified = date('Y-m-d h:i:s');
			$new_event->save();
			$new_event_type_id = $new_event->event_type_id;
		}
		
		$new_evt->event_type_id = $new_event_type_id;
		
		$transaction = ECash::getFactory()->getModel('TransactionType');
		$transaction->loadBy(array('transaction_type_id' => $evt->transaction_type_id));
		$new_transaction = $transaction->copy();
		$new_transaction->company_id = $new_company_id;
		$new_transaction->date_created = date('Y-m-d h:i:s');
		$new_transaction->date_modified = date('Y-m-d h:i:s');
		$new_transaction->save();
		
		$new_evt->transaction_type_id = $new_transaction->transaction_type_id;
		$new_evt->save();
		
	}
}