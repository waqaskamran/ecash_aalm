<?php
/**
 * @package ApplicationContact
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Mar 1, 2007
 *
 * @version $Revision$
 */

class eCash_Application_Contact 
{

	static public $types = array(
		'phone',
		'fax',
		'email',
	);

	protected $db;

	protected $application_id;

	/**
	 * array used to store the attribute_ids for the available flags
	 *
	 * @var array
	 */
	private $flag_attribute_ids = array();

	/**
	 * Associative array of application field => application_contact category names
	 * @var array
	 */
	public $application_contact_types = array(
		'phone_home' => 'Home Phone',
		'phone_cell' => 'Cell Phone',
		'phone_work' => 'Work Phone'
	);

	/**
	 * Associate category names with internal ids. The ids are actually only used internally to the
	 * input form, as the table itself is storing them as strings (but we don't want users to use
	 * just any name, so this is what we work with.
	 * @var array
	 */
	private $category_types = array(
		1 => 'Home Phone',
		2 => 'Cell Phone',
		3 => 'Work Phone',
		0 => 'Other Phone',
		4 => 'Personal Reference'
	);

	/**
	 * Initial values for the flags array, this array is modified upon construction to reflect the actual database.
	 * Associate application_field_attribute field names => external user-friendly names
	 * @var array
	 */
	private $flags = array(
		'' => 'None',
		'bad_info' => 'Bad Info',
		'do_not_contact' => 'Do Not Contact',
		'best_contact' => 'Best Contact',
		'do_not_market' => 'Do Not Market',
		'do_not_loan' => 'Do Not Loan',
		'high_risk' => 'High Risk',
		'fraud' => 'Fraud'
	);

	public function __construct($application_id)
	{
		$this->db = ECash::getMasterDb();
		if ($application_id) $this->application_id = $application_id;

		$this->flag_attribute_ids = $this->getApplicationFieldAttributes($this->flags);

		// @todo is it necessary to remove these?
		foreach ($this->flags as $key=>$value)
		{
			if(!in_array($key, $this->flag_attribute_ids))
			{
				unset($this->flags[$key]);
			}
		}
	}

	public function getCategories()
	{
		return $this->category_types;
	}

	public function getFlags()
	{
		return $this->flags;
	}

	public function getFlagAttributeIDs()
	{
		return $this->flag_attribute_ids;
	}

	public function addContact($application_id, $value, $category, $type = 'phone', $notes = "", $global_existing_check=FALSE)
	{
		// Search for the number in all categories and in the application table.
		// If it is found use that for the category instead.
		if ($global_existing_check && $type == 'phone')
		{
			$existing = $this->getContactByNumber($value);

			if (!empty($existing))
			{
				if (is_numeric($existing['application_contact_id']))
				{
					return $existing['application_contact_id'];
				}

				$category = $existing['category'];
			}
		}

		if (!$global_existing_check || $type != 'phone')
		{
			$chk_query = "
				SELECT application_contact_id
				FROM application_contact
				WHERE
					application_id = ? AND
					type = ? AND
					category = ? AND
					value = ?
			";
			$id = $this->db->querySingleValue($chk_query, array($application_id, $type, $category, $value));				

			if ($id !== FALSE)
			{
				return $id;
			}
		}

		$query = "
			INSERT INTO application_contact
			(application_id, type, category, value, notes)
			VALUES (?, ?, ?, ?, ?)
		";
		$this->db->queryPrepared($query, array($application_id, $type, $category, $value, $notes));
		return $this->db->lastInsertId();
	}

	public function addAttribute($contact_id, $attribute)
	{
		$query = "
			UPDATE application_contact
			SET
				application_field_attribute_id = (
					SELECT application_field_attribute_id
					FROM application_field_attribute
					WHERE field_name = ?
				)
			WHERE application_contact_id = ?
		";
		$this->db->queryPrepared($query, array($attribute, $contact_id));
	}

	//delete all currently existing attributes linked to the table/column/record, since THERE CAN BE ONLY ONE!
	private function deleteExistingAttributes($application_id, $table_name, $column_name)
	{
		$query = "
			DELETE FROM
				application_field
			WHERE table_row_id = ?
				AND table_name = ?
				AND column_name = ?
				AND application_field_attribute_id IN ('". implode("','", array_keys($this->flag_attribute_ids))."')
		";
		$this->db->queryPrepared($query, array($application_id, $table_name, $column_name));
	}

