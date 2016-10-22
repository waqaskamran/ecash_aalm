<?php

require_once( LIB_DIR . "common_functions.php");

/**
 * @package Reporting
 * @category Display
 */
class Open_Advances_Detail_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Open Advances Detail";
		$this->column_names       = array(  'status' => 'Status',
														'positive_count' => 'Count Pos',
														'positive_balance' => '$ Pos',
														'negative_count' => 'Count Neg',
														'negative_balance' => '$ Neg',
														'total_count' => 'Count Total',
														'total_balance' => 'Total');

		$this->sort_columns        = array();

		$this->link_columns        = array();

		$this->totals              = array();

		$this->totals_conditions   = null;
		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type           = false;
		$this->download_file_name  = null;
		$this->company_list_no_all = true;

		parent::__construct($transport, $module_name);
	}

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

	protected function returnTableRow($header, $data, $field, $reset_counter = false) {
		static $counter;
		
		if ($reset_counter || !isset($counter)) $counter = 0;
		if (isset($data[$field])) 
		{
			$formatted_pb = $this->Format_Field('positive_balance', $data[$field]['positive_balance']);
			$formatted_nb = $this->Format_Field('positive_balance', $data[$field]['negative_balance']);
			$formatted_tb = $this->Format_Field('positive_balance', $data[$field]['total_balance']);
			$counter++;
			return 	"    <tr>\n".
					"      <td ".($counter % 2 ? 'class="align_left_alt" ' : '')."style=\"text-align: left\">".htmlentities($header)."</td>\n".
					"      <td ".($counter % 2 ? 'class="align_left_alt" ' : '')."style=\"text-align: right\">".
						number_format($data[$field]['positive_count'])."</td>\n".
					'      <td '.($counter % 2 ? 'class="align_left_alt" ' : '').'style="text-align: right">' .
						$formatted_pb."</td>\n".
					"      <td ".($counter % 2 ? 'class="align_left_alt" ' : '')."style=\"text-align: right\">".
						number_format($data[$field]['negative_count'])."</td>\n".
					'      <td '.($counter % 2 ? 'class="align_left_alt" ' : '').'style="text-align: right">' .
						$formatted_nb."</td>\n".
					"      <td ".($counter % 2 ? 'class="align_left_alt" ' : '')."style=\"text-align: right\">".
						number_format($data[$field]['total_count'])."</td>\n".
					'      <td '.($counter % 2 ? 'class="align_left_alt" ' : '').'style="text-align: right; padding-right: 17px;">' .
						$formatted_tb."</td>\n".
					"    </tr>\n";
		}
	}
	
	protected function returnDLTableRow($header, $data, $field, $reset_counter = false) {
		static $counter;

		if ($reset_counter || !isset($counter)) $counter = 0;
		if (isset($data[$field])) 
		{
			$formatted_pb = strip_tags($this->Format_Field('positive_balance', $data[$field]['positive_balance'], $false, $false));
			$formatted_nb = strip_tags($this->Format_Field('positive_balance', $data[$field]['negative_balance'], $false, $false));
			$formatted_tb = strip_tags($this->Format_Field('positive_balance', $data[$field]['total_balance'], $false, $false));
			$counter++;
			return 	"$header\t".
				number_format($data[$field]['positive_count'])."\t".
				$formatted_pb."\t".
				number_format($data[$field]['negative_count'])."\t".
				$formatted_nb."\t".
				number_format($data[$field]['total_count'])."\t".
				$formatted_tb."\t".
				"\n";
		}
 	}

   protected function Get_Data_HTML($data, &$company_totals)
   {
      $line = "";

		$x = 0;
	
		$status_map = Fetch_Status_Map(false);
		
      foreach($data as $status_id => $status)
      {
         // 1 row of data
         $name =  $status_map[$status_id]['name'];
         $line .= 	"    <tr>\n".
      				"<th class=\"report_head\" style=\"text-align: left;\" colspan=\"7\"><strong>".htmlentities($name)."</strong></th>\n".
      				"    </tr>";
      	
      	$line .= $this->returnTableRow('Total Yesterday', $status, 'total_yesterday', true);
      	$line .= $this->returnTableRow('Balanced Accounts', $status, 'balanced');
      	
      	if (isset($status['changed_to'])) 
		{
      		foreach ($status['changed_to'] as $new_status_id => $row) 
			{
	      		$new_status = $status_map[$new_status_id]['name'];
		      	$line .= $this->returnTableRow($name.' -> '.$new_status, $status['changed_to'], $new_status_id);
      		}
      	}
      	
      	$line .= $this->returnTableRow('No Changes', $status, 'outstanding');
      	$line .= $this->returnTableRow('Balance Adjustments', $status, 'changed_balance');
      	$line .= $this->returnTableRow('Unbalanced', $status, 'unbalanced');
      	$line .= $this->returnTableRow('New', $status, 'new');
      	
      	if (isset($status['changed_from'])) 
		{
      		foreach ($status['changed_from'] as $new_status_id => $row) 
			{
	      		$new_status = $status_map[$new_status_id]['name'];
		      	$line .= $this->returnTableRow($new_status.' -> '.$name, $status['changed_from'], $new_status_id);
		      	$i++;
      		}
      	}
      	
      	$line .= $this->returnTableRow('Total Today', $status, 'total_today');
      }
     	return $line;
   }

	public function Download_Data()
	{
		$status_map = Fetch_Status_Map(false);
		$dl_data = "";

		$dl_data .= $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";


		// Is the report run for a specific date, date range, or do dates not matter?
		switch($this->date_dropdown)
		{
			case self::$DATE_DROPDOWN_RANGE:
				$dl_data .= "Date Range: " . $this->search_criteria['start_date_MM']   . '/'
				. $this->search_criteria['start_date_DD']   . '/'
				. $this->search_criteria['start_date_YYYY'] . " to "
				. $this->search_criteria['end_date_MM']     . '/'
				. $this->search_criteria['end_date_DD']     . '/'
				. $this->search_criteria['end_date_YYYY']   . "\n";
				break;
			case self::$DATE_DROPDOWN_SPECIFIC:
				$dl_data .= "Date: " . $this->search_criteria['specific_date_MM'] . '/'
				. $this->search_criteria['specific_date_DD'] . '/'
				. $this->search_criteria['specific_date_YYYY'] . "\n";
				break;
			case self::$DATE_DROPDOWN_NONE:
			default:
				// Nothing to do
				break;
		}


		$dl_data .= "\n";
		foreach( $this->column_names as $column_name )
		{
			$dl_data .= $column_name . "\t";
		}

		$dl_data = substr( $dl_data, 0, -1 ) . "\n";

		// Sort through each company's data
		foreach( $this->search_results as $company_name => $company_data )
		{
			/* Mantis:1508#2 */
			if( "summary" != $company_name )
			{
				// An array of company totals which gets added to grand_totals
				$company_totals = array();
				foreach( $this->column_names as $data_name => $column_name )
				{
					$company_totals[$data_name] = 0;
				}

				// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
				if( isset($x) )
				$dl_data .= "\n";

				foreach($company_data as $status_id => $status)
				{
					// 1 row of data
					$line = '';
					$name =  $status_map[$status_id]['name'];
					$line .= 	"$name\t\t\t\t\t\t\n";

					$line .= $this->returnDLTableRow('Total Yesterday', $status, 'total_yesterday', true);
					$line .= $this->returnDLTableRow('Balanced Accounts', $status, 'balanced');

					if (isset($status['changed_to'])) 
					{
						foreach ($status['changed_to'] as $new_status_id => $row) 
						{
							$new_status = $status_map[$new_status_id]['name'];
							$line .= $this->returnDLTableRow($name.' -> '.$new_status, $status['changed_to'], $new_status_id);
						}
					}

					$line .= $this->returnDLTableRow('No Changes', $status, 'outstanding');
					$line .= $this->returnDLTableRow('Balance Adjustments', $status, 'changed_balance');
					$line .= $this->returnDLTableRow('Unbalanced', $status, 'unbalanced');
					$line .= $this->returnDLTableRow('New', $status, 'new');

					if (isset($status['changed_from'])) 
					{
						foreach ($status['changed_from'] as $new_status_id => $row) 
						{
							$new_status = $status_map[$new_status_id]['name'];
							$line .= $this->returnDLTableRow($new_status.' -> '.$name, $status['changed_from'], $new_status_id);
							$i++;
						}
					}

					$line .= $this->returnDLTableRow('Total Today', $status, 'total_today');
					$dl_data .= $line."\n";
				}

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
	}
}

?>
