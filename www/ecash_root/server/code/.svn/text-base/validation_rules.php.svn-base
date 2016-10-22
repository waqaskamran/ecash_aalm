<?php

$rules = new StdClass();

// NORMALIZATION RULES
// personal
$rules->normalize->dob['type'] = "date";
$rules->normalize->name_first['type'] = "name";
$rules->normalize->name_last['type'] = "name";
$rules->normalize->name_middle['type'] = "name";
$rules->normalize->gender['type'] = "string";
$rules->normalize->customer_email['type'] = "email";
$rules->normalize->phone_home['type'] = "all_digits";
$rules->normalize->phone_cell['type'] = "all_digits";
$rules->normalize->phone_fax['type'] = "all_digits";
$rules->normalize->best_call_time['type'] = "string";
$rules->normalize->ssn['type'] = "all_digits";
$rules->normalize->ssn_part_1['type'] = "all_digits";
$rules->normalize->ssn_part_2['type'] = "all_digits";
$rules->normalize->ssn_part_3['type'] = "all_digits";
$rules->normalize->state_id_number['type'] = "string";
$rules->normalize->state_id_state['type'] = "string";
$rules->normalize->cali_agree['type'] = "string";
// demographics
$rules->normalize->checking_account['type'] = "boolean"; // ** No boolean type
$rules->normalize->monthly_1200['type'] = "boolean";
$rules->normalize->income_stream['type'] = "boolean";
$rules->normalize->citizen['type'] = "boolean";
$rules->normalize->offers['type'] = "boolean";
$rules->normalize->unsecured_debt['type'] = "boolean";
// employment
$rules->normalize->phone_work['type'] = "all_digits";
$rules->normalize->phone_work_extension['type'] = "all_digits";
$rules->normalize->phone_work_ext['type'] = $rules->normalize->phone_work_extension['type'];
$rules->normalize->employer_name['type'] = "string";
$rules->normalize->employer_length['type'] = "string";
$rules->normalize->income_type['type'] = "string";
$rules->normalize->shift['type'] = "string";
$rules->normalize->date_of_hire['type'] = "date";
$rules->normalize->job_title['type'] = "string";
// residence
$rules->normalize->residence_type['type'] = "string";
// so it doesn't mess up the satori call and normalization
$rules->normalize->street['type'] = "string";
$rules->normalize->unit['type'] = "string";
$rules->normalize->city['type'] = "string";
$rules->normalize->state['type'] = "";
$rules->normalize->zip['type'] = "all_digits";
$rules->normalize->zip_code['type'] = "all_digits";
// bank_info
$rules->normalize->income_direct_deposit['type'] = "string";
$rules->normalize->bank_name['type'] = "string";
$rules->normalize->bank_aba['type'] = "all_digits";
$rules->normalize->bank_account['type'] = "all_digits";
$rules->normalize->bank_account_type['type'] = "string";
// income
$rules->normalize->income_monthly['type'] = "real_dollar_amount";
$rules->normalize->income_frequency['type'] = "string";
$rules->normalize->pay_date1['type'] = "date";
$rules->normalize->pay_date2['type'] = "date";
$rules->normalize->ent_pay_date3['type'] = "date";
$rules->normalize->ent_pay_date4['type'] = "date";
$rules->normalize->net_pay['type'] = "all_digits";

// Payment Cards
$rules->normalize->card_number['type'] = "all_digits";
$rules->normalize->cardholder_name['type'] = "string";
$rules->normalize->card_exp1['type'] = "all_digits";
$rules->normalize->card_exp2['type'] = "all_digits";


// references - Duplicate to support more than 6
$rules->normalize->ref_name_1['type'] = "string";
$rules->normalize->ref_phone_1['type'] = "all_digits";
$rules->normalize->ref_relationship_1['type'] = "string";

$rules->normalize->ref_name_2['type'] = "string";
$rules->normalize->ref_phone_2['type'] = "all_digits";
$rules->normalize->ref_relationship_2['type'] = "string";

$rules->normalize->ref_name_3['type'] = "string";
$rules->normalize->ref_phone_3['type'] = "all_digits";
$rules->normalize->ref_relationship_3['type'] = "string";

$rules->normalize->ref_name_4['type'] = "string";
$rules->normalize->ref_phone_4['type'] = "all_digits";
$rules->normalize->ref_relationship_4['type'] = "string";