	private function updateExistingAttribute($application_id, $table_name, $column_name, $attribute_id)
	{
		// @todo remove session use
		$query = "
			UPDATE application_field
			SET
				application_field_attribute_id = ?,
				date_modified = now(),
				agent_id = ?
			WHERE  table_row_id = ?
				AND table_name = ?
				AND column_name = ?
				AND application_field_attribute_id IN ('". implode("','", array_keys($this->flag_attribute_ids))."')
		";
		$this->db->queryPrepared(
			$query,
			array(
				ECash::getCompany()->company_id,
				$application_id,
				$table_name,
				$column_name,
				$attribute_id,
				ECash::getAgent()->getAgentId(),
			)
		);
	}

	private function addNewAttribute($application_id, $table_name, $column_name, $attribute_id)
	{
		// @todo remove session use
		$query = "
			INSERT INTO application_field
			(
				date_modified,
				date_created,
				company_id,
				table_row_id,
				table_name,
				column_name,
				application_field_attribute_id,
				agent_id
			)
			VALUES (now(), now(), ?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
				date_modified = now()
		";
		$this->db->queryPrepared(
			$query,
			array(
				ECash::getCompany()->company_id,
				$application_id,
				$table_name,
				$column_name,
				$attribute_id,
				ECash::getAgent()->getAgentId(),
			)
		);
	}

	//adds an attribute to the contact application table's contact info.
	public function addApplicationAttribute($application_id, $table_name, $column_name, $attribute_id)
	{
		$query = "
			SELECT COUNT(*) AS count
			FROM application_field
			WHERE table_row_id = ?
				AND table_name = ?
				AND column_name = ?
			AND application_field_attribute_id IN ('". implode("','", array_keys($this->flag_attribute_ids))."')
		";
		$count = $db->querySingleValue($query, array($application_id, $table_name, $column_name));

		if ($count == 1)
		{
			$this->updateExistingAttribute($application_id, $table_name, $column_name, $attribute_id);
		}
		else
		{
			if ($count)
			{
				$this->deleteExistingAttributes($application_id, $table_name, $column_name);
			}
			$this->addNewAttribute($application_id, $table_name, $column_name, $attribute_id);
		}
	}

	/**
	 * @todo FIX ME!!!
	 */
	public function updateContact($contact_id, $fields, $replaceNote = FALSE)
	{
		if (!is_Array($fields)) 
		{
			return FALSE;
		}
		$sec = array();
		foreach ($fields as $field_name => $value) 
		{

			if ($value === NULL) 
			{
				$value = "";
			} 
			elseif (!in_array($field_name, array("application_field_attribute_id", "attribute"))) 
			{
				$value = $this->db->quote($value);
			}

			switch($field_name) 
			{
				case "type":
					if (!in_array($value, self::$types)) 
					{
						throw new InvalidArgumentException(__METHOD__ . " Error: {$value} is not a valid contact type");
					}
				case "category":
				case "value":
				case "application_field_attribute_id":
					break;

				case "number":
					$field_name = "value";
					break;

				case "notes":
					if ($replaceNote == FALSE) 
					{
						$value = "concat(notes,{$value})";
					}
					break;

				case "attribute":
					$this->addAttribute($contact_id, $attribute);

				default:
					continue 2;
			}

			$sec[] = "{$field_name} = {$value}";
		}

		if (!count($sec)) 
		{
			return FALSE;
		}

		// @todo prepare this?
		$query = "
			UPDATE application_contact
			SET " . implode(", ", $sec). "
			WHERE
				application_contact_id = {$contact_id}
		";
		$this->db->exec($query);
	}

	public function deleteContact($contact_id)
	{
		$query = "
			DELETE FROM application_contact
			WHERE
				application_contact_id = {$contact_id}
		";
		$this->db->exec($query);
	}

	public function updateApplicationContact($application_id,$contact_type,$number,$attribute=null)
	{
		if (!is_null($attribute))
		{
			$this->addApplicationAttribute($application_id, 'application', $contact_type, $attribute);
		}

		$query = "
			UPDATE application
			SET {$contact_type} = ?
			WHERE application_id = ?
		";
		$this->db->queryPrepared($query, array($number, $application_id));
	}

	public function getContact($contact_id)
	{
		$query = "
			SELECT
				ac.application_id,
				ac.type,
				ac.category,
				ac.value,
				ac.notes
				fa.field_name as attribute
			FROM application_contact ac
				LEFT JOIN application_field_attribute fa
					ON (ac.application_field_attribute_id = af.application_field_attribute_id)
			WHERE
				ac.application_contact_id = ?
		";
		return $this->db->querySingleRow($query, array($contact_id));
	}

	public function getApplicationContacts($application_id, $types = NULL)
	{
		if (is_array($types)) 
		{
			$where = " AND type IN ('" . implode("','",$types) . "')";
		} 
		elseif ($types != NULL) 
		{
			$where = " AND type = '{$types}'";
		}

		$query = "
			SELECT
				ac.application_contact_id,
				ac.type,
				ac.category,
				ac.value,
				ac.notes
				fa.field_name as attribute
			FROM
				application_contact ac
				LEFT JOIN application_field_attribute fa
					ON ac.application_field_attribute_id = af.application_field_attribute_id
			WHERE
				ac.application_id = {$application_id}
				{$where}
		";
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$ret[$row->application_contact_id] = $row;
		}

		return $ret;
	}


