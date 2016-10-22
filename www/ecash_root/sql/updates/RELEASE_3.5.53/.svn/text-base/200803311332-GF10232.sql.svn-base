--on dev and rc, creates rule to determine to use action date or effective date when setting payment arrangements
insert into rule_component (name,name_short) values ('Display action or effective date','display_action_effective');

SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'display_action_effective';

insert into rule_component_parm (rule_component_id,parm_name,display_name,parm_type,user_configurable,input_type,presentation_type,enum_values,description, value_label, value_min, value_max, value_increment, length_min, length_max) values (@rule_component_id,'display_action_effective','Display action or effective date','string','yes','select','text','effective,action','Determine which date is chosen when making arrangements action or effective ', '',0,100,5,1,3);
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = 'display_action_effective';


insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,48 from rule_set where name like '%Rule%';


INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, 'effective'
from rule_set where name like '%Rule%';
