--Already on RC and dev

insert into rule_component (name,name_short) values ('Minimum Vehicle Year','minimum_vehicle_year');

SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'minimum_vehicle_year';

insert into rule_component_parm (rule_component_id,parm_name,display_name,parm_type,user_configurable,input_type,presentation_type) values (@rule_component_id,'minimum_vehicle_year','Minimum Vehicle Year','numeric','yes','text','scalar');
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = 'minimum_vehicle_year';

insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,36 from rule_set where name like '%Title%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, '1998'
from rule_set where name like '%Title%';