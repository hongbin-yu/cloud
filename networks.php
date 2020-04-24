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

	$dir = $ROOT;
	if(file_exists($dir)) {
		if(is_dir($dir)) { 
			header("Content-Type: application/json");
			echo "[";
			$files = glob($dir.'*.json');
			foreach($files as $filename) {
				readfile($filename);
				if(next($files)) echo ",\r\n"; 
			}
			echo "]";

		}else {
			header("HTTP/1.0 401 error");
			die('{"status":"fail","message":"'.$dir.' is not dir"');
		}
	}else {
		header("HTTP/1.0 401 error");
		echo die('{"status":"fail","message":"'.$dir.' does not exists"');;
	}



?>




