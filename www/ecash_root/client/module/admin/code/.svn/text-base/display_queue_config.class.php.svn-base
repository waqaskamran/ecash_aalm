<?php
/**
 * <CLASSNAME>
 * <DESCRIPTION>
 *
 * Created on Jan 4, 2007
 *
 * @package <PACKAGE>
 * @category <CATEGORY>
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{

	public function Get_Header()
	{
		$data = ECash::getTransport()->Get_Data();

		$fields = new stdClass();

		switch ($data->view)
		{
			case 'reset_queue_cycle_limits':
				$fields->queue_form_name = 'queue_recycle_limit';
				$fields->queue_list_name = 'queue_recycle_limit_rule';
			break;

			case 'recycle_queues':
				$fields->queue_form_name = 'queue_timeout';
				$fields->queue_list_name = 'queue_timeout_rule';
			break;

			case 'reset_queue_timeouts':
				$fields->queue_form_name = 'queue_timeout';
				$fields->queue_list_name = 'queue_timeout_rule';
			break;
		}
		$js = new Form(ECASH_WWW_DIR.'js/admin_queue.js');
		return parent::Get_Header() . $js->As_String($fields);
	}

	public function Get_Module_HTML()
	{
		$data = ECash::getTransport()->Get_Data();

		$fields = new stdClass();

		switch ($data->view) {
			case "new_queue":

					$html = file_get_contents(CLIENT_MODULE_DIR.$this->module_name."/view/queue_config_new_queue.html");
					$selected = ' selected';

					$alt_module_listing = "<option value=\"\"></option>\n";
					$module_listing = '';
					foreach ($data->module_mode_list as $module)
					{
						$module_string = "<option value=\"{$module[0]}\"" . (!empty($data->req->section_id) && $module[0] == $data->req->section_id ? $selected : '') . ($module[2] ? '' : ' disabled' ) . ">{$module[1]}</option>\n";
						$module_listing .= $module_string;
						$alt_module_listing .= $module_string;
					}

					$group_listing = '';
					foreach ($data->queue_group_list as $queue_group)
					{
						$group_listing .= "<option value=\"{$queue_group->Model->queue_group_id}\"" . (!empty($data->req->queue_group_id) && $queue_group->Model->queue_group_id == $data->req->queue_group_id ? $selected : '') . ">{$queue_group->Model->name}</option>\n";
					}

					$company_listing = '';

					foreach ($data->queue_company_list as $company)
					{
						$company_listing .= "<option value=\"{$company->company_id}\"" . (!empty($data->req->queue_company_id) && $company->company_id == $data->req->queue_company_id ? $selected : '') . ">{$company->name} ({$company->name_short})</option>";
					}

					//set up the errors for invalid data
					$name_color = $display_color = $short_color = '';
					$error_block = '';					
					if($data->completed)
					{
						$error_block = "<span style=\"color: red;\">{$data->completed}</span>";
					} elseif(isset($data->req->go) && !$data->req->completed) {
						if(empty($data->req->queue_name))
						{
							$name_color = ' style="color: red"';
						}
						if(empty($data->req->queue_name_display))
						{
							$display_color = ' style="color: red"';
						}
						if(empty($data->req->queue_name_short))
						{
							$short_color = ' style="color: red"';
						}
						if($name_color || $display_color || $short_color)
						{
							$error_block = '<span style="color: red">Missing Data</span>';
						}
					}

					$manager = ECash::getFactory()->getQueueManager();
					$queue_list = '';
					$queue_json = array();

					//order the queues before running through them
					$queues = $manager->getQueues();
					$ordered_queues = array();
					foreach($queues as $queue)
					{
						$model = $queue->getModel();

						$name_short = trim($model->name_short);

						$ordered_queues[$name_short] = trim($model->name);
						$sections = $manager->getSectionsByQueueId($model->queue_id);
						$alt_section_id = NULL;
						if(($key = array_search($model->section_id, $sections)) !== FALSE)
						{
							//unset the section that's already set in queue->section_id
							unset($sections[$key]);
							//only display one alternate display (for the time being)
							$alt_section_id = array_pop($sections);
						}

						$json = array();
						$json['queue_name'] = $model->name;
						$json['queue_display_name'] = $model->display_name;
						$json['company_id'] = $model->company_id;
						$json['type'] = $model->control_class;
						$json['group'] = $model->queue_group_id;
						$json['location'] = $model->section_id;
						$json['alt_location'] = $alt_section_id;
						$json['recycle_limit'] = $queue->getConfig()->isValueSpecified('recycle_limit') ? ($queue->getConfig()->getValue('recycle_limit')) : "COMPANY";
						$json['recycle_time'] = $queue->getConfig()->isValueSpecified('recycle_time') ? ($queue->getConfig()->getValue('recycle_time')/60) : "COMPANY";
						$json['count'] = $queue->count();
						$json['is_system_queue'] = $model->is_system_queue;
						$json['date_modified'] = $model->date_modified;
						$json['date_created'] = $model->date_created;
						$queue_json[$name_short] = $json;
					}
					asort($ordered_queues, SORT_LOCALE_STRING);

					//now loop through the queues and get them ready for
					foreach ($ordered_queues as $name_short => $display_name)
					{
						$queue = $queues[$name_short];
						$queue_config = $queue->getConfig();
						if(!empty($data->req->queue_select) && $name_short == $data->req->queue_select)
						{
							$selected = ' selected';
						} else {
							$selected = '';
						}
						$count = $queue->count();

						$queue_list .= "<option value=\"{$name_short}\"{$selected}>{$display_name} ({$count})</option>\n";
					}

					//initialize the company defaults
					$config_model = ECash::getFactory()->getModel('QueueConfig');
					$loaded_limit = $config_model->loadBy(array("queue_id" => 0, 'config_key' => 'recycle_limit'));
					//[#40090] I gave this a value of one if not set
					$limit_company_default = $loaded_limit ? $config_model->config_value : 1;

					$timeout_model = ECash::getFactory()->getModel('QueueConfig');
					$loaded_timeout = $timeout_model->loadBy(array("queue_id" => 0, 'config_key' => 'recycle_time'));
					//[#40090] I gave this a value of one if not set
					$timeout_company_default = $loaded_timeout ? $timeout_model->config_value / 60 : 1;
					if($timeout_company_default >= 60)
					{
						$timeout_company_default /= 60;
						if($timeout_company_default >= 24)
						{
							$timeout_company_default = round($timeout_company_default / 24, 2) . ' days';
						}
						else
						{
							$timeout_company_default = round($timeout_company_default, 2) . ' hours';
						}
					}
					else
					{
						$timeout_company_default = round($timeout_company_default, 2) . ' minutes';
					}

					//now get the recycle limit options
					$queue_recycle_limit_options = "<option value=\"\">Company Default ({$limit_company_default})</option>\n";
					for( $i = 0 ; $i < 100 ; $i++)
					{
					
						//Bump 50 to 100 - about as bullshit as the hack below!
						if($i == 51)
						{
							$i = 100;
						}
					
						/**
						 * what is this bullshit?
						 * oh. i see. bullshit nonetheless.
						 */
						if($i >= 10 && (($i + 4) % 5) == 0 ) 
						{
							$i = $i + 4;
						}

						if(isset($data->req->queue_recycle_limit) && $i == $data->req->queue_recycle_limit)
						{
							$selected = ' selected';
						} else {
							$selected = '';
						}

						if($i === 0)
						{
							$queue_recycle_limit_options .= "<option value=\"{$i}\"{$selected}>No Limit</option>\n";
						}
						else
						{
							$queue_recycle_limit_options .= "<option value=\"{$i}\"{$selected}>{$i}</option>\n";
						}
					}

					//this should eventually become a lot better
					$queue_type_list = '
										<option value="Queues_BasicQueue"' . (!empty($data->req->queue_type) && $data->req->queue_type == 'Queues_BasicQueue' ? ' selected' : '') . '>BasicQueue: Basic Queue</option>
										<option value="Queues_TimeSensitiveQueue"' . (!empty($data->req->queue_type) && $data->req->queue_type == 'Queues_TimeSensitiveQueue' ? ' selected' : '') . '>TimeSensitiveQueue: Basic Queue w/ Timezone Awareness</option>
										<option value="Queues_CollectionsQueue"' . (!empty($data->req->queue_type) && $data->req->queue_type == 'Queues_CollectionsQueue' ? ' selected' : '') . '>CollectionsQueue: Collections Queue</option>
					';

					$queue_timeout_options = '';
					foreach ($GLOBALS["DEFAULT_QUEUE_TIMEOUTS"] as $val => $nam)
					{
						$queue_timeout_options .= "<option value=\"{$val}\"" . (!empty($data->req->queue_timeout_limit) && $val == $data->req->queue_timeout_limit ? ' selected' : '') . ">{$nam}" . ($val == 'COMPANY' ? " ({$timeout_company_default})" : '') . "</option>\n";
					}

					return Display_Utility::Token_Replace($html,
						array(
							'module_listing' => $module_listing,
							'alt_module_listing' => $alt_module_listing,
							'queue_name' => empty($data->req->queue_name) ? '' : $data->req->queue_name,
							'queue_name_display' => empty($data->req->queue_name_display) ? '' : $data->req->queue_name_display,
							'queue_name_short' => empty($data->req->queue_name_short) ? '' : $data->req->queue_name_short,
							'queue_groups' => $group_listing,
							'queue_company_list' => $company_listing,
							'error_block' => $error_block,
							'name_color' => $name_color,
							'display_color' => $display_color,
							'short_color' => $short_color,
							'queue_list' => $queue_list,
							'queue_json' => json_encode($queue_json),
							'feedback' => $data->completed,
							'queue_function' => empty($data->req->queue_function) ? '' : ucfirst($data->req->queue_function),
							"queue_recycle_limit_options" => $queue_recycle_limit_options,
							"queue_timeout_options" => $queue_timeout_options,
							'queue_type_list' => $queue_type_list,
						)
					);
				break;
			case "reset_queue_cycle_limits":
				$rule_name = "Recycle Limit";

				$manager = ECash::getFactory()->getQueueManager();
				foreach ($manager->getQueues() as $queue)

				{
					$queue_model = $queue->getModel();
					$queue_config = $queue->getConfig();

					$queue_name_short = $queue_model->name_short;
					$queue_name = $queue_model->display_name;
					$queue_full_name = $queue_model->name;

					$fields->queue_recycle_limit_list .= "<option value=\"{$queue_name_short}\">{$queue_full_name}</option>\n";
					$fields->queue_recycle_limit_value_list .= "<input type=\"hidden\" name=\"queue_recycle_limit[{$queue_name_short}]\" id=\"queue_recycle_limit_{$queue_name_short}_id\" value=\"". $queue_config->getValue('recycle_limit') . "\" />\n";
					//yeah this is dirty, but it is faster than looping through \nthe list of queues and caluculating each matching rule name
					$fields->queue_recycle_limit_value_list .= "<input type=\"hidden\" name=\"queue_short[{$queue_name_short}]\" value=\"{$queue_name_short}\" />\n";

				}

				$default_limit = Company_Rules::Get_Config("company_queue_recycle_limit");

				for( $i = 0 ; $i < 100 ; $i++)
				{
					
					//Bump 50 to 100 - about as bullshit as the hack below!
					if($i == 51)
					{
						$i = 100;
					}
					
					/**
					 * what is this bullshit?
					 * oh. i see. bullshit nonetheless.
					 */
					if($i >= 10 && (($i + 4) % 5) == 0 )
					{
						$i = $i + 4;
					}
					$default = $i == $default_limit ? ' (default)' : '';

					if($i === 0)
					{
						$fields->queue_recycle_limit_options .= "<option value=\"{$i}\">No Limit{$default}</option>\n";
					}
					else
					{
						$fields->queue_recycle_limit_options .= "<option value=\"{$i}\">{$i}{$default}</option>\n";
					}
				}

				$recycle_form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/queue_config_queue_recycle_limit.html");

				return $recycle_form->As_String($fields) ;

			case "recycle_queues":
				$view_form = CLIENT_MODULE_DIR . $this->module_name . "/view/queue_config_recycle_now.html";


			case "reset_queue_timeouts":
				$rule_name = "Queue Timeout";

				$view_form = !empty($view_form) ? $view_form : CLIENT_MODULE_DIR.$this->module_name."/view/queue_config_queue_timeouts.html";


				$manager = ECash::getFactory()->getQueueManager();
				foreach ($manager->getQueues() as $queue)
				{
					$queue_model = $queue->getModel();
					$queue_config = $queue->getConfig();

					$queue_name_short = $queue_model->name_short;
					$queue_full_name = $queue_model->name;
					$queue_name = $queue_model->display_name;

					$fields->queue_timeout_value_list .= "<input type=\"hidden\" name=\"queue_timeout[{$queue_model->name_short}]\" id=\"queue_timeout_{$queue_model->name_short}_id\" value=\"" . ($queue_config->isValueSpecified('recycle_time') ? ($queue_config->getValue('recycle_time')/60) : "COMPANY"). "\" />\n";

					//yeah this is dirty, but it is faster than looping through \nthe list of queues and caluculating each matching rule name
					$fields->queue_timeout_value_list .= "<input type=\"hidden\" name=\"queue_short[{$queue_name}]\" value=\"{$queue_name_short}\" />\n";
					$fields->queue_timeout_list .= "<option value=\"{$queue_name_short}\">{$queue_full_name} </option>\n";

				}

				$default_timeout = Company_Rules::Get_Config("company_default_queue_timeout");

				foreach ($GLOBALS["DEFAULT_QUEUE_TIMEOUTS"] as $val => $nam) {
					$default = $val == $default_timeout ? ' (default)' : '';
					$fields->queue_timeout_options .= "<option value=\"{$val}\">{$nam}{$default}</option>\n";
				}

				$timeout_form = new Form($view_form);

				return $timeout_form->As_String($fields);

			default:

		}

	}
}

?>
