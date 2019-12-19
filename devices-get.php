<?php
/**
 * Show the form to edit a system user.
 *
 * @package		ProjectSend
 * @subpackage	Devices
 *
 */
$allowed_levels = array(9,8,7);
require_once('sys.includes.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$active_nav = 'devices';

/** Create the object */
$edit_device = new DeviceActions();

/** Check if the id parameter is on the URI. */
if (isset($_GET['device_id'])) {
	$device_id = "$_GET['device_id']";
	$page_status = 1;//(device_exists_id($device_id)) ? 1 : 2;
}
else {
	/**
	 * Return 0 if the id is not set.
	 */
	$page_status = 0;
}

/**
 * Get the device information from the database to use on the form.
 */
if ($page_status === 1) {
	$editing = $dbh->prepare("SELECT * FROM " . TABLE_DEVICES . " WHERE device_id like ':id'");
	$editing->bindParam(':id', $device_id, PDO::PARAM_STR);
	$editing->execute();
	$editing->setFetchMode(PDO::FETCH_ASSOC);

	while ( $data = $editing->fetch() ) {
		$add_device_data_name = $data['name'];
		$add_device_data_device_id = $data['device_id'];
		$add_device_data_password = $data['password'];	
		$add_device_data_ip = $data['ip'];	
		$add_device_data_mask = $data['mask'];	
		$add_device_data_supernode1 = $data['supernode1'];	
		$add_device_data_supernode2 = $data['supernode2'];	
		$add_device_data_domain = $data['domain'];
		$add_device_data_contact = $data['contact'];	
		$add_device_data_phone = $data['phone'];
		$add_device_data_address = $data['address'];		
		$add_device_data_email = $data['email'];
		$add_device_data_level = $data['level'];
		$add_device_data_maxusersize	= $data['max_user_size'];
		if ($data['active'] == 1) { $add_device_data_active = 1; } else { $add_device_data_active = 0; }
	}
}

header("HTTP/1.0 200");
header("Content-Type: application/json");

echo "{";
echo '"name": "'.$add_device_data_name.'",';
echo '"device_id": "'.$device_id.'",';
echo '"password": "'.$add_device_data_password.'",';
echo '"ip": "'.$add_device_data_ip.'",';
echo '"mask": "'.$add_device_data_mask.'",';
echo '"supernode1": "'.$add_device_data_supernode1.'",';
echo '"supernode2": "'.$add_device_data_supernode2.'",';
echo '"domain": "'.$add_device_data_domain.'"';
echo "}";

?>

