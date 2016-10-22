<?php

/**
 * @package Reporting
 * @category Display
 */
class Accounts_Recievable_Report extends Report_Parent
{
	/**
	* count of customers in each pay period
	* @var array
	* @access private
	*/
	private $run_totals;

	/**
	* Grand totals for all companies
	* @var array
	* @access private
	*/
	private $grand_totals;

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
		$this->report_title       = "AR report";
	
		$this->column_names       = array( 'company_name' => 'Company',
										   'application_id' => 'Application ID',
		                                   'name_last'      => 'Last Name',
		                                   'name_first'     => 'First Name',
		                                   'status'         => 'Current Status',
		                                   'prev_status'      => 'Previous Status',
		                                   'fund_date'             => 'Fund Date',
		                                   'fund_age'      => 'Funded Age',
		                                   'collection_age'           => 'Collection Age',
		                                   'status_age' => 'Status Age',
		                                   'payoff_amt'     => 'Payoff Amount',
		                                   'principal_pending'       => 'Principal Pending',
		                                   'principal_fail'      => 'Principal Failed',
		                                   'principal_total'      => 'Principal Total',
		                                   'service_charge_pending'  => 'Service Charge Pending',
		                                   'service_charge_fail'         => 'Service Charge Failed',
		                                   'service_charge_total'         => 'Service Charge Total',
		                                   'fees_pending'        => 'Return Transaction Fees Pending',
		                                   'fees_fail'        => 'Return Transaction Fees  Failed',
		                                   'fees_total'        => 'Return Transaction Fees Total',
		                                   'nsf_ratio'        => 'NSF Ratio',
		                                   
										 );

