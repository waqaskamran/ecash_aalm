CREATE TABLE IF NOT EXISTS `batch_progress` (
	`date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
	`date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
	`batch_progress_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`percent` int(3) unsigned default NULL,
	`message` text,
	`batch` varchar(20) NOT NULL default 'ach',
	`company_id` int(10) unsigned NOT NULL default '0',
	`viewed` tinyint(1) NOT NULL default 0,
	PRIMARY KEY (`batch_progress_id`)
) ENGINE=InnoDb COMMENT="Holds progress messages during batch execution.";


