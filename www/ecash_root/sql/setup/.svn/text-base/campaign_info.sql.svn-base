CREATE TABLE IF NOT EXISTS `campaign_info` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(10) unsigned NOT NULL default '0',
  `campaign_info_id` int(10) unsigned NOT NULL,
  `promo_id` int(10) unsigned NOT NULL default '0',
  `promo_sub_code` varchar(100) default NULL,
  `site_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`campaign_info_id`),
  KEY `idx_campaign_app_date` (`application_id`,`date_created`)
);
