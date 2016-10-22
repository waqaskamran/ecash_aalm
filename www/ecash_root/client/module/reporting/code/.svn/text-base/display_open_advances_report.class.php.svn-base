<?php

/**
 * @package Reporting
 * @category Display
 */
class Open_Advances_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Open Advances";
		$this->column_names       = array(  'status' => 'Status',
														'positive_count' => 'Count Pos',
														'positive_balance' => '$ Pos',
														'negative_count' => 'Count Neg',
														'negative_balance' => '$ Neg',
														'total_count' => 'Count Total',
														'total_balance' => 'Total');

		$this->sort_columns       = array(  'status', 'positive_count', 'positive_balance',
														'negative_count', '', 'negative_balance', 'total_count', 'total_balance');

		$this->link_columns       = array();

		$this->totals             = array( 'company' => array('positive_count' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'positive_balance' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'negative_count' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'negative_balance' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'total_count' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'total_balance' => Report_Parent::$TOTAL_AS_SUM),
		                                   'grand'   => array('positive_count' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'positive_balance' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'negative_count' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'negative_balance' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'total_count' => Report_Parent::$TOTAL_AS_SUM,
		                                                      'total_balance' => Report_Parent::$TOTAL_AS_SUM));

		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = true;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}

	/**
	* Definition of abstract method in Report_Parent
	* Used to format field data for printing
	*
	* @param string  $name column name to format
	* @param string  $data field data
	* @param boolean $totals formatting totals or data?
	* @param boolean $html format for html?
	* @return string
	*/
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		switch( $name )
		{
			case 'total_balance':
			case 'positive_balance':
			case 'negative_balance':

            if( $html === true )
            {
               $markup = ($data < 0 ? 'color: red;' : '');
               $open = ($data < 0 ? '(' : '');
               $close = ($data < 0 ? ')' : '');
               $data = abs($data);
               return '<div style="text-align:right;'. $markup . '">' .$open.'\\$' . number_format($data, 2, '.', ',') . $close . '</div>';
            }
            else
            {
               return '$' . number_format($data, 2, '.', ',');
            }
				break;
			case 'total_count':
			case 'positive_count':
			case 'negative_count':
				$return_val = number_format( $data, 0, null, "," );
				break;
			default:
				$return_val = $data;
				break;
		}

		return $return_val;
	}


   /**
    * Gets the html for the data section of the report
    * also updates running totals
    * used only by Get_Module_HTML()
    *
    * @param  string name of the company
    * @param  &array running totals
    * @return string
    * @access protected
    */
   protected function Get_Data_HTML($company_data, &$company_totals)
   {
      $line = "";

		$x = 0;
      foreach($company_data as $company_key => $company_value)
      {
         // 1 row of data
         $line .= "    <tr>\n";
         foreach( $this->column_names as $data_name => $column_name )
         {
				if ($column_name == 'Status')
				{
         		$td_class = ($x % 2) ? "align_left" : "align_left_alt";
				}
				else
				{
         		$td_class = ($x % 2) ? "align_right" : "align_right_alt";
				}

				if (is_numeric($company_data[$company_key][$data_name]) && ($data_name != 'positive_count' && $data_name != 'negative_count'))
				{
					if (($company_data[$company_key][$data_name] < 0) || $data_name == 'negative_balance')
					{
              		$line .= "<td class=\"$td_class\">"
              				. $this->Format_Field($data_name, $company_data[$company_key][$data_name])
								. "</td>\n";
					}
					else
					{
              		$line .= "<td class=\"$td_class\">"
              				. $this->Format_Field($data_name, $company_data[$company_key][$data_name])
								. "</td>\n";
					}
				}
				else
				{
              	$line .= "<td class=\"$td_class\">" . $this->Format_Field($data_name, $company_data[$company_key][$data_name]) . "</td>\n";
				}

            // If the col's data matches the criteria, total it up
            if( $this->check_eval($company_data[$company_key], $data_name) && isset($this->totals['company'][$data_name]) )
            {
               switch($this->totals['company'][$data_name])
               {
                  case self::$TOTAL_AS_COUNT:
                     $company_totals[$data_name]++;
                     break;
                  case self::$TOTAL_AS_SUM:
                     $company_totals[$data_name] += $company_data[$company_key][$data_name];
                     break;
               }
            }

         }
         $company_totals['rows']++;
         $line .= "    </tr>\n";

			$x++;
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
					if ($company_totals[$data_name] < 0)
					{
              		$line .= "<th class=\"report_foot\"><div style=\"text-align:right\">"
								. $this->Format_Field($data_name, $company_totals[$data_name], true)
								. "</th>\n";
					}
					else
					{
               	$line .= "<th class=\"report_foot\"><div style=\"text-align:right\">" 
								. $this->Format_Field($data_name, $company_totals[$data_name], true)
								. "</div></th>\n";
					}
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
