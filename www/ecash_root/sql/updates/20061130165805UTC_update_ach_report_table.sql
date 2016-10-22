ALTER TABLE `ach_report`
    ADD COLUMN `date_request` DATE NOT NULL DEFAULT '0000-00-00' COMMENT 'You should find this as the sdate in the ach_report_request' AFTER `date_created`
    , CHANGE `report_status` `report_status` enum('received','processed','failed','obsoleted') NOT NULL default 'received' COMMENT 'obsoleted = newer record exists (see date_request column)'
    ;
UPDATE `ach_report` SET `date_request` = DATE(`date_created`);
