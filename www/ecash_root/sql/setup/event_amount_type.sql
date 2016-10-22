CREATE TABLE IF NOT EXISTS `event_amount_type`
    (   `event_amount_type_id`  INT             UNSIGNED    NOT NULL    AUTO_INCREMENT                                          COMMENT "Primary key"
    ,   `name_short`            VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "Code looks this up"
    ,   `name`                  VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "This represents the 'type' of money"
    ,   `description`           VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "What this really means"
    ,   `date_modified`         TIMESTAMP                   NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP   COMMENT "CONVENIENCE: When"
    ,   `date_created`          TIMESTAMP                   NOT NULL    DEFAULT CURRENT_TIMESTAMP                               COMMENT "CONVENIENCE: When"
    ,   PRIMARY KEY (`event_amount_type_id`)
    ,   UNIQUE `name_short` (`name_short`)
    )   ENGINE=InnoDB COMMENT="REFERENCE TABLE: The different types of dollar amount" ;

INSERT IGNORE INTO `event_amount_type` VALUES (1,'principal'     ,'Principal'      ,'Amounts which affect the initial loan value'               ,NOW(),NOW());
INSERT IGNORE INTO `event_amount_type` VALUES (2,'service_charge','Interest','Amounts accrued due to time (interest)'                    ,NOW(),NOW());
INSERT IGNORE INTO `event_amount_type` VALUES (3,'fee'           ,'Fees'           ,'Amounts accrued due to activities (ex: failed transaction)',NOW(),NOW());
