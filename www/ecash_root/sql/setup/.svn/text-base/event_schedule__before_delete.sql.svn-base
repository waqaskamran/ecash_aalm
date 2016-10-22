DELIMITER |
CREATE TRIGGER
    `event_schedule__before_delete`
BEFORE DELETE ON
    `event_schedule`
FOR EACH ROW
    BEGIN
        DELETE FROM `event_amount` WHERE `event_schedule_id` = `OLD`.`event_schedule_id` ;
    END
|
DELIMITER ;
