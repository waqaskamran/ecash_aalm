--adds moneygram fee to payday loans
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'moneygram_fee';
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id and parm_name = 'moneygram_fee';
SET @default_value := '10';

INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rs.rule_set_id, @rule_component_id, @rule_component_parm_id, @default_value
FROM rule_set AS rs
JOIN loan_type AS lt ON (lt.loan_type_id = rs.loan_type_id)
WHERE lt.name LIKE '%Payday%';

INSERT INTO rule_set_component
SELECT NOW(), NOW(), 'active', rs.rule_set_id, @rule_component_id, 30
FROM rule_set AS rs
JOIN loan_type AS lt ON (lt.loan_type_id = rs.loan_type_id)
WHERE lt.name LIKE '%Payday%';