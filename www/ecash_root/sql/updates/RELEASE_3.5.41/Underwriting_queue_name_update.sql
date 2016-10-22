UPDATE current_queue_status SET queue_name = 'Underwriting' WHERE queue_name LIKE "%Underwriting%";
UPDATE queue SET queue_name = 'Underwriting' WHERE queue_name LIKE "%Underwriting%";
UPDATE queue_history SET queue_name = 'Underwriting' WHERE queue_name LIKE "%Underwriting%";

