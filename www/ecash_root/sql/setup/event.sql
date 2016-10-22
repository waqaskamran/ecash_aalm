CREATE TABLE IF NOT EXISTS `event`
    (   `event_id`              INT             UNSIGNED    NOT NULL    AUTO_INCREMENT                  COMMENT "Primary key"
    ,   `context_id`            INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "Last context or creation context"
    ,   `notes`                 VARCHAR(255)                    NULL    DEFAULT NULL                    COMMENT "When a human manually edits something, make sure they enter a comment"
    ,   `date_scheduled_for`    DATE                        NOT NULL    DEFAULT '0000-00-00'            COMMENT "This is the date upon which the mechanism should pick up the event"
    ,   `application_id`        INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "Who's event is this"
    ,   `parent_transaction_id` INT             UNSIGNED        NULL    DEFAULT NULL                    COMMENT "Which transaction logically induced this event"
    ,   `event_type_id`         INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "For reporting, what kind of event is this?"
    ,   `mechanism_id`          INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "Which mechanism is supposed to pick this up"
    ,   `company_id`            INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "CONVENIENCE: Owner"
    ,   `date_modified`         TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00'   COMMENT "CONVENIENCE: When"
    ,   `date_created`          TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00'   COMMENT "CONVENIENCE: When"
    ,   `date_effective`        DATE                        NOT NULL    DEFAULT '0000-00-00'            COMMENT "CONVENIENCE: Calculated using date_scheduled_for"
    ,   PRIMARY KEY (`event_id`)
    )   ENGINE=InnoDB COMMENT="Past and future transactional events" ;
