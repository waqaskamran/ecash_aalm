/* 
This has to be run for each of the sections listed below.  it will capture any "Documents" without subsections

esig_documents ESig Documents
packaged_docs Packaged Documents
receive_documents Receive Documents
send_documents Send Documents
 */

/* Lookup the sequence, level, read only, queues options and replace the values into the query below */

SELECT 
section_child.sequence_no,
section_child.level,
section_child.read_only_option,
section_child.can_have_queues
FROM section
LEFT JOIN section AS section_child ON section_child.section_parent_id = section.section_id
AND section_child.name = 'send_documents'
WHERE section.description = 'Documents'
AND section_child.name IS NOT NULL
ORDER BY `section_child`.`name` ASC
LIMIT 1;

/* Insert the section */

INSERT INTO section
SELECT NOW( ) , NOW( ) , 'active', (
SELECT system_id
FROM system
WHERE name_short = 'ecash3_0'
), NULL , 'send_documents', 'Send Documents', section.section_id, 
5,
5,
1,
0
FROM section
LEFT JOIN section AS section_child ON section_child.section_parent_id = section.section_id
AND section_child.name = 'send_documents'
WHERE section.description = 'Documents'
AND section_child.name IS NULL
ORDER BY `section_child`.`name` ASC;
