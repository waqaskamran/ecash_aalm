<?php

require_once ("config.php");
require_once('minixml/minixml.inc.php');
require_once (SERVER_CODE_DIR . "bureau_query.class.php");

// Connect to the database
$db = ECash::getMasterDb();

require_once (LIB_DIR . "popup_check_login.php");

?>
<html>
<body onload="self.focus();">
<?

function Datax_HTML($row)
{
    $link = false;
	/**
	 * Sent Package
	 */
	try {
		$sent_array = (array) @new SimpleXMLElement($row->sent_package);
	}
	catch (Exception $e)
	{
		$mini_sent = new MiniXMLDoc();
		$mini_sent->fromString($row->sent_package);
		$sent_array = $mini_sent->toArray();
	}
	if(isset($sent_array['DATAXINQUERY']))
	{
		$sent = $sent_array['DATAXINQUERY']['QUERY']['DATA'];
	}
	else if(isset($sent_array['QUERY']['DATA']))
	{
		$sent = $sent_array['QUERY']['DATA'];
	}
	else if(isset($sent_array['inquiry-purpose-type']))
	{
		$sent = @new SimpleXMLElement($row->sent_package);
		unset($sent->password);
	}
	else if(isset($sent_array['ApplicationInfo']))
	{
		$sent = $sent_array['ApplicationInfo'];
	}
	else 
	{
		$sent = $sent_array['QUERY'];
	}

	/**
	 * Recieved Package
	 */
	try {
		$received_array = (array) @new SimpleXMLElement($row->received_package);
	}
	catch (Exception $e)
	{
		$mini_received = new MiniXMLDoc();
		$mini_received->fromString($row->recieved_pacakge);
		$received_array = $mini_received->toArray();
	}

	if(isset($received_array['Response']))
	{	
		$summary = $received_array['Response'];
	}
	else if(isset($received_array['DataxResponse']['Response']))
	{	
		$summary = $received_array['DataxResponse']['Response'];
	}
	else if(isset($received_array['DataxResponse']['Idv']['Data']))
	{
		$summary = $received_array['DataxResponse']['Idv']['Data'];
	}
	else if(isset($received_array['clear-products-request']))
	{
		$summary =  @new SimpleXMLElement($row->received_package); 
	}
	else if(isset($received_array['ApplicationInfo']))
	{
		$summary = $received_array['ApplicationInfo'];
        $link = (string) $summary->ReportLink;
	}
//	else if ($received_array['DataxResponse']['Response']['Detail'])
//	{	
//		$summary = $received_array['DataxResponse']['Response']['Detail'];
//	}
	else 
	{
		$summary = null;
	}
	echo "<tr><td colspan='2' class='section'>{$row->name} Sent</td></tr>\n";
	
	if(is_a($sent, 'SimpleXMLElement') || is_array($sent))
	{
		Other_HTML($sent);
	}

	//echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
	echo "</table> <br><table border='1'>";
	echo "<tr><td colspan='2' class='section'>{$row->name} Summary</td></tr>\n";
	if ($link) echo "<tr><td colspan='2' class='field'><a href=".$link." target='_blank'>FactorTrust Report Link</a></td></tr>\n";
	
	if(is_a($summary, 'SimpleXMLElement') || is_array($summary))
	{
		Other_HTML($summary);
	}

}

function FactorTrust_HTML($row)
{
    $link = false;
	/**
	 * Sent Package
	 */
	try {
		$sent_array = (array) @new SimpleXMLElement($row->sent_package);
	}
	catch (Exception $e)
	{
		$mini_sent = new MiniXMLDoc();
		$mini_sent->fromString($row->sent_package);
		$sent_array = $mini_sent->toArray();
	}

	$sent = $sent_array['ApplicationInfo'];

	/**
	 * Recieved Package
	 */
	try {
		$received_array = (array) @new SimpleXMLElement($row->received_package);
	}
	catch (Exception $e)
	{
		$mini_received = new MiniXMLDoc();
		$mini_received->fromString($row->recieved_pacakge);
		$received_array = $mini_received->toArray();
	}

    $summary = $received_array['ApplicationInfo'];
    $link = (string) $summary->ReportLink;
	
	if(is_a($sent, 'SimpleXMLElement') || is_array($sent))
	{
		Other_HTML($sent);
	}

	//echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
	echo "</table> <br><table border='1'>";
	echo "<tr><td colspan='2' class='section'>{$row->name} Summary</td></tr>\n";
	if ($link) echo "<tr><td colspan='2' class='field'><a href=".$link.">FactorTrust Report Link</a></td></tr>\n";
	
	if(is_a($summary, 'SimpleXMLElement') || is_array($summary))
	{
		Other_HTML($summary);
	}

}

