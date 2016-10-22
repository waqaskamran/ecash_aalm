<?php

/**
 * added origin_url for loan_data->fetch_loan_all to use, which contains the site of origin rather than the enterprise site.[jeffd][IMPACT LIVE #11065]
 */

//from class Campaign_Info_Query

function Fetch_Campaign_Info($application_id, $company_id)
{
	$db = ECash::getMasterDb();
	$query = "
			SELECT
				camp.campaign_info_id, 
				camp.promo_id, 
				camp.promo_sub_code, 
				s.name as url,
				s.license_key,
				cs.name as origin_url
			FROM
				application a,
				site s,
				site cs,
				campaign_info camp		
			WHERE
				a.application_id = {$application_id}
			AND a.company_id = {$company_id}
			AND camp.application_id = a.application_id
			AND camp.campaign_info_id = 
				(
					SELECT
						MAX(campaign_info_id) 
					FROM
						campaign_info cref
					WHERE
						cref.application_id = camp.application_id
				)
			AND cs.site_id=camp.site_id
				AND a.enterprise_site_id = s.site_id
		";
	$result = $db->query($query);
	return $result->fetch(PDO::FETCH_OBJ);
}

?>