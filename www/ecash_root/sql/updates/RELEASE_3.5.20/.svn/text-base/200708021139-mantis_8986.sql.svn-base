CREATE TABLE `agent_affiliation_reason` (
  `date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_created` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `agent_affiliation_reason_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL DEFAULT '',
  `name_short` VARCHAR(20) NOT NULL DEFAULT '',
  `sort` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY  (`agent_affiliation_reason_id`),
  UNIQUE KEY idx_affil_reason_name_short (`name_short`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `agent_affiliation` 
	ADD COLUMN `agent_affiliation_reason_id` INT(10) UNSIGNED NOT NULL DEFAULT 0;
	
INSERT INTO agent_affiliation_reason
  (date_created, name, name_short, sort)
VALUES
  (NOW(), 'Manual Followup', 'followup', 1),
  (NOW(), 'Broken Arrangements', 'broken_arrangements', 2),
  (NOW(), 'Completed Arrangements With Remaining Balance', 'arr_with_balance', 3),
  (NOW(), 'Arranged Payment Due', 'arrangement_due', 4),
  (NOW(), 'Other', 'other', 5)
;