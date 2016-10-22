<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( SQL_LIB_DIR . "fetch_status_map.func.php");

class NSF_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "NSF Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}
	
	/**
	 * Fetches data for the Inactive Paid Status Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   int  $company_id company_id
	 * @returns array
	 */
	public function Fetch_NSF_Data($startdate, $enddate, $company_id, $achtype, $reptype)
	{
		$this->timer->startTimer(self::$TIMER_NAME);
		
		$FILE = __FILE__;
		$METHOD = __METHOD__;
		$LINE = __LINE__;
		$items = array();
		
		$db = ECash::getMasterDb();
		
		if($achtype == "credit")
		{
			$query_add = "		SUM(if(ach_type = 'credit' AND ach_status != 'returned', ach.amount,0)) as NonCredit,
								SUM(if(ach_type = 'credit' AND ach_status  = 'returned', ach.amount,0)) as RepCredit,
								SUM(if(ach_type = 'credit',ach.amount,0)) as TotalAmount ";
		}
		else 
		{
			$query_add = "  	SUM(IF(ach_type = 'debit' AND ach_status != 'returned', ach.amount, 0)) AS NonDebit,
	    						SUM(IF(ach_type = 'debit' AND ach_status  = 'returned', ach.amount, 0)) AS RepDebit,
	    						SUM(IF(ach_type = 'debit',ach.amount,0)) AS TotalAmount";
		}		
		
		if ($loan_type == 'all' || empty($loan_type))
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";
				
		switch ($reptype)
		{
			case "nsfper":
				$query = "
					SELECT	UPPER(company.name_short) AS Company_Name,ach.ach_batch_id AS Batch_ID,
							DATE_FORMAT(ab.date_created,'%c/%e/%Y') AS Batch_Created,
	    					SUM(IF(ach_type = '$achtype' AND ach_status != 'returned', 1,0)) AS Non_Reported,
	   						SUM(IF(ach_type = '$achtype' AND ach_status  = 'returned', 1,0)) AS Reported,
							SUM(IF(ach_type = '$achtype', 1, 0)) AS Total, 
							$query_add
					FROM company, ach
					JOIN ach_batch AS ab using(ach_batch_id)
					JOIN application AS app using (application_id)
					JOIN loan_type AS lt ON (lt.loan_type_id = app.loan_type_id)
					WHERE ach_date between $startdate AND $enddate
					{$loan_type_sql}
					AND company.company_id = ach.company_id";
				
				// Added for reports for "All" companies, does not work properly when company_id is 0
				if ($company_id != 0)
                                	$query .= " AND company.company_id = $company_id";
 
				$query .= " GROUP BY ach.ach_batch_id ORDER BY Batch_ID";
				$items = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

				$data = $this->repGen($items);
				break;
				
			case "nsfpercusttypek_newloan":
			case "nsfpercusttypek_react":				
				$rep_types = array("nsfpercusttypek_newloan" => "no","nsfpercusttypek_react" => "yes");
				$query = "
						SELECT
							UPPER(company.name_short) AS Company_Name, ach.ach_batch_id AS Batch_ID,
							DATE_FORMAT(ab.date_created,'%c/%e/%Y') AS Batch_Created,
	    					SUM(IF(ach_type = '$achtype' AND ach_status != 'returned', 1,0)) AS Non_Reported,
    						SUM(IF(ach_type = '$achtype' AND ach_status  = 'returned', 1,0)) AS Reported,
						    SUM(IF(ach_type = '$achtype', 1, 0)) AS Total, 
							$query_add
						FROM company, ach
						JOIN application AS app using(application_id) 
						JOIN loan_type   AS lt  ON (lt.loan_type_id = app.loan_type_id)
						JOIN ach_batch AS ab using(ach_batch_id)
						WHERE ach_date between $startdate and $enddate
						{$loan_type_sql}
				        AND company.company_id = ach.company_id";
				
				// Added for reports for "All" companies, does not work properly when company_id is 0
				if ($company_id != 0)
                                	$query .= " AND company.company_id = $company_id";
 
				$query .= " AND app.is_react = '{$rep_types[$reptype]}'
						    GROUP BY ach.ach_batch_id ORDER BY Batch_ID";
                $items = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
				
				$data = $this->repGen($items);
				break;
				
			case "nsfperstattypek":
				$statuses = array();
				$query = "SELECT application_status_id,name FROM application_status WHERE active_status = 'active' ORDER BY name ";
				
				$result = $db->query($query);
				
				while($row = $result->fetch(PDO::FETCH_ASSOC))
				{
					$statuses[$row['application_status_id']] = $row['name'];
				}
	
				$items = array();	
			
				// GF #8517: Changed query to get current status if no status changes occurred after the ach date [benb]
				$query = "
						SELECT 
						   UPPER(company.name_short) AS Company_Name,
						   IFNULL((
						     	SELECT 	sh.application_status_id
							  	FROM 	status_history AS sh
								WHERE	sh.date_created <= ach.ach_date
								AND		ach.application_id = sh.application_id
								ORDER BY sh.date_created DESC LIMIT 1
							),
							(
							    SELECT  sh.application_status_id
                                FROM    status_history AS sh
								JOIN    application AS app ON (sh.application_id = app.application_id)
								JOIN	loan_type AS lt ON (lt.loan_type_id = app.loan_type_id)
                                WHERE  
                                        ach.application_id = sh.application_id
								{$loan_type_sql}
                                ORDER BY sh.date_created DESC LIMIT 1
							)) AS Status,
	    					SUM(IF(ach_type = '$achtype' AND ach_status != 'returned', 1,0)) AS Non_Reported,
	    					SUM(IF(ach_type = '$achtype' AND ach_status  = 'returned', 1,0)) AS Reported,
							SUM(IF(ach_type = '$achtype', 1, 0)) AS Total, 
							$query_add
						FROM company, ach
						WHERE ach_date BETWEEN $startdate AND $enddate
						AND ach.company_id = company.company_id
					";
	
				if ($company_id != 0)
						$query .= " AND ach.company_id = $company_id";
				
				
				$query .= " GROUP by Company_Name,Status ORDER BY Company_Name";
						
				$items = array();

				$result = $db->query($query);

				while( $row = $result->fetch(PDO::FETCH_ASSOC))
				{
					$newray = array();
					@$newray["Status"] = $statuses[$row["Status"]];
					$newray["_"] = "_"; // Place Holder
					foreach($row as $key => $item)
					{
						if($key != "Status") $newray[$key] = $item;
					}
					$items[] = $newray;
				}
				
				
				$data = $this->repGen($items);
				break;
		}
		

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
	
	
	private function repGen($result)
	{
		$rep_types = array("no" => "New Loans","yes" => "Reacts");
		$items = array();
		$companies = array();
			
		for($i=0; $i<count($result); $i++)
		{
			$row          = $result[$i];
			$Company_Name = $row["Company_Name"];
			
			if($row['Total'] > 0)
			{
				$row['Reported_Percent'] = round(($row['Reported'] / $row['Total'] * 100),2);
			}
			else
			{
				$row['Reported_Percent'] = '0';
			}
			
			$row['Amount_Percent'] = "0";
	
			$companies[$Company_Name]['Company_Name']        = $Company_Name;
			$companies[$Company_Name]['total_non'] 	= $companies[$Company_Name]['total_non'] + $row['Non_Reported'];
			$companies[$Company_Name]['total_rep']  = $companies[$Company_Name]['total_rep'] + $row['Reported'];
			
			if(@$row['NonCredit'] > 0 || @$row['RepCredit'] > 0)
			{
				$row['Amount_Percent'] = round(($row['RepCredit'] / $row['TotalAmount'] * 100),2);
                $companies[$Company_Name]['Company_Name']         = $Company_Name;
				$companies[$Company_Name]['total_non_credit_amt'] = $companies[$Company_Name]['total_non_credit_amt'] + $row['NonCredit'];
				$companies[$Company_Name]['total_rep_credit_amt'] = $companies[$Company_Name]['total_rep_credit_amt'] + $row['RepCredit'];
				$companies[$Company_Name]['total_amt'] 		      = $companies[$Company_Name]['total_amt'] + $row['TotalAmount'];
				$companies[$Company_Name]['totals']               = $companies[$Company_Name]['totals'] + $row['Total'];			
			}
			if(@$row['NonDebit'] > 0 || @$row['RepDebit'] > 0)
			{
				$row['Amount_Percent'] = round(($row['RepDebit'] / $row['TotalAmount'] * 100),2);
				$companies[$Company_Name]['Company_Name']        = $Company_Name;
				$companies[$Company_Name]['total_non_debit_amt'] = $companies[$Company_Name]['total_non_debit_amt'] + $row['NonDebit'];
				$companies[$Company_Name]['total_rep_debit_amt'] = $companies[$Company_Name]['total_rep_debit_amt'] + $row['RepDebit'];
				$companies[$Company_Name]['total_amt'] 		     = $companies[$Company_Name]['total_amt'] + $row['TotalAmount'];
				$companies[$Company_Name]['totals'] 		     = $companies[$Company_Name]['totals'] + $row['Total'];			
			}
			if((@$row['NonDebit'] > 0 || @$row['RepDebit'] > 0) || (@$row['NonCredit'] > 0 || @$row['RepCredit'] > 0))
			{
				$item = array();
				$item['Company_Name']     = $row['Company_Name'];
				$item['Batch_ID'] 	      = isset($row['Batch_ID']) ? $row['Batch_ID'] : $row['Status'];
				$item['Batch_Created'] 	  = isset($row['Batch_Created']) ? $row['Batch_Created'] : "";
				$item['Non_Reported'] 	  = $row['Non_Reported'];
				$item['Reported'] 	      = $row['Reported'];
	            
				$item['Total'] 		      = $row['Total'];
				$item['NonDebit'] 	      = isset($row['NonDebit']) ? $row['NonDebit'] : $row['NonCredit'];
				$item['RepDebit'] 	      = isset($row['RepDebit']) ? $row['RepDebit'] : $row['RepCredit'];
				$item['Amount_Percent']   = $row['Amount_Percent'];
				$item['Reported_Percent'] = $row['Reported_Percent'];
				$item['TotalAmount'] 	  = $row['TotalAmount'];
	            
				$items[$Company_Name][]   = $item;

			}
		}
			
		foreach ($companies as $Company_Name => $Company_Data)
		{
			if (isset($items[$Company_Name]))
			{
				$Company_Data['ratio'] = round(($Company_Data['total_rep'] / $Company_Data['totals'] * 100),2);
		
				$item = array();

				$item['Batch_ID'] 			= null;
				$item['Batch_Created'] 		= null;
				$item['Non_Reported'] 		= $Company_Data['total_non'];
				$item['Reported'] 			= $Company_Data['total_rep'];
				$item['Total'] 				= $Company_Data['totals'];			

				if($Company_Data['total_non_credit_amt'])
				{
					$Company_Data['ratio_amt_credit'] = round(($Company_Data['total_rep_credit_amt'] / ($Company_Data['total_non_credit_amt'] + $Company_Data['total_rep_credit_amt']) * 100),2);
					$Company_Data['ratio_amt_disp']   = round(($Company_Data['total_rep_credit_amt'] / $Company_Data['total_amt'] * 100),2);
					$item['NonDebit'] = $Company_Data['total_non_credit_amt'];
					$item['RepDebit'] = $Company_Data['total_rep_credit_amt'];				
				}
				else 
				{
					$Company_Data['ratio_amt_debit']  = round(($Company_Data['total_rep_debit_amt'] / ($Company_Data['total_non_debit_amt'] + $Company_Data['total_rep_debit_amt']) * 100),2);
					$Company_Data['ratio_amt_disp']   =  round(($Company_Data['total_rep_debit_amt'] / $Company_Data['total_amt'] * 100),2);
					$item['NonDebit'] = $Company_Data['total_non_debit_amt'];
					$item['RepDebit'] = $Company_Data['total_rep_debit_amt'];							
				}

				$item['TotalAmount'] 		= $Company_Data['total_amt'];
				$item['Reported_Percent'] 	= $Company_Data['ratio'];
				$item['Amount_Percent']  	= $Company_Data['ratio_amt_disp'];		

				$item['Company_Name']       = $Company_Name . ' Totals';
				$items[$Company_Name][] = $item;
			}
		
		}
		
		return $items;
		
	}		
}

?>
