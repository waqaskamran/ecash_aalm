--
-- Create a table to track ach return items that do not process.
--

DROP TABLE ach_exception;

CREATE TABLE  `ach_exception` ( 
   `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, 
   `date_created` timestamp NOT NULL default '0000-00-00 00:00:00', 
   `ach_exception_id` int(10) unsigned NOT NULL auto_increment, 
   `return_date` date NOT NULL default '0000-00-00', 
   `recipient_id` int(10) unsigned NOT NULL, 
   `recipient_name` varchar(127) NOT NULL, 
   `ach_id` int(10) unsigned NOT NULL, 
   `debit_amount` decimal(7,2) NOT NULL default '0.00', 
   `credit_amount` decimal(7,2) NOT NULL default '0.00', 
   `reason_code` varchar(20) NOT NULL default '', 
   PRIMARY KEY  (`ach_exception_id`), 
   KEY `idx_return_date` (`return_date`) 
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1;