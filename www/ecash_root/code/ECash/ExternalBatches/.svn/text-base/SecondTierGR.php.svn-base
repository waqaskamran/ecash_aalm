<?php


class ECash_ExternalBatches_SecondTierGR extends ECash_ExternalBatches_SecondTierBatch
{
	function __construct($db)
	{
		parent::__construct($db);

		$this->after_status  = array('queued', 'contact', 'collections', 'customer', '*root');
	}
}

?>
