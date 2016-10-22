CREATE TABLE IF NOT EXISTS `transactional_name`
    (   `transactional_name_id` INT             UNSIGNED    NOT NULL    AUTO_INCREMENT                  COMMENT "Primary Key"
    ,   `company_id`            INT             UNSIGNED    NOT NULL    DEFAULT 0                       COMMENT "Owner of this business name"
    ,   `date_modified`         TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00'   COMMENT "CONVENIENCE: When"
    ,   `date_created`          TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00'   COMMENT "CONVENIENCE: When"
    ,   `event_amount_type_id`  INT             UNSIGNED        NULL    DEFAULT NULL                    COMMENT "NULL == Covers all / none"
    ,   `mechanism_id`          INT             UNSIGNED        NULL    DEFAULT NULL                    COMMENT "NULL == Covers all / none"
    ,   `event_type_id`         INT             UNSIGNED        NULL    DEFAULT NULL                    COMMENT "NULL == Covers all / none"
    ,   `context_id`            INT             UNSIGNED        NULL    DEFAULT NULL                    COMMENT "NULL == Covers all / none"
    ,   `event_sum_cardinality` ENUM('positive','negative')     NULL    DEFAULT NULL                    COMMENT "NULL == Covers all / none"
    ,   `name`                  VARCHAR(255)                NOT NULL    DEFAULT ''                      COMMENT "What the monkey should see for this combination"
    ,   PRIMARY KEY (`transactional_name_id`)
    ,   UNIQUE `lookup` (`event_amount_type_id`,`mechanism_id`,`event_type_id`,`context_id`,`event_sum_cardinality`)
    )   ENGINE=InnoDB COMMENT="Stores a mapping for business-model names" ;
