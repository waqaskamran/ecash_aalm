--insert minimum loan amount business rule on dev,rc and doc

insert into rule_component (name,name_short) values ('Minimum Loan Amount','minimum_loan_amount');

SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'minimum_loan_amount';

insert into rule_component_parm (rule_component_id,parm_name,display_name,parm_type,user_configurable,input_type,presentation_type) values (@rule_component_id,'min_react','Minimum React','numeric','yes','text','scalar');
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = 'min_react';


insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,36 from rule_set where name like '%Payday%';
insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,36 from rule_set where name like '%Title%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, '100'
from rule_set where name like '%Payday%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, '100'
from rule_set where name like '%Title%';


insert into rule_component_parm (rule_component_id,parm_name,display_name,parm_type,user_configurable,input_type,presentation_type) values (@rule_component_id,'min_non_react','Minimum Non React','numeric','yes','text','scalar');
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = 'min_non_react';


INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, '150'
from rule_set where name like '%Payday%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, '150'
from rule_set where name like '%Title%';