--- GF: 3514 -- Add documents to the Document Process Table
select @agree_status := application_status_id from application_status_flat where level0 = 'agree' and level1 = 'prospect';
select @confirm_status := application_status_id from application_status_flat where level0 = 'queued' and level1 = 'verification';

insert into document_process
select now(), now(), document_list_id, @confirm_status, @agree_status 
from document_list
where name_short like 'application%' or name_short like '%agreement%'
and name_short != 'lease_or_mortgage_agreement'
order by name;

select c.name_short, dl.name, status1.name as new_status, status2.name as current_status
from document_process as dp
left join document_list as dl on (dl.document_list_id = dp.document_list_id)
join application_status as status1 on (status1.application_status_id = dp.application_status_id)
join application_status as status2 on (status2.application_status_id = dp.current_application_status_id)
join company as c on (c.company_id = dl.company_id);
--- End GF: 3514
