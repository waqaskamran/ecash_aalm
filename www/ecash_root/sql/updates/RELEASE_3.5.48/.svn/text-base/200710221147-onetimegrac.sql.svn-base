--Business Rules for one time arrangement grace period calls already on RC and DEV


insert into rule_component (name,name_short) values ('One Time Arrangement grace period','one_time_arrangement_grace');
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'one_time_arrangement_grace';

insert into rule_component_parm (rule_component_id,parm_name,display_name,parm_type,user_configurable,input_type,presentation_type,value_label) values (@rule_component_id,'one_time_arrangement_grace','One Time Arrangement grace period','integer','yes','text','scalar','Days Forward');
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = 'one_time_arrangement_grace';

insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,36 from rule_set where name like '%Payday%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, '7'
from rule_set where name like '%Payday%';


insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,36 from rule_set where name like '%Title%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, '7'
from rule_set where name like '%Title%';