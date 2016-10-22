DELIMITER |

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    `app_updt`
BEFORE UPDATE ON
    `application`
FOR EACH ROW
BEGIN
        IF(NEW.application_status_id != OLD.application_status_id ) THEN
        		SET NEW.date_application_status_set = now();
                INSERT INTO status_history (company_id, application_id, agent_id, application_status_id)
                 VALUES (NEW.company_id, NEW.application_id, NEW.modifying_agent_id, NEW.application_status_id);
        END IF;
        IF(LOWER(NEW.name_last) != LOWER(OLD.name_last)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'name_last', OLD.name_last, NEW.name_last,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.name_first) != LOWER(OLD.name_first)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'name_first', OLD.name_first, NEW.name_first,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.bank_aba != OLD.bank_aba) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'bank_aba', OLD.bank_aba, NEW.bank_aba,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.bank_account_type != OLD.bank_account_type) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'bank_account_type', OLD.bank_account_type, NEW.bank_account_type,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.bank_account != OLD.bank_account) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'bank_account', OLD.bank_account, NEW.bank_account,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (NEW.date_fund_actual <=> OLD.date_fund_actual)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'date_fund_actual', OLD.date_fund_actual, NEW.date_fund_actual,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
		IF(NOT (NEW.fund_actual <=> OLD.fund_actual)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after, 
				update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'fund_actual', OLD.fund_actual, NEW.fund_actual, 
				 'mysql::trigger:app_updt', NEW.modifying_agent_id);
		END IF;
        IF(NOT (NEW.date_first_payment <=> OLD.date_first_payment)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'date_first_payment', OLD.date_first_payment, NEW.date_first_payment,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.income_monthly != OLD.income_monthly) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'income_monthly', OLD.income_monthly, NEW.income_monthly,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.income_direct_deposit) != LOWER(OLD.income_direct_deposit)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'income_direct_deposit', OLD.income_direct_deposit, NEW.income_direct_deposit,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.income_frequency) != LOWER(OLD.income_frequency)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'income_frequency', OLD.income_frequency, NEW.income_frequency,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.paydate_model) != LOWER(OLD.paydate_model)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'paydate_model', OLD.paydate_model, NEW.paydate_model,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (LOWER(NEW.day_of_week) <=> LOWER(OLD.day_of_week))) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'day_of_week', OLD.day_of_week, NEW.day_of_week,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (NEW.last_paydate <=> OLD.last_paydate)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'last_paydate', OLD.last_paydate, NEW.last_paydate,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (NEW.day_of_month_1 <=> OLD.day_of_month_1)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'day_of_month_1', OLD.day_of_month_1, NEW.day_of_month_1,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (NEW.day_of_month_2 <=> OLD.day_of_month_2)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'day_of_month_2', OLD.day_of_month_2, NEW.day_of_month_2,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (NEW.week_1 <=> OLD.week_1)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'week_1', OLD.week_1, NEW.week_1,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (NEW.week_2 <=> OLD.week_2)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'week_2', OLD.week_2, NEW.week_2,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.ssn != OLD.ssn) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'ssn', OLD.ssn, NEW.ssn,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.dob != OLD.dob) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'dob', OLD.dob, NEW.dob,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.email) != LOWER(OLD.email)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'email', OLD.email, NEW.email,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.street) != LOWER(OLD.street)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'street', OLD.street, NEW.street,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (LOWER(NEW.unit) <=> LOWER(OLD.unit))) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'unit', OLD.unit, NEW.unit,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NOT (LOWER(NEW.legal_id_number) <=> LOWER(OLD.legal_id_number))) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'legal_id_number', OLD.legal_id_number, NEW.legal_id_number,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.county) != LOWER(OLD.county)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'county', OLD.county, NEW.county,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.city) != LOWER(OLD.city)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'city', OLD.city, NEW.city,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(LOWER(NEW.state) != LOWER(OLD.state)) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'state', OLD.state, NEW.state,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.zip_code != OLD.zip_code) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'zip_code', OLD.zip_code, NEW.zip_code,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
        IF(NEW.phone_home != OLD.phone_home) THEN
                INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after,
                update_process,agent_id)
                VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'phone_home', OLD.phone_home, NEW.phone_home,
                 'mysql::trigger:app_updt', NEW.modifying_agent_id);
        END IF;
	-- Work Phone & Extension
		IF(NOT (NEW.phone_work <=> OLD.phone_work)) THEN
			INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after, update_process,agent_id)
			VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'phone_work', OLD.phone_work, NEW.phone_work, 'mysql::trigger:app_updt', NEW.modifying_agent_id);
		END IF;
		IF(NOT (NEW.phone_work_ext <=> OLD.phone_work_ext)) THEN
			INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, value_before, value_after, update_process,agent_id)
			VALUES (now(), NEW.company_id, NEW.application_id, 'application', 'phone_work_ext', OLD.phone_work_ext, NEW.phone_work_ext, 'mysql::trigger:app_updt', NEW.modifying_agent_id);
		END IF;
        SET NEW.date_modified = now();
END

|

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    `flag_insert`
AFTER INSERT ON
    `application_flag`
FOR EACH ROW
BEGIN
    INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, 
	value_before, value_after, update_process,agent_id)
    VALUES (now(), NEW.company_id, NEW.application_id, 'application_flag', (SELECT name_short FROM flag_type WHERE flag_type_id = NEW.flag_type_id), 
	'CLEARED', 'SET', 'mysql::trigger:flag_nsrt', NEW.modifying_agent_id);
END

|

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    `flag_updt`
BEFORE UPDATE ON
    `application_flag`
FOR EACH ROW
BEGIN
        INSERT INTO application_audit (date_created, company_id, application_id, table_name, column_name, 
		value_before, 
		value_after,
        update_process,agent_id)
        VALUES (now(), OLD.company_id, OLD.application_id, 'application_flag', 
		(SELECT name_short FROM flag_type WHERE flag_type_id = NEW.flag_type_id), 
		CASE WHEN OLD.active_status = 'active' THEN 'SET' ELSE 'CLEARED' END, 
		CASE WHEN NEW.active_status = 'active' THEN 'SET' ELSE 'CLEARED' END,
        'mysql::trigger:app_updt', NEW.modifying_agent_id);
END

|
