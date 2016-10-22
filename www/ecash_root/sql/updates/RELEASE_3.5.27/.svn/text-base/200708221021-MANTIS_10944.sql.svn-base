

insert into section (active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) values ('active',3,'skip_trace','Skip Trace',1104,41,3,0);

insert into acl select distinct now(),now(),'active', company_id,access_group_id,(select section_id from section where name= 'skip_trace'),'',0 from acl;
