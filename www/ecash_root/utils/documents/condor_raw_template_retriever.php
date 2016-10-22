#!/usr/bin/php
<?php

function Usage()
{
	echo "Usage: {$argv[0]} [company] \n";
	exit;
}

// Because of configuration file changes, these constants were already getting defined as local UFC
if ($argv[1] == 'occ')
{
	define ("CONDOR_HOST", 'condor.3.edataserver.com');
	define ("CONDOR_DOC_URL_BASE", 'http://'.CONDOR_HOST.'/?page=admin&show=detail&type=reprint_doc');
	define ("CONDOR_SERVER_URL_FRAGMENT", 'condor.4.internal.edataserver.com/condor_api.php');

	define ('CONDOR_SERVER_IDPW', 'oneclickcash:OCCc0nd0r@');
	define ('CONDOR_DOC_URL', CONDOR_DOC_URL_BASE . '&property_short=occ');
}

require_once(dirname(__FILE__) . '/../../www/config.php');
require_once LIB_DIR . 'Document/Document.class.php';
require_once eCash_Document_DIR . 'DeliveryAPI/Condor.class.php';

if ($argc < 2)
{
	Usage();
}

Set_Company_Constants($argv[1]);

$documents = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Template_Names();

if (!is_dir('document_list'))
{
	mkdir('document_list', 0744);
}

if (!is_dir('document_list/' . $argv[1]))
{
	mkdir('document_list/' . $argv[1], 0744);
}

$tidyconf = array(
	'indent' => true,
	'output-xhtml' => true,
	'indent-spaces' => 4,
	'bare' => true,
	'wrap' => 0);

foreach ($documents as $name)
{
	$filename = $name . '.html';

	$document = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Raw_Template_Data($name);

	$tidy = tidy_repair_string($document, $tidyconf, 'utf8');
	$document = (string) $tidy;

	file_put_contents('document_list/' . $argv[1] . '/' . $filename, $document);
}

?>
