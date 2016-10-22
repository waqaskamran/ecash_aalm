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
		$this->db['aalm']['local']['user'] = "ecash";
		$this->db['aalm']['local']['pass'] = "lacosanostra";
		$this->db['aalm']['local']['host'] = "monster.tss";
		$this->db['aalm']['local']['port'] = "3309";
		$this->db['aalm']['local']['name'] = "ldb_mls";

		$this->db['aalm']['local']['obj']  = mysqli_connect(
				$this->db['aalm']['local']['host'],
				$this->db['aalm']['local']['user'],
				$this->db['aalm']['local']['pass'],
				$this->db['aalm']['local']['name'],
				$this->db['aalm']['local']['port']);

		$this->db['aalm']['rc']['user'] = "ecash";
		$this->db['aalm']['rc']['pass'] = "lacosanostra";
		$this->db['aalm']['rc']['host'] = "db101.ept.tss";
		$this->db['aalm']['rc']['port'] = "3308";
		$this->db['aalm']['rc']['name'] = "ldb_mls";

		$this->db['aalm']['rc']['obj']  = mysqli_connect(
				$this->db['aalm']['rc']['host'],
				$this->db['aalm']['rc']['user'],
				$this->db['aalm']['rc']['pass'],
				$this->db['aalm']['rc']['name'],
				$this->db['aalm']['rc']['port']);


        $this->db['aalm']['live']['user'] = "ecash";
        $this->db['aalm']['live']['pass'] = "Hook6Zoh";
        $this->db['aalm']['live']['host'] = "writer.ecashaalm.ept.tss";
        $this->db['aalm']['live']['port'] = "3306";
        $this->db['aalm']['live']['name'] = "ldb_mls";

		$this->db['aalm']['live']['obj']  = mysqli_connect(
				$this->db['aalm']['live']['host'],
				$this->db['aalm']['live']['user'],
				$this->db['aalm']['live']['pass'],
				$this->db['aalm']['live']['name'],
				$this->db['aalm']['live']['port']);
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
				$query = "SELECT * FROM loan_actions WHERE type='CS_REVERIFY'";

				$res = $mysqli->query($query);

				if ($res->num_rows >= 2)
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
				$query = "INSERT INTO loan_actions (name_short, description, status, type) VALUES 
								('cs_reverify_qualified', 'Customer does not qualify for loan amount', 'ACTIVE', 'CS_REVERIFY'),
								('cs_reverify_payday', 'Due date does not fall on a payday', 'ACTIVE', 'CS_REVERIFY');";

				$res = $mysqli->query($query);

				$this->db[$company][$rc_lvl]['applied'] = true;
				
				if ($mysqli->affected_rows != 2)
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
				$query = "DELETE FROM loan_actions WHERE type='CS_REVERIFY'";

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
