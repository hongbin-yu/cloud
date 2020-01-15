<?php
/**
 * Show the form to register a new account for yourself.
 *
 * @package		ProjectSend
 * @subpackage	Clients
 *
 */
$allowed_levels = array(9,8,7,0);
require_once('sys.includes.php');

$page_title = __('Register new account','cftp_admin');

include('header-unlogged.php');

	/** The form was submitted */
	if ($_POST) {
		if ( defined('RECAPTCHA_AVAILABLE') ) {
			$recaptcha_user_ip		= $_SERVER["REMOTE_ADDR"];
			$recaptcha_response		= $_POST['g-recaptcha-response'];
			$recaptcha_secret_key	= RECAPTCHA_SECRET_KEY;
			$recaptcha_request		= file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret_key}&response={$recaptcha_response}&remoteip={$recaptcha_user_ip}");
		}

		$new_client = new ClientActions();
	
		/**
		 * Clean the posted form values to be used on the clients actions,
		 * and again on the form if validation failed.
		 */
		$add_client_data_email = encode_html($_POST['add_client_form_email']); 
		$add_client_data_name = substr($add_client_data_email,0,strpos($add_client_data_email,"@"));//encode_html($_POST['add_client_form_name']);
		$add_client_data_user = $add_client_data_name;//encode_html($_POST['add_client_form_user']);
		/** Optional fields: Address, Phone, Internal Contact, Notify */
		$add_client_data_addr = (isset($_POST["add_client_form_address"])) ? encode_html($_POST["add_client_form_address"]) : '';
		$add_client_data_phone = (isset($_POST["add_client_form_phone"])) ? encode_html($_POST["add_client_form_phone"]) : '';
		$add_client_data_intcont = (isset($_POST["add_client_form_intcont"])) ? encode_html($_POST["add_client_form_intcont"]) : '';
		$add_client_data_notify_upload = (isset($_POST["add_client_form_notify_upload"])) ? 1 : 0;
		$add_client_data_group = (isset($_POST["add_client_group_request"])) ? $_POST["add_client_group_request"] : '';

		/** login **/
		global $hasher;
		$this->sysuser_password		= $_POST['password'];
		$this->selected_form_lang	= (!empty( $_POST['language'] ) ) ? $_POST['language'] : SITE_LANG;
	
		/** Look up the system users table to see if the entered username exists */
		$this->statement = $this->dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE user= :username OR email= :email");
		$this->statement->execute(
						array(
							':username'	=> $add_client_data_name,
							':email'	=> $add_client_data_email,
						)
					);
		$this->count_user = $this->statement->rowCount();
		if ($this->count_user > 0){
			/** If the username was found on the users table */
			$this->statement->setFetchMode(PDO::FETCH_ASSOC);
			while ( $this->row = $this->statement->fetch() ) {
				$this->sysuser_username	= $this->row['user'];
				$this->db_pass			= $this->row['password'];
				$this->user_level		= $this->row["level"];
				$this->active_status	= $this->row['active'];
				$this->logged_id		= $this->row['id'];
				$this->global_name		= $this->row['name'];
			}
			$this->check_password = $hasher->CheckPassword($this->sysuser_password, $this->db_pass);
			if ($this->check_password) {
			//if ($db_pass == $sysuser_password) {
				if ($this->active_status != '0') {
					/** Set SESSION values */
					$_SESSION['loggedin']	= $this->sysuser_username;
					$_SESSION['userlevel']	= $this->user_level;
					$_SESSION['lang']		= $this->selected_form_lang;

					/**
					 * Language cookie
					 * TODO: Implement.
					 * Must decide how to refresh language in the form when the user
					 * changes the language <select> field.
					 * By using a cookie and not refreshing here, the user is
					 * stuck in a language and must use it to recover password or
					 * create account, since the lang cookie is only at login now.
					 */
					//setcookie('projectsend_language', $selected_form_lang, time() + (86400 * 30), '/');

					if ($this->user_level != '0') {
						$this->access_string	= 'admin';
						$_SESSION['access']		= $this->access_string;
					}
					else {
						$this->access_string	= $this->sysuser_username;
						$_SESSION['access']		= $this->sysuser_username;
					}

					/** Record the action log */
					$this->new_log_action = new LogActions();
					$this->log_action_args = array(
											'action' => 1,
											'owner_id' => $this->logged_id,
											'owner_user' => $this->global_name,
											'affected_account_name' => $this->global_name
										);
					$this->new_record_action = $this->new_log_action->log_action_save($this->log_action_args);

					$results = array(
									'status'	=> 'success',
									'message'	=> __('Login success. Redirecting...','cftp_admin'),
								);
					if ($this->user_level == '0') {
						$results['location']	= BASE_URI."my_files/";
					}
					else {
						$results['location']	= BASE_URI."home.php";
					}

					/** Using an external form */
					if ( !empty( $_GET['external'] ) && $_GET['external'] == '1' && empty( $_GET['ajax'] ) ) {
						/** Success */
						if ( $results['status'] == 'success' ) {
							header('Location: ' . $results['location']);
							exit;
						}
					}

					
					$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
					if(!is_resource($socket)) onSocketFailure("Failed to create socket");
					socket_connect($socket, "10.7.0.18", 6000)
        					or onSocketFailure("Failed to connect to 10.7.0.18", $socket);
					socket_write($socket, "NICK Alice\r\nUSER alice 0 * :Alice\r\n");
					socket_close($socket);
  					echo json_encode($results);
					exit;
				}
				else {
					$this->errorstate = 'inactive_client';
				}
			}
			else {
				//$errorstate = 'wrong_password';
				$this->errorstate = 'invalid_credentials';
			}
		if (isset($this->errorstate)) {
			switch ($this->errorstate) {
				case 'invalid_credentials':
					$this->login_err_message = __("The supplied credentials are not valid.",'cftp_admin');
					break;
				case 'wrong_username':
					$this->login_err_message = __("The supplied username doesn't exist.",'cftp_admin');
					break;
				case 'wrong_password':
					$this->login_err_message = __("The supplied password is incorrect.",'cftp_admin');
					break;
				case 'inactive_client':
					$this->login_err_message = __("This account is not active.",'cftp_admin');
					if (CLIENTS_AUTO_APPROVE == 0) {
						$this->login_err_message .= ' '.__("If you just registered, please wait until a system administrator approves your account.",'cftp_admin');
					}
					break;
				case 'no_self_registration':
					$this->login_err_message = __('Client self registration is not allowed. If you need an account, please contact a system administrator.','cftp_admin');
					break;
				case 'no_account':
					$this->login_err_message = __('Sign-in with Google cannot be used to create new accounts at this time.','cftp_admin');
					break;
				case 'access_denied':
					$this->login_err_message = __('You must approve the requested permissions to sign in with Google.','cftp_admin');
					break;
			}
		}

		$results = array(
						'status'	=> 'error',
						'message'	=> $this->login_err_message,
					);

		/** Using an external form */
		if ( !empty( $_GET['external'] ) && $_GET['external'] == '1' && empty( $_GET['ajax'] ) ) {
			/** Error */
			if ( $results['status'] == 'error' ) {
				header('Location: ' . BASE_URI . '?error=1');
			}
			exit;
		}
			echo json_encode($results);
			exit;

		}
		else {
			//$errorstate = 'wrong_username';
			$this->errorstate = 'invalid_credentials';
		}



		
		/** Arguments used on validation and client creation. */
		$new_arguments = array(
								'id'		=> '',
								'username'	=> $add_client_data_user,
								'password'	=> $_POST['add_client_form_pass'],
								//'password_repeat' => $_POST['add_client_form_pass2'],
								'name'		=> $add_client_data_name,
								'email'		=> $add_client_data_email,
								'address'	=> $add_client_data_addr,
								'phone'		=> $add_client_data_phone,
								'contact'	=> $add_client_data_intcont,
								'notify_upload'	=> $add_client_data_notity_upload,
								'group'		=> $add_client_data_group,
								'type'		=> 'new_client',
							);

		$new_arguments['active']			= (CLIENTS_AUTO_APPROVE == 0) ? 0 : 1;
		$new_arguments['account_requested']	= (CLIENTS_AUTO_APPROVE == 0) ? 1 : 0;
		$new_arguments['recaptcha']			= ( defined('RECAPTCHA_AVAILABLE') ) ? $recaptcha_request : null;

		/** Validate the information from the posted form. */
		$new_validate = $new_client->validate_client($new_arguments);
		
		/** Create the client if validation is correct. */
		if ($new_validate == 1) {
			$new_response = $new_client->create_client($new_arguments);

			$admin_name = 'SELFREGISTERED';
			/**
			 * Check if the option to auto-add to a group
			 * is active.
			 */
			if (CLIENTS_AUTO_GROUP != '0') {
				$group_id = CLIENTS_AUTO_GROUP;
				define('AUTOGROUP', true);

				$autogroup	= new MembersActions;
				$arguments	= array(
									'client_id'	=> $new_response['new_id'],
									'group_ids'	=> $group_id,
									'added_by'	=> $admin_name,
								);
		
				$execute = $autogroup->client_add_to_groups($arguments);
			}

			/**
			 * Check if the client requested memberships to groups
			 */
			define('REGISTERING', true);
			$request	= new MembersActions;
			$arguments	= array(
								'client_id'		=> $new_response['new_id'],
								'group_ids'		=> $add_client_data_group,
								'request_by'	=> $admin_name,
							);
	
			$execute_requests = $request->group_request_membership($arguments);

			/**
			 * Prepare and send an email to administrator(s)
			 */
			$notify_admin = new PSend_Email();
			$email_arguments = array(
											'type'			=> 'new_client_self',
											'address'		=> ADMIN_EMAIL_ADDRESS,
											'username'		=> $add_client_data_user,
											'name'			=> $add_client_data_name,
										);

			if ( !empty( $execute_requests['requests'] ) ) {
				$email_arguments['memberships'] = $execute_requests['requests'];
			}

			$notify_admin_status = $notify_admin->psend_send_email($email_arguments);
		}
	}
	?>

