<?php

/*** Ignore all this ****/
/************************/
	require_once('libolution/AutoLoad.1.php');
	@require_once('../www/config.php');
	set_include_path(get_include_path() . ':/virtualhosts/ecash_common');
	@require_once(SERVER_CODE_DIR . 'queue_configuration.class.php');

	$company = ECash::getFactory()->getModel('Company');
	$company->loadBy(array('name_short' => 'ccrt'));

	$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
	$config_filename = BASE_DIR . "/config/{$enterprise_prefix}/company/{$company->name_short}.php";

	if(file_exists($config_filename))
	{
		@require_once($config_filename);
		@eCash_Config::useConfig(strtoupper($company->name_short) . "_CompanyConfig", 'Environment');
	}
/************************/
/************************/



/*** Example #1: Inserting application_id=500 in the queue by the name of 'blah' ***/

	$qm = ECash::getFactory()->getQueueManager();
	$q = $qm->getQueue('blah');
	$q->insert(new ECash_Queues_BasicQueueItem(500));


/*** Example #2: Getting all queues. ***/

	$qm = ECash::getFactory()->getQueueManager();
	$all_queues = $qm->getQueues();


/*** Example #3: Pulling the latest app from a queue. ***/

	$qm = ECash::getFactory()->getQueueManager();
	$q = $qm->getQueue('blah');
	$item = $q->dequeue();

/*** Example #4: Getting all queues given a display section. ***/

?>