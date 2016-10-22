-- Do a mass replace on any description containing the misspelled word "recieves"
UPDATE `rule_component_parm`
SET `description` = REPLACE(`description`, 'recieve', 'receive')
WHERE `description` LIKE '%recieve%';
