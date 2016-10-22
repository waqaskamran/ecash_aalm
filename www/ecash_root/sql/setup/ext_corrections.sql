CREATE TABLE IF NOT EXISTS `ext_corrections`
    (   `date_modified`             TIMESTAMP                                   NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ,   `date_created`              TIMESTAMP                                   NOT NULL    DEFAULT '0000-00-00 00:00:00'
    ,   `ext_corrections_id`        INT                             UNSIGNED    NOT NULL    AUTO_INCREMENT
    ,   `company_id`                INT                             UNSIGNED    NOT NULL    DEFAULT 0
    ,   `application_id`            INT                             UNSIGNED    NOT NULL    DEFAULT 0
    ,   `old_balance`               DECIMAL(10,2)                               NOT NULL    DEFAULT 0
    ,   `adjustment_amount`         DECIMAL(10,2)                               NOT NULL    DEFAULT 0
    ,   `new_balance`               DECIMAL(10,2)                               NOT NULL    DEFAULT 0
    ,   `file_name`                 VARCHAR(255)                    BINARY      NOT NULL    DEFAULT 'default.txt'
    ,   `file_contents`             BLOB                                            NULL
    ,   `download_count`            INT                             UNSIGNED    NOT NULL    DEFAULT 0
    ,   PRIMARY KEY (`ext_corrections_id`)
    ,   INDEX `download_count` (`download_count`)
    ,   UNIQUE `file_name` (`file_name`)
    )   ENGINE=InnoDB COMMENT='Stores batched corrections';
