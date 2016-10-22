--This script should take agean up to the CFE database schema that mls uses
ALTER TABLE application ADD COLUMN cfe_rule_set_id int unsigned default NULL;
ALTER TABLE section ADD COLUMN can_have_queues tinyint unsigned default NULL;

CREATE TABLE `cfe_action` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `cfe_action_id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`cfe_action_id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;

CREATE TABLE `cfe_event` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `cfe_event_id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`cfe_event_id`),
  KEY `idx_short_name` (`short_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

CREATE TABLE `cfe_rule` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `cfe_rule_id` int(10) unsigned NOT NULL auto_increment,
  `cfe_rule_set_id` int(10) unsigned NOT NULL,
  `name` varchar(255) default NULL,
  `cfe_event_id` int(10) unsigned NOT NULL,
  `salience` tinyint(4) NOT NULL,
  PRIMARY KEY  (`cfe_rule_id`),
  KEY `idx_rule_set` (`cfe_rule_set_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3062 DEFAULT CHARSET=latin1;

CREATE TABLE `cfe_rule_action` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `cfe_rule_action_id` int(10) unsigned NOT NULL auto_increment,
  `cfe_rule_id` int(10) unsigned NOT NULL,
  `cfe_action_id` int(10) unsigned NOT NULL,
  `params` blob NOT NULL,
  `sequence_no` smallint(5) unsigned NOT NULL,
  `rule_action_type` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`cfe_rule_action_id`),
  KEY `idx_rule` (`cfe_rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3097 DEFAULT CHARSET=latin1;

CREATE TABLE `cfe_rule_condition` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `cfe_rule_condition_id` int(10) unsigned NOT NULL auto_increment,
  `cfe_rule_id` int(10) unsigned NOT NULL,
  `operator` enum('equals','notequals','greater','less') NOT NULL default 'equals',
  `operand1` varchar(255) NOT NULL,
  `operand1_type` tinyint(3) unsigned NOT NULL,
  `operand2` varchar(255) NOT NULL,
  `operand2_type` tinyint(3) unsigned NOT NULL,
  `sequence_no` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`cfe_rule_condition_id`),
  KEY `idx_rule` (`cfe_rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4144 DEFAULT CHARSET=latin1;

CREATE TABLE `cfe_rule_set` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `cfe_rule_set_id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `loan_type_id` int(10) unsigned NOT NULL default '0',
  `date_effective` timestamp NOT NULL default '0000-00-00 00:00:00',
  `created_by` varchar(255) NOT NULL,
  PRIMARY KEY  (`cfe_rule_set_id`),
  UNIQUE KEY `idx_rule_set_loan_type_effdate` (`loan_type_id`,`date_effective`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE `cfe_variable` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `cfe_variable_id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `name_short` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY  (`cfe_variable_id`),
  KEY `idx_name_short` (`name_short`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=latin1;

CREATE TABLE `n_escalated_queue_entry` (
  `queue_entry_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `agent_id` int(10) unsigned default NULL,
  `related_id` int(10) unsigned NOT NULL,
  `source_queue_id` int(10) unsigned NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_available` datetime NOT NULL,
  `date_expire` datetime default NULL,
  `priority` tinyint(3) NOT NULL default '0',
  `dequeue_count` tinyint(3) NOT NULL default '0',
  PRIMARY KEY  (`queue_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `n_queue` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` datetime default NULL,
  `queue_id` int(10) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned default NULL,
  `queue_group_id` int(10) unsigned default NULL,
  `escalate_queue_id` int(10) unsigned default NULL,
  `section_id` int(10) unsigned default NULL,
  `name_short` varchar(253) default NULL,
  `name` varchar(30) NOT NULL,
  `display_name` varchar(16) NOT NULL,
  `sort_order` varchar(60) NOT NULL default 'priority desc, date_available asc',
  `control_class` varchar(20) NOT NULL default 'BasicQueue',
  `is_system_queue` tinyint(4) NOT NULL,
  PRIMARY KEY  (`queue_id`),
  UNIQUE KEY `name_short_idx` (`name_short`,`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

CREATE TABLE `n_queue_config` (
  `queue_config_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `config_key` varchar(32) NOT NULL,
  `config_value` varchar(64) NOT NULL,
  PRIMARY KEY  (`queue_config_id`),
  UNIQUE KEY `uni_config_queue` (`queue_id`,`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;

CREATE TABLE `n_queue_display` (
  `queue_display_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) default NULL,
  `section_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`queue_display_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;

CREATE TABLE `n_queue_entry` (
  `queue_entry_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `agent_id` int(10) unsigned default NULL,
  `related_id` int(10) unsigned NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_available` datetime NOT NULL,
  `date_expire` datetime default NULL,
  `priority` tinyint(3) NOT NULL default '0',
  `dequeue_count` tinyint(3) NOT NULL default '0',
  PRIMARY KEY  (`queue_entry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4618 DEFAULT CHARSET=latin1;

CREATE TABLE `n_queue_group` (
  `queue_group_id` int(10) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned NOT NULL,
  `name_short` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  PRIMARY KEY  (`queue_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

CREATE TABLE `n_queue_history` (
  `queue_history_id` int(10) unsigned NOT NULL auto_increment,
  `date_queued` datetime NOT NULL,
  `date_removed` datetime NOT NULL,
  `queue_entry_id` int(10) unsigned NOT NULL,
  `queue_id` int(10) unsigned NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  `original_agent_id` int(10) unsigned NOT NULL,
  `removal_agent_id` int(10) unsigned NOT NULL,
  `dequeue_count` int(10) unsigned NOT NULL default '0',
  `removal_reason` enum('queue','group','manual','expired') NOT NULL,
  PRIMARY KEY  (`queue_history_id`),
  KEY `idx_removal_agent_id` (`removal_agent_id`),
  KEY `idx_date_removed` (`date_removed`)
) ENGINE=InnoDB AUTO_INCREMENT=23416 DEFAULT CHARSET=latin1;

CREATE TABLE `n_time_sensitive_queue_entry` (
  `queue_entry_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `agent_id` int(10) unsigned default NULL,
  `related_id` int(10) unsigned NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_available` datetime NOT NULL,
  `date_expire` datetime default NULL,
  `priority` tinyint(3) NOT NULL default '100',
  `dequeue_count` tinyint(3) NOT NULL default '0',
  `start_hour` tinyint(3) NOT NULL,
  `end_hour` tinyint(3) NOT NULL,
  PRIMARY KEY  (`queue_entry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6019 DEFAULT CHARSET=latin1;

CREATE TABLE `resolve_ar_report` (
  `date_created` date NOT NULL,
  `company_name_short` varchar(5) default NULL,
  `application_id` varchar(20) NOT NULL default '',
  `name_last` varchar(50) default NULL,
  `name_first` varchar(50) default NULL,
  `status` varchar(40) default NULL,
  `prev_status` varchar(40) default NULL,
  `fund_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` decimal(2,0) default NULL,
  `fund_age` decimal(4,0) default NULL,
  `collection_age` decimal(4,0) default NULL,
  `status_Age` decimal(4,0) default NULL,
  `payoff_amt` decimal(9,2) default NULL,
  `principal_pending` decimal(9,2) default NULL,
  `principal_fail` decimal(9,2) default NULL,
  `service_charge_pending` decimal(9,2) default NULL,
  `service_charge_fail` decimal(9,2) default NULL,
  `principal_total` decimal(9,2) default NULL,
  `fees_total` decimal(9,2) default NULL,
  `service_charge_total` decimal(9,2) default NULL,
  `fees_pending` decimal(9,2) default NULL,
  `fees_fail` decimal(9,2) default NULL,
  `nsf_ratio` decimal(9,2) default NULL,
  PRIMARY KEY  (`date_created`,`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

