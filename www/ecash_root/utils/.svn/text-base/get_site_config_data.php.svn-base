#!/usr/bin/php
<?php

/**
 * retrieves the site_config data for a given application
 */
 
if($argc > 2) { $application_id = $argv[2]; $company = strtolower($argv[1]);}
else Usage($argv);

require_once("../www/config.php");
define ('CUSTOMER_LIB', BASE_DIR . "customer_lib/{$company}/");
require_once("mini-server.class.php");
require_once(COMMON_LIB_DIR."mysqli.1.php");
require_once(SQL_LIB_DIR . "get_mysqli.func.php");
require_once(SQL_LIB_DIR . "fetch_campaign_info.func.php");
require_once(LIB_DIR . 'common_functions.php');
require_once 'config.4.php';

$ci = Fetch_Campaign_Info($application_id, 3);

$site_config = Config_4::Get_Site_Config ($ci->license_key, $ci->promo_id, $ci->promo_sub_code);

var_dump($site_config);

function Usage($argv)
{
        echo "Usage: {$argv[0]} [ic|clk] [application_id]\n";
        exit;
}