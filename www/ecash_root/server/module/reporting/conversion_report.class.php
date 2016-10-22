<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

// This report should probably be removed.

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");
require_once(SERVER_CODE_DIR . "base_report_query.class.php");

class Report extends Report_Generic
{
	public function Generate_Report()
	{
		return($this->Download_Report());
	}

	public function CSV_Escape($value)
	{
		$value = str_replace('"','""',$value);
		$value = '"'.$value.'"';

		return($value);
	}

	public function Download_Report()
	{
		$search = new Conversion_Report_Query($this->server);

		$counts = $search->Fetch_Queue_Counts();

		$dl = array();
		foreach($counts as $company_id => $company_data)
		{
			// COMPANY => array(
			//     'name' => '',                  // Human readable name of company
			//     'total' => 0,                  // Total number of loans added to eCash
			//     'pending' => 0,                // [Summary Section] Pending Loans
			//     'manual' => array(             // [Summary Section] array_sum() == Manual Conversion
			//         'conversion_manager' => 0, // [Detail Section] Manager Queue
			//         'hold' => 0,               // [Detail Section] Hold Queue
			//         'active' => 0,             // [Detail Section] Active Queue
			//         'collection' => 0,         // [Detail Section] Collection Queue
			//         'other' => 0,              // [Detail Section] Other Queue
			//         ),
			//     'automatic' => array(          // [Summary Section] array_sum() == Automatic Conversion
			//         APPLICATION_STATUS => 0,   // [Loans Converted Automatically]
			//         ),
			//     ),
			$dl[$company_id][] = array("Conversion Audit Report");
			$dl[$company_id][] = array("Summary Section");
			$dl[$company_id][] = array("Current Date and Time",date("Y-m-d H:i:s"));
			$dl[$company_id][] = array("Company",$company_data["name"]);
			$dl[$company_id][] = array();
			$dl[$company_id][] = array("Total Number of loans added to eCash",$company_data["total"]);
			$dl[$company_id][] = array();
			$dl[$company_id][] = array("Pending Loans"        ,           $company_data["pending"  ] );
			$dl[$company_id][] = array("Manual Conversion"    , array_sum($company_data["manual"   ]));
			$dl[$company_id][] = array("Automatic Conversion" , array_sum($company_data["automatic"]));
			$dl[$company_id][] = array();
			$dl[$company_id][] = array("Detail");
			$dl[$company_id][] = array();
			$dl[$company_id][] = array("Loans for Manual Conversion");
			$dl[$company_id][] = array("Manager Queue"    , $company_data["manual"]["conversion_manager"]);
			$dl[$company_id][] = array("Hold Queue"       , $company_data["manual"]["hold"              ]);
			$dl[$company_id][] = array("Active Queue"     , $company_data["manual"]["active"            ]);
			$dl[$company_id][] = array("Collection Queue" , $company_data["manual"]["collection"        ]);
			$dl[$company_id][] = array("Other Queue"      , $company_data["manual"]["other"             ]);
			$dl[$company_id][] = array();
			$dl[$company_id][] = array("Total",array_sum($company_data["manual"   ]));
			$dl[$company_id][] = array();
			$dl[$company_id][] = array();
			$dl[$company_id][] = array("Loans Converted Automatically");
			foreach($company_data["automatic"] as $app_stat => $count)
			{
				$dl[$company_id][] = array($app_stat,$count);
			}
			$dl[$company_id][] = array("Total Loans Converted Automatically" , array_sum($company_data["automatic"]));
		}

		foreach($dl as $cmp_id => $company)
		{
			foreach($company as $row_id => $row)
			{
				foreach($row as $col_id => $col)
				{
					$dl[$cmp_id][$row_id][$col_id] = $this->CSV_Escape($dl[$cmp_id][$row_id][$col_id]);
				}
				$dl[$cmp_id][$row_id] = join(",",$dl[$cmp_id][$row_id]);
			}
			$dl[$cmp_id] = join("\n",$dl[$cmp_id]);
		}
		$dl = join("\n\n\n\n\n\n\n",$dl);

		header("Accept-Ranges: bytes\n");
		header("Content-Length: ".strlen($dl)."\n");
		header("Content-Disposition: attachment; filename=conversion_report.csv\n");
		header("Content-Type: text/csv\n\n");
		print($dl);

	}
}

