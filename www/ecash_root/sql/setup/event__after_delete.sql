-- This trigger emulates a foreign key cascade deletion.  The only
-- reason we are not using foreign keys, is that we are confident this
-- trigger will work and not entirely confident of the foreign key,
-- and that the database currently uses triggers but no foreign keys.

DELIMITER |
CREATE TRIGGER
    `event__after_delete`
AFTER DELETE ON
    `event`
FOR EACH ROW
    BEGIN
        DELETE FROM `event_amount` WHERE `event_id` = `OLD`.`event_id` ;
        DELETE FROM `agent_affiliation_event` WHERE `event_id` = `OLD`.`event_id` ;
    END
|
DELIMITER ;