$rules->normalize->ref_name_5['type'] = "string";
$rules->normalize->ref_phone_5['type'] = "all_digits";
$rules->normalize->ref_relationship_5['type'] = "string";

$rules->normalize->ref_name_6['type'] = "string";
$rules->normalize->ref_phone_6['type'] = "all_digits";
$rules->normalize->ref_relationship_6['type'] = "string";

// legal
$rules->normalize->legal_notice_1 = "string";
// other pages
$rules->normalize->email_alternate["type"] = "email";
$rules->normalize->fax_number["type"] = "all_digits";

$rules->normalize->ezm_nsf_count['type'] = "boolean";
$rules->normalize->ezm_terms['type'] = "boolean";
$rules->normalize->ezm_signature['type'] = "string";

$rules->normalize->infinity_account_number = "text_number";

//campaign info
$rules->normalize->promo_sub_code['type'] = "cashline_promo_sub_code";
$rules->normalize->url['type'] = "cashline_string";

//comment
$rules->normalize->comment['type'] = "cashline_string";

//document
$rules->normalize->alt_xfer_date['type'] = "all_digits";
$rules->normalize->verified_date['type'] = "all_digits";

//cashline
$rules->normalize->date_fund_estimated['type'] = "all_digits";
$rules->normalize->employer_name['type'] = "cashline_string";
$rules->normalize->job_title['type'] = "cashline_string";
$rules->normalize->shift['type'] = "cashline_string";
$rules->normalize->bank_name['type'] = "cashline_string";
$rules->normalize->name_first['type'] = "cashline_string";
$rules->normalize->name_middle['type'] = "cashline_string";
$rules->normalize->name_last['type'] = "cashline_string";
$rules->normalize->customer_email['type'] = "cashline_string";
$rules->normalize->ssn_cashline['type'] = "cashline_string";
$rules->normalize->legal_id_number['type'] = "cashline_string";
$rules->normalize->legal_id_state['type'] = "cashline_string";
$rules->normalize->street['type'] = "cashline_string";
$rules->normalize->unit['type'] = "cashline_string";
$rules->normalize->city['type'] = "cashline_string";


$rules->normalize->phone_work['type'] = $rules->normalize->phone_work['type'];
$rules->normalize->phone_work_extension['type'] = $rules->normalize->phone_work_extension['type'];
$rules->normalize->phone_home['type'] = $rules->normalize->phone_home['type'];
$rules->normalize->phone_cell['type'] = $rules->normalize->phone_cell['type'];
$rules->normalize->phone_fax['type'] = $rules->normalize->phone_fax['type'];


//reference
$rules->normalize->name_full['type'] = "cashline_string";
$rules->normalize->relationship['type'] = "cashline_string";
$rules->normalize->phone['type'] = "all_digits";

// validation rules
// personal
$rules->validation->dob['type'] = "over_18";
$rules->validation->name_first['type'] = "name";
$rules->validation->name_first["min"] = 2;
$rules->validation->name_first["max"] = 50;
$rules->validation->name_last['type'] = "name";
$rules->validation->name_last["min"] = 2;
$rules->validation->name_last["max"] = 50;
$rules->validation->name_middle['type'] = "name";
$rules->validation->name_middle['min'] = 1;
$rules->validation->name_middle['max'] = 50;
$rules->validation->gender = array("type" => "string", "min" => 1, "max" => 1);
$rules->validation->customer_email['type'] = "email";
$rules->validation->phone_home['type'] = "phone_number";
$rules->validation->phone_home["min"] = 10;
$rules->validation->phone_home["max"] = 10;
$rules->validation->phone_cell['type'] = "phone_number";
$rules->validation->phone_cell["min"] = 10;
$rules->validation->phone_cell["max"] = 10;
$rules->validation->phone_fax['type'] = "phone_number";
$rules->validation->phone_fax["min"] = 10;
$rules->validation->phone_fax["max"] = 10;
$rules->validation->best_call_time['type'] = "string";				// enum
$rules->validation->ssn['type'] = "all_digits_not_all_zeros";
$rules->validation->ssn['min'] = 9;
$rules->validation->ssn['max'] = 9;
$rules->validation->legal_id_number['type'] = "string";
$rules->validation->legal_id_number['min'] = 3;
$rules->validation->legal_id_number['max'] = 30;
$rules->validation->legal_id_state['type'] = "string";
$rules->validation->legal_id_state['min'] = 2;
$rules->validation->legal_id_state['max'] = 2;
$rules->validation->cali_agree['type'] = "cali_agree";
$rules->validation->cali_agree['deny_blank'] = 1;