function Clarity_HTML($row)
{
    $link = false;
	/**
	 * Sent Package
	 */
	try {
		$sent_array = (array) @new SimpleXMLElement($row->sent_package);
	}
	catch (Exception $e)
	{
		$mini_sent = new MiniXMLDoc();
		$mini_sent->fromString($row->sent_package);
		$sent_array = $mini_sent->toArray();
	}

    $sent = @new SimpleXMLElement($row->sent_package);
    unset($sent->password);

	/**
	 * Recieved Package
	 */
	try {
		$received_array = (array) @new SimpleXMLElement($row->received_package);
	}
	catch (Exception $e)
	{
		$mini_received = new MiniXMLDoc();
		$mini_received->fromString($row->recieved_pacakge);
		$received_array = $mini_received->toArray();
	}
		$summary =  @new SimpleXMLElement($row->received_package); 

	echo "<tr><td colspan='2' class='section'>{$row->name} Sent</td></tr>\n";
	
	if(is_a($sent, 'SimpleXMLElement') || is_array($sent))
	{
		Other_HTML($sent);
	}

	//echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
	echo "</table> <br><table border='1'>";
	echo "<tr><td colspan='2' class='section'>{$row->name} Summary</td></tr>\n";
	if ($link) echo "<tr><td colspan='2' class='field'><a href=".$link.">FactorTrust Report Link</a></td></tr>\n";
	
	if(is_a($summary, 'SimpleXMLElement') || is_array($summary))
	{
		Other_HTML($summary);
	}

}

function CLV_HTML($row)
{
	$mini = new MiniXMLDoc();
	$hacked_xml = str_replace("-", "_", $row->sent_package);
	$mini->fromString($hacked_xml);
	$sent_array = $mini->toArray();
	//echo "<pre>" . print_r($sent_array, TRUE) . "</pre>";

	$hacked_xml = str_replace("-", "_", $row->received_package);
	$mini->fromString($hacked_xml);
	$received_array = $mini->toArray();

	$sent = NULL;
	$received = NULL;
	$title = $row->name;
	//idv_advanced_v2 is sometimes a datax wrappered clv call
	if(!empty($received_array['DataxResponse']))
	{
		$sent = $sent_array['ENVELOPEDATA']['INQUERYDATA'];
		$received = $received_array['DataxResponse'];
		$title .= "/DataX";
	}
	else
	{
		$sent = $sent_array['clv_request']['inquiry'];
		$received = $received_array['clv_response']['inquiry'];
	}
		

	echo "<tr><td colspan=\"2\" style=\"font-weight:bold;\">{$title} Sent</td></tr>\n";

	if( is_array($sent) )
	{
		foreach($sent as $name => $value)
		{
			Print_Row($name, $value);
		}
	}

	echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
	
	echo "<tr><td colspan=\"2\" style=\"font-weight:bold;\">{$title} Summary</td></tr>\n";

	if( is_array($received) )
	{
		foreach($received as $name => $value)
		{
			Print_Row($name, $value);
		}
	}
}

