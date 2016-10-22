

CREATE TABLE application_field_attribute (
	application_field_attribute_id int(10) NOT NULL auto_increment,
	field_name varchar(100) NOT NULL,
    field_description varchar(255) NULL,
	PRIMARY KEY (application_field_attribute_id),
	UNIQUE KEY field_idx (field_name)
);

insert into application_field_attribute
(field_name)
values
('bad_info'),
('do_not_contact'),
('best_contact'),
('do_not_market'),
('do_not_loan'),
('high_risk'),
('fraud');


CREATE TABLE application_field (
	application_field_id int(10) NOT NULL auto_increment,
	date_modified timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	date_created timestamp NOT NULL,
	company_id int(10) unsigned NOT NULL,
	table_name varchar(100) NOT NULL,
	column_name varchar(100) NOT NULL,
	table_row_id int(10) unsigned NOT NULL,
	application_field_attribute_id int(10) NOT NULL,
	PRIMARY KEY (application_field_id),
	UNIQUE KEY uniq_attribute_idx (table_name,table_row_id,application_field_attribute_id,column_name) 
);

