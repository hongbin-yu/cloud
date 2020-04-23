<?php
/**
 * Show the list of current groups.
 *
 * @package		ProjectSend
 * @subpackage	Groups
 *
 */


$allowed_levels = array(9,8);
require_once('sys.includes.php');
$ROOT = '/srv/www/zerotier-one/controller.d/network/';

if(!empty($_GET['nwid'])) {
	$dir = $ROOT.$_GET['nwid'].'/member';
	echo '[';
	if(is_dir($dir)) {
		if($dh = opendir($dir)) {
			while (($file = readdir($dh))!== false) {
				if(endsWith($file,'.json'))
					echo readfile($file);
			}
		}
		closedir($dh);
	}
	echo ']';
}


?>



<?php

