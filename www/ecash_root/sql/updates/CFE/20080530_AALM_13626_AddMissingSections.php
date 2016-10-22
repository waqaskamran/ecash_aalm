<?php
/*
	just php <thisfile>
	
	This applies this change to AALM instances

	AFTER THIS RUN 20080530_AALM_13626_FixACL.sql
*/
class ECash_Update
{	
	protected $db;

	function __construct()
	{
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
				$query = "SELECT s3.name,s2.name,s1.name FROM section s1 JOIN section s2 ON (s2.section_id = s1.section_parent_id) JOIN section s3 ON (s3.section_id = s2.section_parent_id) WHERE s1.level='5' AND s1.description='Send Documents' AND (s3.name='fraud_queue' OR s3.name='high_risk_queue')";

				$res = $mysqli->query($query);

				$this->db[$company][$rc_lvl]['applied'] = false;

				// This should be equal to 2 when the update is applied
				if ($res->num_rows == 2)
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
				$query = "SELECT s1.section_id FROM section s1 JOIN section s2 ON (s2.section_id = s1.section_parent_id) JOIN section s3 ON (s3.section_id = s2.section_parent_id) WHERE s1.name='documents' AND s3.name='fraud' AND s2.name IN ('fraud_queue', 'high_risk_queue');";

				$res = $mysqli->query($query);

				$parent_ids = array();
				while ($row = $res->fetch_array(MYSQLI_ASSOC))
				{
					$parent_ids[] = $row['section_id'];
				}

				if (count($parent_ids) != 2)
				{
					echo "system is fucked, skipping";
					continue;
				}

				$added_ids = array();

				// Insert rows into DB for each missing section
				foreach ($parent_ids as $parent_id)
				{
					$query = "INSERT INTO section (date_modified, date_created, active_status, system_id, name, description, section_parent_id, sequence_no, level, read_only_option, can_have_queues) VALUES (NOW(), NOW(), 'active', 3, 'send_documents', 'Send Documents', {$parent_id}, 5, 5, 0, 0)";
					$res = $mysqli->query($query) or die(mysqli_error($mysqli));
					$added_ids[] = $mysqli->insert_id;

                    $query = "INSERT INTO section (date_modified, date_created, active_status, system_id, name, description, section_parent_id, sequence_no
, level, read_only_option, can_have_queues) VALUES (NOW(), NOW(), 'active', 3, 'receive_documents', 'Receive Documents', {$parent_id}, 10, 5, 0, 0)";
                    $res = $mysqli->query($query);
                    $added_ids[] = $mysqli->insert_id;

                    $query = "INSERT INTO section (date_modified, date_created, active_status, system_id, name, description, section_parent_id, sequence_no
, level, read_only_option, can_have_queues) VALUES (NOW(), NOW(), 'active', 3, 'esig_documents', 'ESig Documents', {$parent_id}, 15, 5, 0, 0)";
                    $res = $mysqli->query($query);
                    $added_ids[] = $mysqli->insert_id;

                    $query = "INSERT INTO section (date_modified, date_created, active_status, system_id, name, description, section_parent_id, sequence_no
, level, read_only_option, can_have_queues) VALUES (NOW(), NOW(), 'active', 3, 'packaged_docs', 'Packaged Documents', {$parent_id}, 20, 5, 0, 0)";
                    $res = $mysqli->query($query);
                    $added_ids[] = $mysqli->insert_id;
				}

				$this->db[$company][$rc_lvl]['applied'] = true;
				
				echo "\t\t\t[Success]\n";

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
				$query = "DELETE FROM loan_actions WHERE name_short='VERIFY_SAME_WC'";

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
