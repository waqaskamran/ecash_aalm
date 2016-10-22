DELIMITER |

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    app_fld_insert
BEFORE INSERT ON
    application_field
FOR EACH ROW
BEGIN
        DECLARE flag_name VARCHAR(100);
        SELECT afa.field_name INTO flag_name FROM application_field_attribute afa WHERE afa.application_field_attribute_id=NEW.application_field_attribute_id;
        INSERT INTO application_audit
                (date_created, company_id, application_id, table_name, column_name, value_before, value_after, update_process, agent_id) 
        VALUES (now(), NEW.company_id, NEW.table_row_id, 'application_field', NEW.column_name, 'off', flag_name,
                 'mysql::trigger:app_fld_insert', NEW.agent_id);
END

|
