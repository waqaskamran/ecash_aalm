CREATE TABLE IF NOT EXISTS `reports_columns`
    ( `table_name`            VARCHAR(255)       BINARY     NOT NULL   DEFAULT ""
    , `column_name`           VARCHAR(255)       BINARY     NOT NULL   DEFAULT ""
    , `human_readable_name`   VARCHAR(255)                  NOT NULL   DEFAULT ""
    , `drop_down_column`      VARCHAR(255)                      NULL   DEFAULT NULL
    , `special_operation`     VARCHAR(255)       BINARY         NULL   DEFAULT NULL
    , PRIMARY KEY (`table_name`,`column_name`)
    ) ENGINE=InnoDB COMMENT="Stores human readable report column names"
    ;
    
delete from reports_columns
;
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_application_status', 'application_id', 'Application Id', NULL, 'route_to_app_link');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_application_status', 'name_first', 'First name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_application_status', 'name_last', 'Last name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_application_status', 'social_security_number', 'SSN', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_application_status', 'status', 'Status', 'reports_app_status.external_value', 'htmlentities');
// Report Application Details
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_city', 'Customer Address (home): City', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_postal', 'Customer Address (home): Zip Code', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_state', 'Customer Address (home): State abbreviation', 'state.state', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_state_business_allowed', 'Customer Address (home): State allows our business', 'state.business_allowed', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_state_dst', 'Customer Address (home): State honors daylight savings', 'state.use_daylight_saving', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_state_name', 'Customer Address (home): State', 'state.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_state_time_zone', 'Customer Address (home): State time zone', 'time_zone.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_state_time_zone_offset', 'Customer Address (home): State time zone GMT offset', 'time_zone.gmt_offset', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_street', 'Customer Address (home): Street', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_home_unit', 'Customer Address (home): Unit', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_city', 'Customer Address (work): City', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_postal', 'Customer Address (work): Zip Code', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_state', 'Customer Address (work): State abbreviation', 'state.state', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_state_business_allowed', 'Customer Address (work): State allows our business', 'state.business_allowed', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_state_dst', 'Customer Address (work): State honors daylight savings', 'state.use_daylight_saving', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_state_name', 'Customer Address (work): State', 'state.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_state_time_zone', 'Customer Address (work): State time zone', 'time_zone.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_state_time_zone_offset', 'Customer Address (work): State time zone GMT offset', 'time_zone.gmt_offset', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_street_1', 'Customer Address (work): Street 1', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'address_work_street_2', 'Customer Address (work): Street 2', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'agent_email', 'Agent: Email Address', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'agent_login', 'Agent: Login Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'agent_name_first', 'Agent: First Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'agent_name_last', 'Agent: Last Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'agent_name_middle', 'Agent: Middle Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'agent_phone', 'Agent: Phone Number', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'application_id', 'Application: Application Id', NULL, 'route_to_app_link');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'archive_cashline_id', 'Application: Archived Cashline Id', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'bank_aba', 'Customer: Bank Routing Number', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'bank_account', 'Customer: Bank Account Number', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'bank_account_type', 'Customer: Bank Account Type', 'application.bank_account_type', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'bank_name', 'Customer: Bank Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'ca_resident_agree', 'Customer: California applicant agrees (?)', 'demographics.ca_resident_agree', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'call_time_pref', 'Customer: Call time preference', 'application.call_time_pref', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'company_name', 'Application: Company', 'company.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'contact_method_pref', 'Customer: Contact method preference', 'application.contact_method_pref', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'date_created', 'Application: Date Created', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'date_modified', 'Application: Date Modified', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'date_of_birth', 'Customer: Date of birth', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'document_date_created', 'Document: Created Date', '(null)', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'document_event_type', 'Document: Type', 'document.document_event_type', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'document_list_name', 'Document: Name', 'document_list.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'email_address', 'Customer: Email Address', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'email_verified', 'Customer: Email Verified', 'application.email_verified', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'employment_date_hired', 'Customer Employment: Date Hired', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'employment_employer_name', 'Customer Employment: Employer Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'employment_job_title', 'Customer Employment: Job Title', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'employment_supervisor_name', 'Customer Employment: Supervisor Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'employment_tenure_amount', 'Customer Employment: Tenure Amount', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'employment_verified', 'Customer Employment: Verified', 'application.employment_verified', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'employment_work_shift', 'Customer Employment: Work Shift', 'application.shift', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'fund_date', 'Application: Fund Date', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'has_checking', 'Customer: Has checking account', 'demographics.has_checking', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'has_income', 'Customer: Has income', 'demographics.has_income', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'has_minimum_income', 'Customer: Has minimum income', 'demographics.has_minimum_income', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'identity_verified', 'Customer: Identity verified?', 'application.identity_verified', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'income_direct_deposit', 'Customer Income: Direct deposited', 'application.income_direct_deposit', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'income_frequency', 'Customer Income: Frequency', 'application.income_frequency', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'income_monthly', 'Customer Income: Monthly amount', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'income_source', 'Customer Income: Source', 'application.income_source', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'ip_address', 'Application: IP Address', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'is_react', 'Application: Is reactivation', 'application.is_react', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'is_watched', 'Application: Is Watched?', 'application.is_watched', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_number', 'Legal identification: Number', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_state', 'Legal identification: State abbreviation', 'state.state', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_state_business_allowed', 'Legal identification: State allows our business', 'state.business_allowed', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_state_dst', 'Legal identification: State honors daylight savings', 'state.use_daylight_saving', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_state_name', 'Legal identification: State', 'state.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_state_time_zone', 'Legal identification: State time zone', 'time_zone.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_state_time_zone_offset', 'Legal identification: State time zone GMT offset', 'time_zone.gmt_offset', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'legal_id_type', 'Legal identification: Type', 'application.legal_id_type', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'loan_amount', 'Application: Loan Amount', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'login_login', 'Application: Login', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'login_status', 'Application: Login status', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'marketing_contact_pref', 'Customner: Marketing contact preference', 'application.marketing_contact_pref', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'minimum_age', 'Customer: Is of minimum age', 'demographics.minimum_age', 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'name_first', 'Customer: First Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'name_last', 'Customer: Last Name', NULL, 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'name_middle', 'Customer: Middle Name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'name_suffix', 'Customer: Suffix', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'opt_in', 'Customer: Has opted in (?)', 'demographics.opt_in', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'paper_type', 'Application: Paper Type', 'application.application_type', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'paydate_model', 'Application: Paydate Frequency', 'reports_paydate_models.external_value', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'phone_cell', 'Customer Phone: Cell', NULL, 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'phone_fax', 'Customer Phone: Fax', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'phone_home', 'Customer Phone: Home', NULL, 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'phone_work', 'Customer Phone: Work', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'phone_work_ext', 'Customer Phone: Work Extension', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'reference_name_full', 'Customer Reference: Full Name', NULL, NULL);
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'reference_phone_home', 'Customer Reference: Home phone', NULL, NULL);
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'reference_relationship', 'Customer Reference: Relationship', NULL, NULL);
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'social_security_number', 'Customer: SSN', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'status', 'Application: Status', 'application_status.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'tenancy', 'Customer: Tenancy', 'application.tenancy_type', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'us_citizen', 'Customer: Is a US citizen', 'demographics.us_citizen', 'htmlentities');


INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_email', 'Agent email address', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_login', 'Agent login name', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_name_first', 'Agent Name: First', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_name_forward', 'Agent Name: Full', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_name_last', 'Agent Name: Last', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_name_middle', 'Agent Name: Middle', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_name_reverse', 'Agent Name: Last, First', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'agent_phone', 'Agent phone number', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'entry_application_id', 'Application Id', NULL, 'route_to_app_link');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'entry_date', 'Date', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'entry_dda_history__id', 'Historical Id', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'entry_description', 'Short Description', 'reports_dda_history.description', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_dda_history', 'entry_schedule_id', 'Event Schedule Id', NULL, 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'application_id', 'Application: Application Id', NULL, 'route_to_app_link');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'date_created', 'Application: Date Created', NULL, 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_amount_principal', 'Event Schedule: Amount Principal', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_amount_non_principal', 'Event Schedule: Amount Non-Principal', NULL, 'htmlentities')
;
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_context', 'Event Schedule: Context', 'event_schedule.context', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_date_created', 'Event Schedule: Date Created', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_date_event', 'Event Schedule: Date Event', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_date_effective', 'Event Schedule: Date Effective', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_name', 'Event Schedule: Type', 'event_type.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'event_status', 'Event Schedule: Status', 'event_schedule.event_status', 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'debt_company_name', 'Debt Company: Name', 'debt_company.company_name' , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'debt_company_address1', 'Debt Company: Address 1', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'debt_company_address2', 'Debt Company: Address 2', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'debt_company_city', 'Debt Company: City', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'debt_company_state', 'Debt Company: State', 'debt_company.state' , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'debt_company_zip', 'Debt Company: Zipcode', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_details', 'debt_company_phone', 'Debt Company: Phone', NULL , 'htmlentities');


INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_transaction_details', 'application_id', 'Application: Application Id', NULL, 'route_to_app_link');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_transaction_details', 'date_created', 'Application: Date Created', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_transaction_details', 'transaction_date_created', 'Transaction: Date Created', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_transaction_details', 'transaction_amount', 'Transaction: Amount', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_transaction', 'transaction_date_effective', 'Transaction: Date Effective', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_event_transaction', 'transaction_status', 'Transaction: Status', 'transaction_register.transaction_status', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_transaction_details', 'transaction_name', 'Transaction: Type', 'transaction_type.name', 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_transaction_details', 'transaction_clearing_type', 'Transaction: Clearing Type', 'transaction_type.clearing_type', 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'application_id', 'Application: Application Id', NULL, 'route_to_app_link');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'date_created', 'Application: Date Created', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_status', 'ACH: Status', 'ach.ach_status' , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_amount', 'ACH: Amount', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_date', 'ACH: Date', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_date_created', 'ACH: Date Created', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_trace_number', 'ACH: Trace Number', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_type', 'ACH: Type', 'ach.ach_type' , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_return_code_name', 'ACH: Return Code', 'ach_return_code.name_short' , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_ach_details', 'ach_return_code_desc', 'ACH: Return Code Description', 'ach_return_code.name' , 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'ra_parent_child_application_id', 'React Affiliation: React Application ID', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'ra_child_parent_application_id', 'React Affiliation: Parent Application ID', NULL , 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_application_id', 'Agent Affiliation: Application ID', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_date_created', 'Agent Affiliation: Date Created', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_area', 'Agent Affiliation: Area', 'a_affil.affiliation_area' , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_agent_email', 'Agent Affiliation: Agent Email', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_agent_login', 'Agent Affiliation: Agent Login', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_agent_name_first', 'Agent Affiliation: Agent First Name', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_agent_name_last', 'Agent Affiliation: Agent Last Name', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'aa_agent_phone', 'Agent Affiliation: Agent Phone', NULL , 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'lah_application_id', 'Loan Action: Application ID', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'lah_date_created', 'Loan Action: Date Created', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'la_description', 'Loan Action: Description', 'loan_actions.description' , 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'application_id', 'Application: Application Id', NULL, 'route_to_app_link');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'date_created', 'Application: Date Created', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'extcol_date_created', 'Ext Collections: Date Created', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'extcol_current_blance', 'Ext Collections: Balance', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'extcol_batch_id', 'Ext Collections: Batch ID', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'extcol_batch_date_created', 'Ext Collections: Batch Date Created', NULL , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'extcol_batch_status', 'Ext Collections: Batch Status', 'ext_collections_batch.batch_status' , 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_extcol_details', 'extcol_batch_company', 'Ext Collections: Batch Company', 'ext_collections_batch.ext_collections_co' , 'htmlentities');

INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_complete', 'Loan Balance: Complete', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_complete_fee', 'Loan Balance: Complete Fee', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_complete_irrecoverable', 'Loan Balance: Complete Irrecoverable', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_complete_principal', 'Loan Balance: Complete Principal', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_complete_service_charge', 'Loan Balance: Complete Interest', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_pending', 'Loan Balance: Pending', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_pending_fee', 'Loan Balance: Pending Fee', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_pending_irrecoverable', 'Loan Balance: Pending Irrecoverable', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_pending_principal', 'Loan Balance: Pending Principal', NULL, 'htmlentities');
INSERT INTO reports_columns(table_name, column_name, human_readable_name, drop_down_column, special_operation)
  VALUES('report_applications', 'balance_pending_service_charge', 'Loan Balance: Pending Interest', NULL, 'htmlentities');

