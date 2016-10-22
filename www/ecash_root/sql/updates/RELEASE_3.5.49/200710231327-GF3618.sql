-- on RC and DEV already

select  @parent_id := section_id from section where name = 'fraud_queue' and section_parent_id = (select section_id from section where name ='fraud');

insert into section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) 
values (now(),now(),'active',3,'documents','Documents',@parent_id,30,4,0);

select  @fraud_id := section_id from section where name = 'documents' and section_parent_id = @parent_id;



select  @parent_id := section_id from section where name = 'high_risk_queue' and section_parent_id = (select section_id from section where name ='fraud');

insert into section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) 
values (now(),now(),'active',3,'documents','Documents',@parent_id,30,4,0);

select  @high_risk_id := section_id from section where name = 'documents' and section_parent_id = @parent_id;



insert into acl select distinct now(),now(),'active',company_id,access_group_id,@fraud_id,'',0 from acl;

insert into acl select distinct now(),now(),'active',company_id,access_group_id,@high_risk_id,'',0 from acl;