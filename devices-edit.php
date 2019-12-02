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
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
$active_nav = 'devices';

/** Create the object */
$edit_device = new DeviceActions();

/** Check if the id parameter is on the URI. */
if (isset($_GET['id'])) {
	$device_id = $_GET['id'];
	$page_status = (device_exists_id($device_id)) ? 1 : 2;
}
else {
	/**
	 * Return 0 if the id is not set.
	 */
	$page_status = 0;
}

/**
 * Get the user information from the database to use on the form.
 */
if ($page_status === 1) {
	$editing = $dbh->prepare("SELECT * FROM " . TABLE_DEVICES . " WHERE id=:id");
	$editing->bindParam(':id', $device_id, PDO::PARAM_INT);
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

/**
 * Form type
 */
if (CURRENT_USER_LEVEL == 7) {
	$device_form_type = 'edit_device_self';
	$ignore_size = true;
}
else {

	$device_form_type = 'edit_device';
	$ignore_size = false;
}

/**
 * Compare the client editing this account to the on the db.
 */
 /*
if (CURRENT_USER_LEVEL != 9) {
	if (CURRENT_USER_USERNAME != $add_device_data_user) {
		$page_status = 3;
	}
}*/

if ($_POST) {
	/**
	 * If the user is not an admin, check if the id of the user
	 * that's being edited is the same as the current logged in one.
	 */
	if (CURRENT_USER_LEVEL != 9) {
		//if ($user_id != CURRENT_USER_ID) {
		die();
		//}
	}

	/**
	 * Clean the posted form values to be used on the user actions,
	 * and again on the form if validation failed.
	 * Also, overwrites the values gotten from the database so if
	 * validation failed, the new unsaved values are shown to avoid
	 * having to type them again.
	 */
	$add_device_data_name			= $_POST['add_device_form_name'];
	$add_device_data_email		= $_POST['add_device_form_email'];

	if ( $ignore_size == false ) {
		$add_device_data_maxusersize	= (isset($_POST["add_device_form_maxusersize"])) ? $_POST["add_device_form_maxusersize"] : 0;
	}
	else {
		$add_device_data_maxusersize	= $add_device_data_maxusersize;
	}

	/**
	 * Edit level only when user is not Uploader (level 7) or when
	 * editing other's account (not own).
	 */	
	$edit_level_active = true;
	/*
	if (CURRENT_USER_LEVEL == 7) {
		$edit_level_active = false;
	}
	else {
		if (CURRENT_USER_USERNAME == $add_device_data_user) {
			$edit_level_active = false;
		}
	}
	*/
	if ($edit_level_active === true) {
		/** Default level to 7 just in case */
		$add_device_data_level = (isset($_POST["add_device_form_level"])) ? $_POST['add_device_form_level'] : '7';
		$add_device_data_active = (isset($_POST["add_device_form_active"])) ? 1 : 0;
	}

	/** Arguments used on validation and user creation. */
	$edit_arguments = array(
							'id'			=> $device_id,
							'ip'			=> $add_device_data_ip,
							'mask'			=> $add_device_data_mask,
							'supernode1'			=> $add_device_data_supernode1,
							'supernode2'			=> $add_device_data_supernode2,														
							'name'			=> $add_device_data_name,
							'domain'			=> $add_device_data_domain,
							'contact'			=> $add_device_data_contact,
							'phone'			=> $add_device_data_phone,	
							'address'			=> $add_device_data_address,							
							'email'				=> $add_device_data_email,
							'role'				=> $add_device_data_level,
							'active'			=> $add_device_data_active,
							'max_user_size'	=> $add_device_data_maxusersize,
							'type'				=> 'edit_user'
						);

	/**
	 * If the password field, or the verification are not completed,
	 * send an empty value to prevent notices.
	 */
	$edit_arguments['password'] = (isset($_POST['add_device_form_pass'])) ? $_POST['add_device_form_pass'] : '';
	//$edit_arguments['password_repeat'] = (isset($_POST['add_user_form_pass2'])) ? $_POST['add_user_form_pass2'] : '';

	/** Validate the information from the posted form. */
	$edit_validate = $edit_device->validate_device($edit_arguments);
	
	/** Create the user if validation is correct. */
	if ($edit_validate == 1) {
		$edit_response = $edit_device->edit_device($edit_arguments);
	}

	$location = BASE_URI . 'devices-edit.php?id=' . $device_id . '&status=' . $edit_response['query'];
	header("Location: $location");
	die();
}

$page_title = __('Edit device','cftp_admin');
/*
if (CURRENT_USER_USERNAME == $add_device_data_user) {
	$page_title = __('My account','cftp_admin');
}
*/
include('header.php');
?>

<div class="col-xs-12 col-sm-12 col-lg-6">
	<?php
		if (isset($_GET['status'])) {
			switch ($_GET['status']) {
				case 1:
					$msg = __('Device edited correctly.','cftp_admin');
					echo system_message('ok',$msg);

					$saved_device = get_device_by_id($device_id);
					/** Record the action log */
					/*
					$new_log_action = new LogActions();
					$log_action_args = array(
											'action' => 13,
											'owner_id' => CURRENT_USER_ID,
											'affected_account' => $user_id,
											'affected_account_name' => $saved_user['username'],
											'get_user_real_name' => true
										);
					$new_record_action = $new_log_action->log_action_save($log_action_args);
					*/
				break;
				case 0:
					$msg = __('There was an error. Please try again.','cftp_admin');
					echo system_message('error',$msg);
				break;
			}
		}
	?>
	
	<div class="white-box">
		<div class="white-box-interior">
		
			<?php
				/**
				 * If the form was submited with errors, show them here.
				 */
				$valid_me->list_errors();
			?>
			
			<?php
				$direct_access_error = __('This page is not intended to be accessed directly.','cftp_admin');
				if ($page_status === 0) {
					$msg = __('No device was selected.','cftp_admin');
					echo system_message('error',$msg);
					echo '<p>'.$direct_access_error.'</p>';
				}
				else if ($page_status === 2) {
					$msg = __('There is no device with that ID number.','cftp_admin');
					echo system_message('error',$msg);
					echo '<p>'.$direct_access_error.'</p>';
				}
				else if ($page_status === 3) {
					$msg = __("Your account type doesn't allow you to access this feature.",'cftp_admin');
					echo system_message('error',$msg);
				}
				else {
					/**
					 * Include the form.
					 */
					include('devices-form.php');
				}
			?>

		</div>		
	</div>
</div>

<?php
	include('footer.php');