// Payment Cards
$rules->validation->card_number['type'] = "all_digits";
$rules->validation->card_exp1['type'] = "all_digits";
$rules->validation->card_exp1['min'] = '1';
$rules->validation->card_exp1['max'] = '2';
$rules->validation->card_exp2['min'] = '2';
$rules->validation->card_exp2['max'] = '4';
$rules->validation->card_exp2['type'] = "all_digits";
$rules->validation->cardholder_name['type'] = "string";
$rules->validation->card_street['type'] = "string";
$rules->validation->card_zip['type'] = "all_digits";
$rules->validation->card_zip["min"] = 5;
$rules->validation->card_zip["max"] = 9;

// demographics
$rules->validation->checking_account['type'] = "boolean"; // ** no boolean type
$rules->validation->monthly_1200['type'] = "boolean";
$rules->validation->income_stream['type'] = "boolean";
$rules->validation->citizen['type'] = "boolean";
$rules->validation->offers['type'] = "string";
$rules->validation->unsecured_debt['type'] = "boolean";
// employment
$rules->validation->phone_work['type'] = "phone_number";
$rules->validation->phone_work["min"] = 10;
$rules->validation->phone_work["max"] = 10;
$rules->validation->employer_name['type'] = "string";
$rules->validation->employer_name["min"] = 2;
$rules->validation->employer_name["max"] = 100;
$rules->validation->employer_length['type'] = "string";
$rules->validation->income_type['type'] = "string";					// enum
$rules->validation->phone_work_extension['type'] = "all_digits_not_all_zeros";
$rules->validation->phone_work_extension['min'] = 1;
$rules->validation->phone_work_extension['max'] = 5;
$rules->validation->shift['type'] = "string";
$rules->validation->date_of_hire['type'] = "date";
$rules->validation->job_title['type'] = "string";
// residence
$rules->validation->residence_type['type'] = "string";
$rules->validation->street['type'] = "string";
$rules->validation->unit['type'] = "string";
$rules->validation->unit['min'] = 1;
$rules->validation->unit['max'] = 10;
$rules->validation->city['type'] = "string";
$rules->validation->city["min"] = 1;
$rules->validation->city["max"] = 100;
$rules->validation->state['type'] = "string";
$rules->validation->state['min'] = "2";
$rules->validation->state['max'] = "2";
$rules->validation->zip['type'] = "all_digits";
$rules->validation->zip["min"] = 5;
$rules->validation->zip["max"] = 9;
$rules->validation->zip_code['type'] = "all_digits";
$rules->validation->zip_code["min"] = 5;
$rules->validation->zip_code["max"] = 9;
// bank_info
$rules->validation->income_direct_deposit['type'] = "string";			// enum
$rules->validation->bank_name['type'] = "string";
$rules->validation->bank_name["min"] = 2;
$rules->validation->bank_name["max"] = 100;
$rules->validation->bank_aba["min"] = 9;
$rules->validation->bank_aba["max"] = 9;
$rules->validation->bank_aba['type'] = "all_digits";
$rules->validation->bank_account['type'] = "all_digits_not_all_zeros";
$rules->validation->bank_account["min"] = 2;
$rules->validation->bank_account["max"] = 15;
$rules->validation->bank_account_type['type'] = "bank_account_type";
$rules->validation->bank_account_type['deny_blank'] = 1;
// income
$rules->validation->income_monthly['type'] = "all_digits";
$rules->validation->income_monthly['min'] = 3;
$rules->validation->income_monthly['max'] = 4;
$rules->validation->income_frequency['type'] = "string";				// enum
$rules->validation->pay_date1['type'] = "date";
$rules->validation->pay_date2['type'] = "date";
$rules->validation->ent_pay_date3['type'] = "date";
$rules->validation->ent_pay_date4['type'] = "date";
$rules->validation->net_pay['type'] = "all_digits";

// references - Replicate to support more than 6
$rules->validation->ref_name_1['type'] = "string";
$rules->validation->ref_name_1["min"] = 2;
$rules->validation->ref_name_1["max"] = 100;
$rules->validation->ref_phone_1['type'] = "phone_number";
$rules->validation->ref_phone_1["min"] = 10;
$rules->validation->ref_phone_1["max"] = 10;
$rules->validation->ref_relationship_1['type'] = "string";
$rules->validation->ref_relationship_1["min"] = 2;
$rules->validation->ref_relationship_1["max"] = 25;

