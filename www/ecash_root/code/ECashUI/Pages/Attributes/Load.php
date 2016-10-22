<?php

/**
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @package ECashUI.Attributes
 */
class ECashUI_Pages_Attributes_Load extends ECashUI_Modules_Admin_Page
{
	/**
	 * Processes the given request.
	 *
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		$flag = $request['flag'];
		$application_id = $request['application_id'];

		$application = new ECash_Application($application_id);
		$contacts = $application->getContacts()->all()->fetchAll();
		$attributes = $application->getAttributes()->allByFlag($flag);

		// The order that $contacts is initialized is specific to the display order.

		$contacts[] = array(
			'type' => 'email',
			'value' => $application->getModel()->email,
			'category' => 'Primary Email',
		);

// Deprecated function, and this code isn't even hit with Commercial,
// but I'm keeping it here in case we do someday use it.
// 
//		foreach (Fetch_References($application_id) as $i => $pr)
//		{
//			$contacts[] = array(
//				'type' => 'phone',
//				'value' => $pr->phone,
//				'category' => 'Personal Reference #' . ($i + 1),
//			);
//		}

		$contacts[] = array(
			'type' => 'address',
			'value' => $application->getModel()->street,
			'category' => 'Home Address',
		);

		$flat = array();
		$values = array();

		// Add contacts to the list of values to display.
		foreach ($contacts as $contact)
		{
			// exclude empty values that are used to hide an old number that was
			// removed from an application
			if (empty($contact['value']))
			{
				continue;
			}

			$normalized_value = strtolower($contact['value']);

			if (empty($values[$contact['type']][$normalized_value]))
			{
				$values[$contact['type']][$normalized_value] = array(
					'fields' => array(),
					'level' => NULL,
					'type' => $contact['type'],
					'value' => $normalized_value,
					'checked' => FALSE,
					'misc' => NULL,
				);
				$flat[] =& $values[$contact['type']][$normalized_value];
			}

			if (!in_array($contact['category'], $values[$contact['type']][$normalized_value]['fields']))
			{
				$values[$contact['type']][$normalized_value]['fields'][] = $contact['category'];
			}
		}

		// Merge the attribute information into the values previously initialized.
		// This will put non-contact attribute information at the bottom.
		foreach ($attributes as $attribute)
		{
			reset($attribute['relation']);
			$normalized_value = strtolower($attribute['value']);

			if (!empty($values[$attribute['type']][$normalized_value]))
			{
				$values[$attribute['type']][$normalized_value]['level'] = key($attribute['relation']);
				$values[$attribute['type']][$normalized_value]['checked'] = TRUE;
				$values[$attribute['type']][$normalized_value]['misc'] = $attribute['misc'];
			}
			elseif ($flag == 'login_lock')
			{
				$values[$attribute['type']][$normalized_value] = array(
					'fields' => array('Application'),
					'level' => key($attribute['relation']),
					'type' => $attribute['type'],
					'value' => $normalized_value,
					'checked' => TRUE,
					'misc' => $attribute['misc'],
				);
				$flat[] =& $values[$attribute['type']][$normalized_value];
			}
		}

		$flag_icons = array(
			'bad_info' => '/image/standard/i_bad_info.gif',
			'do_not_contact' => '/image/standard/i_do_not_contact.gif',
			'best_contact' => '/image/standard/i_best_contact.gif',
			'do_not_market' => '/image/standard/i_do_not_market.gif',
			'high_risk' => '/image/standard/high_risk.gif',
			'fraud' => '/image/standard/fraud.gif',
			'do_not_loan' => '/image/standard/i_do_not_loan.gif',
			'detailed_message' => '/image/standard/message.gif',
			'login_lock' => '/image/standard/login_lock.png',
		);

		return new Site_Response_Json(
			array(
				'application_id' => $application_id,
				'flag' => $flag,
				'flag_display_name' => $application->getAttributes()->getFlagDisplay($flag),
				'flag_img_url' => (!empty($flag_icons[$flag])) ? $flag_icons[$flag] : NULL,
				'values' => $flat,
			)
		);
	}
}

?>
