CREATE TABLE `action`
    (   `date_modified` TIMESTAMP       NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP   COMMENT 'Self explanatory'
    ,   `date_created`  TIMESTAMP       NOT NULL    DEFAULT '0000-00-00 00:00:00'                           COMMENT 'Self explanatory'
    ,   `action_id`     INT UNSIGNED    NOT NULL    AUTO_INCREMENT                                          COMMENT 'Primary key'
    ,   `name`          VARCHAR(255)    NOT NULL    DEFAULT ''                                              COMMENT 'Unique descriptive name indicating what happened'
    ,   `name_short`    VARCHAR(255)    NOT NULL    DEFAULT ''                                              COMMENT 'Name used in source code'
    ,   PRIMARY KEY (`action_id`)
    )   ENGINE=InnoDB COMMENT='Used by agent_action for tracking activity'
    ;
INSERT INTO `action` VALUES
    (   NOW()   ,   NOW()   ,   1   ,   'React button pressed'          ,   'reactivate'    )
    ,
    (   NOW()   ,   NOW()   ,   2   ,   'Request performed'             ,   'request'       )
    ,
    (   NOW()   ,   NOW()   ,   3   ,   'Application search performed'  ,   'search'        )
    ,
    (   NOW()   ,   NOW()   ,   4   ,   'React offer button pressed'    ,   'react_offer'   )
    ,
    (   NOW()   ,   NOW()   ,   5   ,   'Logged in'                     ,   'login'         )
    ,
    (   NOW()   ,   NOW()   ,   6   ,   'Logged out'                    ,   'logout'        )
    ;

CREATE TABLE `agent_action`
    (   `date_created`      TIMESTAMP       NOT NULL    DEFAULT CURRENT_TIMESTAMP   COMMENT 'Self explanatory'
    ,   `agent_id`          INT UNSIGNED    NOT NULL    DEFAULT 0                   COMMENT 'Whodunit'
    ,   `action_id`         INT UNSIGNED    NOT NULL    DEFAULT 0                   COMMENT 'What did they do?'
    ,   `time_expended`     DOUBLE              NULL    DEFAULT NULL                COMMENT 'How many seconds did the operation take?'
    ,   `application_id`    INT UNSIGNED        NULL    DEFAULT NULL                COMMENT 'What application was involved (if any)?'
    ,   INDEX `date_created` (`date_created`)
    ,   INDEX `agent_id` (`agent_id`)
    ,   INDEX `action_id` (`action_id`)
    ,   INDEX `application_id` (`application_id`)
    )   ENGINE=InnoDB COMMENT='Tracks who does what, when'
    ;
