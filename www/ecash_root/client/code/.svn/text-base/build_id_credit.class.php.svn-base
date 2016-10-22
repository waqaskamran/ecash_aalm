<?php
/**
 * Build the ID and Credit Panel
 *
 * @author Kyle Barrett <kyle.barrett@sellingsource.com>
 */
class Build_ID_Credit
{
	/**
	 * @var ECash_ACL
	 */
	private $acl;
	
	/**
	 * @var int
	 */
	private $company_id;
	
	/**
	 * @var stdClass
	 */
	private $data;
	
	/**
	 * @var array
	 */
	private $status_to_call_datax = array('prospect','applicant');
	
	/**
	 * Object Constructor
	 */
	public function __construct($acl, $company_id, stdClass &$data)
	{
		$this->acl = $acl;
		$this->company_id = $company_id;
		$this->data = &$data;
	}
	
	/**
	 * Builds the display data.
	 */
	public function build()
	{
		$this->data->idv_grid_display         = $this->getGridDisplay();
		$this->data->idv_error    			  = $this->getIDVConnectionError();
		$this->data->idv_error                .= $this->getBucketErrors();
		
		$this->data->id_credit_ui_javascript  = $this->buildDataJavascript($this->data->inquiry_packages);
		$this->data->idv_recheck_all          = $this->buildRecheckLink('all', 'Recheck All');
		
		$this->data->fraud_rules           	  = $this->buildFraudRules();
		$this->data->idv_increase			  = (isset($this->data->idv_increase_eligible) && $this->data->idv_increase_eligible) ? 'Yes' : 'No';
		
		if($this->getBucketErrors() != NULL)
		{
			$this->data->idv_recheck_interrupted = $this->buildRecheckLink('interrupted', 'Recheck Interrupted');
		}
		else
		{
			$this->data->idv_recheck_interrupted = NULL;	
		}
	}
	
	/**
	 * Returns the display type for the IDV grid. If there are no packages, the grid should be hidden.
	 *
	 * @return string Grid display param
	 */
	private function getGridDisplay()
	{
		return (count($this->data->inquiry_packages) ? 'block' : 'none');
	}
	
	/**
	 * Returns an error if there are no inquiry packages.
	 *
	 * @return string Connection Error
	 */
	private function getIDVConnectionError()
	{
		$connection_error = NULL;
		
		if(!count($this->data->inquiry_packages))
		{
			$connection_error = "Failed To Connect To Bureau.";
		}
		
		return $connection_error;
	}
	
	/**
	 * Returns an error message is any of the DataX services failed to run (were interrupted).
	 *
	 * @return string Bucket error
	 */
	private function getBucketErrors()
	{
		$bucket_error = NULL;
		
		$connection_error = $this->getIDVConnectionError();
		
		if(empty($connection_error))
		{
			$newest_record = @current($this->data->inquiry_packages);
			$brueau = strtolower($newest_record->name_short);
            
			switch ($brueau) {
				case "datax":
					$response = new ECash_DataX_Responses_Perf();
					break;
				case "factortrust":
					$response = new ECash_FactorTrust_Responses_Perf();
					break;
				case "clarity":
					$response = new ECash_Clarity_Responses_Perf();
					break;
			}
 
			$response->parseXML($newest_record->received_package);
			
			$buckets 		 = $response->getDecisionBuckets();
			$failed_services = array();

			foreach($buckets as $service=>$decision)
			{
				//An 'I' as a bucket result means that the service was interrupted.
				if(preg_match("/I/is", $decision))
				{
					$failed_services[] = $service;
				}	
			}
			
			$num_failed = count($failed_services);
			
			if($num_failed)
			{
				$bucket_error = "The following service" . ($num_failed > 1 ? "s" : "") . " did not run successfully: " . implode(", ", $failed_services) . ".";
			}
		}
		return $bucket_error;
	}

	/**
	 * Builds the ID Recheck Forms
	 *
	 * @param string $recheck_type
	 * @param string $label
	 * @return string Recheck Form
	 */
	private function buildRecheckLink($recheck_type, $label)
	{
		$form = NULL;
		
		if($this->validRecheckStatus())
		{
			// If agent has ACL access for recheck generate the html
			if($this->acl->Acl_Access_Ok('id_recheck', $this->data->company_id))
			{
				$id = "AppCreditIDRecheck".ucfirst($recheck_type);
				$form = '<nobr><form method="post" action="/" name="id_recheck" class="no_padding">
					<input type="hidden" id="' . $id . 'Action" name="action" value="id_recheck" />
					<input type="hidden" id="' . $id . 'Type" name="recheck_type" value="' . $recheck_type . '" />
					<input type="hidden" id="' . $id . 'AppId" name="application_id" value="' . $this->data->application_id . '" />
                	<input type="submit" id="' . $id . 'Submit" value="' . $label .'" class="button" />
                	</form></nobr>';
			}
		}
		
		return $form;
	}
	
