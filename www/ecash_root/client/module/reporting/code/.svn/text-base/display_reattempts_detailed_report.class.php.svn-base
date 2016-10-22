<?php

/**
 * @package Reporting
 * @category Display
 */
class Reattempts_Detailed_Report extends Report_Parent
{


	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Reattempts Detailed Report";
		/*
		
			echo '<table border="1" cellpadding="3" cellspacing="0">';
	echo '<tr><th>Date</th><th>New Principal</th><th>New Svc. Chg.</th><th>New Fees</th>
		<th>Re. Principal</th><th>Re. Svc. Chg.</th><th>Re. Fees</th></tr>';
		
		*/
		$this->column_names       = array(
		                                   'application_id' 	=> 'Application ID',
																			 'status' 	=> 'Status',
																			 'event_schedule_id' => 'Schedule ID',
		                                   'new_principal' 			=> 'New Principal',
		                                   'new_svr_charge' 	=> 'New Svc. Chg',
		                                   'new_fees'			=> 'New Fees',
		                                   're_principal' 		=> 'Re. Principal',
		                                   're_svr_charge' 	=> 'Re. Svc. Chg.',
		                                   're_fees'		=> 'Re. Fees');
		                                   
		$this->sort_columns       = array( 'application_id', 'status', 'event_schedule_id',
																			 'new_principal', 'new_svr_charge', 'new_fees',
																			 're_principal', 're_svr_charge', 're_fees' );
		
		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->column_format      = array(
		                                   //'application_id'   	=> self::FORMAT_TEXT
																			 //'event_schedule_id' 	=> self::FORMAT_TEXT,
		                                   'new_principal'        	=> self::FORMAT_CURRENCY, 
		                                   'new_svr_charge'   	=> self::FORMAT_CURRENCY, 
		                                   'new_fees'         	=> self::FORMAT_CURRENCY, 
		                                   're_principal'     	=> self::FORMAT_CURRENCY, 
		                                   're_svr_charge'	=> self::FORMAT_CURRENCY, 
		                                   're_fees'        => self::FORMAT_CURRENCY, 
		                                   );
		$this->totals             = array( 'company' => array( 
		                                                       'new_principal' 			=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'new_svr_charge'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'new_fees'        	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       're_principal'       => Report_Parent::$TOTAL_AS_SUM,
		                                                       're_svr_charge'  => Report_Parent::$TOTAL_AS_SUM,
		                                                       're_fees'    	=> Report_Parent::$TOTAL_AS_SUM),
		                                   'grand'   => array( 
		                                                       'new_principal' 			=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'new_svr_charge'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'new_fees'       	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       're_principal'       => Report_Parent::$TOTAL_AS_SUM,
		                                                       're_svr_charge'  => Report_Parent::$TOTAL_AS_SUM,
		                                                       're_fees'		=> Report_Parent::$TOTAL_AS_SUM));
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->loan_type          = true;
		$this->ajax_reporting     = true;
		parent::__construct($transport, $module_name);
	}



   protected function Get_Total_HTML($company_name, &$company_totals)
   {
      $line = "";

      // If column 1 has no totals, totals header will go in column 1
      //    else, put totals header on own line
      reset($this->column_names);
      $total_own_line = (! empty($this->totals['company'][$this->column_names[key($this->column_names)]]) ? true : false);

      // If the total header should be on its own line,
      //    or only the # of rows is desired
      if( $total_own_line ||
         ( count($this->totals['company']) == 1 &&
           ! empty($this->totals['company']['rows'])))
      {
         if( ! empty($this->totals['company']['rows']) )
         {
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$company_name Totals ";
            $line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th></tr>\n";
         }
         else
         {
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$company_name Totals</th></tr>\n";
         }
      }

      if( (! empty($this->totals['company']['rows']) && count($this->totals['company']) > 1 ) ||
         (empty($this->totals['company']['rows']) && count($this->totals['company']) > 0 ))
      {
         $line .= "    <tr>\n";
         foreach( $this->column_names as $data_name => $column_name )
         {
            if( ! empty($this->totals['company'][$data_name]) )
            {
               $line .= "     <th class=\"report_foot\"><div style=\"text-align:right\">" . $this->Format_Field($data_name, $company_totals[$data_name], true) . "</div></th>\n";
            }
            else if( ! $total_own_line )
            {
               if( ! empty($this->totals['company']['rows']) )
               {
                  $line .= "     <th class=\"report_foot\">$company_name Totals ";
                  $line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th>\n";
               }
               else
               {
                  $line .= "     <th class=\"report_foot\">$company_name Totals</th>\n";
               }

               // Don't put the total header field again
               $total_own_line = ! $total_own_line;
            }
            else
            {
               $line .= "     <th class=\"report_foot\"></th>\n";
            }
         }
         $line .= "    </tr>\n";
      }

      return $line;
   }
}

?>
