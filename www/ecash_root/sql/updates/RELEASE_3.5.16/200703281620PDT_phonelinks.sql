CREATE TABLE `active_pbx_contacts` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL default '0000-00-00 00:00:00',
  `pbx_dialed` varchar(64) NOT NULL,
  `application_contact_id` int(10) unsigned NOT NULL,
  `agent_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`application_contact_id`,`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `agent_pbx_map` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `pbx_extension` varchar(32) NOT NULL,
  `agent_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pbx_extension`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;