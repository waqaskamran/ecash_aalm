<?php
class ECash_Update
{	
	protected $db;

	function __construct()
	{
		// Impact
		$this->db['qeasy']['local']['user'] = "ecash";
		$this->db['qeasy']['local']['pass'] = "lacosanostra";
		$this->db['qeasy']['local']['host'] = "monster.tss";
		$this->db['qeasy']['local']['port'] = "3309";
		$this->db['qeasy']['local']['name'] = "ldb_qeasy";

		$this->db['qeasy']['local']['obj']  = mysqli_connect(
				$this->db['qeasy']['local']['host'],
				$this->db['qeasy']['local']['user'],
				$this->db['qeasy']['local']['pass'],
				$this->db['qeasy']['local']['name'],
				$this->db['qeasy']['local']['port']);

		$this->db['qeasy']['rc']['user'] = "ecash";
		$this->db['qeasy']['rc']['pass'] = "lacosanostra";
		$this->db['qeasy']['rc']['host'] = "db101.ept.tss";
		$this->db['qeasy']['rc']['port'] = "3308";
		$this->db['qeasy']['rc']['name'] = "ldb_qeasy";

		$this->db['qeasy']['rc']['obj']  = mysqli_connect(
				$this->db['qeasy']['rc']['host'],
				$this->db['qeasy']['rc']['user'],
				$this->db['qeasy']['rc']['pass'],
				$this->db['qeasy']['rc']['name'],
				$this->db['qeasy']['rc']['port']);

		$this->db['qeasy']['live']['user'] = "ecashqeasy";
		$this->db['qeasy']['live']['pass'] = "QuahaaC9";
		$this->db['qeasy']['live']['host'] = "writer.ecashaalm.ept.tss";
		$this->db['qeasy']['live']['port'] = "3306";
		$this->db['qeasy']['live']['name'] = "ldb_qeasy";

		$this->db['qeasy']['live']['obj']  = mysqli_connect(
				$this->db['qeasy']['live']['host'],
				$this->db['qeasy']['live']['user'],
				$this->db['qeasy']['live']['pass'],
				$this->db['qeasy']['live']['name'],
				$this->db['qeasy']['live']['port']);
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
				$query = "SELECT * FROM access_group WHERE name LIKE '%qeasy%'";

				$res = $mysqli->query($query);

				if ($res->num_rows > 0)
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

				$this->db[$company][$rc_lvl]['applied'] = true;
//////////////////////////////////////////////////////////////////////////////////////////////////
				// Clear out all old entries
				$query = "DELETE FROM access_group WHERE company_id != '2'";
				$res = $mysqli->query($query);

				// Populate it with proper data
				$query = "UPDATE access_group SET company_id='1'";
				$res = $mysqli->query($query);
				if ($mysqli->affected_rows != 22)
					$this->db[$company][$rc_lvl]['applied'] = false;	


				$query = "UPDATE access_group SET name = REPLACE(name, 'MYDY', 'QEASY');";
				$res = $mysqli->query($query);
                if ($mysqli->affected_rows != 22)
                    $this->db[$company][$rc_lvl]['applied'] = false;
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
				$query = "UPDATE access_group SET name = REPLACE(name, 'QEASY', 'MYDY');";
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

// Cannot be rolled back
//$update->rollbackChanges();
?>
