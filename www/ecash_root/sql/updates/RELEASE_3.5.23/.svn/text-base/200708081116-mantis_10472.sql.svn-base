-- moves watch under fraud

update section set level=3, 
section_parent_id = (select * from (select section_id from section where name='fraud') as a) where name='watch'
; 