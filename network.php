<?php
/**
 * Show the list of current groups.
 *
 * @package		ProjectSend
 * @subpackage	Groups
 *
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$ROOT = $_SERVER['DOCUMENT_ROOT'].'/zerotier-one/controller.d/network/';

if(!empty($_GET['nwid'])) {
	$dir = $ROOT.$_GET['nwid'].'.json';


	if(file_exists($dir)) {

		header("Content-Type: application/json");
		readfile($$dir));
	}else {
		echo $dir." is not exists";
	}

	

}


?>



<?php

