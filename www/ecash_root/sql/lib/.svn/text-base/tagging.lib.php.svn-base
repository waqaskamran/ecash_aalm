<?php

/**
 * Functions to facilitate the sql operations needed for IMPACT tagging.
 */

/**
 * Prefix to apply to the IG tag type. Please note that this will shorten 
 * the max length for tag names. ALSO, all tags MUST begin with this prefix
 * or they won't be recognized by the tagging system. This is to help deal 
 * with any legacy uses of the tag tables.
 */
define('INVESTOR_GROUP_TAG_PREFIX', 'IG_');

/**
 * Adds a new tag to the database. The spec says this should be done via 
 * support ticket, but it's an easy enough function that I am adding it 
 * in the event that it is decided we will give them access to add their 
 * own tags.
 *
 * @param string $short_name
 * @param string $name
 * @return int The ID of the new tag.
 */
function Create_Tag ($short_name, $name) 
{
	$db = ECash::getMasterDb();
	
	$short_name = INVESTOR_GROUP_TAG_PREFIX.$short_name;
	$company_id = ECash::getCompany()->company_id;
	
	$query = <<<SQL_END
INSERT INTO application_tag_details
	(tag_name, name, description, company_id)
	VALUES ({$db->quote($short_name)}, {$db->quote($name)}, '0', {$db->quote($company_id)})
SQL_END;
	
	$result = $db->exec($query);
	
	if ($result) 
	{
		return $db->lastInsertId();
	} 
	else 
	{
		$company_id = ECash::getCompany()->company_id;
		return false;
	}
}

/**
 * Sets the given tag for the given application.
 * 
 * Returns false if anything other than numbers is passed to the function.
 *
 * @param int $application_id
 * @param int $tag_id
 * @return bool 
 */
function Tag_Application ($application_id, $tag_id) 
{
	$db = ECash::getMasterDb();
	if (!ctype_digit((string)$application_id) || !ctype_digit((string)$tag_id)) 
	{
		return false;
	}
	
	$query = <<<SQL_END
INSERT INTO application_tags
	(tag_id, application_id)
	VALUES ({$tag_id}, {$application_id})
SQL_END;
	
	$result = $db->exec($query);
	
	if ($result) 
	{
		return true;
	} 
	else 
	{
		return false;
	}
}

/**
 * Removes all tags for a given application.
 */
function Remove_Application_Tags($application_id)
{
	$db = ECash::getMasterDb();
	
	$igPrefix = INVESTOR_GROUP_TAG_PREFIX;
	$query = "
		DELETE FROM application_tags 
		WHERE 
			application_id = {$application_id} AND 
			EXISTS (
				SELECT 1
				FROM application_tag_details
				WHERE
					tag_id = application_tags.tag_id AND
					tag_name LIKE '{$igPrefix}%'
			)
	";
	
    $db->exec($query);
}

/**
 * Loads all tags.
 *
 * @return array
 */
function Load_Tags()
{
	$company_id = ECash::getCompany()->company_id;
	
	$db = ECash::getMasterDb();
	$query = "SELECT *
				FROM application_tag_details
				WHERE tag_name LIKE '".INVESTOR_GROUP_TAG_PREFIX."%'
				AND company_id = $company_id
				AND active_status = 'active'
				ORDER BY name";
	$result = $db->query($query);
	
	$tags = array();

	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$row->tag_name = substr($row->tag_name, strlen(INVESTOR_GROUP_TAG_PREFIX));
		$tags[$row->tag_id] = $row;
	}
	
	return $tags;
}

/**
 * Sets the weights for all given tags. The tags and weights are passed as an 
 * associative array using the tag_id as the key and the weight as a value. 
 * This function will not save the data if the passed weights do not add to 
 * 100 or if any of the passed tag_ids could not be found.
 * 
 * In both of those cases an exception is thrown and should be dealt with a 
 * catch in the code.
 *
 * @param array $tag_weights
 */
function Set_Tag_Weights($tag_weights) 
{
	$db = ECash::getMasterDb();
	$total_weights = 0;
	
	foreach ($tag_weights as $weight) 
	{
		$total_weights += intval($weight);
	}
	
	if ($total_weights != 100) 
	{
		throw new Tagging_BadWeights_Exception("The given weights added up to {$total_weights}. They must add to 100 to properly save.");
	} 
	else 
	{
		// GF 9241: no need to quote description, done by $db->quote. [benb]
		$query = "
		UPDATE 
			application_tag_details
		SET 
			description = ?
		WHERE 
			tag_id = ?
		";		

		try 
		{
			foreach ($tag_weights as $tag_id => $weight) 
			{
				$st  = $db->queryPrepared($query, array(intval($weight), $tag_id));

				if (!$st) 
				{
					throw new Tagging_BadWeights_Exception("Could not find a given tag.");
				}
			}
		} 
		catch (Exception $e) 
		{
			throw $e;
		}
	}
}

/**
 * Returns a tag weight map to map wieghts to tag_ids. This will NOT return 
 * any tags whoses weights are 0 or are inactive.
 *
 * @return array
 */
function Load_Tag_Weight_Map() 
{
	$db = ECash::getMasterDb();
	$company_id = ECash::getCompany()->company_id;
	
	$query = "SELECT
				tag_id,
				description
			  FROM application_tag_details
			  WHERE company_id = $company_id
			  AND active_status = 'active'
			  AND tag_name LIKE '".INVESTOR_GROUP_TAG_PREFIX."%'
			  AND description <> 0";
	$result = $db->query($query);
	
	$weight_map = array();
	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$weight_map[$row->tag_id] = $row->description;
	}
	
	return $weight_map;
}

/**
 * Creates an array for use by the tagging system to keep track of 
 * distribution in the currenent tagging session. (Each Batch)
 *
 * @param unknown_type $tag_weights
 * @return unknown
 */
function Create_Distribution_Array($tag_weights) 
{
	return array_combine(array_keys($tag_weights), array_fill(0, count($tag_weights), 0));
}

/**
 * Calculates the next tag based on the the tag weights and current 
 * distributions
 *
 * @param int $application_id
 * @param double $loan_amount
 * @param array $tag_weights
 * @param array $current_distribution
 */
function Assign_Tag($application_id, $loan_amount, $tag_weights, &$current_distribution) 
{
	$deltas = array();
	
	$total_distribution = array_sum($current_distribution);
	$percentage_distribution = array();
	foreach ($current_distribution as $tag_id => $amount) 
	{
		$percentage_distribution[$tag_id] = $total_distribution ? (100 * ($amount / $total_distribution)) : 0;
	}
	
	foreach ($tag_weights as $tag_id => $target_weight) 
	{
		$deltas[$tag_id] = $target_weight - $percentage_distribution[$tag_id];
	}
	
	//reverse sort the deltas (greatest first) and pull the key for the 
	//first value.
	arsort($deltas, SORT_NUMERIC);
	reset($deltas);
	list($new_tag, $junk) = each($deltas);
	
	//Tag the application
	Tag_Application($application_id, $new_tag);
	
	//Update the distribution
	$current_distribution[$new_tag] += $loan_amount;
	
	return $new_tag;
}


class Tagging_BadWeights_Exception extends Exception {
	
}
?>
