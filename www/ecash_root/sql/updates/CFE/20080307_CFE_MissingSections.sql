/* This has been appl:ied to ldb_impact on monster.tss:3318 
 * I realize this is ridiculous, but the only way not to use hardcoded values would be
 * only if we rebuilt the tree with a query, there's a ton of stuff with 'application'
 * as a name.
 * FOR IMPACT ONLY 
 */

/* SECTION [HAS HARDCODED VALUES VERIFY ID='1220' = 'application'] */
INSERT INTO section (active_status, system_id, name, description, section_parent_id, sequence_no, level, read_only_option, can_have_queues)
	VALUES('active',                      /* active_status     */
           '3',                           /* system_id         */
           'payment_arrangement_history', /* name              */
           'Payment Arrangement History', /* description       */
           1220,                          /* section_parent_id */
           15,                            /* sequence_no       */
           5,                             /* level             */
           1,                             /* read_only_option  */
           0);                            /* can_have_queues   */

/* ACL (This is ridiculous) */
/* Company #1 */
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','255',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','256',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','257',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','258',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','259',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','261',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','265',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','267',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','268',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','301',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','1','303',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);

/* Company #2 */
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','280',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','281',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','282',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','283',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','284',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','286',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','290',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','292',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','293',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','296',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','300',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','2','302',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);

/* Company #3 */
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','102',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','182',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','183',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','184',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','185',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','187',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','188',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','189',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','190',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','294',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','3','298',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);

/* Company #4 */
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','205',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','206',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','207',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','208',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','210',(SELECT section_id FROM section WHE
RE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','211',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','215',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','217',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','218',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','297',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
INSERT INTO acl (active_status, company_id, access_group_id, section_id, acl_mask, read_only) VALUES('active','4','299',(SELECT section_id FROM section WHERE name='payment_arrangement_history' AND system_id='3'),NULL,0);