		$this->sort_columns       = array( 'company_name' => 'Company',
											'application_id', 'name_last',
		                                   'name_first',     'status',
		                                   'prev_status',      'fund_date',
		                                   'fund_age',           'service_charge_pending',
		                                   'service_charge_fail',     'payoff_amt',
		                                   'principal_pending',
		                                   'principal_fail',  'collection_age', 'status_age','fees_pending'
		                                   ,'fees_fail','nsf_ratio' ,'fees_total','service_charge_total','service_charge_total');

		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array( 'rows',
		                                                       'Status'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'Funded Aging'           => Report_Parent::$TOTAL_AS_SUM,
		                                                       'Collections Aging' => Report_Parent::$TOTAL_AS_SUM,
		                                                       'Total Open'     => Report_Parent::$TOTAL_AS_SUM,
		                                                       'Payoff Amount'  => Report_Parent::$TOTAL_AS_SUM,
		                                                       'Principal Pending'         => Report_Parent::$TOTAL_AS_SUM,
		                                                       'Principal Failed'        => Report_Parent::$TOTAL_AS_SUM ),
		                                   'grand'   => array( 'rows',
		                                                       'principal_fail'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'fees_fail'           => Report_Parent::$TOTAL_AS_SUM,
		                                                       'service_charge_fail' => Report_Parent::$TOTAL_AS_SUM,
		                                                       'payoff_amt'     => Report_Parent::$TOTAL_AS_SUM,
		                                                       'principal_pending'  => Report_Parent::$TOTAL_AS_COUNT,
		                                                       'service_charge_pending'         => Report_Parent::$TOTAL_AS_SUM,
		                                                       'fees_pending'        => Report_Parent::$TOTAL_AS_SUM ) );

		$this->totals_conditions  = null;  // special is either 0 or 1 so SUM should give us the correct count.
		// $this->totals_conditions  = array( 'special' => " strlen('%%%var%%%') > 0 && '%%%var%%%' != '0' ? true : false " );

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		
		$this->download_file_name = null;
		$this->company_list_no_all = true;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

	

	
	/**
	 * Definition of abstract method in Report_Parent
	 * Used to format field data for printing
	 *
	 * @param  string  $name   column name to format
	 * @param  string  $data   field data
	 * @param  boolean $totals formatting totals or data?
	 * @param  boolean $html   format for html?
	 * @return string          formatted field
	 * @access protected
	 */
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		switch( $name )
		{
			case 'totals':
				if( $html === true )
					return ' &nbsp;(\\$' . $data . ')';
				else
					return '  ($' . $data . ')';
				break;
			case 'principal_pending':
			case 'fees_pending':
			case 'service_charge_pending':
			case 'principal_fail':
			case 'fees_fail':
			case 'service_charge_fail':
			case 'principal_total':
			case 'fees_total':
			case 'service_charge_total':
			case 'payoff_amt':
				if( $html === true )
					return '\\$' . number_format( $data, 2, '.', ',' );
				else
					return '$' . number_format( $data, 2, '.', ',' );
				break;
			case 'nsf_ratio':
				if(is_null($data))
				{
					return '';
				}
				return  number_format( $data, 2, '.', ',' ) . '%';
			break;
			case 'fund_date':
				if(is_null($data))
				{
					return '';
				}
				$align = 'right';
                return date('m/d/y',strtotime($data));
				break;
			case 'first_payment':
			case 'payout':
			case 'special':
				if( $totals === true )
					return $data;

				if( $html === false )
					return ($data==1?"YES":"no");

				if( $data == 1 )
				{
					return '<center><span style="font-weight:bold;color:green;">YES</span></center>';
				}
				else
				{
					return '<center><span style="font-weight:bold;color:red;">no</span></center>';
				}
				break;
			default:
				return $data;
		}
	}

	/**
	* Overrides Report_Parent since this report has custom elements
	*
	* @throws Exception on invalid data stored in this->search_criteria
	* @access public
	*/
	public function Download_Data()
	{
		// Holds output
		$dl_data = "";

		$dl_data .= $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";

		// All loans?  Cards only?  Standard only?
	

		// Is the report run for a specific date, date range, or do dates not matter?
		$dl_data .= "Date: " . $this->search_criteria['specific_date_MM'] . '/'
		                     . $this->search_criteria['specific_date_DD'] . '/'
		                     . $this->search_criteria['specific_date_YYYY'] . "\n";

		// Insert a blank line between report header and column headers
		$dl_data .= "\n";

		$total_rows = 0;
		$dl_data .= $this->Get_Column_Headers( false );
		foreach( $this->search_results as $company_name => $company_data )
		{
			$company_totals = array();

			// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
			if( isset($x) )
				$dl_data .= "\n";

			for( $x = 0 ; $x < count($company_data) ; ++$x )
			{
			//	$dl_data .= $company_name;
				foreach( $this->column_names as $data_col_name => $not_used )
				{
					$dl_data .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";
				}

				foreach( $this->column_names as $data_name => $column_name )
				{
					if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
					{
						if(!is_array($company_totals))
						{
							$company_totals = array();
						}
						if(!isset($company_totals[$data_name]))
						{
							$company_totals[$data_name] = 0;
						}

						switch($this->totals['company'][$data_name])
						{
							case self::$TOTAL_AS_COUNT:
								$company_totals[$data_name]++;
								break;
							case self::$TOTAL_AS_SUM:
								$company_totals[$data_name] += $company_data[$x][$data_name];
								break;
							default:
								// Dont do anything - This should
								// never be reached
						}
					}
				}
				$company_totals['rows']++;

				$dl_data = substr( $dl_data, 0, -1 ) . "\n";
			}

			$total_rows += count($company_data);

			// If there's more than one company, show a company totals line
			//if( count($this->totals['company']) > 0 )
			//{
				$dl_data .= $this->Get_Company_Total_Line($company_name, $company_totals) . "\n\n";
			//}

			$dl_data .= $this->Get_Company_Foot($company_name, false);
		}

		if( $this->num_companies > 0 )
			$dl_data .= $this->Get_Report_Foot( false );

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

//	/**
//	* Overrides Report_Parent since this report has custom processing
//	*/
protected function Get_Data_HTML($company_data, &$company_totals)
        {
                $row_toggle = true;  // Used to alternate row colors
                $line       = "";

		$wrap_data   = $this->wrap_data ? '' : 'nowrap';
		$current_status = '';
		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";
			

			// 1 row of data
			$line .= "    <tr>\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
			//	if (empty($company_totals[$data_name])) $company_totals[$data_name] = 0;
				$align = 'left';
				$data = $this->Format_Field($data_name,  isset($company_data[$x][$data_name]) ? $company_data[$x][$data_name] : null, false, true, $align);
				// the the data link to somewhere?
				if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name]) && isset($company_data[$x]['mode']))
				{
					// do any replacements necessary in the link
					$this->parse_data_row = $company_data[$x];
					$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
					$line .= "     <td $wrap_data class=\"$td_class\" style=\"text-align: $align;\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $data . "</a></td>\n";
				}
				else
				{
					$line .= "     <td $wrap_data class=\"$td_class\" style=\"text-align: $align;\">" . $data . "</td>\n";
				}

				// If the col's data matches the criteria, total it up
			//	if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
			//	{
					switch($data_name)
					{
						case 'status':
							if ($company_data[$x][$data_name] != $current_status)
							{
								$current_status = $company_data[$x][$data_name];
								if(!is_array($company_totals[$current_status]))
									$company_totals[$current_status] = array();	
								$company_totals[$current_status]['open']++;
							}
							else
							{
								$company_totals[$current_status]['open']++;
							}
							$company_totals['count']++;
						break;
						case 'payoff_amt':
							$company_totals[$current_status][$data_name] += $company_data[$x][$data_name];
							$company_totals['total'] += $company_data[$x][$data_name];
						break;
						case 'fund_age':
						case 'collection_age':
						case 'principal_pending':
						case 'principal_fail':
						case 'principal_total':
						case 'service_charge_total':
						case 'service_charge_pending':
						case 'service_charge_fail':
						case 'fees_total':
						case 'fees_pending':
						case 'fees_fail':
						case 'principal_total':
							$company_totals[$current_status][$data_name] += $company_data[$x][$data_name];
						break;
																		
						default:
							// Dont do anything, somebody screwed up
					}
				//}
			}
			
			$company_totals['rows']++;
			$line .= "    </tr>\n";
		}
	//	echo '<pre>'.print_r($company_totals,true).'</pre>';
		return $line;
	}


	/**
	* Overrides Report_Parent since this report has custom processing
	*/
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$line = "<table border =1><tr><th>Company<th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Current&nbsp;Status&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th><th>Total Open<th>Payoff Amount<th>Principal Pending<th>Principal Failed<th>Principal Total";
		$line .= "<th>Service Charge Pending<th>Service Charge Failed<th>Service Charge Total<th>Return Transaction Fees Pending<th>Return Transaction Fees Failed<th>Return Transaction Fees Total";
		$line .= "<th>Count Ratio<th>Payoff Ratio<th>NSF Ratio";
	
	
		$keys = array_keys($company_totals);
		$totals = array();
		$past_due_totals = array();
		sort($keys);
		foreach($keys as $status)
		{
			if($status == 'count' || $status == 'total' || $status == 'rows' || empty($status))
				continue;
			
			if(($status != 'Active') && ($status != 'Refi')) 
			{
				$past_due_totals['payoff_amt'] += $company_totals[$status]['payoff_amt'];
				$past_due_totals['principal_pending'] += $company_totals[$status]['principal_pending'];
				$past_due_totals['principal_fail'] += $company_totals[$status]['principal_fail'];
				$past_due_totals['principal_total'] += $company_totals[$status]['principal_total'];
				$past_due_totals['service_charge_pending'] += $company_totals[$status]['service_charge_pending'];
				$past_due_totals['service_charge_fail'] += $company_totals[$status]['service_charge_fail'];
				$past_due_totals['service_charge_total'] += $company_totals[$status]['service_charge_total'];
				$past_due_totals['fees_pending'] += $company_totals[$status]['fees_pending'];
				$past_due_totals['fees_fail'] += $company_totals[$status]['fees_fail'];
				$past_due_totals['fees_total'] += $company_totals[$status]['fees_total'];
				$past_due_totals['open'] += $company_totals[$status]['open'];
			}
			$totals['payoff_amt'] += $company_totals[$status]['payoff_amt'];
			$totals['principal_pending'] += $company_totals[$status]['principal_pending'];
			$totals['principal_fail'] += $company_totals[$status]['principal_fail'];
			$totals['principal_total'] += $company_totals[$status]['principal_total'];
			$totals['service_charge_pending'] += $company_totals[$status]['service_charge_pending'];
			$totals['service_charge_fail'] += $company_totals[$status]['service_charge_fail'];
			$totals['service_charge_total'] += $company_totals[$status]['service_charge_total'];
			$totals['fees_pending'] += $company_totals[$status]['fees_pending'];
			$totals['fees_fail'] += $company_totals[$status]['fees_fail'];
			$totals['fees_total'] += $company_totals[$status]['fees_total'];
			
			
			$line .= "<tr>";
			$line .= "<th class=\"report_foot\">";
			$line .= $company_name;
			$line .= "<th class=\"report_foot\">";
			$line .= $status;
//			$line .= "<th class=\"report_foot\">";
//			$line .= $company_totals[$status]['fund_age'];
//			$line .= "<th class=\"report_foot\" >";
//			$line .= $company_totals[$status]['collection_age'];		
			$line .= "<th class=\"report_foot\">";
			$line .= $company_totals[$status]['open'];
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['payoff_amt'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['principal_pending'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['principal_fail'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['principal_total'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['service_charge_pending'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['service_charge_fail'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['service_charge_total'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['fees_pending'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['fees_fail'],true);
			$line .= "<th class=\"report_foot\">";
			$line .= @$this->Format_Field('payoff_amt',$company_totals[$status]['fees_total'],true);
			$line .= "<th class=\"report_foot\">";
			$line .=  @$this->Format_Field('nsf_ratio',(($company_totals[$status]['open']/$company_totals['count'])*100),true) ;
			$line .= "<th class=\"report_foot\">";
			$line .=  @$this->Format_Field('nsf_ratio',(($company_totals[$status]['payoff_amt']/$company_totals['total'])*100),true) ;
			$line .= "<th class=\"report_foot\">";
			$line .=  @$this->Format_Field('nsf_ratio',((($company_totals[$status]['principal_fail']+$company_totals[$status]['service_charge_fail'])/$company_totals[$status]['payoff_amt'])*100),true) ;
		
		}
		
		$line .= "<tr><th class=\"report_foot\" colspan=16><tr>";
		$line .= "<th class=\"report_foot\">";
		$line .= $company_name;
		$line .= "<th class=\"report_foot\">";
		$line .= 'Subtotal Current';
//		$line .= "<th class=\"report_foot\">";
//		$line .= "<th class=\"report_foot\">";
		$line .= "<th class=\"report_foot\">";
		$line .= $company_totals['Active']['open'];
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['payoff_amt'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['principal_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['principal_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['principal_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['service_charge_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['service_charge_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['service_charge_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['fees_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['fees_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$company_totals['Active']['fees_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',(($company_totals['Active']['open']/$company_totals['count'])*100),true) ;
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',(($company_totals['Active']['payoff_amt']/$company_totals['total'])*100),true) ;
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',((($company_totals['Active']['principal_fail']+$company_totals['Active']['service_charge_fail'])/$company_totals['Active']['payoff_amt'])*100),true) ;
		$line .= "<tr>";
		$line .= "<th class=\"report_foot\">";
		$line .= $company_name;
		$line .= "<th class=\"report_foot\">";
		$line .= 'Subtotal Past Due';
//		$line .= "<th class=\"report_foot\">";
//		$line .= "<th class=\"report_foot\">";
		$line .= "<th class=\"report_foot\">";
		$line .=	$past_due_totals['open'];
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['payoff_amt'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['principal_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['principal_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['principal_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['service_charge_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['service_charge_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['service_charge_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['fees_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['fees_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$past_due_totals['fees_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',(($past_due_totals['open']/$company_totals['count'])*100),true) ;
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',(($past_due_totals['payoff_amt']/$company_totals['total'])*100),true) ;
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',((($past_due_totals['principal_fail']+$past_due_totals['service_charge_fail'])/$past_due_totals['payoff_amt'])*100),true) ;
		$line .= "<tr>";
		$line .= "<th class=\"report_foot\">";
		$line .= $company_name;
		$line .= "<th class=\"report_foot\">";
		$line .= 'Total';
//		$line .= "<th class=\"report_foot\">";
//		$line .= "<th class=\"report_foot\">";
		$line .= "<th class=\"report_foot\">";
		$line .= $company_totals['count'];
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['payoff_amt'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['principal_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['principal_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['principal_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['service_charge_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['service_charge_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['service_charge_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['fees_pending'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['fees_fail'],true);
		$line .= "<th class=\"report_foot\">";
		$line .= @$this->Format_Field('payoff_amt',$totals['fees_total'],true);
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',(($company_totals['count']/$company_totals['count'])*100),true) ;
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',(($totals['payoff_amt']/$company_totals['total'])*100),true) ;
		$line .= "<th class=\"report_foot\">";
		$line .=  @$this->Format_Field('nsf_ratio',((($totals['principal_fail']+$totals['service_charge_fail'])/$totals['payoff_amt'])*100),true) ;
		
		$line .= '</table>';

		return $line;
	}

	/**
	* Overrides Report_Parent since this report has custom processing
	*/
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		$line = "";

		$slug = "$company_name Totals : " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"");

		$line .= "$slug\t";

		if( (! empty($this->totals['company']['rows']) && count($this->totals['company']) > 1 ) ||
			(empty($this->totals['company']['rows']) && count($this->totals['company']) > 0 ))
		{
			foreach( $this->column_names as $data_name => $column_name )
			{
				if ($data_name != 'company_name')
				{
					if( ! empty($this->totals['company'][$data_name]) )
					{
						$line .= stripslashes($this->Format_Field($data_name, $company_totals[$data_name], true));
					}
					$line .= "\t";
				}
			}
			$line .= "\n";
		}

		return $line;
	}
	
	protected function Get_Column_Headers( $html = true, $grand_totals = null )
	{
		$column_headers = "";
		$wrap_header = $this->wrap_header ? '' : 'nowrap';

		// For company headers (with sort links)
		if( $html === true && ! isset($grand_totals) )
		{
			// Column names
			$column_headers .= "    <tr>\n";
			foreach( $this->column_names as $data_col_name => $column_name )
			{
				//print_r($this)
				// make the column name a sort link if wanted
				if( in_array( $data_col_name, $this->sort_columns ) &! $this->ajax_reporting)
				{
					$column_headers .= "     <th $wrap_header class=\"report_head\"><a href=\"?module=reporting&mode=".$_REQUEST['mode']."&sort=" . urlencode($data_col_name) . "\">$column_name</a></th>\n";
				}
				elseif ($this->ajax_reporting && !in_array($data_col_name,array_keys($this->totals['company'])))
				{
					$column_headers .= "     <th $wrap_header class=\"report_head\"></th>\n";
				}
				else
				{
					$column_headers .= "     <th $wrap_header class=\"report_head\">$column_name</th>\n";
				}
			}
			$column_headers .= "    </tr>\n";
		}
		else if( $html === true ) // For grand totals (no sort links)
		{
			// Column names again for eash reference
			$column_headers .= "    <tr>\n";
			foreach( $this->column_names as $data_col_name => $column_name )
			{
				// Only print the column headers for columns showing a grand total
				if( isset($grand_totals[$data_col_name]) )
				{
					$column_headers .= "     <th class=\"report_head\">$column_name</th>\n";
				}
				else
				{
					$column_headers .= "     <th></th>\n";
				}
			}
			$column_headers .= "    </tr>\n";
		}
		else // For downloading (tab seperated)
		{
			$column_headers .= "";

			foreach( $this->column_names as $data_name => $column_name )
			{
//				if( !empty($this->totals['grand'][$data_name]) )
//				{
					$column_headers .= $column_name . "\t";
//				}
//				else
//				{
		//			$column_headers .= "\t";
	//			}
			}

			$column_headers = substr( $column_headers, 0, -1 ) . "\n";
		}

		return $column_headers;
	}
}


?>