	//functions from ApplicationContactInformation
	//////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////


	public function getContactInformation($application_contact_id=NULL)
	{
		// We need to look in the application table for this information
		if (!empty($this->application_contact_types[$application_contact_id]))
		{
			// Get informaton from application table first, then check if it also exists in the
			// application_contact table too.
			return $this->getContactInformationByApplicationField($application_contact_id);
		}

		// We need to look in the application_contact table
		else if (!empty($application_contact_id))
		{
			// Check the application_contact table for the information, then see if it's in the
			// application table under the same name as well.
			return $this->getContactInformationByContactId($application_contact_id);
		}

		// Load all contact information for the application
		else
		{
			// Load all of the application contact information.
			// Start with the application table, then load it from the application_contact table.
			// Check for duplicates on the way through the application_contact table
			return $this->getAllContactInformation();
		}
	}

	/**
	 * Validate contact information; this only checks the data.
	 */
	public function validateContactInformation(&$contact)
	{
		$errors = array();

		// Only use digits from the phone number; strip everything else.
		$contact['number'] = preg_replace('/\D/', '', $contact['number']);
		if (strlen($contact['number']) != 10)
		{
			$errors['number'] = 'Invalid';
		}

		// Make sure the category given is a valid one we'll accept.
		if (empty($this->category_types[$contact['category']]))
		{
			$errors['category'] = 'Invalid';
		}

		// Check the flag attribute field name to see if it exists.
		if (!empty($contact['flag']) && empty($this->flags[$contact['flag']]))
		{
			$errors['flag'] = 'Invalid';
		}

		return $errors;
	}

	/**
	 * Set contact information based on an array of information in the format provided by
	 * getContactInformation.
	 *
	 * Commented code in this function is a conversion from internal methods to using the Contact
	 * interface instead.
	 */
	public function setContactInformation($contact)
	{
		// If there was an error do nothing.
		$errors = $this->validateContactInformation($contact);
		if (!empty($errors))
		{
			return $errors;
		}

		// Replace the category id with the full text, since the databases uses only the string.
		$contact['category'] = $this->category_types[$contact['category']];

		// Replace the flag with its correct application_field_attribute_id
		$contact['flag'] = array_search($contact['flag'], $this->flag_attribute_ids);

		// This is/was a application contact.
		// This means we'll need to get the original contact, then create a application_contact row
		if (!empty($this->application_contact_types[$contact['application_contact_id']]))
		{
			$existing = $this->getContactInformationByApplicationField($contact['application_contact_id']);

			if ($existing['application_contact_id'] == $contact['application_contact_id'])
			{
				$action = 'app update';

			}
			else
			{
				$action = 'update';
			}
		}

		// This is an application_contact row.
		else if (!empty($contact['application_contact_id']))
		{
			$existing = $this->getContactInformationByContactId($contact['application_contact_id']);

			// This wasn't /really/ an existing row!
			if (empty($existing))
			{
				$action = 'new';
			}
		}

		// At this point we think this isn't an existing contact.
		// Just to be sure, though, let's get all of them and compare for existing numbers.
		else
		{
			$existing_contacts = $this->getAllContactInformation();

			// See if the number matches an existing contact.
			foreach ($existing_contacts as $a_contact)
			{
				// We have an existing contact
				if ($a_contact['number'] == $contact['number']
				&& $a_contact['category'] == $contact['category'])
				{
					// It belongs to the application. Force a new row to be added.
					if (!empty($this->application_contact_types[$a_contact['application_contact_id']]))
					{
						$action = 'new';
					}

					// It's actually in the application_contact row; update
					else
					{
						$action = 'update';
					}

					$existing = $a_contact;
					break;
				}
			}

			$action = 'new';
		}

		if (empty($action))
		{
			// Insert a new row on these conditions:
			if ($existing['application_contact_id'] != $contact['application_contact_id'])
			{
				$action = 'new';
			}
			else
			{
				$action = 'update';
			}
		}
		if (empty($existing))
		{
			$action = 'new';
		}
		switch ($action)
		{
			case 'app update':
				$this->updateApplicationContact($contact['application_id'],$contact['application_contact_id'],$contact['number'],$contact['flag']);

				break;
			case 'new':


				$contact_id = $this->addContact(
				$contact['application_id'],
				$contact['number'],
				$contact['category'],
				'phone',
				$contact['notes']);


				if (!empty($contact_id))
				{
					$this->updateContact(
					$contact_id,
					array('application_field_attribute_id' => $contact['flag']));
				}
				break;

			case 'update':
				foreach ($existing as $field => $field_value)
				{
					if ($field_value != $contact[$field])
					{
						$field_changes[$field] = $contact[$field];
					}
				}

				// Careful here, we're checking one new flag vs many existing flags
				if (!in_array($this->flag_attribute_ids[$contact['flag']], $existing['flags']))
				{
					$field_changes['application_field_attribute_id'] = $contact['flag'];
				}

				// Nothing was changed, so do nothing.
				if (!$field_changes)
				{
					break;
				}

				$this->updateContact(
				$existing['application_contact_id'],
				$field_changes,
				TRUE);

				break;
		}

		return array();
	}

