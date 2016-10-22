<?php
/**
 * The Vehicle Data class is intended as an interface for retrieving and updating
 * an Applicant's vehicle data for "Title" loan types.  It may also be an appropriate
 * place to interface with the NADA lookup data in the near future.
 */
require_once(ECASH_COMMON_DIR . 'nada/NADA.php');

class Vehicle_Data
{
	/**
	 * Retrieve's the vehicle data for a particular applicant.  The application_id
	 * field is unique in this table so we know we'll only ever return one row.
	 *
	 * @param int $application_id
	 * @return object stdClass object containing vehicle data
	 */
	public function fetchVehicleData($application_id)
	{
		$query = "
       		SELECT
       				vehicle_id,
       				vin           AS vehicle_vin,
       				license_plate AS vehicle_license_plate,
       				title_state   AS vehicle_title_state,
       				make          AS vehicle_make,
       				model         AS vehicle_model,
       				series        AS vehicle_series,
       				style         AS vehicle_style,
       				color         AS vehicle_color,
       				year          AS vehicle_year,
       				mileage       AS vehicle_mileage,
       				value         AS vehicle_value
       		FROM vehicle
       		WHERE application_id = {$application_id} ";

		$db = ECash::getMasterDb();

		$result = $db->query($query);

		$data = $result->fetch(PDO::FETCH_OBJ);

		$biz_rules = new ECash_BusinessRulesCache($db);
		$rule_set_id = $biz_rules->Get_Rule_Set_Id_For_Application($application_id);
		$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		if(!empty($data))
		{
			$edit_info = self::Get_Vehicle_Edit_Info($data, $rules);
			$vehicle_data = (object) array_merge((array) $data, (array) $edit_info);
		}
		else
		{
			//Create empty vehicle data
			$empty = new stdClass();
			$empty->vehicle_id = NULL;
			$empty->vehicle_vin = NULL;
			$empty->vehicle_license_plate = NULL;
			$empty->vehicle_title_state = NULL;
			$empty->vehicle_make = NULL;
			$empty->vehicle_model = NULL;
			$empty->vehicle_series = NULL;
			$empty->vehicle_style = NULL;
			$empty->vehicle_color = NULL;
			$empty->vehicle_year = NULL;
			$empty->vehicle_mileage = NULL;
			$empty->vehicle_value = 0;

			$edit_info = self::Get_Vehicle_Edit_Info($empty, $rules);
			$vehicle_data = (object) array_merge((array) $empty, (array) $edit_info);
		}

		return $vehicle_data;
	}

	public function Update_Vehicle_Data($request)
	{
		// Check to make sure there is a record to update
		$prev_data = self::fetchVehicleData($request->application_id);
		$agent_id = Fetch_Current_Agent();
		$db = ECash::getMasterDb();
		
		if($prev_data->vehicle_id)
		{
			$query = "
	       		UPDATE
	       			vehicle
	       		SET
	       			vin = '{$request->vehicle_vin}',
	       			license_plate = '{$request->license_plate}',
	       			title_state = '{$request->title_state}',
	       			make = (SELECT IFNULL((SELECT make FROM nada_vehicle_description WHERE vic_make = '{$request->vehicle_make}' LIMIT 1), '')),
	       			model = (SELECT IFNULL((SELECT model FROM nada_vehicle_description WHERE vic_series = '{$request->vehicle_series}' AND vic_make = '{$request->vehicle_make}' LIMIT 1), '')),
	       			series = (SELECT IFNULL((SELECT series FROM nada_vehicle_description WHERE vic_series = '{$request->vehicle_series}' AND vic_make = '{$request->vehicle_make}' AND vic_year='{$request->vehicle_year}' LIMIT 1), '')),
	       			style= (SELECT IFNULL((SELECT body FROM nada_vehicle_description WHERE vic_body = '{$request->vehicle_body}' AND vic_series = '{$request->vehicle_series}' AND vic_make = '{$request->vehicle_make}' LIMIT 1), '')),
	       			year = '{$request->vehicle_year}',
	       			mileage = '{$request->mileage}',
	       			value ='{$request->value}',
	       			modifying_agent_id = '{$agent_id}'
       		WHERE application_id = {$request->application_id} ";

			$result = $db->query($query);
			return ($result->rowCount() > 0);
		}
		else
		{
			//Insert new Vehicle data
			$query = "
	       	INSERT
	       	INTO
	       		vehicle
	       	(vin, license_plate, title_state, make, model, series, style, year, mileage, value, application_id,modifying_agent_id)
	       	VALUES(
	       		'{$request->vehicle_vin}',
	       		'{$request->license_plate}',
	       		'{$request->title_state}',
	       		(SELECT IFNULL((SELECT make FROM nada_vehicle_description WHERE vic_make = '{$request->vehicle_make}' LIMIT 1), '')),
	       		(SELECT IFNULL((SELECT model FROM nada_vehicle_description WHERE vic_series = '{$request->vehicle_series}' AND vic_make = '{$request->vehicle_make}' LIMIT 1), '')),
	       		(SELECT IFNULL((SELECT series FROM nada_vehicle_description WHERE vic_series = '{$request->vehicle_series}' AND vic_make = '{$request->vehicle_make}' AND vic_year='{$request->vehicle_year}' LIMIT 1), '')),
	       		(SELECT IFNULL((SELECT body FROM nada_vehicle_description WHERE vic_body = '{$request->vehicle_body}' AND vic_series = '{$request->vehicle_series}' AND vic_make = '{$request->vehicle_make}' LIMIT 1), '')),
	       		'{$request->vehicle_year}',
	       		'{$request->mileage}',
	       		'{$request->value}',
	       		'{$request->application_id}',
	       		 '{$agent_id}')
	       		";
	       	$result = $db->query($query);
	       	return ($result->rowCount() > 0);
		}
	}
	
