DELIMITER |

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    transaction_register_before_update
BEFORE UPDATE ON
	transaction_register
FOR EACH ROW
BEGIN
	IF ( NEW.transaction_status != OLD.transaction_status ) THEN
		INSERT INTO
		transaction_history
		(   company_id
		,   application_id
		,   transaction_register_id
		,   agent_id
		,   status_before
		,   status_after
		)
		VALUES
		(   NEW.company_id
		,   NEW.application_id
		,   NEW.transaction_register_id
		,   NEW.modifying_agent_id
		,   OLD.transaction_status
		,   NEW.transaction_status
		)
		;
	END IF;
END

|
