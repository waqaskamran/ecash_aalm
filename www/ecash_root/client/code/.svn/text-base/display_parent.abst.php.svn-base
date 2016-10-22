<?php

require_once(COMMON_LIB_DIR . "data_format.1.php");

abstract class Display_Parent
{
	protected $transport;
	protected $data;
	protected $module_name;
	protected $mode;
	protected $data_format;

	public function __construct(ECash_Transport $transport, $module_name, $mode)
	{
		$this->transport = ECash::getTransport();
		$this->module_name = $module_name;		
		$this->mode = $mode;
		$this->data = $this->transport->Get_Data();
		$this->data->new_app_url = $this->transport->new_app_url;
		$this->data->pbx_enabled = $this->transport->pbx_enabled;
		$this->data_format = new Data_Format_1();
		if (!isset($this->data->closing_time)) $this->data->closing_time = "";
	}

	// Variable replacement callback function.  This got a little complicated, complain to Chris.  Fix later.
	protected function Replace($matches)
	{
		// Is it an edit layer?
		if( strpos($matches[0], "_edit%%%") )
		{
			$matches[1] = substr($matches[1], 0, -5);

			if( !empty($this->data->saved_error_data->{$matches[1]}) )
			{
				$return_value = $this->data->saved_error_data->{$matches[1]};

			}
			elseif(isset($this->data->{$matches[1]}))
			{
				$return_value = $this->data->{$matches[1]};

				if ($matches[1] == 'legal_id_number' || $matches[1] == 'unit')
				{
					$return_value = strtoupper($return_value);
				}
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		else // Non edit replacement.
		{
			if(isset($this->data->{$matches[1]}))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		return $return_value;
	}

	/**
	 * Get the section ID based on the parent section and the section name
	 *
	 * @param integer $parent_section_id
	 * @param string $section_name
	 * @return integer section_id if found, -1 if not found
	 */
	protected function Get_Section_ID_by_Name($parent_section_id, $section_name)
	{
		foreach($this->data->all_sections as $key => $value) {
			//echo "<!-- Searching for parent: $parent_section_id //  $key => Parent ID: {$value->section_parent_id}  Name: {$value->name} -->\n";
			if ($value->section_parent_id === $parent_section_id
					&& $value->name == $section_name)
			{
				return $value->section_id;
			}
		}

		return -1;
	}

	/**
	 * Get the Section ID for the current mode
	 *
	 * @return integer $section_id
	 */
	protected function Get_Section_ID()
	{
		//echo '<!--', print_r($this->data, TRUE), ' -->';
		// find the correct module
		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->name == $this->module_name)
			{
				$module_id = $value->section_id;
				break;
			}
		}
		
		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->name == $this->mode && $value->section_parent_id == $module_id)
			{
				return $value->section_id;
			}
		}
		return  -1;
	}
	
}

?>
