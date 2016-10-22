<?php

/**
 * @package Reporting
 * @category Display
 */
class Default_Report extends Report_Parent
{
	/**
	 * constructor, initializes data used by report_parent
	 *
	 * @param Transport $transport the transport object
	 * @param string $module_name name of the module we're in, not used, but keeps
	 *                            universal constructor call for all modules
	 * @access public
	 */
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->company_totals = array();

		$this->report_title       = "Fraud Report";

		$this->column_names       = array();

		$this->column_format       = array();

		$this->sort_columns       = array();

    
		$this->totals             = array();

		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->wrap_data 		  = false;
		$this->wrap_header 		  = true;
		
		parent::__construct($transport, $module_name);
	}

	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		
	}
	public function Get_Module_HTML()
	{
		
	}
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		
	}
	public function Get_Menu_HTML()
	{

		$report_array		= array();
		$available_reports 	= list_available_reports();
		
		foreach (ECash::getTransport()->user_acl_sub_names as $key => $value) 
		{
	


//			if (array_key_exists($key, $available_reports)) 
			if (isset($available_reports[$key])) 
			{
				$menu_info = $available_reports[$key]["reports"];
				if(count($menu_info))
				{
					foreach ($menu_info as $report_key => $report_item)
					{
						$value = ucwords(str_replace('_', ' ', $value));
						// Check to see if this company can use this report	
						// By default if a report has no acs it will be always shown					
						if(empty($report_item['acs']) || in_array(ECash::getTransport()->company,$report_item['acs']))
						{
							$report_array[$value][$report_key] = array
														(
															'name'			=> $report_item['name'],
															'url'			=> $report_item['link'],
															'description'	=> $report_item['desc']
														);
						}	
					}
				}
				
			}
			
		}

        // Sort the full / search list.
        foreach (array_keys($report_array) as $group)
        {
            uasort($report_array[$group], array($this, 'sortByKey'));
        }

        $map_array = array();
        foreach ($report_array as $category => $reports)
        {
            foreach ($reports as $key => $value)
            {
                $value['category'] = $category;
                $map_array[$value['name']] = $value;
            }
        }

        // Sort the category list.
        ksort($report_array);

        // Sort the combined list.
        ksort($map_array);


		$html = "

			<link rel=\"stylesheet\" type=\"text/css\" href=\"css/tabs.css\" />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"css/basic-dialog.css\" />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"css/tree.css\" />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"css/reports.css\" />	
			<iframe id=\"report_backend\" style=\"width:0px;height:0px;position:absolute;top:0;left:0;visibility:hidden;\"></iframe>						
			<script>
			var tree_data = ". json_encode( $report_array ). "
			var map_data = ". json_encode( $map_array ) ."
			function LoadHelpScreen(title,details)
			{
				document.getElementById('report_detail_title').innerHTML = title;
				document.getElementById('report_detail_html').innerHTML = details;
				//document.getElementById('report_detail_html').innerHTML = details;
				document.getElementById('show-dialog-btn').click();
			}
			</script>
			<div id=\"tabs1\">
				<div id=\"tree\" class=\"tab-content\">
					<div id=\"treeDiv1\" />
				</div>
				<div id=\"search\" class=\"tab-content\" style=\"display:none\">
					<div id=\"report_search_box\">
						Search: <input type=\"text\" onkeyup=\"filter.reportFilter(this.value)\" />
						<div id=\"search_result\"></div>
					</div>
				</div>
			</div>
			<input type=\"button\" id=\"show-dialog-btn\" value=\"ReportDetails\" style=\"width:0px;height:0px;position:absolute;top:0;left:0;visibility:hidden;\"/>
			<script src=\"js/yui/utilities/utilities.js?timer=".time()."\" ></script>
			<script src=\"js/yui/treeview/treeview-min.js?timer=".time()."\" ></script>
			<script src=\"js/lib/yui-ext.js?timer=".time()."\"></script>
			<script src=\"js/lib/reports.js?timer=".time()."\" ></script>
			
			
		";
		
		$html .= "<script> 
		function LoadReportTabs()
		{
		";
		if(!empty($_SESSION['reports']))
		{
			foreach($_SESSION['reports'] as $report => $reprt_obj)
			{
				if(isset($reprt_obj['url_data']['name']))
				{
					$html .= "Tabs.openInTab('".$reprt_obj['url_data']['name']."','".$reprt_obj['url_data']['link']."');\n";
				}
			}
		}
		
		/**
		 * This will activate the Help tab and set the URL to be used for the Pop-up Window
		 */
		if(in_array('reporting_help', ECash::getTransport()->user_acl_sub_names))
		{
			if (isset(ECash::getConfig()->ONLINE_HELP_REPORTING_URL_ROOT) && isset(ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX))
			{
				$help_url = ECash::getConfig()->ONLINE_HELP_REPORTING_URL_ROOT . ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX;
				$html .= "\nTabs.addHelpTab('$help_url');\n";
			}
		}
		
		$html .= "
		}
		</script>

    <div id=\"report-help-dlg\" style=\"visibility:hidden;position:absolute;top:0px;\">
    	<div class=\"ydlg-hd\" id=\"report_detail_title\"></div>
    	<div class=\"ydlg-bd\">	        
	        <div class=\"ydlg-tab\" title=\"Details\">	            
	            <div class=\"inner-tab\" style=\"top:0;left:0;position:absolute;text-align:left;overflow:auto;height:220px;width:465px;\" id=\"report_detail_html\">
	            </div>
	        </div>
        </div>
    </div>	
		";
		return $html;
	}

    private function sortByKey($first, $second)
    {
        if (empty($first['name']) || empty($second['name']))
        {
            return 0;
        }

        return strcmp($first['name'], $second['name']);
    }

}

?>
