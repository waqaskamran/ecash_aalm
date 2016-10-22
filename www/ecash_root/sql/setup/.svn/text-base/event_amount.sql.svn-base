CREATE TABLE IF NOT EXISTS `event_amount`
    (   `event_amount_id`       BIGINT          UNSIGNED    NOT NULL    AUTO_INCREMENT                                          COMMENT "Primary key"
    ,   `event_schedule_id`     INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "Which event does this contribute to"
    ,   `event_amount_type_id`  INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "What is this portion considered (for reporting purposes)"
    ,   `amount`                DECIMAL(7,2)                NOT NULL    DEFAULT 0                                               COMMENT "How much is this amount"
    ,   `application_id`        INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "CONVENIENCE: Ownership"
    ,   `num_reattempt`         INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "If this is a reattempt, this should be the attempt number"
    ,   `company_id`            INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "CONVENIENCE: Owner"
    ,   `date_modified`         TIMESTAMP                   NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP   COMMENT "CONVENIENCE: When"
    ,   `date_created`          TIMESTAMP                   NOT NULL    DEFAULT CURRENT_TIMESTAMP                               COMMENT "CONVENIENCE: When"
    ,   PRIMARY KEY (`event_amount_id`)
    ,   KEY `event_schedule_id` (`event_schedule_id`)
    ,   KEY `application_id` (`application_id`)
    )   ENGINE=InnoDB COMMENT="The dollar portions which combine to make the event's total amount" ;
