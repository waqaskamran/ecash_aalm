/* This can be safely ran against all databases multiple times if desired. */

SELECT @ecash :=  section_id FROM section where name = 'ecash3_0';

SELECT @collections := section_id FROM section WHERE name = 'collections' AND section_parent_id = @ecash;
SELECT @servicing := section_id FROM section WHERE name = 'loan_servicing' AND section_parent_id = @ecash;

SELECT @customer_service := section_id from section where section_parent_id = @servicing AND name = 'customer_service';
SELECT @internal := section_id FROM section WHERE section_parent_id = @collections AND name = 'internal';
SELECT @account_mgmt := section_id from section where section_parent_id = @servicing AND name = 'account_mgmt';

-- Insert Remove Generated Transaction
INSERT INTO section 
    SELECT 
        NOW(), 
        NOW(), 
        'active', 
        system_id, 
        NULL, 
        'delete_generated_transaction', 
        'Delete generated transaction', 
        section_id, 
        20, 
        level+1,
        read_only_option,
        can_have_queues
    FROM
        section 
    WHERE 
        name = 'transactions_overview' 
    AND 
        section_parent_id IN 
            (SELECT section_id from section where section_parent_id IN (@internal, @account_mgmt , @customer_service) AND name = 'transactions')
ON DUPLICATE KEY UPDATE date_modified = NOW();

-- Create acl.  Only give this permission to "All Access" groups 
INSERT INTO acl
	SELECT NOW(), NOW(), 'active', ag.company_id, ag.access_group_id, section.section_id, NULL, NULL  FROM access_group ag LEFT OUTER JOIN section ON (section.name='delete_generated_transaction') WHERE ag.name LIKE '%All Access'
ON DUPLICATE KEY UPDATE date_modified = NOW();
