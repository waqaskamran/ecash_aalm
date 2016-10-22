/**
  * Updates made to CFC required for FBOD
  */
  
-- Add the `county` column
ALTER TABLE application
    ADD COLUMN `county` varchar(30) default NULL AFTER `state`
;

-- Disabled the reprocess feature
SELECT @section_id := section_id FROM section WHERE name = 'reprocess';
UPDATE section SET active_status = 'inactive' WHERE section_id = @section_id;
DELETE FROM acl WHERE section_id = @section_id;

-- Add the landmark clearing type, new Processing Fee event/transaction type
ALTER TABLE `transaction_type`
    MODIFY COLUMN `clearing_type` enum('ach','quickcheck','external','accrued charge','adjustment', 'landmark') NOT NULL default 'ach'
;

-- Update the rule sets and loan types
UPDATE loan_type SET active_status = 'inactive' WHERE name_short = 'classic';
UPDATE loan_type SET name = 'Simply Gold Card' WHERE name_short = 'gold';
UPDATE `rule_set` SET `name`='FBOD Nightly Task Schedule' WHERE `rule_set_id`=254;
UPDATE `rule_set` SET `name`='FBOD Queue Configuration' WHERE `rule_set_id`=256;
UPDATE `rule_set` SET `name`='FBOD Default Rule Set - Gold' WHERE `rule_set_id`=281;
UPDATE `rule_set` SET `name`='FBOD Default Rule Set - Classic' WHERE `rule_set_id`=284;
UPDATE `rule_set` SET `name`='FBOD Company Level Rule Set' WHERE `rule_set_id`=287;
UPDATE `rule_set` SET `name`='FBOD Title Loan Rule Set 07-02-2007 15:00:48' WHERE `rule_set_id`=289;
UPDATE `rule_set` SET `name`='FBOD Title Loan Rule Set  07-02-2007 15:48:25' WHERE `rule_set_id`=290;
UPDATE `rule_set` SET `name`='FBOD Title Loan Rule Set   07-10-2007 09:07:51' WHERE `rule_set_id`=291;
UPDATE `rule_set` SET `name`='FBOD Title Loan Rule Set    07-10-2007 09:07:55' WHERE `rule_set_id`=292;
UPDATE `rule_set` SET `name`='FBOD Title Loan Rule Set     07-10-2007 09:07:57' WHERE `rule_set_id`=293;


SET @company_id := 1;

-- First the Fee Assessment
INSERT INTO event_type(date_modified, date_created, active_status, company_id, event_type_id, name_short, name)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'assess_processing_fee', 'Processing Fee');
SET @event_type_id := LAST_INSERT_ID();

INSERT INTO transaction_type(date_modified, date_created, active_status, company_id, transaction_type_id, name_short, name, clearing_type, affects_principal, pending_period, end_status, period_type)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'assess_processing_fee', 'Processing Fee', 'accrued charge', 'no', '0', 'complete', 'business');
SET @transaction_type_id := LAST_INSERT_ID();

INSERT INTO event_transaction(date_modified, date_created, active_status, company_id, event_type_id, transaction_type_id, distribution_percentage, distribution_amount, spawn_percentage, spawn_amount, spawn_max_num)
  VALUES(NOW(), NOW(), 'active', @company_id, @event_type_id, @transaction_type_id, NULL, NULL, NULL, NULL, NULL);

-- Then the Payment
INSERT INTO event_type(date_modified, date_created, active_status, company_id, event_type_id, name_short, name)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'payment_processing_fee', 'Processing Fee Payment');
SET @event_type_id := LAST_INSERT_ID();

INSERT INTO transaction_type(date_modified, date_created, active_status, company_id, transaction_type_id, name_short, name, clearing_type, affects_principal, pending_period, end_status, period_type)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'payment_processing_fee', 'Processing Fee Payment', 'landmark', 'no', '5', 'complete', 'business');
SET @transaction_type_id := LAST_INSERT_ID();

INSERT INTO event_transaction(date_modified, date_created, active_status, company_id, event_type_id, transaction_type_id, distribution_percentage, distribution_amount, spawn_percentage, spawn_amount, spawn_max_num)
  VALUES(NOW(), NOW(), 'active', @company_id, @event_type_id, @transaction_type_id, NULL, NULL, NULL, NULL, NULL);

-- And finally the Writeoff if the transaction fails
INSERT INTO event_type(date_modified, date_created, active_status, company_id, event_type_id, name_short, name)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'writeoff_processing_fee', 'Processing Fee Writeoff');
SET @event_type_id := LAST_INSERT_ID();

INSERT INTO transaction_type(date_modified, date_created, active_status, company_id, transaction_type_id, name_short, name, clearing_type, affects_principal, pending_period, end_status, period_type)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'writeoff_processing_fee', 'Processing Fee', 'adjustment', 'no', '0', 'complete', 'business');
SET @transaction_type_id := LAST_INSERT_ID();

INSERT INTO event_transaction(date_modified, date_created, active_status, company_id, event_type_id, transaction_type_id, distribution_percentage, distribution_amount, spawn_percentage, spawn_amount, spawn_max_num)
  VALUES(NOW(), NOW(), 'active', @company_id, @event_type_id, @transaction_type_id, NULL, NULL, NULL, NULL, NULL);

-- Add the new landmark_ach table
CREATE TABLE `landmark_ach` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` int(10) unsigned NOT NULL default '0',
  `batch_date` date NOT NULL default '0000-00-00',
  `batch_type` enum('debit','credit') NOT NULL default 'debit',
  `application_id` int(10) unsigned NOT NULL default '0',
  `lm_ach_id` int(10) unsigned NOT NULL auto_increment,
  `return_report_id` int(10) unsigned default NULL,
  `amount` decimal(7,2) NOT NULL default '0.00',
  `bank_aba` varchar(9) NOT NULL default '',
  `bank_account` varchar(17) NOT NULL default '',
  `status` enum('created','batched','returned','processed') NOT NULL default 'created',
  `return_code` varchar(4) NOT NULL default '',
  `return_reason` varchar(32) NOT NULL default '',
  `confirmation_number` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`lm_ach_id`),
  KEY `idx_lm_ach_app_dt` (`application_id`,`batch_date`,`lm_ach_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Add the new landmark_report table
CREATE TABLE `landmark_report` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_request` date NOT NULL default '0000-00-00',
  `company_id` int(10) unsigned NOT NULL default '0',
  `return_report_id` int(10) unsigned NOT NULL auto_increment,
  `return_file_data` longtext,
  `report_status` enum('received','processed','failed','obsoleted') NOT NULL default 'received' COMMENT 'obsoleted = newer record exists (see date_request column)',
  PRIMARY KEY  (`return_report_id`),
  KEY `idx_landmark_report_date_request` (`date_request`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Add new certegy_batch table
CREATE TABLE  `certegy_batch` (
  `certegy_batch_id` int(11) NOT NULL auto_increment,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `batch_type` varchar(30) default '',
  `batch_status` varchar(30) default '',
  `batch_data` blob NOT NULL,
  PRIMARY KEY  (`certegy_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




-- Modify transaction_register to include lm_ach_id
ALTER TABLE transaction_register
    ADD COLUMN `lm_ach_id` int(10) unsigned default NULL AFTER `ecld_id`,
    ADD INDEX `idx_trans_reg_lmachid` (`lm_ach_id`)
;

