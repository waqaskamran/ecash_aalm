#!/usr/bin/php
<?php
	$ecash_base_dir = realpath(dirname(__FILE__) . "/../");
	
	echo "\n*** ECash Company Setup ***\n";
	echo "\nWhat is the customer short name (eg. lcs, agean, etc...)? ";
	$cust_short = strtoupper(trim(fread(STDIN, 1024)));
	
	$default_cust_dir = "/virtualhosts/ecash_" . strtolower($cust_short);
	echo "In which directory is the customer checkout? [{$default_cust_dir}] ";
	$cust_dir = trim(fread(STDIN, 1024));
	$cust_dir = (empty($cust_dir)) ? $default_cust_dir : $cust_dir;

	echo "Where is the Rule Editor located? [/virtualhosts/rule_editor] ";
	$rule_edit = trim(fread(STDIN, 1024));
    $rule_edit = (empty($rule_edit)) ? '/virtualhosts/rule_editor' : $rule_edit;
    
    if(!is_dir($cust_dir))
    {
    	die("Directory doesn't exist!\n");
    }
        
    echo "Checking for default htaccessfile... ";
    $hta_filename = $ecash_base_dir . "/utils/default_htaccess";
    
    if(is_file($hta_filename))
    {
    	echo "OK\n";
    }else{
    	die("Missing default htaccess file.\n");
    }
    
    echo "What Execution Mode would you like to use? [Local] ";
	$exec_mode = strtoupper(trim(fread(STDIN, 1024)));
    $exec_mode = (empty($exec_mode)) ? 'Local' : $exec_mode;
    
    $hta = file_get_contents($hta_filename);
    $htaccess_values = array(
    	'CUSTOMER_SHORT' => $cust_short,
    	'CUSTOMER_DIR' => $cust_dir . "/",
    	'ECASH_WWW' => $ecash_base_dir . "/www/",
    	'EXEC_MODE' => $exec_mode
    	);
    	
    //Perform token replacement
    foreach($htaccess_values as $token => $value)
    {
    	$hta = str_replace("%%%".$token."%%%", $value, $hta);
    }
    
    //put the modified htaccess file into place
    $new_filename = $cust_dir . "/www/.htaccess";
    file_put_contents($new_filename, $hta);
    
    //Create symlinks
    
    //create root link
	$command = "ln -s {$ecash_base_dir} " . $cust_dir . "/www/ecash_root";
	$output = shell_exec($command);
	//create rule editor link
	$command = "ln -s {$rule_edit} " . $cust_dir . "/www/rule_editor";
	$output = shell_exec($command);
	
	echo "\n*** {$cust_short} Setup Complete ***\n";
	echo "Note: If you need modify your execution mode, edit '{$new_filename}'.\n";
?>