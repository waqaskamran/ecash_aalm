CREATE TABLE IF NOT EXISTS `reports_dda_history`
    (   `dda_history__id`   INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "The actual history entry"
    ,   `date`              DATETIME                    NOT NULL    DEFAULT '0000-00-00 00:00:00'   COMMENT "When did the change happen"
    ,   `agent_id`          INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "Who performed the change"
    ,   `application_id`    INT             UNSIGNED        NULL    DEFAULT NULL                    COMMENT "Which application was involved"
    ,   `event_schedule_id` INT             UNSIGNED        NULL    DEFAULT NULL                    COMMENT "Which event was involved"
    ,   `description`       VARCHAR(255)                NOT NULL    DEFAULT 'Changed'               COMMENT "Human readable description of change"
    ,   UNIQUE `dda_history__id` (`dda_history__id`)
    ,   INDEX `date` (`date`)
    ,   INDEX `agent_id` (`agent_id`)
    ,   INDEX `application_id` (`application_id`)
    ,   INDEX `event_schedule_id` (`event_schedule_id`)
    ,   INDEX `description` (`description`)
    )   ENGINE=InnoDB COMMENT="Cron-updated reportable history of dda_history"
    ;