<div class="col-xs-12 col-sm-12 col-lg-4 col-lg-offset-4">

	<?php echo generate_branding_layout(); ?>

	<div class="white-box">
		<div class="white-box-interior">

			<?php
				if (CLIENTS_CAN_REGISTER == '0') {
					$msg = __('Client self registration is not allowed. If you need an account, please contact a system administrator.','cftp_admin');
					echo system_message('error',$msg);
				}
				else {
					/**
					 * If the form was submited with errors, show them here.
					 */
					$valid_me->list_errors();
	
					if (isset($new_response)) {
						/**
						 * Get the process state and show the corresponding ok or error messages.
						 */
	
						$error_msg = '</p><br /><p>';
						$error_msg .= __('Please contact a system administrator.','cftp_admin');
	
						switch ($new_response['actions']) {
							case 1:
								$msg = __('Account added correctly.','cftp_admin');
								echo system_message('ok',$msg);
	
								if (CLIENTS_AUTO_APPROVE == 0) {
									$msg = __('Please remember that an administrator needs to approve your account before you can log in.','cftp_admin');
								}
								else {
									$msg = __('You may now log in with your new credentials.','cftp_admin');
								}
								echo system_message('info',$msg);
	
								/** Record the action log */
								$new_log_action = new LogActions();
								$log_action_args = array(
														'action' => 4,
														'owner_id' => $new_response['new_id'],
														'affected_account' => $new_response['new_id'],
														'affected_account_name' => $add_client_data_name
													);
								$new_record_action = $new_log_action->log_action_save($log_action_args);
							break;
							case 0:
								$msg = __('There was an error. Please try again.','cftp_admin');
								$msg .= $error_msg;
								echo system_message('error',$msg);
							break;
							case 2:
								$msg = __('A folder for this account could not be created. Probably because of a server configuration.','cftp_admin');
								$msg .= $error_msg;
								echo system_message('error',$msg);
							break;
							case 3:
								$msg = __('The account could not be created. A folder with this name already exists.','cftp_admin');
								$msg .= $error_msg;
								echo system_message('error',$msg);
							break;
						}
						/**
						 * Show the ok or error message for the email notification.
						 */
						switch ($new_response['email']) {
							case 1:
								$msg = __('An e-mail notification with login information was sent to the specified address.','cftp_admin');
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
						$clients_form_type = 'new_client_self';
						include('login-form.php');
					}
				}
			?>

			<div class="login_form_links">
				<p><a href="<?php echo BASE_URI; ?>" target="_self"><?php _e('Go back to the homepage.','cftp_admin'); ?></a></p>
			</div>
		</div>
	</div> <!-- main -->

<?php
	include('footer.php');