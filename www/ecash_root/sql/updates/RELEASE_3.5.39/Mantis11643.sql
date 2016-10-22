## Adding loan type abbreviations to use in interface

ALTER TABLE loan_type ADD COLUMN abbreviation varchar(2) NULL;

UPDATE loan_type SET abbreviation = 'PD' WHERE name_short LIKE "%payday%";
UPDATE loan_type SET abbreviation = 'AT' WHERE name_short LIKE "%title%";


