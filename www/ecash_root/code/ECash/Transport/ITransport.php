<?php
interface ECash_Transport_ITransport
{
	public function putFile($name, $file_contents);
	public function getfile($name);

}

?>
