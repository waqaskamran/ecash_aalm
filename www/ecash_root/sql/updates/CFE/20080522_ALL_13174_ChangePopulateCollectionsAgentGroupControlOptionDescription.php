<?php
/*
	just php <thisfile>
	
	This applies this change to ALL CFE companies (Including Agean)

Company: agean
Checking whether local DB has been updated:                     [Already Done]
Checking whether rc DB has been updated:                        [Already Done]
Checking whether live DB has been updated:                      [Not Yet]
Company: intacash
Checking whether local DB has been updated:                     [Already Done]
Checking whether rc DB has been updated:                        [Already Done]
Checking whether live DB has been updated:                      [Not Yet]
Company: aalm
Checking whether local DB has been updated:                     [Already Done]
Checking whether rc DB has been updated:                        [Already Done]
Checking whether live DB has been updated:                      [Not Yet]
Company: impact
Checking whether local DB has been updated:                     [Already Done]
Checking whether rc DB has been updated:                        [Already Done]
Checking whether live DB has been updated:                      [Not Yet]
Company: qeasy
Checking whether local DB has been updated:                     [Already Done]
Checking whether rc DB has been updated:                        [Already Done]
Checking whether live DB has been updated:                      [Not Yet]
Company: lcs
Checking whether local DB has been updated:                     [Already Done]
Checking whether rc DB has been updated:                        [Already Done]
Checking whether live DB has been updated:                      [Not Yet]
*/

class ECash_Update
{	
	protected $db;

