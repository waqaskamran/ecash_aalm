alter table application modify column application_id int(10) unsigned NOT NULL auto_increment;

create table billing_date
(
	billing_date_id int unsigned auto_increment primary key,
	approved_date date not null,
	billing_date tinyint unsigned not null,
	unique key date_idx (approved_date, billing_date)
);