	/**
	 * Look in both the application table and the application_contact table for a specific number.
	 */
	public function getContactByNumber($number)
	{
		$contacts = $this->getContactInformation();
		foreach ($contacts as $contact)
		{
			if ($contact['number'] == $number)
			{
				return $contact;
			}
		}

		return array();
	}

	/**
	 * Get contact information by application field. Also checks application_contact for matching
	 * contact information and returns it instead if so.
	 */
	public function getContactInformationByApplicationField($field)
	{
		$contact = $this->getApplicationContactInformation($field);

		$query = "
			SELECT app_con.application_contact_id,
				app_con.notes,
				app_fi_att.field_name AS 'flag'
			FROM application_contact AS app_con
				LEFT JOIN application_field_attribute AS app_fi_att
					ON (app_con.application_field_attribute_id = app_fi_att.application_field_attribute_id)
			WHERE application_id = ?
				AND value = ?
				AND category = ?
		";
		$st = $this->db->queryPrepared($query, array($this->application_id, $contact['number'], $contact['category']));

		if (($row = $st->fetch(PDO::FETCH_ASSOC)) !== FALSE)
		{
			$contact['application_contact_id'] = $row['application_contact_id'];
			$contact['notes'] = $row['notes'];
			if ($row['flag']) $contact['flags'] = $row['flag'];
		}

		return $contact;
	}

