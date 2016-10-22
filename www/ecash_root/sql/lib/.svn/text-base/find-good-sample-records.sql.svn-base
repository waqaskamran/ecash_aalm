-- Needs to have one or more pending transaction_register entries
-- Needs to have one or more failed transaction_register entries
-- Needs to have one or more complete transaction_register entries
-- Needs to have one or more scheduled event_schedule entries
-- Needs to have zero event_amount entries

SELECT          a1.application_id       AS  `application_id`
    FROM        application             AS  a1
    JOIN        transaction_register    AS  tr1 ON  (   tr1.application_id      =   a1.application_id
                                                    AND tr1.transaction_status  =   'pending'
                                                    )
    JOIN        transaction_register    AS  tr2 ON  (   tr2.application_id      =   a1.application_id
                                                    AND tr2.transaction_status  =   'complete'
                                                    )
    JOIN        transaction_register    AS  tr3 ON  (   tr3.application_id      =   a1.application_id
                                                    AND tr3.transaction_status  =   'failed'
                                                    )
    JOIN        event_schedule          AS  es1 ON  (   es1.application_id      =   a1.application_id
                                                    AND es1.event_status        =   'scheduled'
                                                    )
    LEFT JOIN   event_amount            AS  ea1 ON  (   ea1.application_id      =   a1.application_id
                                                    )
    WHERE       ea1.application_id      IS  NULL
    LIMIT       1
    ;
