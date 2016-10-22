--Business Rules for DataX calls already on RC and DEV


insert into rule_component (name,name_short) values ('IDV_CALL','IDV_CALL');
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'IDV_CALL';

insert into rule_component_parm (rule_component_id,parm_name,display_name,parm_type,user_configurable,input_type,presentation_type) values (@rule_component_id,'IDV_CALL','IDV_CALL','string','no','text','array');
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = 'IDV_CALL';

insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,36 from rule_set where name like '%Payday%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, 'agean-perf'
from rule_set where name like '%Payday%';


insert into rule_set_component select now(),now(),'active',rule_set_id,@rule_component_id ,36 from rule_set where name like '%Title%';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, 'agean-title'
from rule_set where name like '%Title%';
