--
-- Add a new queue area for agent affiliations.
-- Essentially a band-aid until we  can reopen the queue recycling code.
--

ALTER TABLE agent_affilitaion
  MODIFY COLUMN affiliation_area enum('collections','conversion','watch','manual', 'queue');