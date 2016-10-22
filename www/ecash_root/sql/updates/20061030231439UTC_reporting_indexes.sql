ALTER TABLE `event_amount` DROP INDEX `idx_event_amt_app_esid`, ADD INDEX `event_schedule_id` (`event_schedule_id`) , ADD INDEX `application_id` (`application_id`) ;
