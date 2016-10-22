<?php
/* this will need the following things in the get string:
 *
 * table
 * data_column
 * id_column
 * id_value
 * file_name
 * file_size
 * file_type
 */
require_once('config.php');
require_once(SERVER_CODE_DIR . "server.class.php");

$session_id =  isset($_REQUEST['ssid']) ? $_REQUEST['ssid'] : NULL;
$request = (object) $_REQUEST;
$server = new Server($session_id);
//make sure they're logged in
$server->Process_Data($request);

$db = ECash::getMasterDb();

$query = "
		select attachment
		from fraud_proposition
		where fraud_proposition_id = {$db->quote($_GET['id_value'])}";

$result = $db->query($query);

$row = $result->fetch(PDO::FETCH_OBJ);

header("Content-length: {$_GET['file_size']}");
header("Content-type: {$_GET['file_type']}");
header("Content-Disposition: attachment; filename=\"{$_GET['file_name']}\"");
echo $row->{$_GET['data_column']};

?>