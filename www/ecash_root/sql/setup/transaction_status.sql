-- Examples: Sent, Complete, Failed, Pending...
CREATE TABLE IF NOT EXISTS `transaction_status`
    (   `transaction_status_id` INT             UNSIGNED    NOT NULL    AUTO_INCREMENT                                          COMMENT "Primary key"
    ,   `mechanism_id`          INT             UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT "Which tree"
    ,   `name_short`            VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "Code looks this up"
    ,   `name`                  VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "Status tree"
    ,   `date_modified`         TIMESTAMP                   NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP   COMMENT "CONVENIENCE: When"
    ,   `date_created`          TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00'                           COMMENT "CONVENIENCE: When"
    ,   PRIMARY KEY (`transaction_status_id`)
    ,   UNIQUE `mechanism__name_short` (`mechanism_id`,`name_short`)
    )   ENGINE=InnoDB COMMENT="REFERENCE TABLE: The states each mechanism can go through" ;
