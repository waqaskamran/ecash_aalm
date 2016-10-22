--on RC, DEV, DOC

insert into section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) values (now(),now(),'active',3,'next_payment_arrangement','Next Payment Arrangement',1169,5,6,1);
insert into section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) values (now(),now(),'active',3,'next_payment_arrangement','Next Payment Arrangement',1185,5,6,1);
insert into section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) values (now(),now(),'active',3,'next_payment_arrangement','Next Payment Arrangement',1204,5,6,1);
insert into section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) values (now(),now(),'active',3,'next_payment_arrangement','Next Payment Arrangement',1224,5,6,1);
insert into section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option) values (now(),now(),'active',3,'next_payment_arrangement','Next Payment Arrangement',1240,5,6,1);

select  @section := section_id from section where name = 'next_payment_arrangement' and section_parent_id = 1169;
insert ignore into acl select distinct now(),now(),'active',company_id,access_group_id,@section,'',0 from acl;

select  @section := section_id from section where name = 'next_payment_arrangement' and section_parent_id = 1185;
insert ignore into acl select distinct now(),now(),'active',company_id,access_group_id,@section,'',0 from acl;

select  @section := section_id from section where name = 'next_payment_arrangement' and section_parent_id = 1204;
insert ignore into acl select distinct now(),now(),'active',company_id,access_group_id,@section,'',0 from acl;

select  @section := section_id from section where name = 'next_payment_arrangement' and section_parent_id = 1224;
insert ignore into acl select distinct now(),now(),'active',company_id,access_group_id,@section,'',0 from acl;

select  @section := section_id from section where name = 'next_payment_arrangement' and section_parent_id = 1240;
insert ignore into acl select distinct now(),now(),'active',company_id,access_group_id,@section,'',0 from acl;
