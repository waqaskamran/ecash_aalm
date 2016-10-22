CREATE TABLE IF NOT EXISTS `dda_history`
    (   `id`                INT             UNSIGNED    NOT NULL    AUTO_INCREMENT                  COMMENT "Every table needs a primary key"
    ,   `date`              DATETIME                    NOT NULL    DEFAULT '0000-00-00 00:00:00'   COMMENT "This is when this was inserted"
    ,   `class`             VARCHAR(255)    BINARY      NOT NULL    DEFAULT ''                      COMMENT "Which class called this"
    ,   `serialized`        LONGBLOB                        NULL                                    COMMENT "PHP Serialized: Contains everything needed to process transaction, reverse it, and report on it."
    ,   PRIMARY KEY (`id`)
    )   ENGINE=InnoDB COMMENT="A reversable history of changes"
    ;
