CREATE TABLE IF NOT EXISTS `reports_paydate_models`
    ( `internal_value` enum("dw","dwpd","dmdm","wwdw","dm","dwdm","wdw") NOT NULL DEFAULT "dw"
    , `external_value` VARCHAR(255) NOT NULL DEFAULT ""
    , PRIMARY KEY (`internal_value`)
    , KEY (`external_value`)
    ) ENGINE=InnoDB COMMENT="Stores static human-readable names"
    ;
INSERT IGNORE INTO `reports_paydate_models` VALUES
    ("dw"  ,"Weekly on day"                   ),
    ("dwpd","Every other week on day"         ),
    ("dmdm","Twice per month on days"         ),
    ("wwdw","Twice per month on week and day" ),
    ("dm"  ,"Monthly on day"                  ),
    ("dwdm","Monthly on week and day"         ),
    ("wdw" ,"Monthly on day of week after day");
