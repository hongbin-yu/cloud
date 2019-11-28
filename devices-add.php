<?php
/**
 * Show the form to add a new system user.
 *
 * @package		ProjectSend
 * @subpackage	Users
 *
 */
$allowed_levels = array(9);
require_once('sys.includes.php');

if(!check_for_admin()) {
    return;
}

$active_nav = 'users';

$page_title = __('Add system user','cftp_admin');

include('header.php');

/**
 * Set checkboxes as 1 to defaul them to checked when first entering
 * the form
 */
$add_device_data_active = 1;
$add_device_data_notify_account = 1;

if ($_POST) {
	$new_device = new DeviceActions();

	/**
	 * Clean the posted form values to be used on the user actions,
	 * and again on the form if validation failed.
	 */
	$add_device_data_name = encode_html($_POST['add_device_form_name']);
	$add_device_data_email = encode_html($_POST['add_device_form_email']);
	$add_device_data_level = encode_html($_POST['add_device_form_level']);
	$add_device_data_device_id = encode_html($_POST['add_device_form_device_id']);
	$add_device_data_maxusersize = (isset($_POST["add_device_form_maxusersize"])) ? encode_html($_POST["add_device_form_maxusersize"]) : '';
	$add_device_data_active = (isset($_POST["add_device_form_active"])) ? 1 : 0;
	$add_device_data_notify_account = (isset($_POST["add_device_form_notify_account"])) ? 1 : 0;

	/** Arguments used on validation and user creation. */
	$new_arguments = array(
							'id' => '',
							'device_id' => $add_device_data_device_id,
							'password' => $_POST['add_device_form_pass'],
							//'password_repeat' => $_POST['add_device_form_pass2'],
							'ip' => $add_device_data_ip,
							'mask' => $add_device_data_mask,
							'supernode1' => $add_device_data_supernode1,		
							'supernode2' => $add_device_data_supernode2,	
							'domain' => $add_device_data_domain,								
							'name' => $add_device_data_name,
							'email' => $add_device_data_email,
							'role' => $add_device_data_level,
							'active' => $add_device_data_active,
							'max_file_size'	=> $add_device_data_maxfilesize,
							'notify_account' => $add_device_data_notify_account,
							'type' => 'new_device'
						);

	/** Validate the information from the posted form. */
	$new_validate = $new_device->validate_device($new_arguments);
	
	/** Create the user if validation is correct. */
	if ($new_validate == 1) {
		$new_response = $new_device->create_device($new_arguments);
	}
	
}
?>
<div class="col-xs-12 col-sm-12 col-lg-6">
	<div class="white-box">
		<div class="white-box-interior">
		
			<?php
				/**
				 * If the form was submited with errors, show them here.
				 */
				$valid_me->list_errors();
			?>
			
			<?php
				if (isset($new_response)) {
					/**
					 * Get the process state and show the corresponding ok or error message.
					 */
					switch ($new_response['query']) {
						case 1:
							$msg = __('User added correctly.','cftp_admin');
							echo system_message('ok',$msg);
	
							/** Record the action log */
							$new_log_action = new LogActions();
							$log_action_args = array(
													'action' => 2,
													'owner_id' => CURRENT_device_ID,
													'affected_account' => $new_response['new_id'],
													'affected_account_name' => $add_device_data_name
												);
							$new_record_action = $new_log_action->log_action_save($log_action_args);
	
						break;
						case 0:
							$msg = __('There was an error. Please try again.','cftp_admin');
							echo system_message('error',$msg);
						break;
					}
					/**
					 * Show the ok or error message for the email notification.
					 */
					switch ($new_response['email']) {
						case 2:
							$msg = __('A welcome message was not sent to the new user.','cftp_admin');
							echo system_message('ok',$msg);
						break;
						case 1:
							$msg = __('A welcome message with login information was sent to the new user.','cftp_admin');
							echo system_message('ok',$msg);
						break;
						case 0:
							$msg = __("E-mail notification couldn't be sent.",'cftp_admin');
							echo system_message('error',$msg);
						break;
					}
				}
				else {
					/**
					 * If not $new_response is set, it means we are just entering for the first time.
					 * Include the form.
					 */
					$user_form_type = 'new_user';
					include('users-form.php');
				}
			?>

		</div>
	</div>
</div>

<?php
	include('footer.php');
