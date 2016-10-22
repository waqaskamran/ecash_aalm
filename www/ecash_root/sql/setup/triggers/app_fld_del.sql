DELIMITER |

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    app_fld_del
BEFORE DELETE ON
    application_field
FOR EACH ROW
BEGIN
        DECLARE flag_name VARCHAR(100);
        SELECT afa.field_name INTO flag_name FROM application_field_attribute afa WHERE afa.application_field_attribute_id=OLD.application_field_attribute_id;
        INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after, update_process,agent_id) 
        VALUES (now(), OLD.company_id, OLD.table_row_id, 'application_field', OLD.column_name, flag_name, 'off',
                 'mysql::trigger:app_fld_del', 0);
END

|
