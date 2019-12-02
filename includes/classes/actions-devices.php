<?php
/**
 * Class that handles all the actions and functions that can be applied to
 * users accounts.
 *
 * @package		ProjectSend
 * @subpackage	Classes
 */

class DeviceActions
{

	var $device = '';

	function __construct() {
		global $dbh;
		$this->dbh = $dbh;
	}

	/**
	 * Validate the information from the form.
	 */
	function validate_device($arguments)
	{
		require(ROOT_DIR.'/includes/vars.php');

		global $valid_me;
		$this->state = array();

		$this->id = $arguments['id'];
		$this->name = $arguments['name'];
		$this->email = $arguments['email'];
		$this->password = $arguments['password'];
		//$this->password_repeat = $arguments['password_repeat'];
		$this->role = $arguments['role'];
		$this->notify_account = $arguments['notify_account'];
		$this->max_user_size = ( !empty( $arguments['max_user_size'] ) ) ? $arguments['max_user_size'] : 255;
		$this->type = $arguments['type'];

		/**
		 * These validations are done both when creating a new user and
		 * when editing an existing one.
		 */
		$valid_me->validate('completed',$this->name,$validation_no_name);
		$valid_me->validate('completed',$this->email,$validation_no_email);
		$valid_me->validate('completed',$this->role,$validation_no_level);
		$valid_me->validate('email',$this->email,$validation_invalid_mail);
		$valid_me->validate('number',$this->max_user_size,$validation_user_size);

		/**
		 * Validations for NEW USER submission only.
		 */
		if ($this->type == 'new_device') {
			$this->device_id = $arguments['device_id'];

			//$valid_me->validate('email_exists',$this->email,$add_user_mail_exists);
			/** devie_id checks */
			$valid_me->validate('device_exists',$this->device_id,$add_device_exists);
			$valid_me->validate('completed',$this->device_id,$validation_no_device);
			$valid_me->validate('alpha_dot',$this->device_id,$validation_alpha_device);
			$valid_me->validate('length',$this->device_id,$validation_length_user,MIN_USER_CHARS,MAX_USER_CHARS);

			$this->validate_password = true;
		}
		/**
		 * Validations for USER EDITING only.
		 */
		else if ($this->type == 'edit_device') {
			/**
			 * Changing password is optional.
			 * Proceed only if any of the 2 fields is completed.
			 */
			if($arguments['password'] != '' /* || $arguments['password_repeat'] != '' */) {
				$this->validate_password = true;
			}
			/**
			 * Check if the email is currently assigned to this users's id.
			 * If not, then check if it exists.
			 */
			$valid_me->validate('email_exists',$this->email,$add_user_mail_exists,'','','','','',$this->id);
		}

		/** Password checks */
		if (isset($this->validate_password) && $this->validate_password === true) {
			$valid_me->validate('completed',$this->password,$validation_no_pass);
			$valid_me->validate('password',$this->password,$validation_valid_pass.' '.$validation_valid_chars);
			$valid_me->validate('pass_rules',$this->password,$validation_rules_pass);
			$valid_me->validate('length',$this->password,$validation_length_pass,MIN_PASS_CHARS,MAX_PASS_CHARS);
			//$valid_me->validate('pass_match','',$validation_match_pass,'','',$this->password,$this->password_repeat);
		}

		if ($valid_me->return_val) {
			return 1;
		}
		else {
			return 0;
		}
	}

	/**
	 * Create a new user.
	 */
	function create_device($arguments)
	{
		global $hasher;
		$this->state = array();

		/** Define the account information */
		$this->device_id		= encode_html($arguments['device_id']);
		$this->password		= $arguments['password'];
		$this->ip		= $arguments['ip'];	
		$this->mask		= $arguments['mask'];	
		/*$this->macaddress		= $arguments['macaddress'];*/
		$this->supernode1		= $arguments['supernode1'];		
		$this->supernode2		= $arguments['supernode2'];
		$this->domain		= $arguments['domain'];			
		$this->name				= encode_html($arguments['name']);
		$this->contact				= encode_html($arguments['contact']);
		$this->phone				= encode_html($arguments['phone']);
		$this->address				= encode_html($arguments['address']);		
		$this->email			= encode_html($arguments['email']);
		$this->role				= $arguments['role'];
		$this->active			= $arguments['active'];
		$this->notify_account           = ( $arguments['notify_account'] == '1' ) ? 1 : 0;
		$this->max_user_size	= ( !empty( $arguments['max_user_size'] ) ) ? $arguments['max_user_size'] : 255;
		//$this->enc_password = md5(mysql_real_escape_string($this->password));
		$this->enc_password	= $this->password;//$hasher->HashPassword($this->password);

		if (strlen($this->enc_password) >= 20) {

			$this->state['hash'] = 1;

			$this->timestamp = time();
			$this->sql_query = $this->dbh->prepare("INSERT INTO " . TABLE_DEVICES . " (device_id,password,ip,mask,supernode1,supernode2,domain,name,contact,phone,address,email,level,active,max_user_size)"
												." VALUES (:device_id, :password,:ip,:mask,:supernode1,:supernode2,:domain, :name,:contact,:phone,:address, :email, :role, :active, :max_user_size)");
			$this->sql_query->bindParam(':device_id', $this->device_id);
			$this->sql_query->bindParam(':password', $this->enc_password);
			$this->sql_query->bindParam(':ip', $this->ip);
			$this->sql_query->bindParam(':mask', $this->mask);
			/*$this->sql_query->bindParam(':macaddress', $this->macaddress);*/			
			$this->sql_query->bindParam(':supernode1', $this->supernode1);
			$this->sql_query->bindParam(':supernode2', $this->supernode2);	
			$this->sql_query->bindParam(':domain', $this->domain);			
			$this->sql_query->bindParam(':name', $this->name);
			$this->sql_query->bindParam(':contact', $this->conact);
			$this->sql_query->bindParam(':phone', $this->phone);
			$this->sql_query->bindParam(':address', $this->address);		
			$this->sql_query->bindParam(':email', $this->email);
			$this->sql_query->bindParam(':role', $this->role);
			$this->sql_query->bindParam(':active', $this->active, PDO::PARAM_INT);
			$this->sql_query->bindParam(':max_user_size', $this->max_user_size, PDO::PARAM_INT);

			$this->sql_query->execute();

			if ($this->sql_query) {
				$this->state['query'] = 1;
				$this->state['new_id'] = $this->dbh->lastInsertId();

				/** Send account data by email */
				$this->notify_user = new PSend_Email();
				$this->email_arguments = array(
												'type'		=> 'new_user',
												'address'	=> $this->email,
												'device_id'	=> $this->device_id,
												'password'	=> $this->password
											);
				if ($this->notify_account == 1) {
					$this->notify_send = $this->notify_user->psend_send_email($this->email_arguments);

					if ($this->notify_send == 1){
						$this->state['email'] = 1;
					}
					else {
						$this->state['email'] = 0;
					}
				}
				else {
					$this->state['email'] = 2;
				}
			}
			else {
				$this->state['query'] = 0;
			}
		}
		else {
			$this->state['hash'] = 0;
		}

		return $this->state;
	}

