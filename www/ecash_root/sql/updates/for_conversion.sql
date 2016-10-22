-- Create Manager queue status

INSERT INTO application_status
VALUES
(
  NOW(),
  NOW(),
  'active',
  NULL,
  'Conversion Manager Queue',
  'manager_queued',
  104,
  2
);

INSERT INTO application_status
VALUES
(
  NOW(),
  NOW(),
  'active',
  NULL,
  'Conversion Manager Queue',
  'manager_dequeued',
  104,
  2
);

