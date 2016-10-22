-- NOTE: This was reworked to be somewhat more efficient.  Do not add
-- an "ORDER BY" clause, or else this will always use a TEMP TABLE
-- instead of merging the query.  Ideally very few queries should use
-- this, as it effectively adds six joins to any query (five internal
-- plus the join *to* the view).

DROP VIEW IF EXISTS application_status_flat;
CREATE
    DEFINER=CURRENT_USER
    SQL SECURITY DEFINER
    VIEW
        application_status_flat
    AS
        SELECT
            as0.application_status_id,
            as0.name_short as level0,
            as0.name  as level0_name,
            IF(    1 = 0
                OR (NOT(ISNULL(as0.active_status)) AND as0.active_status = 'inactive')
                OR (NOT(ISNULL(as1.active_status)) AND as1.active_status = 'inactive')
                OR (NOT(ISNULL(as2.active_status)) AND as2.active_status = 'inactive')
                OR (NOT(ISNULL(as3.active_status)) AND as3.active_status = 'inactive')
                OR (NOT(ISNULL(as4.active_status)) AND as4.active_status = 'inactive')
                OR (NOT(ISNULL(as5.active_status)) AND as5.active_status = 'inactive')
                ,
                'inactive'
                ,
                'active'
            ) AS active_status,
            as1.name_short as level1,
            as2.name_short as level2,
            as3.name_short as level3,
            as4.name_short as level4,
            as5.name_short as level5
        FROM application_status as0
        LEFT JOIN application_status as1 ON (as0.application_status_parent_id=as1.application_status_id)
        LEFT JOIN application_status as2 ON (as1.application_status_parent_id=as2.application_status_id)
        LEFT JOIN application_status as3 ON (as2.application_status_parent_id=as3.application_status_id)
        LEFT JOIN application_status as4 ON (as3.application_status_parent_id=as4.application_status_id)
        LEFT JOIN application_status as5 ON (as4.application_status_parent_id=as5.application_status_id)
    ;
