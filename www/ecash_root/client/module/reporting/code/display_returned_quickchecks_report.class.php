<?php

/**
 * @package Reporting
 * @category Display
 */
class Returned_Quickchecks_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Returned Quickchecks Report";
		$this->column_names = array( 'date_created'         => 'Date',
									 'application_id'		=> 'Application ID',
									 'full_name'			=> 'Name',
									 'return_reason_code'	=> 'Reason Code',
									 'return_name'			=> 'Reason Description',									 
									 'app_count'			=> 'Return Count',
									 'amount'				=> 'Amount',

		                             );
		$this->sort_columns = array(	'date_created',
										'application_id',
										'full_name',
										'return_reason_code',
										'return_name',
										'amount',
										'app_count');
		
		$this->column_format       = array(
								   'date_modified'				=> self::FORMAT_TIME ,
								   'return_reason_code'			=> self::FORMAT_TEXT,
                                   'amount'  					=> self::FORMAT_CURRENCY 
                                   );
		$this->link_columns = array('application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%');
		$this->totals       = array( 'company' => array( 'rows' ),
		                             'grand'   => array( 'rows' ),
		                             'subtotal' => array('amount','app_count')
		                            );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = "returned_quickchecks_report.txt";
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

   protected function Get_Total_HTML($company_name, &$company_totals)
   {
   	
      $line = "";

      //print_r($this->search_results[$company_name]);
      $return_totals = array();
      $item = null;
      for($i=0; $i<count($this->search_results[$company_name]); $i++)
      {
      	$item = $this->search_results[$company_name][$i];
      	@$return_totals[$item['return_name']]['total']++;
      	@$return_totals[$item['return_name']]['total_amount'] = $return_totals[$item['return_name']]['total_amount'] + $item['amount'];
      	if(@!$return_totals[$item['return_name']]['return_once']) $return_totals[$item['return_name']]['return_once'] = 0;
      	if(@!$return_totals[$item['return_name']]['return_twice']) $return_totals[$item['return_name']]['return_twice'] = 0;
      	if(@!$return_totals[$item['return_name']]['return_more']) $return_totals[$item['return_name']]['return_more'] = 0;
      	switch ($item['app_count'])
      	{
      		case 1:
      			@$return_totals[$item['return_name']]['return_once']++;
      			break;
      		case 2:
      			@$return_totals[$item['return_name']]['return_twice']++;
      			break;      			
      		default:
      			@$return_totals[$item['return_name']]['return_more']++;
      			break;
      	}
      }
      foreach($return_totals as $key => $value)
      {
      	// colspan=\"{$this->num_columns}\"
      	$line .= "<tr>";
      	$line .= "<th class=\"report_foot\" nowrap>$key:</th>";
      	$line .= "<th class=\"report_foot\" nowrap>Count:{$value["total"]}</th>";
      	$line .= "<th class=\"report_foot\" nowrap>Returned Once:{$value["return_once"]}</th>";
      	$line .= "<th class=\"report_foot\" nowrap>Returned Twice:{$value["return_twice"]}</th>";
      	$line .= "<th class=\"report_foot\" nowrap>Returned More:{$value["return_more"]}</th>";
      	$line .= "<th class=\"report_foot\" nowrap></th>";
      	$line .= "<th class=\"report_foot\" nowrap>".$this->Format_Field("amount",$value["total_amount"])."</th>";
      	$line .= "</tr>";
      	$total["total"] 		= $value["total"] + $total["total"];
      	$total["return_once"] 	= $value["return_once"] + $total["return_once"];
      	$total["return_twice"] 	= $value["return_twice"] + $total["return_twice"];
      	$total["return_more"] 	= $value["return_more"] + $total["return_more"];
      	$total["total_amount"] 	= $value["total_amount"] + $total["total_amount"];
      	
      }
	  	$line .= "<tr>";
	  	$line .= "<th class=\"report_foot\" nowrap>Total:</th>";
	  	$line .= "<th class=\"report_foot\" nowrap>Count: {$total["total"]}</th>";
	  	$line .= "<th class=\"report_foot\" nowrap>Returned Once: {$total["return_once"]}</th>";
	  	$line .= "<th class=\"report_foot\" nowrap>Returned Twice: {$total["return_twice"]}</th>";
	  	$line .= "<th class=\"report_foot\" nowrap>Returned More: {$total["return_more"]}</th>";
	  	$line .= "<th class=\"report_foot\" nowrap></th>";
	  	$line .= "<th class=\"report_foot\" nowrap>".$this->Format_Field("amount",$total["total_amount"])."</th>";
	  	$line .= "</tr>";      
      return $line;
   }
}

?>
