/* THIS IS ONLY TO BE RAN ON AALM LIVE, IT HAS ALREADY BEEN APPLIED TO Local and RC */
/* APPLY THIS AFTER 20080530_AALM_13626_AddMissingSections.php */
INSERT INTO acl
    SELECT NOW(), NOW(), 'active', acl.company_id, acl.access_group_id, s.section_id, acl.acl_mask, acl.read_only FROM acl
    RIGHT join section s ON s.section_parent_id = acl.section_id
    WHERE s.name IN ('send_documents', 'receive_documents', 'esig_documents', 'packaged_docs')
    ON DUPLICATE KEY UPDATE date_modified = NOW();

