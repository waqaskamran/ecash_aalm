<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

class Display_View extends Admin_Parent
{
        private $ach_providers;
	private $states;
        private $app_statuses;
        private $abas;

        public function __construct(ECash_Transport $transport, $module_name)
        {
                parent::__construct($transport, $module_name);
                $returned_data = ECash::getTransport()->Get_Data();
                $this->ach_providers = $returned_data->ach_providers;
		$this->states = $returned_data->states;
                $this->app_statuses = $returned_data->app_statuses;
                $this->abas = $returned_data->abas;
        }

        public function Get_Module_HTML()
        {
                switch ( ECash::getTransport()->Get_Next_Level() )
                {
                        case 'default':
                        default:
                                $fields = new stdClass();

                                $fields->ach_provider_list = "";
                                if (count($this->ach_providers) > 0)
                                {
                                        foreach ($this->ach_providers as $ach_provider)
                                        {
                                                //Fund Dates, Interval
						$ach_fund_dates_start = $ach_fund_dates_end = NULL;
						if (!empty($ach_provider->ach_fund_dates_start))
							$ach_fund_dates_start = date("m/d/Y", strtotime($ach_provider->ach_fund_dates_start));
						if (!empty($ach_provider->ach_fund_dates_end))
							$ach_fund_dates_end = date("m/d/Y", strtotime($ach_provider->ach_fund_dates_end));

                                                //Event Dates, Weekdays
						$weekdays = explode(",", $ach_provider->ach_event_dates_weekdays);
						$weekdays_string = "";
						foreach($weekdays as $weekday)
						{
							$weekdays_string .= "<option value='" . $weekday . "'>" . $weekday . "</option>";
						}
                                                
                                                //Event Dates, Weekdays
						$monthdays = explode(",", $ach_provider->ach_event_dates_monthdays);
						$monthdays_string = "";
						foreach($monthdays as $monthday)
						{
							$monthdays_string .= "<option value='" . $monthday . "'>" . $monthday . "</option>";
						}

                                                //Event Dates, Interval
						$ach_event_dates_start = $ach_event_dates_end = NULL;
						if (!empty($ach_provider->ach_event_dates_start))
							$ach_event_dates_start = date("m/d/Y", strtotime($ach_provider->ach_event_dates_start));
						if (!empty($ach_provider->ach_event_dates_end))
							$ach_event_dates_end = date("m/d/Y", strtotime($ach_provider->ach_event_dates_end));

                                                //States
						$states = explode(",", $ach_provider->states);
						asort($states);
						$state_string = "";
						foreach($states as $state)
						{
							$state_string .= "<option value='" . $state . "'>" . $state . "</option>";
						}
                                                
                                                //App Statuses
						$app_statuses = explode(",", $ach_provider->app_statuses);
						asort($app_statuses);
						$app_status_string = "";
						foreach($app_statuses as $app_status)
						{
							$app_status_name = Status_Utility::Get_Status_Name_By_ID($app_status);
							$app_status_string .= "<option value='" . $app_status . "'>" . $app_status_name . "</option>";
						}

                                                //ABAs
						$abas = $this->abas[$ach_provider->ach_provider_id];
						asort($abas);
						$aba_string = "";
						foreach($abas as $aba)
						{
							$aba_string .= "<option value='" . $aba . "'>" . $aba . "</option>";
						}
						$aba_string_to_pass = implode(",", $abas);
                                                
                                                //Failures
						$failures = explode(",", $ach_provider->failures);
						asort($failures);
						$failure_string = "";
						foreach($failures as $failure)
						{
							$failure_string .= "<option value='" . $failure . "'>" . $failure . "</option>";
						}

                                                $fields->ach_provider_list .= "<tr valign='top'>" .
                                                        "<td style='text-align:left;'>$ach_provider->name</td>" .
                                                        "<td style='text-align:left;'>$ach_provider->active_status</td>" .
							"<td style='text-align:left;'>$ach_provider->ach_batch_type</td>" .
							"<td style='text-align:left;'>$ach_provider->credit_percentage</td>" .
							"<td style='text-align:left;'>$ach_provider->debit_percentage</td>" .
                                                        
                                                        "<td style='text-align:left;'>$ach_fund_dates_start</td>" .
							"<td style='text-align:left;'>$ach_fund_dates_end</td>" .
                                                        
                                                        "<td style='text-align:left;'><select STYLE='width: 90px' id='weekdays' name='weekdays[]' multiple size=5 DISABLED>" .
							$weekdays_string .
							"</select></td>" .
                                                        
                                                        "<td style='text-align:left;'><select id='monthdays' name='monthdays[]' multiple size=5 DISABLED>" .
							$monthdays_string .
							"</select></td>" .

							"<td style='text-align:left;'>$ach_event_dates_start</td>" .
							"<td style='text-align:left;'>$ach_event_dates_end</td>" .

							"<td style='text-align:left;'><select id='states' name='states[]' multiple size=5 DISABLED>" .
							$state_string .
							"</select></td>" .
                                                        
                                                        "<td style='text-align:left;'><select STYLE='width: 150px' id='app_statuses' name='app_statuses[]' multiple size=5 DISABLED>" .
							$app_status_string .
							"</select></td>" .

							"<td style='text-align:left;' width='80'><select STYLE='width: 80px' id='abas' name='abas[]' multiple size=5 DISABLED>" .
							$aba_string .
							"</select></td>" .
							
                                                        "<td style='text-align:left;'>$ach_provider->ach_new_react</td>" .
                                                        
                                                        "<td style='text-align:left;'><select id='failures' name='failures[]' multiple size=5 DISABLED>" .
							$failure_string .
							"</select></td>" .
                                                        
							"<td style='text-align:left;'>$ach_provider->ach_batch_time</td>" .
							"<td class='ci_edit_col'>" 
							. "<button onclick=\"edit_ach_provider("
							. $ach_provider->ach_provider_id
							. "," . "'" . $ach_provider->name . "'"
							. "," . "'" . $ach_provider->active_status . "'"
							. "," . "'" . $ach_provider->ach_batch_type . "'"
							. "," . "'" . $ach_provider->credit_percentage . "'"
							. "," . "'" . $ach_provider->debit_percentage . "'"
                                                        . "," . "'" . $ach_fund_dates_start . "'"
							. "," . "'" . $ach_fund_dates_end . "'"
                                                        . "," . "'" . $ach_provider->ach_event_dates_weekdays . "'"
                                                        . "," . "'" . $ach_provider->ach_event_dates_monthdays . "'"
							. "," . "'" . $ach_event_dates_start . "'"
							. "," . "'" . $ach_event_dates_end . "'"
							. "," . "'" . $ach_provider->states . "'"
                                                        . "," . "'" . $ach_provider->app_statuses . "'"
							. "," . "'" . $aba_string_to_pass . "'"
                                                        . "," . "'" . $ach_provider->ach_new_react . "'"
                                                        . "," . "'" . $ach_provider->failures . "'"
							. "," . "'" . $ach_provider->ach_batch_time . "'"
							. ")\">Edit</button>"
							. "</td>" .
                                                        "</tr>";
                                        }

                                        $fields->ach_provider_list .= "\n";
                                }
                                else
                                {
                                        $fields->ach_provider_list .= "\n<br><br><b>No Ach Providers found!</b>\n";
                                }
				
				//States
				$fields->state_list = "";
				$fields->state_list .= "<td style='text-align:left;'>"
				. "<select id=\"ach_provider_states\" name=\"ach_provider_states[]\" multiple size=10>";
				if (count($this->states) > 0)
				{
					foreach ($this->states as $state)
					{
						$fields->state_list .= "<option value='" . $state->state . "'>" . $state->state . "</option>";
					}
				}
				$fields->state_list .= "</select></td>";
                                //////////////
                                
                                //App Statuses
				$fields->app_status_list = "";
				$fields->app_status_list .= "<td style='text-align:left;'>"
				. "<select STYLE='width: 150px' id=\"ach_provider_app_statuses\" name=\"ach_provider_app_statuses[]\" multiple size=10>";
				if (count($this->app_statuses) > 0)
				{
					foreach ($this->app_statuses as $app_status)
					{
						$fields->app_status_list .= "<option value='" . $app_status->application_status_id . "'>" . $app_status->name . "</option>";
					}
				}
				$fields->app_status_list .= "</select></td>";
                                //////////////
                                
				$form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/admin_ach_providers.html");

                                return $form->As_String($fields);
                }
        }
}

?>
