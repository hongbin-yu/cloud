<?php
	/* encode file */
require_once('sys.includes.php');
	if(isset($_GET['url'])) {
		header("HTTP/1.0 200");
		header("Content-Type: application/json");
		$file = UPLOADED_FILES_FOLDER.$_GET['url'];
		$img = file_get_contents($file);
		echo '{ "ok" : "'.$file.'",';
		echo '"data":"';
		echo base64_encode($img);
		echo '"}';

	}else {
		die('{"error":"File not found"}');
	}

?>
