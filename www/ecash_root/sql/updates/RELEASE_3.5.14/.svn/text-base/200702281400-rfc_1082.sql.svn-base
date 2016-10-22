-- add 2 tables for 'clickable phone links' project: for phone contacts and for calling history

create table application_contact (
	date_created timestamp not null default CURRENT_TIMESTAMP,
	application_contact_id int(10) unsigned not null,
	application_id int(11) unsigned not null, 
	application_field_attribute_id int(10) unsigned not null,
	type varchar(16) not null,
	category varchar(48) not null,
	value varchar(128) not null,
	notes varchar(255) not null,
	primary key (application_contact_id),
	index (application_id),
	index (application_field_attribute_id),
	index (type, category)
);
	
create table pbx_history (
	date_created timestamp not null default CURRENT_TIMESTAMP,
	pbx_history_id int(10) unsigned not null auto_increment,
	company_id int(10) unsigned not null,
	application_id int(11) unsigned not null,
	agent_id int(10) unsigned not null, 
	application_contact_id int(10) unsigned not null,
	phone varchar(10) not null default '',
	pbx_event varchar(32)  not null default 'unknown',
	result text
	primary key (pbx_history_id),
	index (application_id),
	index (company_id),
	index (agent_id),
	index (application_contact_id)
	index (pbx_event),
	index (phone(3))
);