alter table current_queue_status add column sortable varchar(255);
update current_queue_status join queue on (current_queue_status.application_id = queue.key_value and current_queue_status.queue_name = queue.queue_name) set current_queue_status.sortable = queue.sortable;

