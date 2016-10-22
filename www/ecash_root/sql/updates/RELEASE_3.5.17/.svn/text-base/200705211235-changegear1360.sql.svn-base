alter table agent_pbx_map add column company_id int(10) unsigned not null;

alter table agent_pbx_map drop primary key;

alter table agent_pbx_map add primary key ( pbx_extension, company_id );