DELIMITER |

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    app_insrt
BEFORE INSERT ON
    application
FOR EACH ROW
BEGIN
        INSERT INTO status_history (company_id, application_id, agent_id, application_status_id)
         VALUES (NEW.company_id, NEW.application_id, NEW.modifying_agent_id, NEW.application_status_id);
        SET NEW.date_application_status_set = now();
        SET NEW.date_modified = now();
        SET NEW.date_created = now();
END

|
