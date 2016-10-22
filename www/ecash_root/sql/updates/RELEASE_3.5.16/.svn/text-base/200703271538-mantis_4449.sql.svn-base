/* [RFC:1122] Create the incoming_email_queue table */
CREATE TABLE `incoming_email_queue` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_follow_up` timestamp NOT NULL default '0000-00-00 00:00:00',
  `archive_id` int(10) unsigned NOT NULL default '0',
  `company_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(10) unsigned NOT NULL default '0',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `is_failed` enum('no','yes') NOT NULL default 'no',
  `queue_name` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`archive_id`),
  KEY `idx_date_modified_queue_name` (`date_modified`,`company_id`,`queue_name`,`date_follow_up`),
  KEY `idx_date_followup_queue_name` (`date_follow_up`,`company_id`,`queue_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* [RFC:1142] Create the unassociated_incoming_email table */
CREATE TABLE `unassociated_incoming_email` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `archive_id` int(10) unsigned NOT NULL default '0',
  `company_id` int(10) unsigned NOT NULL default '0',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `is_failed` enum('no','yes') NOT NULL default 'no',
  `queue_name` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`archive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* [RFC:1138] Create the email_response_footer table */
CREATE TABLE `email_response_footer` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `email_response_footer_id` int(10) unsigned NOT NULL auto_increment,
  `email_incoming` varchar(100) NOT NULL default '',
  `email_replyto` varchar(100) NOT NULL default '',
  `footer_text` text NOT NULL,
  `company_id` int(10) unsigned NOT NULL default '0',
  `queue_name` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`email_response_footer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* [RFC:1142] Create the email_queue_report */
CREATE TABLE `email_queue_report` (
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `email_queue_report_id` int(10) unsigned NOT NULL auto_increment,
  `agent_id` int(10) unsigned NOT NULL default '0',
  `company_id` int(10) unsigned NOT NULL default '0',
  `archive_id` int(10) unsigned NOT NULL default '0',
  `queue_name` varchar(20) NOT NULL default '',
  `action` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`email_queue_report_id`),
  KEY `idx_date_agent_action` (`date_created`,`agent_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*	Set 'EMAIL_RESPONSE_DOCUMENT' to 'Generic Message' in company_property table */
INSERT INTO company_property (company_id, property, value) 
VALUES (3, 'EMAIL_RESPONSE_DOCUMENT', 'Generic Message');

/*	Set 'DEFAULT_INCOMING_EMAIL_QUEUE' to 'servicing' in company_property table */
INSERT INTO company_property (company_id, property, value) 
VALUES (3, 'DEFAULT_INCOMING_EMAIL_QUEUE', 'servicing');

/*	Set 'EMAIL_RECEIVE_DOCUMENT' to 'Other (Archive ID)' in company_property table */
INSERT INTO company_property (company_id, property, value) 
VALUES (3, 'EMAIL_RECEIVE_DOCUMENT', 'Other (Archive ID)');

/* Add Email Queue Section to Loan Servicing */
INSERT INTO section (date_created, active_status, system_id,name,description,
 section_parent_id,level,sequence_no,read_only_option)
VALUES 
 (NOW(), 'active', 3, 'servicing_email_queue', 'Servicing Email Queue', 1106, 3, 25, 0);

/* Add Email Queue Section to Collections */
INSERT INTO section (date_created, active_status, system_id,name,description,
 section_parent_id,level,sequence_no,read_only_option) 
VALUES 
 (NOW(), 'active', 3, 'collections_email_queue', 'Collections Email Queue', 1104, 3, 25, 0);

/* Set Generic Message to 'other' */
INSERT INTO document_list
(date_created, active_status, company_id, name, name_short, required, 
 esig_capable, system_id, send_method, document_api, only_receivable) 
VALUES
 (NOW(), 'active', 3, 'Generic Message', 'other_generic_message', 'no',
  'no', 3, 'email', 'condor', 'no');
