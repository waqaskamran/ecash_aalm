<?php
class ECash_Update
{	
	// This script cannot be rolled back.
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
				$query = "SELECT * FROM access_group WHERE name LIKE '%iic%'";

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
				$query = "DELETE FROM access_group WHERE company_id NOT IN ('1','5')";
				$res = $mysqli->query($query);

				// Populate it with proper data
				$query = "UPDATE access_group SET company_id='5'";
				$res = $mysqli->query($query);
				if ($mysqli->affected_rows == 0)
					$this->db[$company][$rc_lvl]['applied'] = false;	


				$query = "UPDATE access_group SET name = REPLACE(name, 'IPDL', 'IIC');";
				$res = $mysqli->query($query);
                if ($mysqli->affected_rows == 0)
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
				$query = "UPDATE access_group SET name = REPLACE(name, 'IIC', 'IPDL');";
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