	/**
	 * Generates fraud and high risk rows if the application if the application is flagged.
	 * @return string Fraud & High Risk row HTML.
	 */
	private function buildFraudRules()
	{
		$field_types = array("fraud_rules",
							 "fraud_fields",
							 "risk_rules",
							 "risk_fields",
							 );
		
		$fraud_html    = NULL;
		$field_counter = 0;
		
		foreach($field_types as $type)
		{
			if(isset($this->data->$type))
			{
				$type_label = $type . "_label";
				$type_list  = $type . "_list";
				
				$label = ucwords(str_replace("_", " ", $type));
				$field = join(", ", split(";", $this->data->$type));
			
				$fraud_html .= $this->buildFraudRow($label, $field, $field_counter++);
			}			
		}
		
		return $fraud_html;
	}
	
	/**
	 * Builds an individual table row for fraud/high risk.
	 * @param string $label 
	 * @param string $field
	 * @param int $counter Row number
	 * @return string HTML 
	 */
	private function buildFraudRow($label, $field, $counter)
	{
		$html = NULL;
		
		$row_class = ($counter%2 != 0 ? '' : '_alt');
		
		$html .= '<tr class="height">';
		$html .= '<td class="align_left' . $row_class . '_bold">&nbsp;' . (preg_match("/Risk/is",$label) ? "High ".$label : $label) . '</td>';
		$html .= '<td class="align_right' . $row_class . '">';
		
		$url            = "?action=fraud_risk_rules&application_id=" . $this->data->application_id;
		$window_options = "height=400,width=450,scrollbars=1,status=0,screenX=200,screenY=200;";
		$javascript     = "window.open('{$url}', 'Fraud & High Risk Matching Rules', '{$window_options}');";
		
		$html .= '<div style="width: 250px; overflow:hidden;" onClick="' . $javascript . '">';
		$html .= '<nobr>' . $field . '&nbsp;</nobr>';
		$html .= '</div>';
		$html .= '</td>';
		$html .= '</tr>';
		
		return $html;
	}
	
	/**
	 * Checks to see if the app is in a valid status for ID Rechecks
	 *
	 * @return boolean Recheck Access Available
	 */
	private function validRecheckStatus()
	{
		return (in_array($this->data->level1, $this->status_to_call_datax) && $this->data->level2 == '*root'	||
				in_array($this->data->level2, $this->status_to_call_datax) && $this->data->level3 == '*root'	||
				in_array($this->data->level3, $this->status_to_call_datax) && $this->data->level4 == '*root'	||
				in_array($this->data->level4, $this->status_to_call_datax) && $this->data->level5 == '*root'
				);
	}
	
	/**
	 * Generates the javascript for the EXT UI.
	 *
	 * @param array Array of Inquiry Packages (std_class objects)
	 * @return string Javascript array body
	 */
	private function buildDataJavascript($inquiry_packages)
	{
		$javascript = NULL;

		if(!empty($inquiry_packages))
		{
			foreach($inquiry_packages as $package)
			{
				$brueau = strtolower($package->name_short);

				switch ($brueau) {
					case "datax":
						$response = new ECash_DataX_Responses_Perf();
						break;
					case "factortrust":
						$response = new ECash_FactorTrust_Responses_Perf();
						break;
					case "clarity":
						$response = new ECash_Clarity_Responses_Perf();
						break;
				}

				$response->parseXML($package->received_package);
				
				$id         = ($package->failed ? "f" : NULL) . $package->bureau_inquiry_id;
				$date       = date("m-d-Y h:ia", strtotime($package->date_created));
				$call_type  = $package->inquiry_type;
				$decision   = $response->getDecision();
				$outcome    = $package->outcome;
				$agent      = $package->agent_name;
				$trace_data = ''; //This hasn't been defined yet
				
				$javascript .= "['{$id}','{$date}', '{$call_type}', '{$decision}', '{$agent}', '{$outcome}'],\n";
				//throw new Exception($javascript);
			}
		}
		
		return $javascript;
	}
}
?>
