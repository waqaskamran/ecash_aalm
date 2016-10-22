SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'loan_cap';

SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = '11';

update rule_component_parm set value_max=5000 where rule_component_id = @rule_component_id and parm_name = '11';

update rule_set_component_parm_value set parm_value='5000' where
rule_component_id= @rule_component_id and rule_component_parm_id= @rule_component_parm_id 
and rule_set_id in (select rule_set_id from rule_set where name like '%Title%');

