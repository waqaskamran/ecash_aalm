CREATE TABLE IF NOT EXISTS `ext_collections_batch` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` int(10) unsigned NOT NULL,
  `ext_collections_batch_id` int(10) unsigned NOT NULL,
  `ec_file_outbound` longtext NOT NULL,
  `ec_filename` varchar(50) default NULL,
  `batch_status` enum('created','sent') NOT NULL,
  `ext_collections_co` enum('crsi','pinion','pinion north','other') NOT NULL,
  `item_count` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ext_collections_batch_id`)
);
