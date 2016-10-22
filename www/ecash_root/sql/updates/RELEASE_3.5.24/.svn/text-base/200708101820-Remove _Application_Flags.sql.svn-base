-- Remove Application Flags from Fraud Module / Watch Mode
select @watch_section  := section_id from section where name = 'watch';
select @application_flag := section_id from section where section_parent_id = @watch_section and name = 'application_flag';
delete from section where section_parent_id = @application_flag;
delete from section where section_id = @application_flag;

-- Remove Application Flags from Reporting / Watch Mode
select @reporting_section  := section_id from section where name = 'reporting';
select @application_flag := section_id from section where section_parent_id = @reporting_section and name = 'application_flag';
delete from section where section_parent_id = @application_flag;
delete from section where section_id = @application_flag;
