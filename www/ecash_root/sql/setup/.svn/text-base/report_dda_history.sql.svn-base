DROP VIEW IF EXISTS `report_dda_history`;
CREATE
    ALGORITHM=MERGE
    DEFINER=CURRENT_USER
    SQL SECURITY DEFINER
    VIEW
        `report_dda_history`
    AS
        SELECT
                IFNULL(`application`.`application_status_id`,0) AS `_application_status_id`
            ,
                `reports_dda_history`.`dda_history__id` AS `entry_dda_history__id`
            ,
                `reports_dda_history`.`date` AS `entry_date`
            ,
                `reports_dda_history`.`application_id` AS `entry_application_id`
            ,
                `reports_dda_history`.`event_schedule_id` AS `entry_schedule_id`
            ,
                `reports_dda_history`.`description` AS `entry_description`
            ,
                `agent`.`name_last` AS `agent_name_last`
            ,
                `agent`.`name_first` AS `agent_name_first`
            ,
                `agent`.`name_middle` AS `agent_name_middle`
            ,
                CONCAT( ""
                    , `agent`.`name_first`
                    , IF(ISNULL(`agent`.`name_middle`),"",CONCAT(" ",`agent`.`name_middle`))
                    , " "
                    , `agent`.`name_last`
                    ) AS `agent_name_forward`
            ,
                CONCAT( ""
                    , `agent`.`name_last`
                    , ", "
                    , `agent`.`name_first`
                    , IF(ISNULL(`agent`.`name_middle`),"",CONCAT(" ",`agent`.`name_middle`))
                    ) AS `agent_name_reverse`
            ,
                `agent`.`email` AS `agent_email`
            ,
                `agent`.`phone` AS `agent_phone`
            ,
                `agent`.`login` AS `agent_login`
        FROM `reports_dda_history` AS `reports_dda_history`
        LEFT JOIN `agent` AS `agent` ON ( 1 = 1
                AND `reports_dda_history`.`agent_id` = `agent`.`agent_id`
                )
        LEFT JOIN `application` AS `application` ON ( 1 = 1
                AND `reports_dda_history`.`application_id` = `application`.`application_id`
                )
    ;
