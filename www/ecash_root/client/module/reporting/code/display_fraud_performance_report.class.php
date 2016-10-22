<?php

/**
 * @package Reporting
 * @category Display
 */
class Fraud_Performance_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$temp = ECash::getTransport()->Get_Data();
				
		$this->report_title = "Fraud Performance Report";
		$this->column_names = array( 'agent_name'          => 'Agent',
									 'num_pulled'		   => 'Pulled',
		                             'num_in_moved'        => 'Released',
		                             'num_withdrawn'       => 'Withdrawn',
		                             'num_denied'          => 'Denied');
		$this->sort_columns = array( 'agent_name',          'num_pulled',
		                             'num_in_moved', 		
		                             'num_withdrawn',       'num_denied');
		$this->link_columns = array();
		$this->totals       = array( 'company' => array( 'num_pulled'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_in_moved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_followup'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_denied'          => Report_Parent::$TOTAL_AS_SUM),
		                             'grand'   => array( 'num_pulled'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_in_moved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_followup'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_denied'          => Report_Parent::$TOTAL_AS_SUM));
		$this->totals_conditions  = NULL;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = NULL;

		//fraud module shows all companies
		$this->company_list = TRUE;

		parent::__construct($transport, $module_name);
	}

	protected function Get_Form_Options_HTML(stdClass &$substitutions)
	{
		parent::Get_Form_Options_HTML($substitutions);

		//put a queue_type selection box where the loan_type dropdown would normally be
		$substitutions->loan_type_select_list  = '<span style="float:left;">Queue Type : </span><span style="float:left;margin-left:10px;"><select name="queue_type" size="1" style="width:90px;"></span>';
		switch( $this->search_criteria['queue_type'] )
		{
			case 'fraud':
				$substitutions->loan_type_select_list .= '<option value="fraud" selected>Fraud</option>';
				$substitutions->loan_type_select_list .= '<option value="high_risk">High Risk</option>';
				break;
			default:
			case 'high_risk':
				$substitutions->loan_type_select_list .= '<option value="fraud">Fraud</option>';
				$substitutions->loan_type_select_list .= '<option value="high_risk" selected>High Risk</option>';
				break;
		}
		$substitutions->loan_type_select_list .= '</select>';
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
               $line .= "<td class=\"$td_class\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</a></td>\n";
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
                     $company_totals[$data_name] += $company_data[$x][$data_name];
                     break;
                  default:
                     // Dont do anything, somebody screwed up
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
