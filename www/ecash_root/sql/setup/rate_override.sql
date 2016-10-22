/**
 * The rate override needs to become independent of the application table, so
 * to do that we're creating a new rate_override table.
 */
CREATE TABLE IF NOT EXISTS `rate_override` (
    `application_id` int(10) unsigned NOT NULL default '0',
    `rate_override` decimal(7,4) unsigned default NULL,
    PRIMARY KEY  (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT IGNORE INTO rate_override
    SELECT  application_id,
            rate_override
    FROM application
    WHERE rate_override IS NOT NULL;