$rules->validation->ref_name_2['type'] = "string";
$rules->validation->ref_name_2["min"] = 2;
$rules->validation->ref_name_2["max"] = 100;
$rules->validation->ref_phone_2['type'] = "phone_number";
$rules->validation->ref_phone_2["min"] = 10;
$rules->validation->ref_phone_2["max"] = 10;
$rules->validation->ref_relationship_2['type'] = "string";
$rules->validation->ref_relationship_2["min"] = 2;
$rules->validation->ref_relationship_2["max"] = 25;

$rules->validation->ref_name_3['type'] = "string";
$rules->validation->ref_name_3["min"] = 2;
$rules->validation->ref_name_3["max"] = 100;
$rules->validation->ref_phone_3['type'] = "phone_number";
$rules->validation->ref_phone_3["min"] = 10;
$rules->validation->ref_phone_3["max"] = 10;
$rules->validation->ref_relationship_3['type'] = "string";
$rules->validation->ref_relationship_3["min"] = 2;
$rules->validation->ref_relationship_3["max"] = 25;

$rules->validation->ref_name_4['type'] = "string";
$rules->validation->ref_name_4["min"] = 2;
$rules->validation->ref_name_4["max"] = 100;
$rules->validation->ref_phone_4['type'] = "phone_number";
$rules->validation->ref_phone_4["min"] = 10;
$rules->validation->ref_phone_4["max"] = 10;
$rules->validation->ref_relationship_4['type'] = "string";
$rules->validation->ref_relationship_4["min"] = 2;
$rules->validation->ref_relationship_4["max"] = 25;

$rules->validation->ref_name_5['type'] = "string";
$rules->validation->ref_name_5["min"] = 2;
$rules->validation->ref_name_5["max"] = 100;
$rules->validation->ref_phone_5['type'] = "phone_number";
$rules->validation->ref_phone_5["min"] = 10;
$rules->validation->ref_phone_5["max"] = 10;
$rules->validation->ref_relationship_5['type'] = "string";
$rules->validation->ref_relationship_5["min"] = 2;
$rules->validation->ref_relationship_5["max"] = 25;

$rules->validation->ref_name_6['type'] = "string";
$rules->validation->ref_name_6["min"] = 2;
$rules->validation->ref_name_6["max"] = 100;
$rules->validation->ref_phone_6['type'] = "phone_number";
$rules->validation->ref_phone_6["min"] = 10;
$rules->validation->ref_phone_6["max"] = 10;
$rules->validation->ref_relationship_6['type'] = "string";
$rules->validation->ref_relationship_6["min"] = 2;
$rules->validation->ref_relationship_6["max"] = 25;


// legal
$rules->validation->legal_notice_1['type'] = "boolean";				// enum
// other
$rules->validation->address_check["type"] = "address";
// other pages
$rules->validation->email_alternate["type"] = "email";
$rules->validation->email_alternate["deny_blank"] = 1;
$rules->validation->fax_number["type"] = "phone_number";
$rules->validation->fax_number["deny_blank"] = 1;
$rules->validation->fax_number["min"] = 10;
$rules->validation->fax_number["max"] = 15;

$rules->validation->infinity_account_number = "string";

$rules->validation->ezm_nsf_count['type'] = "boolean";
$rules->validation->ezm_terms['type'] = "boolean";
$rules->validation->ezm_signature['type'] = "string";

//campaign info
$rules->validation->promo_sub_code['type'] = "string";
$rules->validation->promo_sub_code['min'] = 0;
$rules->validation->url['type'] = "string";
$rules->validation->url['min'] = 0;

//comment
$rules->validation->comment['type'] = "string";
$rules->validation->comment['min'] = 0;
$rules->validation->comment['max'] = 32700;

//document
$rules->validation->alt_xfer_date['type'] = "all_digits";
$rules->validation->alt_xfer_date['min'] = 8;
$rules->validation->alt_xfer_date['max'] = 8;
$rules->validation->verified_date['type'] = "all_digits";
$rules->validation->verified_date['min'] = 8;
$rules->validation->verified_date['max'] = 8;

