<?php
/**
 * Show the form to add a new client.
 *
 * @package		ProjectSend
 * @subpackage	Clients
 *
 */
$load_scripts	= array(
						'chosen',
					); 

$allowed_levels = array(9,8);
require_once('sys.includes.php');

$active_nav = 'clients';

$page_title = __('Add client','cftp_admin');

//include('header.php');

/**
 * Set checkboxes as 1 to default them to checked when first entering
 * the form
 */
$add_client_data_notify_upload = 1;
$add_client_data_active = 1;
$add_client_data_notify_account = 1;
$headers = apache_request_headers();
$username =  isset($headers["Authorization"]) ? $headers["Authorization"] : '';
if($username == '') {
    header("HTTP/1.0 408 no username; Content-Type: application/json");
	
    die('{"error":"username must not empty"}');
}
if ($_GET) {
	$editing = $dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE user=:username");
	$editing->bindParam(':username', $username, PDO::PARAM_STR);
	$editing->execute();
	$editing->setFetchMode(PDO::FETCH_ASSOC);

	while ( $data = $editing->fetch() ) {
		$add_client_data_id			= $data['id'];
		$add_client_data_name			= $data['name'];
		$add_client_data_user			= $data['user'];
		$add_client_data_email			= $data['email'];
		$add_client_data_addr			= $data['address'];
		$add_client_data_phone			= $data['phone'];
		$add_client_data_intcont		= $data['contact'];
		$add_client_data_maxfilesize	= $data['max_file_size'];
		if ($data['notify'] == 1) { $add_client_data_notify_upload = 1; } else { $add_client_data_notify_upload = 0; }
		if ($data['active'] == 1) { $add_client_data_active = 1; } else { $add_client_data_active = 0; }
	}

	if(!empty($data)) {
		/**
		 * Clean the posted form values to be used on the user actions,
		 * and again on the form if validation failed.
		 * Also, overwrites the values gotten from the database so if
		 * validation failed, the new unsaved values are shown to avoid
		 * having to type them again.
		 */
		$add_client_data_name			= $_GET['name'];
		$add_client_data_user			= $username;
		$add_client_data_email			= $_GET['email'];
		/** Optional fields: Address, Phone, Internal Contact, Notify */
		$add_client_data_addr			= (isset($_GET["address"])) ? $_GET["address"] : '';
		$add_client_data_phone			= (isset($_GET["phone"])) ? $_GET["phone"] : '';
		$add_client_data_intcont		= (isset($_GET["intcont"])) ? $_GET["intcont"] : '';
		$add_client_data_notify_upload  	= (isset($_GET["notify_upload"])) ? 1 : 0;

		if ( $ignore_size == false ) {
			$add_client_data_maxfilesize	= (isset($_GET["maxfilesize"])) ? $_GET["maxfilesize"] : '255';
		}
		else {
			$add_client_data_maxfilesize	= $add_client_data_maxfilesize;
		}

		if ($global_level != 0) {
			$add_client_data_active	= (isset($_GET["active"])) ? 1 : 0;
		}

		/** Arguments used on validation and client creation. */
		$edit_arguments = array(
								'id'			=> $add_client_data_id,
								'username'		=> $add_client_data_user,
								'name'			=> $add_client_data_name,
								'email'			=> $add_client_data_email,
								'address'		=> $add_client_data_addr,
								'phone'			=> $add_client_data_phone,
								'contact'		=> $add_client_data_intcont,
								'notify_upload' 	=> $add_client_data_notify_upload,
								'active'		=> $add_client_data_active,
								'max_file_size'	=> $add_client_data_maxfilesize,
								'type'			=> 'edit_client'
							);

		/**
		 * If the password field, or the verification are not completed,
		 * send an empty value to prevent notices.
		 */
		$edit_arguments['password'] = (isset($_GET['password'])) ? $_GET['password'] : '';
		//$edit_arguments['password_repeat'] = (isset($_GET['add_client_form_pass2'])) ? $_GET['add_client_form_pass2'] : '';

		/** Validate the information from the posted form. */
		$edit_validate = $edit_client->validate_client($edit_arguments);
		
		/** Edit the account if validation is correct. */
		if ($edit_validate == 1) {
			$edit_response = $edit_client->edit_client($edit_arguments);

			$edit_groups = (!empty( $_GET['add_client_group_request'] ) ) ? $_GET['add_client_group_request'] : array();
			$memberships	= new MembersActions;
			$arguments		= array(
									'client_id'		=> $add_client_data_id,
									'group_ids'		=> $edit_groups,
									'request_by'	=> $username,
								);

			$memberships->update_membership_requests($arguments);
		}

		//$location = BASE_URI . 'clients-edit.php?id=' . $client_id . '&status=' . $edit_response['query'];
		//header("Location: $location");
		header("Content-Type: application/json");
		die('{"ok":"user updated :'.$username.'"}');	
	}else {
		$new_client = new ClientActions();

		/**
		 * Clean the posted form values to be used on the clients actions,
		 * and again on the form if validation failed.
		 */
		$add_client_data_name = encode_html($_GET['name']);
		$add_client_data_user = encode_html($username);
		$add_client_data_email = encode_html($_GET['email']);
		/** Optional fields: Address, Phone, Internal Contact, Notify */
		$add_client_data_addr = (isset($_GET["address"])) ? encode_html($_GET["address"]) : '';
		$add_client_data_phone = (isset($_GET["phone"])) ? encode_html($_GET["phone"]) : '';
		$add_client_data_intcont = (isset($_GET["intcont"])) ? encode_html($_GET["intcont"]) : '';
		$add_client_data_maxfilesize = (isset($_GET["maxfilesize"])) ? encode_html($_GET["maxfilesize"]) : '255';
		$add_client_data_notify_upload = (isset($_GET["notify_upload"])) ? 1 : 0;
		$add_client_data_notify_account = (isset($_GET["notify_account"])) ? 1 : 0;
		$add_client_data_active = (isset($_GET["active"])) ? 1 : 0;

		/** Arguments used on validation and client creation. */
		$new_arguments = array(
								'id'			=> '',
								'username'		=> $add_client_data_user,
								'password'		=> $_GET['password'],
								//'password_repeat' => $_GET['add_client_form_pass2'],
								'name'			=> $add_client_data_name,
								'email'			=> $add_client_data_email,
								'address'		=> $add_client_data_addr,
								'phone'			=> $add_client_data_phone,
								'contact'		=> $add_client_data_intcont,
								'notify_upload' 	=> $add_client_data_notify_upload,
								'notify_account' 	=> $add_client_data_notify_account,
								'active'		=> $add_client_data_active,
								'max_file_size'	=> $add_client_data_maxfilesize,
								'type'			=> 'new_client',
							);

		/** Validate the information from the posted form. */
		$new_validate = $new_client->validate_client($new_arguments);
		
		/** Create the client if validation is correct. */
		if ($new_validate == 1) {
			$new_response = $new_client->create_client($new_arguments);
			
			$add_to_groups = (!empty( $_GET['add_client_group_request'] ) ) ? $_GET['add_client_group_request'] : '';
			if ( !empty( $add_to_groups ) ) {
				array_map('encode_html', $add_to_groups);
				$memberships	= new MembersActions;
				$arguments		= array(
										'client_id'	=> $new_response['new_id'],
										'group_ids'	=> $add_to_groups,
										'added_by'	=> CURRENT_USER_USERNAME,
									);
		
				$memberships->client_add_to_groups($arguments);
			}
			header("Content-Type: application/json");
			die('{"ok":"user added :'.$username.'"}');	
		}else {
			header("HTTP/1.0 401 validate fail: ".$add_client_data_email);
			die('{"error":"user validate fail :'.$username.'"}');	

		}
	}

	
} else {
	header("HTTP/1.0 404");
	die("GET Only");
}
?>
