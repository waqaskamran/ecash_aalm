<?php

/**
 * @package Reporting
 * @category Display
 */
class Agent_Tracking_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Agent Tracking Report";
		$this->column_names = array( 'agent_name'          			=> 'Agent',
		                             //'num_action_login'        	=> 'Logins',
		                             //'num_action_logout' 			=> 'Logouts',
		                            'num_action_reactivate'    		=> 'React Button',
		                            'num_action_react_offer'   		=> 'React Offer Button',
		                            'num_action_search'   		=> 'Search Apps',
 									'num_verification_react'		=> 'Verification (react)', 	
 									'num_verification_non_react' 	=> 'Verification (non-react)',
 									'num_underwriting_react'		=> 'Underwriting (react)',
						 			'num_underwriting_non_react'	=> 'Underwriting (non-react)',
									'num_watch'						=> 'Watch',
									'num_collections_new'			=> 'Collections New',
						 			'num_collections_returned_qc'	=> 'Collections Returned QC',
									'num_collections_general'		=> 'Collections General'
		                             );
		$this->sort_columns = array(	'agent_name',          
										'num_action_login',
                                         'num_action_logout',
                                         'num_action_search',
                                         'num_action_reactivate',
                                         'num_action_react_offer',
                                         'num_verification_react',
                                         'num_verification_non_react',
                                         'num_underwriting_react',
                                         'num_underwriting_non_react',
                                         'num_watch',
                                         'num_collections_new',
                                         'num_collections_returned_qc',
                                         'num_collections_general',
		                             	);
		$this->link_columns = array();
		$this->totals       = array( 'company' => array( 'num_action_login'        		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_action_logout' 			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_action_search'			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_action_reactivate'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_action_react_offer'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_verification_react'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_verification_non_react'   => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_underwriting_react'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_underwriting_non_react'   => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_watch'   					=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_collections_new'   		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_collections_returned_qc'  => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_collections_general'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 ),
		                             
		                             'grand'   => array( 'num_action_login'        	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_action_logout' 			=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_action_reactivate'    	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_action_react_offer'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_verification_react'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_verification_non_react'   => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_underwriting_react'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_underwriting_non_react'   => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_watch'   					=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_collections_new'   		=> Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_collections_returned_qc'  => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_collections_general'   	=> Report_Parent::$TOTAL_AS_SUM,
		                                                 ) 
		                            );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		// Turns on AJAX Reporting
		$this->ajax_reporting 	  = true;

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

            if (empty($company_totals[$data_name])) $company_totals[$data_name] = 0;
         	
            // the the data link to somewhere?
            if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
            {
               // do any replacements necessary in the link
               $this->parse_data_row = $company_data[$x];
               $href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
               $line .= "     <td class=\"$td_class\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</a></td>\n";
            }
            else
            {
					if (is_numeric($company_data[$x][$data_name]))
					{
						if ($td_class == 'align_left')
						{
               		$line .= "<td class=\"align_right\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
						}
						else
						{
               		$line .= "<td class=\"align_right_alt\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
						}
					}
					else
					{
               	$line .= "<td class=\"$td_class\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
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
					$company_totals[$data_name] += isset($company_data[$x][$data_name]) ? $company_data[$x][$data_name] : 0;
                     break;
                  default:
                     // Dont do anything, somebody screwed up
               }
            }
         }
         $company_totals['rows']++;

      }

      return;
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
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap>$company_name Totals ";
            $line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th></tr>\n";
         }
         else
         {
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\" nowrap>$company_name Totals</th></tr>\n";
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
                  $line .= "     <th class=\"report_foot\" nowrap>$company_name Totals ";
                  $line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th>\n";
               }
               else
               {
                  $line .= "     <th class=\"report_foot\" nowrap>$company_name Totals</th>\n";
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
