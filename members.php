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


$ROOT = '/srv/www/zerotier-one/controller.d/network/';

if(!empty($_GET['nwid'])) {
	$dir = $ROOT.$_GET['nwid'].'/member';
	echo $dir;
	echo '[';
	if(is_dir($dir)) {
		if($dh = opendir($dir)) {
			while (($file = readdir($dh))!== false) {
				echo $file;
				if(endsWith($file,'.json'))
					echo readfile($dir.'/'.$file);
			}
		}
		closedir($dh);
	}
	echo ']';
}


?>



<?php

