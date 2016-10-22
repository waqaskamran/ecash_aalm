<?php
/*
	just php <thisfile>
	
	it will check if already applied to the various different runlevels, and apply them
	as needed. I commented out live and ran it, so Local and RC are already done.
*/
class ECash_Update
{	
	protected $db;

	function __construct()
	{
		// Impact
		$this->db['impact']['local']['user'] = "ecash";
		$this->db['impact']['local']['pass'] = "lacosanostra";
		$this->db['impact']['local']['host'] = "monster.tss";
		$this->db['impact']['local']['port'] = "3309";
		$this->db['impact']['local']['name'] = "ldb_impact";

		$this->db['impact']['local']['obj']  = mysqli_connect(
				$this->db['impact']['local']['host'],
				$this->db['impact']['local']['user'],
				$this->db['impact']['local']['pass'],
				$this->db['impact']['local']['name'],
				$this->db['impact']['local']['port']);

		$this->db['impact']['rc']['user'] = "ecash";
		$this->db['impact']['rc']['pass'] = "lacosanostra";
		$this->db['impact']['rc']['host'] = "db101.ept.tss";
		$this->db['impact']['rc']['port'] = "3308";
		$this->db['impact']['rc']['name'] = "ldb_impact";

		$this->db['impact']['rc']['obj']  = mysqli_connect(
				$this->db['impact']['rc']['host'],
				$this->db['impact']['rc']['user'],
				$this->db['impact']['rc']['pass'],
				$this->db['impact']['rc']['name'],
				$this->db['impact']['rc']['port']);


        $this->db['impact']['live']['user'] = "ecash";
        $this->db['impact']['live']['pass'] = "showmethemoney";
        $this->db['impact']['live']['host'] = "writer.ecashimpact.ept.tss";
        $this->db['impact']['live']['port'] = "3307";
        $this->db['impact']['live']['name'] = "ldb_impact";

		$this->db['impact']['live']['obj']  = mysqli_connect(
				$this->db['impact']['live']['host'],
				$this->db['impact']['live']['user'],
				$this->db['impact']['live']['pass'],
				$this->db['impact']['live']['name'],
				$this->db['impact']['live']['port']);
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
				$query = "SELECT * FROM application_tag_details WHERE name = 'Little Circle B'";

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
				// Populate it with proper data
				$query = "UPDATE application_tag_details SET name='Little Circle B' WHERE name='TSR L3'";

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
				$query = "UPDATE application_tag_details SET name='TSR L3' WHERE name='Little Circle B'";

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
