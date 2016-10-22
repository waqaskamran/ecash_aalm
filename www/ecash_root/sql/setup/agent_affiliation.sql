CREATE TABLE IF NOT EXISTS `agent_affiliation` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `date_expiration` timestamp NULL default NULL COMMENT "When this person's control expires, NULL = never",
  `company_id` int(10) unsigned NOT NULL COMMENT "Foreign Key, Convenience",
  `application_id` int(10) unsigned NOT NULL COMMENT "Foreign Key, Primary interface point",
  `agent_affiliation_id` int(10) unsigned NOT NULL COMMENT "Primary Key",
  `affiliation_area` enum('collections','conversion','watch','manual') default NULL COMMENT "Deprecated: Only applies to collections right now",
  `affiliation_type` enum('owner','manager','creator') default NULL COMMENT "Deprecated: Only applies to 'owner' right now",
  `agent_id` int(10) unsigned NOT NULL COMMENT "Foreign Key, Primary interface point",
  PRIMARY KEY  (`agent_affiliation_id`),
  KEY `idx_affil_app_area_type_agent` (`application_id`,`affiliation_area`,`affiliation_type`,`agent_id`)
) ENGINE=InnoDB COMMENT="Maps agents to applications for ownership";
