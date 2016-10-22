<?php

declare (ticks = 1000);

require_once LIB_DIR . "/PBX/PBX.class.php";


function getLastRecord()
{
	global $server; 

	$db = ECash::getMasterDb();

	$last_record_query = "
		SELECT result FROM pbx_history WHERE company_id = {$server->company_id} AND pbx_event = 'CDR Import' ORDER BY date_created DESC limit 1
	";
	
	$res = $db->querySingleValue($last_record_query);
	
	if ($res) 
	{
		return unserialize($res);
	}
	
}

function getCDREvents(eCash_PBX $pbx, $stm = "yesterday")
{
	$host = $pbx->getConfig("PBX Asterisk Host");
	$host = ($host) ? $host : "10.100.6.53";

	$cfg = new DB_MySQLConfig($host, 'asterisk', 'asterisk', 'asterisk');
	$db = $cfg->getConnection();
	
	$last = getLastRecord();
	
	if ( !$last || !count($last) ) 
	{
		$qpart = " calldate > " . date("YmdHis", strtotime($stm));
	} 
	else 
	{
		$qpart = " calldate >= '{$last['calldate']}' ";
	}
	
	$query = "
		SELECT * FROM cdr WHERE dcontext = 'from-inside' AND lastapp = 'Hangup' AND {$qpart} ORDER BY calldate limit 100
		";
	
	eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . $query);
	
	$res = $db->query($query);
	
	$records = array();
	while ($row = $res->fetch(PDO::FETCH_ASSOC))
	{
		if ($row == $last) continue;
		
		$records[] = $row;
	}
	
	eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . ( (count($records)) ? count($records) : "0" ) . " CDR Records Retrieved");
	
	return $records;
	
}

function prepareCallRecord($cdr_event)
{
	global $server;

	$db = ECash::getMasterDb();
	
	// 1. check against channel
	// a. semi-serialize the channel data
	$cq = "s:7:\"Channel\";s:" . strlen($cdr_event['channel']) . ":\"{$cdr_event['channel']}\";";

	$query = "
		SELECT DISTINCT 
			p.company_id, 
			c.application_id, 
			p.agent_id, 
			p.application_contact_id 
		FROM 
			pbx_history p 
		JOIN 
			application_contact c 
		ON 
			p.application_contact_id = c.application_contact_id 
		WHERE 
			p.result LIKE '%{$cq}%' AND 
			c.value = right('{$cdr_event['dst']}',10) AND 
			c.type = 'phone' 
		ORDER BY 
			p.date_created DESC LIMIT 1
	";
	
	eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . $query);
	
	$res = $db->query($query);
	$data = $res->fetch(PDO::FETCH_ASSOC);
	
	eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . var_export($data, true));
	
	// revert to old default behaviour
	if (!$data['company_id'])
	{
		$data['company_id'] = $server->company_id;
	}
	
	if (!$data['agent_id']) 
	{
		$query = "
			SELECT 
				agent_id, 
				date_created 
			FROM 
				pbx_history 
			WHERE 
				company_id = {$data['company_id']} AND
				result LIKE '%s:7:\"Channel\";s:12:\"SIP/{$cdr_event['src']}-____\";%' AND 
				date_created < date_add('{$cdr_event['calldate']}', interval 2 hour) 
			ORDER BY 
				date_created DESC
		";

		eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . $query);
	
		$res = $db->query($query);
		
		while ($row = $res->fetch(PDO::FETCH_ASSOC))
		{
			if (strtotime($cdr_event['calldate']) < strtotime($row['date_created'])) continue;
			$data['agent_id'] = $row['agent_id'];
			break;
		}
		
	}
	
	if(!$data['application_contact_id'])
	{
		$query = "
			SELECT 
				date_created,
				application_id,
				application_contact_id
			FROM
				application_contact 
			WHERE
				value = right('{$cdr_event['dst']}',10) AND 
				type = 'phone' 			
		";	
		
		eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . $query);
		
		$res = $db->query($query);
		
		eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . $res->rowCount() . " Application Contact(s) Found for this CDR Record.");
		


		switch ($res->rowCount()) 
		{
			case 0:
				break;
				
			case 1:
				$row = $res->fetch(PDO::FETCH_ASSOC);
				$data['application_id'] = $row['application_id'];
				$data['application_contact_id'] = $row['application_contact_id'];
				break;
				
			default:
				while ($row = $res->fetch(PDO::FETCH_ASSOC))
				{
					if (strtotime($cdr_event['calldate']) < strtotime($row['date_created'])) continue;
					$data['application_id'] = $row['application_id'];
					$data['application_contact_id'] = $row['application_contact_id'];
				}

		}
		
	}
	
	$data['result'] = $cdr_event;
	$data['pbx_event'] = "CDR Import";

	return $data;

}

function insertPbxHistory(eCash_PBX $pbx, $data)
{
	$history = eCash_PBX_History::Factory($pbx, ($data['application_contact_id']) ? $data['application_contact_id'] : 0 );
	
	eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "(): Setting Agent ID to {$data['agent_id']} ");
	
	$history->setAgent( ($data['agent_id']) ? $data['agent_id'] : 0 );
	$id = $history->addHistory($data['pbx_event'], NULL, $data['result']);
	
	$query = "
		UPDATE pbx_history SET date_created = '{$data['result']['calldate']}' where pbx_history_id = {$id}
		";
	
	eCash_PBX::Log()->write(__FILE__ . ": " . __METHOD__ . "() : " . $query);
	$db = ECash::getMasterDb();
	$db->exec($query);
	
}


function Main($args)
{
	global $server;
	
	$pbx = new eCash_PBX($server);
	$events = getCDREvents($pbx);
	
	foreach ($events as $cdr) 
	{
		
		try 
		{
			insertPbxHistory( $pbx , prepareCallRecord($cdr));
		} 
		catch (Exception $e) 
		{
			eCash_PBX::Log()->write($e->getMessage());
		}

	}
}
