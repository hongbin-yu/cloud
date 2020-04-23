<?php
/**
 * Show the form to edit an existing group.
 *
 * @package		ProjectSend
 * @subpackage	Groups
 *
 */
$load_scripts	= array(
						'chosen',
						'ckeditor',
					);

$allowed_levels = array(9,8);
require_once('sys.includes.php');

$active_nav = 'groups';

$page_title = __('Edit group','cftp_admin');


/** Create the object */
$edit_group = new GroupActions();

/** Check if the id parameter is on the URI. */
if (isset($_GET['group_name'])) {
	$group_name = $_GET['group_name'];
	$editing = $dbh->prepare("SELECT * FROM " . TABLE_GROUPS . " WHERE name='".$group_name."'");
	//$editing->bindParam(':id', $group_id, PDO::PARAM_INT);
	$editing->execute();
	$editing->setFetchMode(PDO::FETCH_ASSOC);

	while ( $data = $editing->fetch() ) {
		$add_group_data_id = $data['id'];
		$add_group_data_name = $data['name'];
		$add_group_data_description = $data['description'];
		if ($data['public'] == 1) { $add_group_data_public = 1; } else { $add_group_data_public = 0; }
	}

	if(!empty($data) {
		header("Content-Type: application/json");
		die('{"id": '.$add_group_data_id.',"name":"'.$add_group_data_name.'","description":"'.$add_group_data_description.'"}');
	}else {
		header("HTTP/1.0 Error 404");
		die('{"error":"group name is not found"}');
	}
}else {

		header("HTTP/1.0 Error 404");
		die('{"error":"group name is empty"}');
}

?>

