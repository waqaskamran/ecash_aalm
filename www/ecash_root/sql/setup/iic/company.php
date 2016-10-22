<?php
class ECash_Update
{	
	protected $db;

	function __construct()
	{
		$this->db['iic']['local']['user'] = "ecash";
		$this->db['iic']['local']['pass'] = "lacosanostra";
		$this->db['iic']['local']['host'] = "monster.tss";
		$this->db['iic']['local']['port'] = "3309";
		$this->db['iic']['local']['name'] = "ldb_intacash";

		$this->db['iic']['local']['obj']  = mysqli_connect(
				$this->db['iic']['local']['host'],
				$this->db['iic']['local']['user'],
				$this->db['iic']['local']['pass'],
				$this->db['iic']['local']['name'],
				$this->db['iic']['local']['port']);

		$this->db['iic']['rc']['user'] = "ecash";
		$this->db['iic']['rc']['pass'] = "lacosanostra";
		$this->db['iic']['rc']['host'] = "db101.ept.tss";
		$this->db['iic']['rc']['port'] = "3308";
		$this->db['iic']['rc']['name'] = "ldb_intacash";

		$this->db['iic']['rc']['obj']  = mysqli_connect(
				$this->db['iic']['rc']['host'],
				$this->db['iic']['rc']['user'],
				$this->db['iic']['rc']['pass'],
				$this->db['iic']['rc']['name'],
				$this->db['iic']['rc']['port']);

		/* No Live yet
		$this->db['iic']['live']['user'] = "";
		$this->db['iic']['live']['pass'] = "";
		$this->db['iic']['live']['host'] = "";
		$this->db['iic']['live']['port'] = "";
		$this->db['iic']['live']['name'] = "";

		$this->db['iic']['live']['obj']  = mysqli_connect(
				$this->db['iic']['live']['host'],
				$this->db['iic']['live']['user'],
				$this->db['iic']['live']['pass'],
				$this->db['iic']['live']['name'],
				$this->db['iic']['live']['port']);*/
	}

	function checkConnections($return_on_failure = FALSE)
	{
		echo "\nChecking Connections\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				echo "Checking whether {$rc_lvl} DB connection succeeded:";

				if ($this->db[$company][$rc_lvl]['obj'] != FALSE)
					echo "\t\t\t[Connected]\n";
				else
				{
					echo "\t\t\t[Failed]\n";

					if ($return_on_error)
						return FALSE;
				}
			}
		}

		return TRUE;
	}

	function checkApplied($return_if_applied = FALSE)
	{
		echo "\nChecking if updates have already been applied\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				echo "Checking whether {$rc_lvl} DB has been updated:";

				$mysqli = $var_array['obj'];

//////////////////////////////////////////////////////////////////////////////////////////////////
				$query = "SELECT * FROM company WHERE name_short='iic'";

				$res = $mysqli->query($query);

				if ($res->num_rows == 1)
				{
					echo "\t\t\t[Already Done]\n";
					$this->db[$company][$rc_lvl]['applied'] = true;

					if ($return_if_applied)
						return FALSE;
				}
				else
				{
					echo "\t\t\t[Not Yet]\n";
					$this->db[$company][$rc_lvl]['applied'] = false;
				}
//////////////////////////////////////////////////////////////////////////////////////////////////
			}
		}

		return TRUE;
	}

	function applyChanges($return_on_error = FALSE)
	{
		echo "\nApplying Updates\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				if ($this->db[$company][$rc_lvl]['applied'] == true)
					continue; // Skip already applied updates

				echo "Applying update to {$rc_lvl}:";

				$mysqli = $var_array['obj'];

//////////////////////////////////////////////////////////////////////////////////////////////////
				// Clear out all old entries
				$query = "TRUNCATE company";

				$res = $mysqli->query($query);

				// Populate it with proper data
				// Intacash will be company_id 5 as per BrianR
				$query = "INSERT INTO company (active_status, company_id, name, name_short, co_entity_type, ecash_process_type, property_id )
							VALUES('active', 5, 'IntaCash', 'iic', 'clk_company', 2, 0);";

				$res = $mysqli->query($query);

				$this->db[$company][$rc_lvl]['applied'] = true;
				
				if ($mysqli->affected_rows != 1)
				{
					$this->db[$company][$rc_lvl]['applied'] = false;
				}
				
				
///////////////////////////////////////////////////////////////////////////////////////////////////
				if ($this->db[$company][$rc_lvl]['applied'] == false)
				{
					echo "\t\t\t[Failed]\n";

					if ($return_on_error)
					{
						echo mysqli_error($mysqli);
						return FALSE;
					}
				}
				else
				{
					$this->db[$company][$rc_lvl]['applied'] = true;
					echo "\t\t\t[Success]\n";
				}

			}
		}

		return TRUE;

	}


	function rollbackChanges($return_on_error = FALSE)
	{
		echo "\nRolling Back Updates\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				if ($var_array['applied'] != true)
					continue;

				$mysqli = $var_array['obj'];

				echo "Rolling back update to {$rc_lvl}:";

//////////////////////////////////////////////////////////////////////////////////////////////////
				// Just make it so the check applied fails
				$query = "TRUNCATE company";

				$res = $mysqli->query($query);
//////////////////////////////////////////////////////////////////////////////////////////////////

				if ($mysqli->affected_rows < 1)
				{
					echo "\t\t\t[Failed]\n";	
					break;
				}
				else
				{
					echo "\t\t\t[Succeeded]\n";
				}


			}
		}
	}

}

$update = new ECash_Update();

$update->checkConnections(TRUE);
$update->checkApplied(FALSE);
$update->applyChanges(TRUE);
//$update->rollbackChanges();
?>
