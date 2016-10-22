<?php
//requires libolution
require_once("AutoLoad.1.php");


/*
 * host: reader.ecashclk.ept.tss
 * port: 3306
 * username: ecash
 * password: ugd2vRjv
 * db: ldb
 */
$ecash_reader = new DB_Database_1(new DB_MySQLConfig_1("reader.ecashclk.ept.tss", "ecash", "ugd2vRjv", "ldb"));
$legacy_stats_writer = new DB_Database_1(new DB_MySQLConfig_1("writer.olp.ept.tss", "sellingsource", "%selling\$_db"));
$clk_statpro_writer = new DB_Database_1(new DB_MySQLConfig_1("fw1.epointps.net", "owner", "llama", "clk_statpro_data", 13307));

$limits = array('d1' => NULL,
				'ca' => 409,
				'pcl' => 159,
				'ucl' => NULL,
				'ufc' => 45);

foreach($limits as $name_short => $limit)
{	
	$ecash_query = "select
	app.track_id,
	sh.date_created,
	sh.application_id,
	c.name_short,
	ci.promo_id,
	ci.promo_sub_code
from status_history sh
inner join application_status aps on (aps.application_status_id = sh.application_status_id)
inner join application app on (app.application_id = sh.application_id)
inner join agent on (agent.agent_id = sh.agent_id)
inner join company c on (c.company_id = sh.company_id)
inner join campaign_info ci on (ci.application_id = sh.application_id)
where sh.date_created between '2006-04-03 00:00:00' and '2006-04-03 23:59:59'
and aps.name_short = 'active'
and agent.login = 'ecash_support'
and ci.campaign_info_id =
(
select max(campaign_info_id)
from campaign_info ci_inner
where ci_inner.application_id = ci.application_id
and ci_inner.date_created <= sh.date_created
)
and c.name_short = '{$name_short}'
order by sh.date_created
";

	$result = $ecash_reader->query($ecash_query, PDO::FETCH_OBJ);
	$count = 0;

	foreach($result as $ecash_row)
	{

		if($limit == NULL || $limit > $count)
		{
			//print_r($ecash_row); exit;
	
			/*** update legacy stats ***/
			$block_query = "select block_id, page_id from {$name_short}_tracking.id_blocks 
where promo_id = {$ecash_row->promo_id}
and promo_sub_code = '{$ecash_row->promo_sub_code}'
and stat_date = '2006-04-03'
;";
			//echo $block_query, "\n";
				
			$blocks = $legacy_stats_writer->querySingleRow($block_query);

			if(!$blocks)
			{
				echo "legacy: {$name_short} {$ecash_row->promo_id} {$ecash_row->promo_sub_code} not found!\n";
				continue;
			}
		
			$legacy_query = "select
stats.funded as old_funded,
stats.funded - 1 as new_funded
from {$name_short}_tracking.stats{$blocks->page_id}_2006_04 stats
where block_id = {$blocks->block_id}
;";
			//echo $legacy_query, "\n";		
			$stats_row = $legacy_stats_writer->querySingleRow($legacy_query);		
			//print_r($stats_row);

			$legacy_update = "update {$name_short}_tracking.stats{$blocks->page_id}_2006_04
set funded = funded - 1
where block_id = {$blocks->block_id}";
			fwrite(STDERR, "{$legacy_update};\n");
			$legacy_row_count = 0;
			$legacy_row_count = $legacy_stats_writer->exec($legacy_update);
			fwrite(STDERR, "Rows affected: {$legacy_row_count}\n\n");

			/*** end update legacy stats ***/

			/*** update statpro ***/
			$statpro_query = "select
el0.event_log_id,
sd0.page_id,
sd0.promo_id,
sd0.promo_sub_code,
et0.event_type_key,
from_unixtime(el0.date_occured) as date_occurred
from event_log el0
inner join event_type et0 on (et0.event_type_id = el0.event_type_id)
inner join track tr0 on (tr0.track_id = el0.track_id)
inner join enterprisepro.space_definition sd0 on (sd0.space_id = el0.space_id)
where el0.date_occured between unix_timestamp('2006-04-03 00:00:00') and unix_timestamp('2006-04-03 23:59:59')
and et0.event_type_key = 'funded'
and tr0.track_key = '{$ecash_row->track_id}'
order by date_occured desc limit 1
";

			$statpro_row = $clk_statpro_writer->querySingleRow($statpro_query);
			if(!$statpro_row)
			{
				echo "statpro: {$ecash_row->track_id} not found!\n";
			}
			//print_r($statpro_row); exit;
			$statpro_update = "update event_log
set event_type_id = 1
where event_log_id = {$statpro_row->event_log_id}";

			fwrite(STDERR, "{$statpro_update};\n");
			$statpro_row_count = 0;
			$statpro_row_count = $clk_statpro_writer->exec($statpro_update);
			fwrite(STDERR, "Rows affected: {$statpro_row_count}\n\n");

			//only if all updates are made successfully
			$count++;
			/*** end update statpro ***/
		}
	}
	echo "{$name_short} updated {$count} rows\n";
}


?>