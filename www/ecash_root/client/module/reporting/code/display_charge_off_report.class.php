<?php

/**
 * @package Reporting
 * @category Display
 */
class Charge_Off_Report extends Report_Parent
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
		$this->report_title       = "Charge-Off Report";

		$this->column_names       = array( 
				'company_name'    => 'Company',
				'loan_type'       => 'Loan Type',
				'chargeoff_date'  => 'Date',
				'application_id'  => 'Application ID',
				'loan_status'     => 'Loan Status',
				'name_first'      => 'First Name',
				'name_last'       => 'Last Name',
				'princ_balance'   => 'Principal Balance',
				'svc_chg_balance' => 'Interest Balance',
				'fee_balance'     => 'Fees Balance',
				'total_balance'   => 'Total Balance'
		);

		$this->column_format       = array( 
				'chargeoff_date'  => self::FORMAT_DATE,
				'application_id'  => self::FORMAT_ID,
				'princ_balance'   => self::FORMAT_CURRENCY,
				'svc_chg_balance' => self::FORMAT_CURRENCY,
				'total_balance'   => self::FORMAT_CURRENCY 
		);


		$this->sort_columns       = array(
				'company_name',
				'loan_type',
				'chargeoff_date',
				'application_id',
				'loan_status',
				'name_first',
				'name_last',
				'princ_balance',
				'svc_chg_balance',
				'fee_balance',
				'total_balance'
		);

		// FIXME: Formatting

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 	= array('company' => array( 'rows' => Report_Parent::$TOTAL_AS_COUNT, 'princ_balance','svc_chg_balance', 'fee_balance', 'total_balance'),
        						'grand'   => array( 'rows' => Report_Parent::$TOTAL_AS_COUNT, 'princ_balance','svc_chg_balance', 'fee_balance', 'total_balance'));
		$this->totals_conditions  = NULL;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = TRUE;
		$this->download_file_name = NULL;
		$this->ajax_reporting     = TRUE;

		parent::__construct($transport, $module_name);
	}

	// This is a hack
	protected function Get_Column_Headers( $html = true, $grand_totals = null )
	{
		if ($html !== true)
			return parent::Get_Column_Headers( $html, $grand_totals);
	}

	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		$data  = "\n";
		$data .= "Status\t";
		$data .= "# Loans\t";
		$data .= "% of Loan\t";
		$data .= "Principal\t";
		$data .= "% of Principal\t";
		$data .= "Interest\t";
		$data .= "Fees\t";
		$data .= "Total\t\n";

		foreach($this->search_results as $company => $rows)
		{
			if ($company != $company_name)
				continue;

			$n = count($rows);
				
			$status = array();

			for($i=0; $i < $n; $i++)
			{
				if (!isset($totals[$company]))
				{
					$totals[$company] = array('0', '0', '0', '0', '0', '0', '0');
				}

				if (!isset($status[$company][$rows[$i]['loan_status']]))
				{
					$status[$company][$rows[$i]['loan_status']] = array('0', '0', '0', '0', '0', '0', '0');
				}
				
				// Number of loans
				$status[$company][$rows[$i]['loan_status']][0]++;

				// Principal
				$status[$company][$rows[$i]['loan_status']][2] += $rows[$i]['princ_balance'];
				
				$status[$company][$rows[$i]['loan_status']][4] += $rows[$i]['svc_chg_balance'];

				$status[$company][$rows[$i]['loan_status']][5] += $rows[$i]['fee_balance'];
				$status[$company][$rows[$i]['loan_status']][6] += $rows[$i]['princ_balance'] + $rows[$i]['fee_balance'] + $rows[$i]['svc_chg_balance'];
				
				$totals[$company][0]++;
				$totals[$company][2] += $rows[$i]['princ_balance'];
				$totals[$company][4] += $rows[$i]['svc_chg_balance'];
				$totals[$company][5] += $rows[$i]['fee_balance'];
				$totals[$company][6] += $rows[$i]['princ_balance'] + $rows[$i]['fee_balance'] + $rows[$i]['svc_chg_balance'];
				
			}
		}

		foreach($status as $company => $c_data)
		{
			if ($company != $company_name)
				continue;

			foreach ($c_data as $m_status => $row)
			{
				if ($row[0] || $row[1] || $row[2] || $row[3] || $row[4] || $row[5] || $row[6])
				{
					$data .= "{$m_status}\t";
					$data .= "{$row[0]}\t";
					// Percentage of total
					$data .= round((($row[0] / $totals[$company][0]) * 100)) . "%\t";
					$data .= "{$row[2]}\t";
					// Percentage of Principal
					$data .= round((($row[2] / $totals[$company][2]) * 100)) . "%\t";
					$data .= "{$row[4]}\t";
					$data .= "{$row[5]}\t";
					$data .= "{$row[6]}\t\n";
				}
			}

			// Company Totals
			$data .= "$company Summary:\t";
			$data .= "{$totals[$company][0]}\t\t";
			$data .= "{$totals[$company][2]}\t\t";
			$data .= "{$totals[$company][4]}\t";
			$data .= "{$totals[$company][5]}\t";
			$data .= "{$totals[$company][6]}\t\n";
		}

		return $data;
	}

	// This also
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$html  = "";
		$html .= "<tr>\n";
		$html .= "  <th class='report_head'>Status</th>\n";
		$html .= "  <th class='report_head'># Loans</th>\n";
		$html .= "  <th class='report_head'>% of Loan</th>\n";
		$html .= "  <th class='report_head'>Principal</th>\n";
		$html .= "  <th class='report_head'>% of Principal</th>\n";
		$html .= "  <th class='report_head'>Interest</th>\n";
		$html .= "  <th class='report_head'>Fees</th>\n";
		$html .= "  <th class='report_head'>Total</th>\n";
		$html .= "</tr>\n";

		foreach($this->search_results as $company => $rows)
		{
			if ($company != $company_name)
				continue;

			$n = count($rows);
				
			$status = array();

			for($i=0; $i < $n; $i++)
			{
				if (!isset($totals[$company]))
				{
					$totals[$company] = array('0', '0', '0', '0', '0', '0', '0');
				}

				if (!isset($status[$company][$rows[$i]['loan_status']]))
				{
					$status[$company][$rows[$i]['loan_status']] = array('0', '0', '0', '0', '0', '0', '0');
				}
				
				// Number of loans
				$status[$company][$rows[$i]['loan_status']][0]++;

				// Principal
				$status[$company][$rows[$i]['loan_status']][2] += $rows[$i]['princ_balance'];
				
				$status[$company][$rows[$i]['loan_status']][4] += $rows[$i]['svc_chg_balance'];

				$status[$company][$rows[$i]['loan_status']][5] += $rows[$i]['fee_balance'];
				$status[$company][$rows[$i]['loan_status']][6] += $rows[$i]['princ_balance'] + $rows[$i]['fee_balance'] + $rows[$i]['svc_chg_balance'];
				
				$totals[$company][0]++;
				$totals[$company][2] += $rows[$i]['princ_balance'];
				$totals[$company][4] += $rows[$i]['svc_chg_balance'];
				$totals[$company][5] += $rows[$i]['fee_balance'];
				$totals[$company][6] += $rows[$i]['princ_balance'] + $rows[$i]['fee_balance'] + $rows[$i]['svc_chg_balance'];
				
			}
		}

		foreach($status as $company => $data)
		{
			if ($company != $company_name)
				continue;

			foreach ($data as $status => $row)
			{
				if ($row[0] || $row[1] || $row[2] || $row[3] || $row[4] || $row[5] || $row[6])
				{
					$html .= "<tr>\n";
					$html .= "  <th class='report_foot'>$status</td>\n";
					$html .= "  <th class='report_foot'>{$row[0]}</td>\n";
					// Percentage of total
					$html .= "  <th class='report_foot'>" . round((($row[0] / $totals[$company][0]) * 100)) . "%</td>\n";
					$html .= "  <th class='report_foot'>{$row[2]}</td>\n";
					// Percentage of Principal
					$html .= "  <th class='report_foot'>" . round((($row[2] / $totals[$company][2]) * 100)) . "%</td>\n";
					$html .= "  <th class='report_foot'>{$row[4]}</td>\n";
					$html .= "  <th class='report_foot'>{$row[5]}</td>\n";
					$html .= "  <th class='report_foot'>{$row[6]}</td>\n";
					$html .= "</tr>\n";
				}
			}

			// Company Totals
			$html .= "<tr>\n";
			$html .= "  <th class='report_foot'>$company Summary:</td>\n";
			$html .= "  <th class='report_foot'>{$totals[$company][0]}</td>\n";
			$html .= "  <td>&nbsp;</td>\n";
			$html .= "  <th class='report_foot'>{$totals[$company][2]}</td>\n";
			$html .= "  <td>&nbsp;</td>\n";
			$html .= "  <th class='report_foot'>{$totals[$company][4]}</td>\n";
			$html .= "  <th class='report_foot'>{$totals[$company][5]}</td>\n";
			$html .= "  <th class='report_foot'>{$totals[$company][6]}</td>\n";
			$html .= "</tr>\n";

		}


		return $html;
	}



}

?>
