-- [RFC:1165] Add 'failed' value option to document_event_type column
ALTER TABLE document MODIFY document_event_type enum('sent','received','failed') NOT NULL default 'sent';
