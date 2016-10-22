-- Add a column to event_schedule that can determine whether or not an event 
-- has been rescheduled via the transaction overview screen.

ALTER TABLE event_schedule
  ADD COLUMN is_shifted tinyint unsigned NOT NULL default '0' AFTER source_id;