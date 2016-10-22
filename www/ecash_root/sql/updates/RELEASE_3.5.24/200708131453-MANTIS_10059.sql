-- change %s-%s to %s - %s without adding extra spaces to other -

update loan_actions set description = REPLACE(REPLACE(description,'-',' - '),'  -  ',' - ') 
where status='ACTIVE' and description REGEXP '[[:alpha:]][[.-.]][[:alpha:]]' ;