function Print_Row($name, $value)
{
	//mantis:5490
	switch (strtoupper($name))
	{
		case 'NAMEFIRST':
			$name = "First Name";
		break;
		case 'NAMELAST':
			$name = "Last Name";
		break;
		case 'NAMEMIDDLE':
			$name = "Middle Name";
		break;
		case 'STREET1':
			$name = "Address 1";
		break;
		case 'STREET2':
			$name = "Address 2";
		break;
		case 'PHONEHOME':
			$name = "Home Phone";
		break;
		case 'PHONELISTED':
			$name = "Listed Phone";
		break;
		case 'DRIVERLICENSENUMBER':
			$name = "Driver License Number";
		break;
		case 'DRIVERSLICENSENUMBER':
			$name = "Driver License Number";
		break;
		case 'DRIVERLICENSESTATE':
			$name = "Driver License State";
		break;
		case 'DRIVERSLICENSESTATE':
			$name = "Driver License State";
		break;
		case 'DOBYEAR':
			$name = "Birth Year";
		break;
		case 'DOBMONTH':
			$name = "Birth Month";
		break;
		case 'DOBDAY':
			$name = "Birth Date";
		break;
		case 'BANKNAME':
			$name = "Bank Name";
		break;
		case 'BANKABA':
			$name = "Bank ABA Number";
		break;
		case 'BANKACCTNUMBER':
			$name = "Bank Account Number";
		break;
		case 'BANKACCTTYPE':
			$name = "Bank Account Type";
		break;
		case 'PHONEWORK':
			$name = "Work Phone";
		break;
		case 'WORKNAME':
			$name = "Work Name";
		break;
		case 'PHONENEXT':
			$name = "Next Phone";
		break;
		case 'PROMOID':
			$name = "Promo Id";
		break;
		case 'DECISIONBUCKET':
			$name = "Decision Bucket";
		break;
		case 'CHARGEOFFS':
			$name = "Charge Offs";
		break;

		default:
			$name = ucwords(strtolower(str_replace('_', ' ', $name)));
		break;
	}
	//end mantis:5490
	
	if(is_a($value, 'SimpleXMLElement'))
	{
		if(count($value->attributes()) === 0)
		{
	        $value = str_replace("<", "&lt;", $value);
	        $value = str_replace(">", "&gt;", $value);		
			echo "<tr><td class='field' >&nbsp;&nbsp;{$name}:</td><td class='data'>{$value}</td></tr>\n";
		}
		else
		{
			echo "<tr><td class='title' colspan=2>&nbsp;<strong>{$name}</strong><br></td></tr>\n";
			foreach($value->attributes() as $a => $b)
			{
				echo "<tr><td class='field' >&nbsp;&nbsp;{$a}:</td><td class='data'>{$b}</td></tr>\n";
			}
		}
	}

}


function Other_HTML($row)
{
	if(is_a($row, 'SimpleXMLElement') || is_array($row))
	{
	   	foreach($row as $column => $value)
		{
			Print_Row($column, $value);
			if(is_array($value) || is_a($value, 'SimpleXMLElement'))
			{
					Other_HTML($value);	
			}
		}
	}
}


?>
<html>
<head>
<title> Underwriting Inquiry Record</title>

<style type="text/css">
body{
background: #eee;
font-family: Arial;
}
table{
background: #fff;
border-collapse: collapse;
border: 1px solid #aaa;
width: 400px;
margin-bottom: 20px;
}
td{
  padding: 3px;
  border: 1px solid #aaa;
}
.section{
  font-size: 15px;
font-weight: bold;
text-align: center;
}
.title{
font-size: 13px;
font-weight: bold;
background: #f6f6f6;
}
.field{
font-size: 11px;
font-weight: bold;
}
.data{
font-size: 11px;
text-align: right;
}
tr:hover{
background: #f9f9f9;
}
</style>

<?
if (isset ($_REQUEST["bureau_inquiry_id"]))
{
   	$bureau = new Bureau_Query($db, ECash::getLog());
   	$id 	= $_REQUEST['bureau_inquiry_id'];
   	$table  = NULL;
   	
   	if(substr($id, 0, 1) == "f")
   	{
   		$id = substr($id, 1, strlen($id));
   		$table = 'failed';
   	}
   	
	$rows = $bureau->getPackages(NULL, NULL, 'all', $table, $id);
	if( is_array($rows) )
	{
		foreach($rows as $row)
		{
			?>
			<table border="1">
			<?//echo "<table >\n";
			echo "<tr><td class='section' colspan='2'>{$row->name}</td></tr>\n";
			echo "<tr><td class='field'>Date Created</td>
			<td class='data'><pre>" . date( "r", strtotime($row->date_created)) . "</pre></td></tr>\n";
			echo "</table><br><table border='1'>";
			//echo "<tr><td colspan=\"2\"><br /></td></tr>\n";

            switch ($row->name_short){
                case 'datax':
				Datax_HTML($row);
                    break;
                case 'factortrust':
                    FactorTrust_HTML($row);
                    break;
                case 'clarity':
                    Clarity_HTML($row);
                    break;
                case 'clverify':
				CLV_HTML($row);
                    break;
                default:
				Other_HTML($row);
                    break;
			}

			echo "</table>";
			echo "<hr>\n";
		}
	}
}

?>
</body>
</html>