	function __construct()
	{
		// Agean

		$this->db['agean']['local']['user'] = "ecash";
		$this->db['agean']['local']['pass'] = "lacosanostra";
		$this->db['agean']['local']['host'] = "monster.tss";
		$this->db['agean']['local']['port'] = "3309";
		$this->db['agean']['local']['name'] = "ldb_agean";

		$this->db['agean']['local']['obj']  = mysqli_connect(
				$this->db['agean']['local']['host'],
				$this->db['agean']['local']['user'],
				$this->db['agean']['local']['pass'],
				$this->db['agean']['local']['name'],
				$this->db['agean']['local']['port']);

		// RC
		$this->db['agean']['rc']['user'] = "ecash";
		$this->db['agean']['rc']['pass'] = "lacosanostra";
		$this->db['agean']['rc']['host'] = "db101.ept.tss";
		$this->db['agean']['rc']['port'] = "3308";
		$this->db['agean']['rc']['name'] = "ldb_agean";

		$this->db['agean']['rc']['obj']  = mysqli_connect(
				$this->db['agean']['rc']['host'],
				$this->db['agean']['rc']['user'],
				$this->db['agean']['rc']['pass'],
				$this->db['agean']['rc']['name'],
				$this->db['agean']['rc']['port']);


		// Live
        $this->db['agean']['live']['user'] = "ecash";
        $this->db['agean']['live']['pass'] = "Zeir5ahf";
        $this->db['agean']['live']['host'] = "writer.ecashagean.ept.tss";
        $this->db['agean']['live']['port'] = "3306";
        $this->db['agean']['live']['name'] = "ldb_agean";

		$this->db['agean']['live']['obj']  = mysqli_connect(
				$this->db['agean']['live']['host'],
				$this->db['agean']['live']['user'],
				$this->db['agean']['live']['pass'],
				$this->db['agean']['live']['name'],
				$this->db['agean']['live']['port']);

		// IntaCash
		// Local
		$this->db['intacash']['local']['user'] = "ecash";
		$this->db['intacash']['local']['pass'] = "lacosanostra";
		$this->db['intacash']['local']['host'] = "monster.tss";
		$this->db['intacash']['local']['port'] = "3309";
		$this->db['intacash']['local']['name'] = "ldb_intacash";

		$this->db['intacash']['local']['obj']  = mysqli_connect(
				$this->db['intacash']['local']['host'],
				$this->db['intacash']['local']['user'],
				$this->db['intacash']['local']['pass'],
				$this->db['intacash']['local']['name'],
				$this->db['intacash']['local']['port']);

		// RC
		$this->db['intacash']['rc']['user'] = "ecash";
		$this->db['intacash']['rc']['pass'] = "lacosanostra";
		$this->db['intacash']['rc']['host'] = "db101.ept.tss";
		$this->db['intacash']['rc']['port'] = "3308";
		$this->db['intacash']['rc']['name'] = "ldb_intacash";

		$this->db['intacash']['rc']['obj']  = mysqli_connect(
				$this->db['intacash']['rc']['host'],
				$this->db['intacash']['rc']['user'],
				$this->db['intacash']['rc']['pass'],
				$this->db['intacash']['rc']['name'],
				$this->db['intacash']['rc']['port']);

		// Live
        $this->db['intacash']['live']['user'] = "ecash";
        $this->db['intacash']['live']['pass'] = "at4aeDul";
        $this->db['intacash']['live']['host'] = "writer.intacash.ept.tss";
        $this->db['intacash']['live']['port'] = "3306";
        $this->db['intacash']['live']['name'] = "ldb_intacash";

		$this->db['intacash']['live']['obj']  = mysqli_connect(
				$this->db['intacash']['live']['host'],
				$this->db['intacash']['live']['user'],
				$this->db['intacash']['live']['pass'],
				$this->db['intacash']['live']['name'],
				$this->db['intacash']['live']['port']);


		// AALM/MLS
		// Local
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

		// RC
		$this->db['aalm']['rc']['user'] = "ecash";
		$this->db['aalm']['rc']['pass'] = "lacosanostra";
		$this->db['aalm']['rc']['host'] = "db101.clkonline.com";
		$this->db['aalm']['rc']['port'] = "3308";
		$this->db['aalm']['rc']['name'] = "ldb_mls";

		$this->db['aalm']['rc']['obj']  = mysqli_connect(
				$this->db['aalm']['rc']['host'],
				$this->db['aalm']['rc']['user'],
				$this->db['aalm']['rc']['pass'],
				$this->db['aalm']['rc']['name'],
				$this->db['aalm']['rc']['port']);


		// Live
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

		// Impact
		// Local
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

		// RC
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


		// Live
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

		// QEASY
		// Local
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

		// RC
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

		// Live
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

		// LCS
		// Local
		$this->db['lcs']['local']['user'] = "ecash";
		$this->db['lcs']['local']['pass'] = "lacosanostra";
		$this->db['lcs']['local']['host'] = "monster.tss";
		$this->db['lcs']['local']['port'] = "3309";
		$this->db['lcs']['local']['name'] = "ldb_lcs";

		$this->db['lcs']['local']['obj']  = mysqli_connect(
				$this->db['lcs']['local']['host'],
				$this->db['lcs']['local']['user'],
				$this->db['lcs']['local']['pass'],
				$this->db['lcs']['local']['name'],
				$this->db['lcs']['local']['port']);

		// RC
		$this->db['lcs']['rc']['user'] = "ecash";
		$this->db['lcs']['rc']['pass'] = "lacosanostra";
		$this->db['lcs']['rc']['host'] = "db101.ept.tss";
		$this->db['lcs']['rc']['port'] = "3308";
		$this->db['lcs']['rc']['name'] = "ldb_lcs";

		$this->db['lcs']['rc']['obj']  = mysqli_connect(
				$this->db['lcs']['rc']['host'],
				$this->db['lcs']['rc']['user'],
				$this->db['lcs']['rc']['pass'],
				$this->db['lcs']['rc']['name'],
				$this->db['lcs']['rc']['port']);


		// Live
        $this->db['lcs']['live']['user'] = "ecashlcs";
        $this->db['lcs']['live']['pass'] = "hahMeeg4";
        $this->db['lcs']['live']['host'] = "writer.ecashaalm.ept.tss";
        $this->db['lcs']['live']['port'] = "3306";
        $this->db['lcs']['live']['name'] = "ldb_lcs";

		$this->db['lcs']['live']['obj']  = mysqli_connect(
				$this->db['lcs']['live']['host'],
				$this->db['lcs']['live']['user'],
				$this->db['lcs']['live']['pass'],
				$this->db['lcs']['live']['name'],
				$this->db['lcs']['live']['port']);
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
					echo "\t\t\t[Connected to {$this->db[$company][$rc_lvl]['name']}]\n";
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
				$query = "SELECT * FROM control_option WHERE name='Populate Collections Agent' AND description='The agents associated with this feature will be visible as a collections agent in reports which list collections agents.'";

				$res = $mysqli->query($query);

				// This should be equal to 2 when the update is applied
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
				// Get Parent IDs
				$query = "UPDATE control_option SET description='The agents associated with this feature will be visible as a collections agent in reports which list collections agents.' WHERE name='Populate Collections Agent';";

				$res = $mysqli->query($query);

				if ($mysqli->affected_rows == 1)
				{
					$this->db[$company][$rc_lvl]['applied'] = true;
					echo "\t\t\t[Success]\n";
					continue;
				}
				else
				{
					$this->db[$company][$rc_lvl]['applied'] = false;
					echo "\t\t\t[FAILURE]\n";
					continue;
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
				$query = "UPDATE control_option SET description='The agents associated with this feature will be visible as a collections agent.' WHERE name='Populate Collections Agent';";

				$res = $mysqli->query($query);

				if ($mysqli->affected_rows == 1)
				{
					$this->db[$company][$rc_lvl]['applied'] = true;
					echo "\t\t\t[Success]\n";
					continue;
				}
				else
				{
					$this->db[$company][$rc_lvl]['applied'] = false;
					echo "\t\t\t[FAILURE]\n";
					continue;
				}
//////////////////////////////////////////////////////////////////////////////////////////////////
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