	/**
	 * Edit an existing user.
	 */
	function edit_device($arguments)
	{
		global $hasher;
		$this->state = array();

		/** Define the account information */
		$this->id				= $arguments['id'];
		$this->ip		= $arguments['ip'];	
		$this->mask		= $arguments['mask'];	
		/*$this->macaddress	= $arguments['macaddress'];		*/	
		$this->supernode1		= $arguments['supernode1'];		
		$this->supernode2		= $arguments['supernode2'];
		$this->domain		= $arguments['domain'];	
		$this->name				= encode_html($arguments['name']);
		
		$this->email			= encode_html($arguments['email']);
		$this->role				= $arguments['role'];
		$this->active			= ( $arguments['active'] == '1' ) ? 1 : 0;
		$this->password		= $arguments['password'];
		$this->max_file_size	= ( !empty( $arguments['max_user_size'] ) ) ? $arguments['max_user_size'] : 0;
		//$this->enc_password = md5(mysql_real_escape_string($this->password));
		$this->enc_password 	= $this->password;//$hasher->HashPassword($this->password);

		if (strlen($this->enc_password) >= 20) {

			$this->state['hash'] = 1;

			/** SQL query */
			$this->edit_user_query = "UPDATE " . TABLE_USERS . " SET
									ip = :ip,
									mask = :mask,
									supernode1 = :supernode1
									supernode2 = :supernode2
									domain = :domain,
									name = :name,
									contact = :contact,
									phone = :phone,
									address = :address,
									email = :email,
									level = :level,
									active = :active,
									max_user_size = :max_user_size
									";

			/** Add the password to the query if it's not the dummy value '' */
			if (!empty($arguments['password'])) {
				$this->edit_user_query .= ", password = :password";
			}

			$this->edit_user_query .= " WHERE id = :id";

			$this->sql_query = $this->dbh->prepare( $this->edit_user_query );
			$this->sql_query->bindParam(':name', $this->name);
			$this->sql_query->bindParam(':contact', $this->conact);
			$this->sql_query->bindParam(':phone', $this->phone);
			$this->sql_query->bindParam(':address', $this->address);			
			$this->sql_query->bindParam(':email', $this->email);
			$this->sql_query->bindParam(':level', $this->role);
			$this->sql_query->bindParam(':active', $this->active, PDO::PARAM_INT);
			$this->sql_query->bindParam(':max_file_size', $this->max_file_size, PDO::PARAM_INT);
			$this->sql_query->bindParam(':id', $this->id, PDO::PARAM_INT);
			if (!empty($arguments['password'])) {
				$this->sql_query->bindParam(':password', $this->enc_password);
			}

			$this->sql_query->execute();


			if ($this->sql_query) {
				$this->state['query'] = 1;
			}
			else {
				$this->state['query'] = 0;
			}
		}
		else {
			$this->state['hash'] = 0;
		}

		return $this->state;
	}

	/**
	 * Delete an existing device.
	 */
	function delete_device($device_id)
	{
		$this->check_level = array(9);
		if (isset($device_id)) {
			/** Do a permissions check */
			if (isset($this->check_level) && in_session_or_cookies($this->check_level)) {
				$this->sql = $this->dbh->prepare('DELETE FROM ' . TABLE_DEVICES . ' WHERE id=:id');
				$this->sql->bindParam(':id', $device_id, PDO::PARAM_INT);
				$this->sql->execute();
			}
		}
	}

	/**
	 * Mark the user as active or inactive.
	 */
	function change_device_active_status($device_id,$change_to)
	{
		$this->check_level = array(9);
		if (isset($device_id)) {
			/** Do a permissions check */
			if (isset($this->check_level) && in_session_or_cookies($this->check_level)) {
				$this->sql = $this->dbh->prepare('UPDATE ' . TABLE_DEVICES . ' SET active=:active_state WHERE id=:id');
				$this->sql->bindParam(':active_state', $change_to, PDO::PARAM_INT);
				$this->sql->bindParam(':id', $device_id, PDO::PARAM_INT);
				$this->sql->execute();
			}
		}
	}

}
