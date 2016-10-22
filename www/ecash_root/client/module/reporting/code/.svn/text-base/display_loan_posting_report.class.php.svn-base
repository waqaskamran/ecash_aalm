<?php

/**
 * @package Reporting
 * @category Display
 */
class Loan_Posting_Report extends Report_Parent
{
	/**
	* Constructor sets a few things then lets Report_Parent's take care of business
	*
	* @param Transport $transport Info from the server side
	* @param string    $module_name Not used.  present for compatability with Report_Parent
	* @access public
	*/
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Loan Posting";
		$this->column_names       = array( 'company_name'     => 'Company',
											'application_id'   => 'Application ID',
		                                   'name_last'        => 'Last Name',
		                                   'name_first'       => 'First Name',
		                                   'aba'              => 'ABA #',
		                                   'card_number'      => 'Card #',
		                                   'amount'           => 'Amount',
		                                   'current_due_date' => 'Current Due Date',
		                                   'loan_type'        => 'Loan Type' );

		// Ignored by custom Format_Field() method in this file
		$this->column_format      = array( 'application_id'	  => self::FORMAT_ID, 
		                                   'aba'              => self::FORMAT_ID,  
		                                   'current_due_date' => self::FORMAT_DATE );

		$this->sort_columns       = array( 'application_id', 'name_last', 'name_first', 'amount', 'current_due_date', 'loan_type' );
		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );

		// Not used as intended for this report, but still used.
		$this->totals             = array( 'company' => array( 'rows' ), 'grand' => array( 'rows'));
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;
		
		//We're unsetting the grand totals here so they don't keep accumulating!
		unset($_SESSION['loan_posting']['grand_totals']);
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
	* @access protected
	*/
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		switch( $name )
		{
			case 'amount':
				if( $html === true )
					$return_val = '\\$' . number_format( $data, 2, null, "," );
				else
					$return_val = '$' . number_format($data, 2, null, "," );
				break;
			case 'name_last':
			case 'name_first':
				$return_val = ucwords($data);
				break;
			case 'current_due_date':
				if(($data == NULL) || ($data == "")) { $return_val = ""; }
				else
				{
					$return_val = date("n/j/y", strtotime($data));
				}
				break;
			default:
				$return_val = $data;
				break;
		}

		return $return_val;
	}

	/**
	* Gets the html for 1 company's totals
	* and updates running totals
	* Overrides Report_Parent's
	*
	* @param  array  name of the company (ufc, d1, etc)
	* @param  &array running totals so far
	* @return string
	* @access protected
	*/
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		// Save for downloading
		$_SESSION['loan_posting']['company_totals'][$company_name] = $company_totals;

		$total_html  = "    <tr>\n";
		$total_html .= "     <th class=\"report_foot\" colspan=\"6\">{$company_name} Totals : " . $company_totals['rows'];
		$total_html .= " row" . ($company_totals['rows']!=1?"s":"") . "</th>\n";
		$total_html .= "     <th class=\"report_foot\"><div style=\"text-align:right\">" . $this->Format_Field('amount',$company_totals['amount']) . "</div></th>\n";
		$total_html .= "     <th class=\"report_foot\"></th>\n";
		$total_html .= "     <th class=\"report_foot\">New: " . $company_totals['new'] . "</th>\n";
		$total_html .= "    </tr>\n";
		$total_html .= "    <tr>\n";
		$total_html .= "     <th class=\"report_foot\" colspan=\"8\"></th>\n";
		$total_html .= "     <th class=\"report_foot\">React: " . $company_totals['react'] . "</th>\n";
		$total_html .= "    </tr>\n";
		$total_html .= "    <tr>\n";
		$total_html .= "     <th class=\"report_foot\" colspan=\"8\"></th>\n";
		$total_html .= "     <th class=\"report_foot\">Resend: " . $company_totals['resend'] . "</th>\n";
		$total_html .= "    </tr>\n";
		$total_html .= "    <tr>\n";
		$total_html .= "     <th class=\"report_foot\" colspan=\"8\"></th>\n";
		$total_html .= "     <th class=\"report_foot\">Refund: " . $company_totals['refund'] . "</th>\n";
		$total_html .= "    </tr>\n";

		return $total_html;
	}

	/**
	* Gets the html for the grand totals
	* Overrides Report_Parent's
	*
	* @param  array  the grand totals for the entire report
	* @return string
	* @access protected
	*/
	protected function Get_Grand_Total_HTML($grand_totals)
	{
		//$_SESSION['loan_posting']['grand_totals'] = $grand_totals;
		$grand_totals = $_SESSION['loan_posting']['grand_totals'] ;

		$grand_html  = "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\" ";
		$grand_html .= "style=\"border-top: thin solid black;\">Grand Totals ";
		$grand_html .= ": " . $grand_totals['rows'] . " rows</th></tr>\n";
		$grand_html .= "    <tr>\n";
		$grand_html .= "     <th class=\"report_foot\" colspan=\"6\"></th>\n";
		$grand_html .= "     <th class=\"report_foot\">" . $this->Format_Field( 'amount', $grand_totals['amount']) . "</th>\n";
		$grand_html .= "     <th class=\"report_foot\"></th>\n";
		$grand_html .= "     <th class=\"report_foot\">New: " . $grand_totals['new'] . "</th>\n";
		$grand_html .= "    </tr>\n";
		$grand_html .= "    <tr>\n";
		$grand_html .= "     <th class=\"report_foot\" colspan=\"8\"></th>\n";
		$grand_html .= "     <th class=\"report_foot\">React: " . $grand_totals['react'] . "</th>\n";
		$grand_html .= "    </tr>\n";
		$grand_html .= "    <tr>\n";
		$grand_html .= "     <th class=\"report_foot\" colspan=\"8\"></th>\n";
		$grand_html .= "     <th class=\"report_foot\">Resend: " . $grand_totals['resend'] . "</th>\n";
		$grand_html .= "    </tr>\n";
		$grand_html .= "    <tr>\n";
		$grand_html .= "     <th class=\"report_foot\" colspan=\"8\"></th>\n";
		$grand_html .= "     <th class=\"report_foot\">Refund: " . $grand_totals['refund'] . "</th>\n";
		$grand_html .= "    </tr>\n";

		return $grand_html;
	}

	/**
	* Overriding Report_Parent version to get funky totals
	*
	* @param  string name of the company
	* @param  &array running totals
	* @return string
	* @access protected
	*/
	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		$company_totals = array( 'rows'    => 0,
		                         'new'     => 0,
		                         'react'   => 0,
		                         'resend'  => 0,
		                         'refund'  => 0,
		                         'amount'  => 0 );

		if (isset($_SESSION['loan_posting']['grand_totals']))
			$grand_totals = $_SESSION['loan_posting']['grand_totals'];
		else
		{
			$grand_totals = array( 'rows'    => 0,
			                       'new'     => 0,
				                   'react'   => 0,
		    	                   'resend'  => 0,
		       		               'refund'  => 0,
		           		           'amount'  => 0 );
		}


		$row_toggle = true;  // Used to alternate row colors
		$line       = "";

		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			// 1 row of data
			$line .= "    <tr>\n";
			foreach( $this->column_names as $data_name => $long_name )
			{
                if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
                {
                    // do any replacements necessary in the link
                    $this->parse_data_row = $company_data[$x];
                    $href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
                    $line .= "     <td $wrap_data class=\"$td_class\" style=\"text-align: $align;\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $this->Format_Field($data_name, $company_data[$x][$data_name])  . "</a></td>\n";
				}
				else
				{
					if ($data_name == 'amount')
					{
						$line .= "<td class=\"{$td_class}\"><div style=\"text-align:right\">"
								. '\\$' . number_format($company_data[$x][$data_name], 2, null, "," )
								. "</div></td>\n";
					}
					else if ($data_name == 'aba' || $data_name == 'account')
					{
						$line .= "<td class=\"{$td_class}\"><div style=\"text-align:right\">"
							   . $company_data[$x][$data_name]
							   . "</div></td>\n";
					}
					else
					{
						$line .= "<td class=\"{$td_class}\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
					}
				}

				// Custom totals
				switch( $data_name )
				{
					case 'loan_type':
						if( $company_data[$x][$data_name] == "React" )
						{
							$grand_totals['react']++;
							$company_totals['react']++;
						}
						elseif( $company_data[$x][$data_name] == "New" )
						{
							$grand_totals['new']++;
							$company_totals['new']++;
						}
						elseif( $company_data[$x][$data_name] == "Resend" )
						{
							$grand_totals['resend']++;
							$company_totals['resend']++;
						}
						elseif( $company_data[$x][$data_name] == "Refund" )
						{
							$grand_totals['refund']++;
							$company_totals['refund']++;
						}
						break;
					case 'amount':
						$company_totals['amount'] += $company_data[$x][$data_name];
						$grand_totals['amount']   += $company_data[$x][$data_name];
						break;
				}
			}
			$company_totals['rows']++;
			$grand_totals['rows']++;
			$line .= "    </tr>\n";
		}
		$_SESSION['loan_posting']['grand_totals'] = $grand_totals;	

		return $line;
	}

	public function Download_Data()
	{
		$grand_totals = array();
		// Holds output
		$dl_data = "";

		$dl_data .= $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";

		// All loans?  Cards only?  Standard only?
		if( $this->loan_type === true )
		{
			switch($this->search_criteria['loan_type'])
			{
				case 'all':
					$dl_data .= "For loan types: All\n";
					break;
				case 'standard':
					$dl_data .= "For loan type: Standard\n";
					break;
				case 'card':
					$dl_data .= "For loan type: Card\n";
					break;
				default:
					throw new Exception( "Unrecognized loan type: " . $this->search_criteria['loan_type'] );
					break;
			}
		}

		$dl_data .= "Date: " . $this->search_criteria['specific_date_MM'] . '/'
		                     . $this->search_criteria['specific_date_DD'] . '/'
		                     . $this->search_criteria['specific_date_YYYY'] . "\n";

		// Insert a blank line in between report header and column headers
		$dl_data .= "\n";

		$dl_data .= $this->Get_Column_Headers( false );

		// Each company
		foreach( $this->search_results as $company_name => $company_data )
		{	
			$company_totals = $_SESSION['loan_posting']['company_totals'][$company_name];

			// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
			if( isset($x) )
				$dl_data .= "\n";

			// Each row of data
			for( $x = 0 ; $x < count($company_data) ; ++$x )
			{

				// Each column
				foreach( $this->column_names as $data_col_name => $not_used )
				{
					$dl_data .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";
				}

				$dl_data = substr( $dl_data, 0, -1 ) . "\n";
			}

			//Doing grand totals based off the session is retarded.
			$grand_totals['new'] += $company_totals['new'];
			$grand_totals['rows'] += $company_totals['rows'];
			$grand_totals['amount'] = bcadd($company_totals['amount'],$grand_totals['amount'],2);
			$grand_totals['react'] += $company_totals['react'];
			$grand_totals['resend'] += $company_totals['resend'];
			$grand_totals['refund'] += $company_data['refund'];


			$dl_data .= "\n";
			$dl_data .= "Rows:\t\t\t\t\t\t\tAmount:\tNew:\t" . $company_totals['new'] . "\n";
			$dl_data .= $company_totals['rows'] . "\t\t\t\t\t\t\t\$" . number_format( $company_totals['amount'], 2, '.', ',' );
                        $dl_data .= "\tReact:\t" . $company_totals['react'] . "\n";
			$dl_data .= "\t\t\t\t\t\t\t\tResend:\t" . $company_totals['resend'] . "\n";
			$dl_data .= "\t\t\t\t\t\t\t\tRefund:\t" . $company_totals['refund'] . "\n";
		}

		if( $this->num_companies > 1 )
		{
			$dl_data .= "\n";
			$dl_data .= "GRAND\n";
			$dl_data .= "Rows:\t\t\t\t\t\t\tAmount:\tNew:\t" . $grand_totals['new'] . "\n";
			$dl_data .= $grand_totals['rows'] . "\t\t\t\t\t\t\t\$" . number_format( $grand_totals['amount'], 2, '.', ',' );
                        $dl_data .= "\tReact:\t" . $grand_totals['react'] . "\n";
			$dl_data .= "\t\t\t\t\t\t\t\tResend:\t" . $grand_totals['resend'] . "\n";
			$dl_data .= "\t\t\t\t\t\t\t\tRefund:\t" . $grand_totals['refund'] . "\n";
		}

		// for the html headers
		$data_length = strlen($dl_data);

		header( "Accept-Ranges: bytes\n");
		header( "Content-Length: $data_length\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: text/csv\n\n");

		//mantis:4324
		$generic_data = ECash::getTransport()->Get_Data();
		
		if($generic_data->is_upper_case)
			$dl_data = strtoupper($dl_data);
		else
			$dl_data = strtolower($dl_data);
		//end mantis:4324

		echo $dl_data;
	}
}

?>