//cashline
$rules->validation->date_fund_estimated['type'] = "all_digits";
$rules->validation->date_fund_estimated['min'] = 8;
$rules->validation->date_fund_estimated['max'] = 8;
$rules->validation->employer_name['type'] = $rules->validation->employer_name['type'];
$rules->validation->employer_name['min'] = $rules->validation->employer_name['min'];
$rules->validation->employer_name['max'] = $rules->validation->employer_name['max'];
$rules->validation->job_title['type'] = $rules->validation->job_title['type'];
$rules->validation->shift['type'] = $rules->validation->shift['type'];
$rules->validation->bank_name['type'] = $rules->validation->bank_name['type'];
$rules->validation->bank_name['min'] = $rules->validation->bank_name['min'];
$rules->validation->bank_name['max'] = $rules->validation->bank_name['max'];
$rules->validation->name_first['type'] = $rules->validation->name_first['type'];
$rules->validation->name_first['min'] = $rules->validation->name_first['min'];
$rules->validation->name_first['max'] = $rules->validation->name_first['max'];
$rules->validation->name_middle['type'] = $rules->validation->name_middle['type'];
$rules->validation->name_middle['min'] = $rules->validation->name_middle['min'];
$rules->validation->name_middle['max'] = $rules->validation->name_middle['max'];
$rules->validation->name_last['type'] = $rules->validation->name_last['type'];
$rules->validation->name_last['min'] = $rules->validation->name_last['min'];
$rules->validation->name_last['max'] = $rules->validation->name_last['max'];
$rules->validation->customer_email['type'] = $rules->validation->customer_email['type'];
$rules->validation->customer_email['type'] = $rules->validation->customer_email['type'];
$rules->validation->ssn['type'] = $rules->validation->ssn['type'];
$rules->validation->ssn['min'] = $rules->validation->ssn['min'];
$rules->validation->ssn['max'] = $rules->validation->ssn['max'];
$rules->validation->legal_id_number['type'] = $rules->validation->legal_id_number['type'];
$rules->validation->legal_id_number['min'] = $rules->validation->legal_id_number['min'];
$rules->validation->legal_id_number['max'] = $rules->validation->legal_id_number['max'];
$rules->validation->legal_id_state['type'] = $rules->validation->legal_id_state['type'];
$rules->validation->legal_id_state['min'] = $rules->validation->legal_id_state['min'];
$rules->validation->legal_id_state['max'] = $rules->validation->legal_id_state['max'];
$rules->validation->street['type'] = $rules->validation->street['type'];
$rules->validation->unit['type'] = $rules->validation->unit['type'];
$rules->validation->unit['min'] = $rules->validation->unit['min'];
$rules->validation->unit['max'] = $rules->validation->unit['max'];
$rules->validation->city['type'] = $rules->validation->city['type'];
$rules->validation->city['min'] = $rules->validation->city['min'];
$rules->validation->city['max'] = $rules->validation->city['max'];

$rules->validation->phone_work['type'] = $rules->validation->phone_work['type'];
$rules->validation->phone_work['min'] = $rules->validation->phone_work['min'];
$rules->validation->phone_work['max'] = $rules->validation->phone_work['max'];
$rules->validation->phone_work_ext['type'] = $rules->validation->phone_work_extension['type'];
$rules->validation->phone_work_ext['min'] = $rules->validation->phone_work_extension['min'];
$rules->validation->phone_work_ext['max'] = $rules->validation->phone_work_extension['max'];
$rules->validation->phone_work_extension['type'] = $rules->validation->phone_work_extension['type'];
$rules->validation->phone_work_extension['min'] = $rules->validation->phone_work_extension['min'];
$rules->validation->phone_work_extension['max'] = $rules->validation->phone_work_extension['max'];
$rules->validation->phone_home['type'] = $rules->validation->phone_home['type'];
$rules->validation->phone_home['min'] = $rules->validation->phone_home['min'];
$rules->validation->phone_home['max'] = $rules->validation->phone_home['max'];
$rules->validation->phone_cell['type'] = $rules->validation->phone_cell['type'];
$rules->validation->phone_cell['min'] = $rules->validation->phone_cell['min'];
$rules->validation->phone_cell['max'] = $rules->validation->phone_cell['max'];
$rules->validation->phone_fax['type'] = $rules->validation->phone_fax['type'];
$rules->validation->phone_fax['min'] = $rules->validation->phone_fax['min'];
$rules->validation->phone_fax['max'] = $rules->validation->phone_fax['max'];

//reference
$rules->validation->full_name['type'] = "string";
$rules->validation->relationship['type'] = "string";
$rules->validation->phone['type'] = "all_digits";

?>
