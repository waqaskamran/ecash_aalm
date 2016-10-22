-- Adds columns necessary for the new agent affiliation changes and rebuilds 
-- the indexes in a more current manner.

ALTER TABLE agent_affiliation
  ADD COLUMN date_available timestamp NULL default NULL,
  ADD COLUMN date_expiration_actual timestamp NULL default NULL,
  ADD COLUMN affiliation_status enum('active', 'expired'),
  DROP KEY idx_affil_app_area_type_agent,
  DROP KEY agent_id,
  DROP KEY date_expiration,
  ADD KEY idx_affil_app_area_type_status_exp (application_id, affiliation_area, affiliation_type, affiliation_status),
  ADD KEY idx_affil_agent_area_type_status (agent_id, affiliation_area, affiliation_type, affiliation_status)
;
