-- Examples: Paydown, Periodic Charge, Failed Transaction Fee, Initial
-- Fund Event, etc.
CREATE TABLE IF NOT EXISTS `event_type`
    (   `event_type_id`         INT             UNSIGNED    NOT NULL    AUTO_INCREMENT                                          COMMENT "Primary key"
    ,   `name_short`            VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "Code looks this up"
    ,   `name`                  VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "What type of event this is"
    ,   `description`           VARCHAR(255)                NOT NULL    DEFAULT ''                                              COMMENT "What does this cover"
    ,   `date_modified`         TIMESTAMP                   NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP   COMMENT "CONVENIENCE: When"
    ,   `date_created`          TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00'                           COMMENT "CONVENIENCE: When"
    ,   PRIMARY KEY (`event_type_id`)
    ,   UNIQUE `name_short` (`name_short`)
    )   ENGINE=InnoDB COMMENT="REFERENCE TABLE: What kind of even this is" ;

INSERT IGNORE INTO `event_type` VALUES (1,'disbursement','Disbursement / Cancellation','Changes to the initial loan amount at the start of the loan'                                                ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (2,'payment'     ,'Payment'                    ,'Changes to the current balance made during the loan lifetime representing money received from customer'     ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (3,'failure'     ,'Transaction Failure'        ,'Changes to the current balance made due to a transaction failure, generally fees'                           ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (4,'write_off'   ,'Write-off'                  ,'Adjustment of balance to zero due to complete inability to recover funds (ex: bankruptcy)'                  ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (5,'conversion'  ,'Conversion'                 ,'Balance-forward when brought in from another system'                                                        ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (6,'default'     ,'Default'                    ,'When customer defaults on payments, perform a "default" action (ex: full pull of balance)'                  ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (7,'foregiveness','Foregiveness Adjustment'    ,'When the customer has called in and the company allows a change of balance (not full balance)'              ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (8,'refund'      ,'Refund'                     ,'When the customer has paid more than they needed to, and we refund the difference (sets balance to zero)'   ,NOW(),NOW());
INSERT IGNORE INTO `event_type` VALUES (9,'payout'      ,'Payout'                     ,'When the customer decides to pay "out" the full balance amount, indicating a positive early end to the loan',NOW(),NOW());
