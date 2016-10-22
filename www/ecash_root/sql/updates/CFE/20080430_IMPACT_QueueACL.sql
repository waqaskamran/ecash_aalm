SELECT @script_start_time := now();

INSERT INTO section
SELECT
	now(),
	now(),
	'active',
	3,
	'',
	if(locate('_queue',q.name_short), q.name_short, concat(q.name_short, '_queue')),
	if(locate(' Queue',q.name), q.name, concat(q.name, ' Queue')),
	q.section_id,
	((select max(sequence_no) from section where section_parent_id=q.section_id) + 5 * (select (count(*) + 1) as count from n_queue where section_id = q.section_id and queue_id < q.queue_id and queue_id IN (SELECT MIN(queue_id) FROM n_queue GROUP BY name_short))) as sequence,
	p.level + 1,
	0,
	1
FROM n_queue q
INNER JOIN section p on p.section_id=q.section_id
LEFT JOIN section s on s.name=if(locate('_queue',q.name_short), q.name_short, concat(q.name_short, '_queue'))
WHERE s.section_id IS NULL
AND q.queue_id IN (SELECT MIN(queue_id) FROM n_queue GROUP BY name_short);

INSERT INTO acl
SELECT
	now(),
	now(),
	'active',
	g.company_id,
	g.access_group_id,
	s.section_id,
	null,
	0
FROM 
	acl a
	inner join access_group g on g.access_group_id=a.access_group_id
	inner join section s on s.section_parent_id=a.section_id
WHERE
	s.name like '%_queue'
	AND s.date_created >= @script_start_time;
	
UPDATE n_queue q
INNER JOIN section s on s.name=if(locate('_queue',q.name_short), q.name_short, concat(q.name_short, '_queue'))
SET q.section_id=s.section_id;