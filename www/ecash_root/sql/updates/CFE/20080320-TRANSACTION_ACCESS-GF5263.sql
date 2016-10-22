SELECT @ecash :=  section_id FROM section where name = 'ecash3_0';

SELECT @collections := section_id FROM section WHERE name = 'collections' AND section_parent_id = @ecash;
SELECT @servicing := section_id FROM section WHERE name = 'loan_servicing' AND section_parent_id = @ecash;

SELECT @customer_service := section_id from section where section_parent_id = @servicing AND name = 'customer_service';
SELECT @internal := section_id FROM section WHERE section_parent_id = @collections AND name = 'internal';
SELECT @account_mgmt := section_id from section where section_parent_id = @servicing AND name = 'account_mgmt';

-- Insert Complete option
INSERT INTO section 
    SELECT 
        NOW(), 
        NOW(), 
        'active', 
        system_id, 
        NULL, 
        'complete_transaction', 
        'Set to Complete', 
        section_id, 
        5, 
        level+1,
        read_only_option,
        can_have_queues
    FROM
        section 
    WHERE 
        name = 'transactions_overview' 
    AND 
        section_parent_id IN 
            (SELECT section_id from section where section_parent_id IN (@internal, @account_mgmt , @customer_service) AND name = 'transactions');

-- Insert Failed Option
INSERT INTO section 
    SELECT 
        NOW(), 
        NOW(), 
        'active', 
        system_id, 
        NULL, 
        'fail_transaction', 
        'Set to Failed', 
        section_id, 
        10, 
        level+1,
        read_only_option,
        can_have_queues
    FROM
        section 
    WHERE 
        name = 'transactions_overview' 
    AND 
        section_parent_id IN 
            (SELECT section_id from section where section_parent_id IN (@internal, @account_mgmt , @customer_service) AND name = 'transactions');

-- Insert Remove Scheduled application
INSERT INTO section 
    SELECT 
        NOW(), 
        NOW(), 
        'active', 
        system_id, 
        NULL, 
        'delete_transaction', 
        'Delete scheduled transaction', 
        section_id, 
        15, 
        level+1,
        read_only_option,
        can_have_queues
    FROM
        section 
    WHERE 
        name = 'transactions_overview' 
    AND 
        section_parent_id IN 
            (SELECT section_id from section where section_parent_id IN (@internal, @account_mgmt , @customer_service) AND name = 'transactions');

-- Create acl.  If they have access to transactions overview, they retain access to modify transactions.
INSERT INTO acl
    SELECT NOW(), NOW(), 'active', acl.company_id, acl.access_group_id, s.section_id, acl.acl_mask, acl.read_only FROM acl 
    RIGHT join section s ON s.section_parent_id = acl.section_id
    WHERE s.name IN ('delete_transaction', 'complete_transaction', 'fail_transaction')
    ON DUPLICATE KEY UPDATE date_modified = NOW();