	protected function Get_Vehicle_Edit_Info($data, $rules)

	{
		$db = ECash::getMasterDb();
		
		$nada = new NADA_API($db);

		$edit_info = new stdClass();
		
		if(isset($rules['minimum_vehicle_year']))
		{
			$min_year = $rules['minimum_vehicle_year'];
		}
		else
		{
			$min_year = NULL;
		}
		
//		 Generate the vehicle info editing fields
		$edit_info->edit_vehicle_year .= "<select id=\"vehicle_year\" name=\"vehicle_year\" onChange=\"getMakes(this.value);\">";
		$edit_info->edit_vehicle_year .= self::Create_Options_From_Array($nada->getYears($min_year), $data->vehicle_year, FALSE);
		$edit_info->edit_vehicle_year .= "</select>";

		$edit_info->edit_vehicle_make .= "<select id=\"vehicle_make\" name=\"vehicle_make\" onChange=\"getSeries(this.value);\">";
		$edit_info->edit_vehicle_make .= self::Create_Options_From_Array($nada->getMakes(), $data->vehicle_make);
		$edit_info->edit_vehicle_make .= "</select>";

		$edit_info->edit_vehicle_series .= "<select id=\"vehicle_series\" name=\"vehicle_series\" onChange=\"getBodies();\">";
		$edit_info->edit_vehicle_series .= self::Create_Options_From_Array($nada->getSeries($data->vehicle_year, NULL, $data->vehicle_make), $data->vehicle_series);
		$edit_info->edit_vehicle_series .= "</select>";

		$edit_info->edit_vehicle_body .= "<select id=\"vehicle_body\" name=\"vehicle_body\" onChange=\"getVehicleValue();\">";
		$edit_info->edit_vehicle_body .= self::Create_Options_From_Array($nada->getBodies($data->vehicle_year, NULL, NULL, $data->vehicle_make, $data->vehicle_series), $data->vehicle_style);
		$edit_info->edit_vehicle_body .= "</select>";

		$edit_info->edit_vehicle_state .= "<select id=\"title_state\" name=\"title_state\" onChange=\"getVehicleValue();\">";
		$edit_info->edit_vehicle_state .= self::Create_Options_From_Array($nada->getRegions(TRUE), $data->vehicle_title_state);
		$edit_info->edit_vehicle_state .= "</select>";
		
		$edit_info->edit_vehicle_vin = "<input type=text maxlength=17 id=\"vehicle_vin\" name=\"vehicle_vin\" value=\"{$data->vehicle_vin}\" onChange=\"getVehicleValueFromVIN(this.value);\" onblur = \"return strip_all_but(this,keybPureAlphaNumeric,((window.event)?window.event:event));\" onkeypress=\"return editKeyBoard(this,keybPureAlphaNumeric,((window.event)?window.event:event));\">";
		return $edit_info;
	}

	protected function Create_Options_From_Array($arrVals, $selected_option = NULL, $keys_as_values = TRUE)
	{
		$html = "<option value=\"\"></option>";
		$selected_option = strtolower(trim($selected_option));

		$selected = array($selected_option => " SELECTED");
		if(!$arrVals){ return; }
		foreach($arrVals as $index => $value)
		{
			if(is_array($value))
			{
				foreach($value as $val)
				{
					$val = trim($val);
					$index = ($keys_as_values) ? trim($index) : trim($val);
					if(!empty($index)) $html .= "<option value=\"{$index}\"{$selected[strtolower($val)]}>{$val}</option>";
				}
			}
			else
			{
				$value = trim($value);
				$index = ($keys_as_values) ? trim($index) : trim($value);
				$selected_val = isset($selected[strtolower($value)]) ? $selected[strtolower($value)] : null;
				if(!empty($index)) $html .= "<option value=\"{$index}\"$selected_val>{$value}</option>";	
			}
		}
		return $html;
	}
}
