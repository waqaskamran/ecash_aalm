-- Modifications to current_queue_status table for new recycling per Mantis 8986
ALTER TABLE current_queue_status
  ADD COLUMN `date_to_recycle` timestamp NOT NULL default '0000-00-00 00:00:00',
  ADD KEY `idx_queue_recycle_time` (`queue_name`,`date_to_recycle`)
;