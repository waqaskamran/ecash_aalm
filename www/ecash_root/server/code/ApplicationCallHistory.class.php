<?php
/**
 * Get call records related to a specific Application ID
 *
 * @package PBX
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @created 2007-04-18
 * @version $Revision$
 */

require_once(LIB_DIR . '/PBX/PBX.class.php');

class ApplicationCallHistory
{
	private $server;
	private $application_id;

	public function __construct(Server $server, $application_id)
	{
		$this->server = $server;
		$this->application_id = $application_id;
	}

	/**
	 * Get the application call history data.
	 *
	 * @return mixed NULL if disabled, or associative array of call information
	 */
	public function getData()
	{
		if (!eCash_PBX::isEnabled($this->server, $this->server->company_id))
		{
			return NULL;
		}

		$call_history = array();
		$db = ECash::getMasterDb();
		$query = $this->getQuery();
		$result = $db->query($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$raw_event = unserialize($row['result']);

			$call_history[] = array(
				'date' => date('m/d/Y H:j:s A', strtotime($row['date_created'])),
				'agent' => $row['name_last'] . ', ' . $row['name_first'],
				'type' => $row['type'],
				'category' => $row['category'],
				'number' => $row['value'],
				'duration' => $raw_event['duration']);
		}

		return $call_history;
	}

	/**
	 * The query to get the information needed in the application call history.
	 */
	private function getQuery()
	{
		return "
			SELECT
				ph.date_created,
				a.name_first,
				a.name_last,
				ac.category,
				ac.value,
				ph.result
			FROM application_contact ac
				JOIN pbx_history ph ON ph.phone = ac.value
					AND ph.pbx_event = 'CDR Import'
				LEFT JOIN agent a ON a.agent_id = ph.agent_id
			WHERE ac.application_id = {$this->application_id}
				AND ac.type = 'phone'
			ORDER BY ph.date_created";
	}

	/**
	 * Transform an associative array of call history into an HTML table.
	 *
	 * @param mixed $call_history associative array of call information
	 * @return string HTML table of call information
	 */
	public function getHtml($call_history, $data_format)
	{
		$html = '<p>Call History:<br />';

		if (empty($call_history))
		{
			return $html . 'No call history associated with this application.</p>';
		}

		$html .= <<<HTML
<table width="500" border="1" style="font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;">
	<tr style="font-weight: bold; background: #F6C8A9;">
		<td>Date</td>
		<td>Category</td>
		<td>Number</td>
		<td>Duration</td>
		<td>Agent</td>
	</tr>
HTML;

		$timezone = date('T');

		foreach($call_history as $call)
		{
			$data_format->Display('phone', $call['number']);
			$html .= <<<CALL_ROW
	<tr style="background: #FFF3EB;">
		<td title="{$call['date']} [{$timezone}]"><nobr>{$call['date']}</nobr></td>
		<td><nobr>{$call['category']}</nobr></td>
		<td><nobr>{$call['number']}</nobr></td>
		<td><nobr>{$call['duration']}s</nobr></td>
		<td>{$call['agent']}</td>
	</tr>
CALL_ROW;
		}

		$html .= '</table></p>';

		return $html;
	}

	public function getHtmlDataOutput($data_format)
	{
		$call_history = $this->getData();
		if ($call_history === NULL)
		{
			return '';
		}

		return $this->getHtml($call_history, $data_format);
	}
}

?>
