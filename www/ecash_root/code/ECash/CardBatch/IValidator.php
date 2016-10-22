<?php
interface ECash_CardBatch_IValidator
{
	public function Validate(array $transactions);

	public function getMessageArray();

}





?>
