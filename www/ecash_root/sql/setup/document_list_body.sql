CREATE TABLE IF NOT EXISTS `document_list_body` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` int(10) unsigned NOT NULL default '0',
  `document_list_id` int(10) unsigned NOT NULL default '0',
  `document_list_body_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`document_list_id`,`document_list_body_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A way of linking a document to a body doc'