	/**
	 * Get information from application table about a field. Only returns application information
	 */
	private function getApplicationContactInformation($field)
	{
		$query = "
			SELECT app.{$field},
				app_fi_att.field_name AS 'flag'
			FROM application AS app
				LEFT JOIN application_field AS app_fi
					ON (app_fi.table_name = 'application'
					AND app_fi.column_name = ?
					AND app_fi.table_row_id = ?
				LEFT JOIN application_field_attribute AS app_fi_att
					ON (app_fi.application_field_attribute_id = app_fi_att.application_field_attribute_id)
			WHERE application_id = ?
		";
		$st = $this->db->queryPrepared($query, array($field, $this->application_id, $this->application_id));

		if (($contact = $st->fetch(PDO::FETCH_ASSOC)) !== FALSE)
		{
			$contact = $c;

			// replace flags with the other rows...
			while ($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				if (!empty($row['flag']))
				{
					$contact['flags'][] = $row['flag'];
				}
			}
			return $contact;
		}
		return array();
	}

	/**
	 * Returns contact information based on contact id. Also checks if information matches
	 * application table and returns its flags as well.
	 */
	public function getContactInformationByContactId($application_contact_id)
	{
		$contact = $this->getContactTableInformation($application_contact_id);

		$field_name = array_search($application_contact_id, $this->application_contact_types);
		if ($field_name)
		{
			$application_contact = $this->getApplicationContactInformation($field_name);
			$contact['flags'] = array_merge($contact['flags'], $application_contact['flags']);
		}

		return $contact;
	}

	public function getApplicationFieldAttributes($flags)
	{
		$query = "
			SELECT application_field_attribute_id,
				application_field_attribute.field_name AS 'flag'
			FROM
				application_field_attribute
			WHERE field_name IN ('" .implode("','",array_keys($flags)) . "')
		";
		$st = $this->db->query($query);

		$flag_ids = array(0 => '');

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$flag_ids[$row['application_field_attribute_id']] = $row['flag'];
		}

		return $flag_ids;
	}
	/**
	 * Get information from the application_contact table ONLY. All or one.
	 */
	private function getContactTableInformation($application_contact_id=NULL)
	{
		$contacts = array();
		$args = array($this->application_id);

		$query = "
			SELECT app_con.application_contact_id,
				app_con.application_id,
				app_con.category,
				app_con.value AS 'number',
				app_con.notes,
				app_fi_att.field_name AS 'flag'
			FROM application_contact AS app_con
				LEFT JOIN application_field_attribute AS app_fi_att
					ON (app_con.application_field_attribute_id = app_fi_att.application_field_attribute_id)
			WHERE type = 'phone'
				AND application_id = ?
		";
		if (!empty($application_contact_id))
		{
			$query .= " AND application_contact_id = ?";
			$args[] = $application_contact_id;
		}

		$st = $this->db->queryPrepared($query, $args);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// @todo gay
			$row['flags'] = array();
			if ($row['flag'])
			{
				$row['flags'][] = $row['flag'];
				unset($row['flag']);
			}

			$row['application_id'] = $this->application_id;
			$contacts[$row['application_contact_id']] = $row;
		}

		if (!empty($application_contact_id))
		{
			return $contacts[$application_contact_id];
		}

		return $contacts;
	}

	/**
	 * Get all contact information from both application and application_contact tables.
	 * Merges results on matching category and number.
	 */
	public function getAllContactInformation()
	{
		$fields = array_keys($this->application_contact_types);

		$query = "
			SELECT ".implode(', ', $fields)."
			FROM application
			WHERE application_id = ?
		";
		$row = $this->db->querySingleRow($query, array($this->application_id));

		if ($row !== FALSE)
		{
			foreach ($this->application_contact_types as $field=>$cat)
			{
				$app_contacts[$field] = array(
					'application_contact_id' => $field,
					'application_id' => $this->application_id,
					'category' => $cat,
					'number' => $row[$field],
					'notes' => 'N/A',
					'primary' => '1',
					'flags' => array()
				);
			}
		}

		// Get application contact information flags
		if (!empty($app_contacts))
		{
			$in_fields = "'" . implode("', '", $fields) . "'";
			$query = "
				SELECT app_fi.column_name,
					app_fi_att.field_name AS 'flag'
				FROM application_field AS app_fi
					LEFT JOIN application_field_attribute AS app_fi_att
						ON (app_fi.application_field_attribute_id = app_fi_att.application_field_attribute_id)
				WHERE app_fi.table_name = 'application'
					AND app_fi.table_row_id = ?
					AND app_fi.column_name IN ({$in_fields})";
			$st = $this->db->queryPrepared($query, array($this->application_id));

			while ($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				$app_contacts[$row['column_name']]['flags'][] = $row['flag'];
			}
		}

		// Get contact table contacts
		$contacts = $this->getContactTableInformation();

		// Merge application_contact and application table contacts
		if (!empty($app_contacts))
		{
			$prepend_contacts = $this->application_contact_types;
			foreach ($contacts as $key => $contact)
			{
				$field = array_search($contact['category'], $this->application_contact_types);
				if ($field && !empty($app_contacts[$field])
				&& $app_contacts[$field]['number'] == $contact['number'])
				{
					// Move that specific entry to the front of the array, then unset the other
					$contact['flags'] = array_merge($contact['flags'], $app_contacts[$field]['flags']);
					$prepend_contacts[$field] = $contact;
					unset($app_contacts[$field], $contacts[$key]);
				}
			}

			foreach ($app_contacts as $field => $contact)
			{
				$prepend_contacts[$field] = $contact;
			}

			$contacts = array_merge($prepend_contacts, $contacts);
		}

		return $contacts;
	}
}

?>
