<?php
// This will create a file that contains the SQL needed to insert all of the missing apps into the collections queue.
require_once(dirname(__FILE__).'/../../../www/config.php');


$db = ECash::getMasterDb();

$queue_query = "SELECT application_id,date_application_status_set,date_modified
FROM application 
WHERE application_status_id='125' 
AND (
    SELECT COUNT(*) 
    FROM n_time_sensitive_queue_entry ntqe 
    WHERE ntqe.related_id = application.application_id
) = 0 
AND (
    SELECT COUNT(*)  
    FROM event_schedule es
    LEFT JOIN transaction_register tr ON es.event_schedule_id = tr.event_schedule_id
    WHERE es.application_id = application.application_id 
    AND (es.event_status = 'scheduled' OR tr.transaction_status = 'pending')
) = 0 
AND (
    SELECT COUNT(*)
    FROM agent_affiliation 
    WHERE application_id = application.application_id
    AND affiliation_status = 'active'
    ) = 0";


				$file = '/tmp/queueless.sql';
				$fp = fopen($file, 'w');
		
				
				$application_list = $db->Query($queue_query);
	
				$queue_id = 12;
				while($row = $application_list->fetch(PDO::FETCH_OBJ))
				{
					$agent = 'null';
					$available = time();
							$daylight_savings = date('I');
							$app_query = "select zip_code from application where application_id = $row->application_id";
							$zip = $db->Query($app_query)->fetch(PDO::FETCH_OBJ)->zip_code;
						
							 $zip_query = "
				                        SELECT
				                        (CASE
				                                        WHEN
				                 $daylight_savings AND
				                 dst = 'Y'
				                                        THEN tz - 1
				                                        ELSE tz
				                                END) as offset
				                        FROM zip_tz
				                        WHERE
				                                zip_code = $zip
				                        LIMIT 1
	                                ";
						
							$company_tz = new DateTimeZone(ECash::getConfig()->TIME_ZONE);
							$company_tz_offset = $company_tz->getOffset(new DateTime("now", $company_tz)) /3600;
							
							if (!is_null($zip_row = $db->Query($zip_query)->fetch(PDO::FETCH_OBJ)) )
							{
								echo "\ntime zone info: {$zip_row->offset}";
								$offset = $zip_row->offset + $company_tz_offset;
								$start_time =	ECash::getConfig()->LOCAL_EARLIEST_CALL_TIME + $offset;
								$end_time =	ECash::getConfig()->LOCAL_LATEST_CALL_TIME + $offset;
							}
							else 
							{
								echo "\nno time zone info";
								$start_time =	ECash::getConfig()->LOCAL_EARLIEST_CALL_TIME;
								$end_time =	ECash::getConfig()->LOCAL_LATEST_CALL_TIME ;
							}
						
							$priority = 100;
						
							$queue_entry = "insert into n_time_sensitive_queue_entry (queue_id,agent_id,related_id,date_queued,date_available,dequeue_count,start_hour,end_hour,priority)
						 				values ($queue_id,$agent,$row->application_id,NOW(),NOW(),1,$start_time,$end_time, $priority);";
						 	echo "\nAdding Time Sensitive Queue Entry for Queue $row->queue_name for App: $row->application_id with Priority: $priority\n";
						 	
						 	//$db->query($queue_entry);
						
		
			
				fwrite($fp,$queue_entry."\n");
				
		
		

				
		
		
				}
				fclose($fp);


?>
