CREATE TABLE IF NOT EXISTS `transaction`
    (   `transaction_id`        INT             UNSIGNED    NOT NULL    AUTO_INCREMENT                                          COMMENT "Primary key"
    ,   `transaction_status_id` INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "Where is this transaction right now"
    ,   `event_amount`          DECIMAL(7,2)                NOT NULL    DEFAULT 0                                               COMMENT "CONVENIENCE: How much was sent/received, total of column from event table"
    ,   `application_id`        INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "CONVENIENCE: Ownership"
    ,   `company_id`            INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "CONVENIENCE: Owner"
    ,   `date_modified`         TIMESTAMP                   NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP   COMMENT "CONVENIENCE: When"
    ,   `date_created`          TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00'                           COMMENT "CONVENIENCE: When"
    ,   `mechanism_id`          INT             UNSIGNED        NULL    DEFAULT NULL                                            COMMENT "CONVENIENCE: Duplicate of event_amount.mechanism_id"
    ,   PRIMARY KEY (`transaction_id`)
    )   ENGINE=InnoDB COMMENT="The actual line-request sent/received, created by the mechanism in question" ;
