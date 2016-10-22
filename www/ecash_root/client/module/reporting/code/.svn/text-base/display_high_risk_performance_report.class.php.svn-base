<?php

/**
 * @package Reporting
 * @category Display
 */
class Fraud_Performance_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title       = "High Risk Agent Actions Report";
		$this->column_names       = array( 'agent_name'          		=> 'Agent',
		                                   'num_in_verify_new'       		=> 'Received Verify (New)',
		                                   'num_in_verify_react'       		=> 'Received Verify (React)',
		                                   'num_in_underwriting_new' 		=> 'Received UW (New)',
		                                   'num_in_underwriting_react' 		=> 'Received UW (React)',
		                                   'num_approved_new'        		=> 'Approved (New)',
		                                   'num_approved_react'        		=> 'Approved (React)',
		                                   'num_funded_new'          		=> 'Funded (New)',
		                                   'num_funded_dupe'          		=> 'Funded (Dupl.)',
		                                   'num_funded_react'          		=> 'Funded (React)',
		                                   'num_withdrawn_new'       		=> 'Withdrawn (New)',
		                                   'num_withdrawn_react'       		=> 'Withdrawn (React)',
		                                   'num_denied_new'          		=> 'Denied (New)',
		                                   'num_denied_react'          		=> 'Denied (React)',
		                                   'num_reverified_new'      		=> 'Reverified (New)',
		                                   'num_reverified_react'      		=> 'Reverified (React)',
		                                   'num_follow_up_new'       		=> 'Follow Up (New)',
		                                   'num_follow_up_react'       		=> 'Follow Up (React)',
		                                   'num_put_in_verify_new'      	=> 'Put Verify (New)',
		                                   'num_put_in_verify_react'      	=> 'Put Verify (React)',
		                                   'num_put_in_underwriting_new'	=> 'Put UW (New)',
		                                   'num_put_in_underwriting_react'	=> 'Put UW (React)',);
		$this->sort_columns       = array( 'agent_name',          
										   'num_approved_new',
										   'num_approved_react',
		                                   'num_in_underwriting_new',
		                                   'num_in_underwriting_react',
		                                   'num_funded_new',
		                                   'num_funded_dupe',
		                                   'num_funded_react',
		                                   'num_withdrawn_new',
		                                   'num_withdrawn_react',
		                                   'num_denied_new',
		                                   'num_denied_react',
		                                   'num_reverified_new',
		                                   'num_reverified_react',
		                                   'num_in_verify_new',
		                                   'num_in_verify_react',
						   				   'num_follow_up_new',
						   				   'num_follow_up_react' 
						   				    );
		$this->link_columns       = array();
		$this->totals             = array( 'company' => array( 'num_approved_new'        		=> Report_Parent::$TOTAL_AS_SUM,
															   'num_approved_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_underwriting_new' 		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_underwriting_react' 		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_funded_new'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_funded_dupe'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_funded_react'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_withdrawn_new'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_withdrawn_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_denied_new'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_denied_react'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_reverified_new'      		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_reverified_react'      		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_verify_new'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_verify_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_follow_up_new'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_follow_up_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_underwriting_new'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_underwriting_react'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_verify_new'       	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_verify_react'       	=> Report_Parent::$TOTAL_AS_SUM,),
		                                                       
		                                   'grand'   => array( 'num_approved_new'        		=> Report_Parent::$TOTAL_AS_SUM,
															   'num_approved_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_underwriting_new' 		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_underwriting_react' 		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_funded_new'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_funded_dupe'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_funded_react'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_withdrawn_new'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_withdrawn_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_denied_new'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_denied_react'          		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_reverified_new'      		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_reverified_react'      		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_verify_new'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_in_verify_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_follow_up_new'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_follow_up_react'       		=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_underwriting_new'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_underwriting_react'	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_verify_new'       	=> Report_Parent::$TOTAL_AS_SUM,
		                                                       'num_put_in_verify_react'       	=> Report_Parent::$TOTAL_AS_SUM,) );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->react_type		  = true;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}





   protected function Get_Data_HTML($company_data, &$company_totals)
   {
      $row_toggle = true;  // Used to alternate row colors
      $line       = "";

      for( $x = 0 ; $x < count($company_data) ; ++$x )
      {
         $td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

         // 1 row of data
         $line .= "    <tr>\n";
         foreach( $this->column_names as $data_name => $column_name )
         {
            // the the data link to somewhere?
            if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
            {
               // do any replacements necessary in the link
               $this->parse_data_row = $company_data[$x];
               $href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
               $line .= "<td class=\"$td_class\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</a><
/td>\n";
            }
            else
            {
               if ($data_name == 'agent_name')
               {
                  $line .= "<td class=\"{$td_class}\">"
                        . $company_data[$x][$data_name]
                        . "</td>\n";
               }
               else
               {
                  if ($td_class == 'align_left')
                  {
                     $line .= "<td class=\"align_right\">"
                           . number_format($company_data[$x][$data_name], 0, null, "," )
                           . "</td>\n";
                  }
                  else
                  {
                     $line .= "<td class=\"align_right_alt\">"
                           . number_format($company_data[$x][$data_name], 0, null, "," )
                           . "</td>\n";
                  }
               }
            }

            // If the col's data matches the criteria, total it up
            if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
            {
               switch($this->totals['company'][$data_name])
               {
                  case self::$TOTAL_AS_COUNT:
                     $company_totals[$data_name]++;
                     break;
                  case self::$TOTAL_AS_SUM:
                     $company_totals[$data_name] += $company_data[$x][$data_name];
                     break;
               }
            }
         }
         $company_totals['rows']++;
         $line .= "    </tr>\n";
      }

      return $line;
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
