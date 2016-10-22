DELIMITER |

CREATE DEFINER='ecashtrigger'@'%' TRIGGER
    event_amount_before_delete
BEFORE DELETE ON
    event_amount
FOR EACH ROW
BEGIN
	DECLARE amount_type VARCHAR(255);
	SELECT eat.name_short INTO amount_type FROM event_amount_type eat WHERE eat.event_amount_type_id = OLD.event_amount_type_id;
	IF amount_type = 'principal' THEN
		UPDATE event_schedule SET amount_principal = amount_principal - OLD.amount WHERE event_schedule_id = OLD.event_schedule_id;
	ELSE
		UPDATE event_schedule SET amount_non_principal = amount_non_principal - OLD.amount WHERE event_schedule_id = OLD.event_schedule_id;
	END IF;
END

|
