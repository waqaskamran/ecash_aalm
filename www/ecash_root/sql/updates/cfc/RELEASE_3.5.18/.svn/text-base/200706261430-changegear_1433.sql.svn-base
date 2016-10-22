
-- These are changing due to the addition of auto_increment to application (not using blackbox app IDs)

drop trigger app_insrt; -- if exists is only 5.0.32+

DELIMITER //

create 
definer = 'ecashtrigger'@'%'
trigger app_insrt_before BEFORE INSERT on application
FOR EACH ROW BEGIN
        SET NEW.date_application_status_set = now();
        SET NEW.date_modified = now();
        SET NEW.date_created = now();
END;
//

create 
definer = 'ecashtrigger'@'%'
trigger app_insrt_after AFTER INSERT on application
FOR EACH ROW BEGIN
        INSERT INTO status_history (company_id, application_id, agent_id, application_status_id)
         VALUES (NEW.company_id, NEW.application_id, NEW.modifying_agent_id, NEW.application_status_id);
END;
//

delimiter ;