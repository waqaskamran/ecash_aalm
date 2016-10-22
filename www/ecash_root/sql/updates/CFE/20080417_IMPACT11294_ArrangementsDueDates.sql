--these are the non-ach event types that have payment arrangements where the date_effective doesn't equal the date_event
--this should only be run for impact, on db101 it took 11 second to complete
update event_schedule set date_effective=date_event where event_type_id in (10,17,18,20,43,107,139) and context='arrangement' and event_status <> 'registered'