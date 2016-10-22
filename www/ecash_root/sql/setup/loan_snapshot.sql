DROP TABLE IF EXISTS `loan_snapshot`;
CREATE TABLE `loan_snapshot`
    (   `date_modified`                     TIMESTAMP       NOT NULL    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP   COMMENT 'This should match the last change to ANY complete/pending transaction'
    ,   `date_created`                      TIMESTAMP       NOT NULL    DEFAULT '0000-00-00 00:00:00'                           COMMENT 'This should be the date of the first transaction'
    ,   `company_id`                        INT UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT 'Convenience column: application::company_id'
    ,   `application_id`                    INT UNSIGNED    NOT NULL    DEFAULT 0                                               COMMENT 'Foreign column: application::application_id'
    ,   `balance_complete_principal`        DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete AND event_amount_type_name = principal'
    ,   `balance_complete_service_charge`   DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete AND event_amount_type_name = service_charge'
    ,   `balance_complete_fee`              DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete AND event_amount_type_name = fee'
    ,   `balance_complete_irrecoverable`    DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete AND event_amount_type_name = irrecoverable'
    ,   `balance_complete`                  DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete'
    ,   `sum_pending_principal`             DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = pending AND event_amount_type_name = principal'
    ,   `sum_pending_service_charge`        DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = pending AND event_amount_type_name = service_charge'
    ,   `sum_pending_fee`                   DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = pending AND event_amount_type_name = fee'
    ,   `sum_pending_irrecoverable`         DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = pending AND event_amount_type_name = irrecoverable'
    ,   `sum_pending`                       DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = pending'
    ,   `balance_pending_principal`         DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete,pending AND event_amount_type_name = principal'
    ,   `balance_pending_service_charge`    DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete,pending AND event_amount_type_name = service_charge'
    ,   `balance_pending_fee`               DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete,pending AND event_amount_type_name = fee'
    ,   `balance_pending_irrecoverable`     DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete,pending AND event_amount_type_name = irrecoverable'
    ,   `balance_pending`                   DECIMAL(12,2)   NOT NULL    DEFAULT 0                                               COMMENT 'Trigger-maintained w/ transaction_status = complete,pending'
    ,   PRIMARY KEY (`application_id`)
    )   ENGINE=InnoDB COMMENT='Contains meta-data about applications generated from transactional tables'
    ;
