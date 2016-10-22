<?php
/**
 * Class to retreive Bureau Inquiry records.
 *
 * @author Kyle Barrett <kyle.barrett@sellingsource.com>
 * @package Bureau_Inquiry
 */
class Bureau_Query
{
	/**
	 * @var DB_Database_1
	 */
	private $db;
	
	/**
	 * @var Applog
	 */
	private $log;

	/**
	 * Object Constructor
	 *
	 * @param DB_Database_1 $db
	 * @param Applog $log
	 */ 
	public function __construct(DB_Database_1 $db, Applog $log)
	{
		$this->db = $db;
		$this->log = $log;
	}

	/**
	 * Retreives bureau inquiry packages.
	 *
	 * @param int $application_id Application ID
	 * @param int $company_id Company ID
	 * @param string $call_type Optional. Defaults to 'all'.
	 * @param int $bureau_inquiry_id Optiona. Defaults to NULL.
	 * @return array Data packages
	 */
	public function getData($application_id, $company_id, $call_type = 'all')
	{
		$data = NULL;
		$data = $this->getPackages($application_id, $company_id, $call_type);
		$data = $this->sortPackagesByDateCreated($data);
		
		return count($data) ? $data : NULL;
	}

	/**
	 * Queries the database for packages.
	 *
	 * @param int $application_id Application ID
	 * @param int $company_id Company ID
	 * @param string $call_type Call Type
	 * @param string $table NULL for bureau_inquiry, 'failed' for bureau_inquiry_failed
	 * @return array Packages
	 */
	public function getPackages($application_id, $company_id, $call_type = 'all', $table = NULL, $bureau_inquiry_id = NULL)
	{
		$this->log->Write("DEBUG: appId:{$application_id}, c:{$company_id},call:{$call_type}, table:{$table}, bur:{$bureau_inquiry_id}");
		$packages = array();

		$inquiry_client = ECash::getFactory()->getWebServiceFactory()->getWebService('inquiry');

		if (!empty($bureau_inquiry_id))
		{
			$rp = $inquiry_client->findInquiryById($bureau_inquiry_id);

			/**
			 * Check for MySQL compression
			 * MySQL packs four characters in the start, but then the
			 * rest is standard gzip compression.  I'm checking for the second
			 * byte to see if it's a hi-bit character (156)
			 */
			if(ord(substr($rp->receive_package, 5, 1)) == '156')
			{
				$rp->received_package = gzuncompress(substr($rp->receive_package, 4));
			}
			else
			{
				$rp->received_package = $rp->receive_package;
			}
			if(ord(substr($rp->sent_package, 5, 1)) == '156')
			{
				$rp->sent_package = gzuncompress(substr($rp->sent_package, 4));
			}
			else
			{
				$rp->sent_package = $rp->sent_package;
			}
			$rp->name_short = strtolower($rp->bureau);
			$packages[$table . 0] = $rp;
		}
		else
		{
			$rp = $inquiry_client->findInquiriesByApplicationID($application_id);

			$item = array();

			if(!empty($rp->item))
			{
				if (is_array($rp->item))
				{
					$item = array_values($rp->item);
				}
				else
				{
					$item[] = $rp->item;
				}
			}
				

			/* make sure the array has return values (empty() will not catch this) */
			$counter = 0;
			foreach ($item as $row)
			{
				if ($row->bureau_inquiry_id > 0)
				{
					$b = new stdClass();
					$b->name_short = strtolower($row->bureau);
					$b->bureau_inquiry_id = $row->bureau_inquiry_id;
					$b->inquiry_type = $row->inquiry_type;
					$b->outcome = $row->outcome;
					$b->score = $row->score;
					$b->reason = $row->reason;
					$b->date_created = $row->date_created;

					/**
					 * Check for compressed data
					 */
					if(ord(substr($row->receive_package, 5, 1)) == '156')
					{
						$b->received_package = gzuncompress(substr($row->receive_package, 4));
					}
					else
					{
						$b->received_package = $row->receive_package;
					}
					/*
					/////////////////
					$app_id = intval($application_id);
					$query = "
					SELECT UNCOMPRESS(received_package) AS received_package
					FROM bureau_inquiry
					WHERE application_id = {$app_id}
					ORDER BY bureau_inquiry_id ASC
					LIMIT 1
					";
					$result = $this->db->query($query);
					$row_ldb = $result->fetch(PDO::FETCH_OBJ);
					$b->received_package = $row_ldb->received_package;
					/////////////////
					*/
					if(ord(substr($row->sent_package, 5, 1)) == '156')
					{
						$b->sent_package = gzuncompress(substr($row->sent_package, 4));
					}
					else
					{
						$b->sent_package = $row->sent_package;
					}

					if (empty($row->agent_name))
					{
						$b->agent_name = 'U. Unknown';
					}
					else
					{
						$b->agent_name = ucwords(strtolower($row->agent_name));
					}

					$packages[$table . $counter] = $b;
					$counter++;
				}
			}
		}
		return $packages;
	}
	
	
	/**
	 * Sorts the package by Date Created.
	 *
	 * @param array $inquiry_packages An array of stdObjects
	 * @return array An array of sorted packages
	 */
	private function sortPackagesByDateCreated($inquiry_packages)
	{
		if(count($inquiry_packages))
		{
			$date_created = array();
			foreach($inquiry_packages as $key=>$package)
			{
				//Switch the packages to arrays so they may be sorted
				$pack = (array) $package;
				$date_created[$key] = $pack['date_created'];
				
				$inquiry_packages[$key] = $pack;
			}
			
			array_multisort($inquiry_packages, SORT_DESC, $date_created);
			
			//Switch them back to stdObjects
			$sorted_packages = array();
			$counter = 0;
			foreach($inquiry_packages as $key=>$package)
			{
				$pack = (object) $package;
				$sorted_packages[$counter] = $pack;	
				$counter++;
			}

			return $sorted_packages;
		}
		
		return $inquiry_packages;
	}
}
?>
