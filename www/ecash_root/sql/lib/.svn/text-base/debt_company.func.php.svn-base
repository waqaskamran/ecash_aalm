<?php
function Get_Debt_Companies()
{
	$db = ECash::getMasterDb();
	$companies = array();
		
	$query = "
			SELECT
				company_id,
				company_name,
				address_1,
				address_2,
				city,
				state,
				zip_code,
				contact_phone
			FROM
				debt_company
			ORDER BY
				company_name";
	$result = $db->query($query);
	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$companies[$row->company_id] = $row;
	}

	return $companies;
}

function Get_Debt_Company($id)
{
	$db = ECash::getMasterDb();
	$company = null;
		
	$query = "
			SELECT
				company_id,
				company_name,
				address_1,
				address_2,
				city,
				state,
				zip_code,
				contact_phone
			FROM
				debt_company
			where company_id = $id";
	return $db->querySingleRow($query);
}

function Assoc_Event_Debt_Company($dc_id, $ev_id)
{
	$db = ECash::getMasterDb();
	$query = "INSERT INTO debt_company_event_schedule (company_id,event_schedule_id) VALUES ($dc_id,$ev_id)";
	if($result = $db->exec($query))
	{
		return true;
	}

	return false;
}

function Add_Debt_Company($company_name, $address_1, $address_2, $city, $state, $zip_code, $contact_phone)
{
	$db = ECash::getMasterDb();
	$query = "
		INSERT INTO debt_company 
			(company_name,address_1,address_2,city,state,zip_code,contact_phone) 
		VALUES
			('".addslashes($company_name)."','".addslashes($address_1)."','".addslashes($address_2)."','".addslashes($city)."','".addslashes($state)."','$zip_code','".addslashes($contact_phone)."')";

	if($result = $db->exec($query))
	{
		return true;
	}

	return false;
}

function Edit_Debt_Company($company_id,$company_name, $address_1, $address_2, $city, $state, $zip_code, $contact_phone)
{
	$db = ECash::getMasterDb();
	$query = "
		UPDATE debt_company
			SET
			company_name = '".addslashes($company_name)."',
			address_1 = '".addslashes($address_1)."',
			address_2 = '".addslashes($address_2)."',
			city = '".addslashes($city)."',
			state = '".addslashes($state)."',
			zip_code = '$zip_code',
			contact_phone = '".addslashes($contact_phone)."'
		WHERE company_id = $company_id";

	if($result = $db->exec($query))
	{
		return true;
	}

	return false;
}
?>