class Conversion_Report_Query extends Base_Report_Query
{
	public function Fetch_Queue_Counts()
	{
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				`application`.`company_id` AS `output_company_id`,
				`application`.`application_status_id` AS `output_application_status_id`,
				IF(!ISNULL(@a:=`cl_pending_data`.`cashline_status`) AND (`application_status_flat`.`level0` IN ('queued','dequeued') AND `application_status_flat`.`level1`='cashline' AND `application_status_flat`.`level2`='*root')
					, @a
					, IF(!ISNULL(`agent_affiliation`.`application_id`) AND (`application_status_flat`.`level0` = 'dequeued' AND `application_status_flat`.`level1`='cashline' AND `application_status_flat`.`level2`='*root')
						, 'conversion_manager'
						, ''
						)
					) AS `output_cashline_status`,
				`company`.`name` AS `output_company_name`,
				`application_status_flat`.`level0_name` AS `output_application_status`,
				COUNT(*) AS `output_count`
			FROM      `application`
			JOIN      `company`                 ON (`application`.`company_id` = `company`.`company_id`)
			LEFT JOIN `cl_pending_data`         ON (`cl_pending_data`.`application_id` = `application`.`application_id`)
			LEFT JOIN `application_status_flat` ON (`application_status_flat`.`application_status_id` = `application`.`application_status_id`)
			LEFT JOIN `agent_affiliation`       ON (`agent_affiliation`.`application_id` = `application`.`application_id` AND `agent_affiliation`.`affiliation_area` = 'conversion' AND `agent_affiliation`.`affiliation_type` = 'owner')
			WHERE
				`application`.`application_id` BETWEEN 11000000 AND 14000000
			GROUP BY
				`output_company_id`,
				`output_application_status_id`,
				`output_cashline_status`
			";
		$query = preg_replace('/(^\s+--.*$)|(^\s+)/m','',$query);
		$st = $this->db->query($query);

		$return = array(
			// COMPANY => array(
			//     'name' => '',                  // Human readable name of company
			//     'total' => 0,                  // Total number of loans added to eCash
			//     'pending' => 0,                // [Summary Section] Pending Loans
			//     'manual' => array(             // [Summary Section] array_sum() == Manual Conversion
			//         'conversion_manager' => 0, // [Detail Section] Manager Queue
			//         'hold' => 0,               // [Detail Section] Hold Queue
			//         'active' => 0,             // [Detail Section] Active Queue
			//         'collection' => 0,         // [Detail Section] Collection Queue
			//         'other' => 0,              // [Detail Section] Other Queue
			//         ),
			//     'automatic' => array(          // [Summary Section] array_sum() == Automatic Conversion
			//         APPLICATION_STATUS => 0,   // [Loans Converted Automatically]
			//         ),
			//     ),
			);
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$c = $row['output_company_id'];

			if(!isset($return[$c]))
			{
				$return[$c] = array(
					'name' => $row['output_company_name'],
					'total' => 0,
					'pending' => 0,
					'manual' => array(
						'conversion_manager' => 0,
						'hold' => 0,
						'active' => 0,
						'collection' => 0,
						'other' => 0,
						),
					'automatic' => array(
						),
					);
			}

			$return[$c]['total'] += $row['output_count'];
			if(in_array($row['output_application_status_id'],array(114)))
			{
				$return[$c]['pending'] += $row['output_count'];
			}
			elseif(in_array($row['output_application_status_id'],array(115,116)))
			{
				if(!isset($return[$c]['manual'][$row['output_cashline_status']]))
				{
					$row['output_cashline_status'] = 'other';
				}

				$return[$c]['manual'][$row['output_cashline_status']] += $row['output_count'];
			}
			else
			{
				if(!isset($return[$c]['automatic'][$row['output_application_status']]))
				{
					$return[$c]['automatic'][$row['output_application_status']] += 0;
				}

				$return[$c]['automatic'][$row['output_application_status']] += $row['output_count'];
			}
		}

		return($return);
	}
}

?>
