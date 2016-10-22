-- Recommended usage:
-- $ mysql -Nf <connection_parameters> ldb < SETUP.sql

-- This file exists for the express reason of creating the following
-- tables, views, and triggers in an order which will NOT cause
-- errors. Every one of these files should be safe to re-run on a live
-- database, so be careful people!!!

-- For the sake of sanity, comment what depends on what.
SELECT "Cleanup, expect errors";
SOURCE CLEANUP.sql;
SELECT "Cleanup complete, setting up tables";
SOURCE application_queue.sql;
SOURCE context.sql;
SOURCE dda_history.sql;
SOURCE event_amount_type.sql;
SOURCE event_type.sql;
SOURCE holiday.sql;
SOURCE mechanism.sql;
SOURCE reports_columns.sql;
SOURCE reports_cache.sql;
SOURCE reports_dda_history.sql;
SOURCE reports_paydate_models.sql;
SOURCE state.sql;
SOURCE system.sql;
SOURCE time_zone.sql;
SOURCE application_status.sql;
SOURCE session.sql;
SOURCE company.sql;
SOURCE ach_return_code.sql;
SOURCE return_reason.sql;
SOURCE bureau.sql;
SOURCE bureau_inquiry_type.sql;
SOURCE contact_outcome.sql;
SOURCE contact_type.sql;
SOURCE control_option.sql;
SOURCE debt_company.sql;
SOURCE loan_actions.sql;
SOURCE rule_component.sql;
SOURCE site.sql;
SOURCE ach_report.sql; -- company
SOURCE ach_batch.sql; -- company
SOURCE rule_component_parm.sql; -- rule_component
SOURCE contact_type_outcome.sql; -- contact_type contact_outcome
SOURCE company_property.sql; -- company
SOURCE bureau_login.sql; -- company bureau
SOURCE agent.sql; -- system
SOURCE agent_affiliation.sql; -- company application agent
SOURCE transactional_name.sql; -- company event_amount_type mechanism event_type context
SOURCE application_status_flat.sql; -- application_status
SOURCE ach.sql; -- company application ach_batch ach_report ach_return_code
SOURCE ach_company.sql; -- company ach ach_batch ach_report ach_return_code
SOURCE document_package.sql; -- company
SOURCE document_list.sql; -- company system
SOURCE document_list_package.sql; -- company document_list document_package
SOURCE document_list_state.sql; -- company document_list state
SOURCE document.sql; -- application company document_list agent
SOURCE access_group.sql; -- company system
SOURCE agent_access_group.sql; -- company agent access_group
SOURCE access_group_control_option.sql; -- access_group control_option
SOURCE standby.sql; -- application
SOURCE status_history.sql; -- company application agent application_status
SOURCE process_log.sql; -- company
SOURCE application_audit.sql; -- company application agent
SOURCE loan_type.sql; -- company
SOURCE rule_set.sql; -- loan_type
SOURCE application.sql; -- login loan_type rule_set application_status agent state
SOURCE report_dda_history.sql; -- reports_dda_history agent application
SOURCE application_column.sql; -- application company
SOURCE rule_set_component.sql; -- rule_set rule_component
SOURCE rule_set_component_parm_value.sql; -- agent rule_set rule_component rule_component_parm
SOURCE reports_app_status.sql; -- application_status
SOURCE reports_cache_stats.sql; -- reports_cache
SOURCE reports_cache_stats_ag.sql; -- reports_cache_stats
SOURCE section.sql; -- system
SOURCE acl.sql; -- company access_group section
SOURCE transaction_status.sql; -- mechanism
SOURCE transaction.sql; -- transaction_status application company mechanism
SOURCE event.sql; -- context application event_type mechanism company
SOURCE agent_affiliation_event.sql; -- agent_affiliation event
SOURCE event_amount.sql; -- event event_amount_type transaction application company mechanism loan_snapshot loan_snapshot_maintenance
SOURCE event_amount__before_insert.sql; -- event_amount event
SOURCE event__after_delete.sql; -- event event_amount
SOURCE event_amount__before_delete.sql; -- event_amount
SOURCE ecld.sql; -- company application ecld_file ecld_return event_schedule return_reason
SOURCE ecld_file.sql; -- company
SOURCE ecld_return.sql; -- company
SOURCE bureau_inquiry.sql; -- company application bureau
SOURCE campaign_info.sql; -- company application site
SOURCE comment.sql; -- company application agent
SOURCE contact_history.sql; -- application contact_type contact_outcome agent
SOURCE debt_company_event.sql; -- company event
SOURCE demographics.sql; -- company application
SOURCE ext_collections.sql; -- company application ext_collections_batch
SOURCE ext_collections_batch.sql; -- company
SOURCE loan_action_history.sql; -- loan_actions application status_history
SOURCE loan_snapshot.sql; -- company application
SOURCE login.sql; -- company
SOURCE report_applications.sql; -- application reports_app_status company application_column agent login demographics state time_zone reports_paydate_models
SOURCE report_application_status.sql; -- report_applications
SOURCE open_advances_report.sql; -- company loan_type
SOURCE paperless_queue.sql; -- company application
SOURCE personal_reference.sql; -- company application
SELECT "Done";
