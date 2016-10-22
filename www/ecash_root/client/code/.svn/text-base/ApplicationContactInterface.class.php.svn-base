<?php
/**
 * Get and allow editing of contact information for an application
 *
 * @package PBX
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @created 2007-04-19
 * @version $Revision: 9104 $
 */

require_once(LIB_DIR . '/PBX/PBX.class.php');
class ApplicationContactInterface
{
	private $pbx_enabled = false;

	private $category_types = array();

	private $flag_precedence = array(
		'bad_info' => 0,
		'do_not_contact' => 1,
		'best_contact' => 2,
		'do_not_market' => 3,
		'high_risk' => 4,
		'fraud' => 5,
		'do_not_loan' => 6);

	private $flags = array();

	private $flag_icons = array(
		'bad_info' => '<img src="/image/standard/i_bad_info.gif" border="0">',
		'do_not_contact' => '<img src="/image/standard/i_do_not_contact.gif" border="0">',
		'best_contact' => '<img src="/image/standard/i_best_contact.gif" border="0">',
		'do_not_market' => '<img src="/image/standard/i_do_not_market.gif" border="0">',
		'high_risk' => '<img src="/image/standard/high_risk.gif" border="0">',
		'fraud' => '<img src="/image/standard/fraud.gif" border="0">',
		'do_not_loan' => '<img src="/image/standard/i_do_not_loan.gif" border="0">');

	/**
	 * @param bool $pbx_enabled
	 */
	public function __construct ($pbx_enabled)
	{
		$this->pbx_enabled = $pbx_enabled;
	}

	public function getCategoryDropdown($categories=null)
	{
		$this->category_types=$categories?$categories:$this->category_types;
		$html = '<select name="contact[category]" id="contact_category" style="width: 100%;">';
		foreach ($this->category_types as $key => $value)
		{
			$html .= '<option value="' . $key . '">' . $value . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	public function getFlagDropdown($flags=null)
	{
		$this->flags = $flags;
		$html = '<select name="contact[flag]" id="contact_flag" style="width: 100%;">';
		foreach ($this->flags as $id => $flag)
		{
			$html .= '<option value="' . $id . '">' . $flag . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	public function getContactFlagByPrecedence($contact)
	{
 		$flag_set = '';
		foreach ($contact['flags'] as $flag)
		{
			if (empty($flag_set)
				|| $this->flag_precedence[$flag] > $this->flag_precedence[$flag_set])
			{
				$flag_set = $flag;
			}
		}

		return $flag_set;
	}

	private function getDialLink($contact)
	{
		// NOTE: This functionality is a duplicate of Generate_Phone_Link() in display_overview

		if (empty($contact['number'])/* || in_array('disable_phone_link', $this->data->read_only_fields)*/)
		{
			return '';
		}

		$cat_url = '';
		if (!empty($contact['application_contact_id'])
			&& !in_array($contact['application_contact_id'], array('phone_home', 'phone_cell', 'phone_work')))
		{
			$ph_url = '&contact_id=' . $contact['application_contact_id'];
		}
		else
		{
			$ph_url = '&dial_number=' . preg_replace('[^\d]', '', $contact['number']);
			if ($contact['category'])
			{
				$cat_url = '&add_contact=true&type=phone&category=' . urlencode($contact['category']);
			}
		}

		return ' [<a href="#" onclick="javascript:window.open(\'/?action=pbx_dial&application_id=' . $contact['application_id'] . $ph_url . $cat_url . '\', \'PBX Dial\', \'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=300,height=100,left=150,top=150,screenX=150,screenY=150\')">Dial</a>]';
	}

	public function getHtml($contacts, $mode, $data_format, $module_name)
	{
// 		$pbx_enabled = eCash_PBX::isEnabled($this->server, $this->server->company_id);

		$javascript_array = array(
			0 => array(
				'application_contact_id' => 0,
				'category' => 0,
				'flag' => 0,
				'number' => '',
				'notes' => ''));

		$html = <<<HEAD_HTML
<table class="{$mode}" border="0" cellspacing="0" width="100%">
	<tr class="height">
		<th class="search" width="20%" style="padding-left: 4px;">Category</th>
		<th class="search" width="5%">&nbsp;</td>
		<th class="search" width="20%">Number</th>
		<th class="search" width="55%">Notes</div></th>
	</tr>

HEAD_HTML;

		$alt = TRUE;
		$rowcount = 0;
		foreach ($contacts as $contact)
		{
			$alt = !$alt;
			$td_class = $alt ? 'align_left_alt' : 'align_left';
			$div_id = 'generic_contact_information' . $rowcount;
			$rowcount++;

			$dial_link = ($this->pbx_enabled) ? $this->getDialLink($contact) : '';

			$flag_image = '';
			$flag_set = $this->getContactFlagByPrecedence($contact);
			if (!empty($flag_set))
			{
				$flag_image = $this->flag_icons[$flag_set];
			}

			$data_format->Display('phone', $contact['number']);
			$data_format->Display('sentence', $contact['notes']);
			$note_clean = str_replace(array("\r", "\n"), "; ", $contact['notes']);
			$note_clean = str_replace('"', "'", $note_clean);
			$note_clean = addslashes($note_clean);

			$javascript_array[$contact['application_contact_id']] = array(
				'application_contact_id' => $contact['application_contact_id'],
				'category' => array_search($contact['category'], $this->category_types),
				'flag' => $flag_set,
				'number' => $contact['number'],
				'notes' => $contact['notes'],
				'primary' => $contact['primary']);

			$html .= <<<CONTACT_HTML
	<tr class="height">
		<td class="{$td_class}" width="20%" style="padding-left: 4px;">
			<nobr>
				<a href="#" onClick="return Display_Contact_Information('{$contact['application_contact_id']}');">{$contact['category']}</a>
			</nobr>
		</td>
		<td class="{$td_class}" width="5%"><nobr>{$flag_image}</nobr></td>
		<td class="{$td_class}" width="20%"><nobr>{$contact['number']} {$dial_link}</nobr></td>
		<td class="{$td_class}" width="55%">
			<div id="{$div_id}">
				<nobr>{$contact['notes']}</nobr>
			</div>
		</td>
	</tr>

CONTACT_HTML;
		}

		$html = '<script type="text/javascript"> varContactInformation = ' . json_encode($javascript_array) . '</script>' . "\n\n" . $html;

		$html .= '</table>';

		return $html;
	}
}

?>
