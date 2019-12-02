<?php
/**
 * Contains the form that is used when adding or editing users.
 *
 * @package		ProjectSend
 * @subpackage	Users
 *
 */
?>

<script type="text/javascript">
	$(document).ready(function() {
		$("form").submit(function() {
			clean_form(this);

			is_complete(this.add_device_form_name,'<?php echo $validation_no_name; ?>');
			is_complete(this.add_device_form_device_id,'<?php echo $validation_no_device_id; ?>');
			//is_complete(this.add_device_form_email,'<?php echo $validation_no_email; ?>');
			is_complete(this.add_device_form_level,'<?php echo $validation_no_level; ?>');
			is_length(this.add_device_form_device_id,<?php echo MIN_USER_CHARS; ?>,<?php echo MAX_USER_CHARS; ?>,'<?php echo $validation_length_user; ?>');
			is_email(this.add_device_form_email,'<?php echo $validation_invalid_mail; ?>');
			is_alpha_or_dot(this.add_device_form_device_id,'<?php echo $validation_alpha_device; ?>');
			is_number(this.add_device_form_maxusersize,'<?php echo $validation_user_size; ?>');
			
			<?php
				/**
				 * Password validation is optional only when editing a user.
				 */
				if ($device_form_type == 'edit_device' || $device_form_type == 'edit_device_self') {
			?>
					// Only check password if any of the 2 fields is completed
					var password_1 = $("#add_device_form_pass").val();
					//var password_2 = $("#add_device_form_pass2").val();
					if ($.trim(password_1).length > 0/* || $.trim(password_2).length > 0*/) {
			<?php
				}
			?>

						is_complete(this.add_device_form_pass,'<?php echo $validation_no_pass; ?>');
						//is_complete(this.add_device_form_pass2,'<?php echo $validation_no_pass2; ?>');
						is_length(this.add_device_form_pass,<?php echo MIN_PASS_CHARS; ?>,<?php echo MAX_PASS_CHARS; ?>,'<?php echo $validation_length_pass; ?>');
						is_password(this.add_device_form_pass,'<?php $chars = addslashes($validation_valid_chars); echo $validation_valid_pass." ".$chars; ?>');
						//is_match(this.add_device_form_pass,this.add_device_form_pass2,'<?php echo $validation_match_pass; ?>');

			<?php
				/** Close the jquery IF statement. */
				if ($device_form_type == 'edit_device' || $device_form_type == 'edit_device_self') {
			?>
					}
			<?php
				}
			?>

			// show the errors or continue if everything is ok
			if (show_form_errors() == false) { return false; }
		});
	});
</script>

<?php
switch ($device_form_type) {
	case 'new_device':
	    $add_device_data_device_id = uniqid();//generateRandomString(12);
		$submit_value = __('Add device','cftp_admin');
		$disable_device = false;
		$require_pass = true;
		$form_action = 'devices-add.php';
		$extra_fields = true;
		break;
	case 'edit_device':
		$submit_value = __('Save device','cftp_admin');
		$disable_device = true;
		$require_pass = false;
		$form_action = 'devices-edit.php?id='.$device_id;
		$extra_fields = true;
		break;
	case 'edit_device_self':
		$submit_value = __('Update account','cftp_admin');
		$disable_device = true;
		$require_pass = false;
		$form_action = 'devices-edit.php?id='.$device_id;
		$extra_fields = false;
		break;
}
?>
<form action="<?php echo html_output($form_action); ?>" name="adddevice" method="post" class="form-horizontal">
	<div class="form-group">
		<label for="add_device_form_name" class="col-sm-4 control-label"><?php _e('Name','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_name" id="add_device_form_name" class="form-control required" value="<?php echo (isset($add_device_data_name)) ? html_output(stripslashes($add_device_data_name)) : ''; ?>" />
		</div>
	</div>

	<div class="form-group">
		<label for="add_device_form_device_id" class="col-sm-4 control-label"><?php _e('Device ID','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_device_id" id="add_device_form_device_id" class="form-control <?php if (!$disable_device) { echo 'required'; } ?>" maxlength="<?php echo MAX_device_CHARS; ?>" value="<?php echo (isset($add_device_data_device_id)) ? html_output(stripslashes($add_device_data_device_id)) : ''; ?>" <?php if ($disable_device) { echo 'readonly'; } ?> placeholder="<?php _e("Must be alphanumeric",'cftp_admin'); ?>" />
		</div>
	</div>

	<div class="form-group">
		<label for="add_device_form_pass" class="col-sm-4 control-label"><?php _e('Password','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<div class="input-group">
				<input name="add_device_form_pass" id="add_device_form_pass" class="form-control <?php if ($require_pass) { echo 'required'; } ?> password_toggle" type="password" maxlength="<?php echo MAX_PASS_CHARS; ?>" />
				<div class="input-group-btn password_toggler">
					<button type="button" class="btn pass_toggler_show"><i class="glyphicon glyphicon-eye-open"></i></button>
				</div>
			</div>
			<button type="button" name="generate_password" id="generate_password" class="btn btn-default btn-sm btn_generate_password" data-ref="add_device_form_pass" data-min="<?php echo MAX_GENERATE_PASS_CHARS; ?>" data-max="<?php echo MAX_GENERATE_PASS_CHARS; ?>"><?php _e('Generate','cftp_admin'); ?></button>
			<?php echo password_notes(); ?>
		</div>
	</div>
	<div class="form-group">
		<label for="add_device_form_ip" class="col-sm-4 control-label"><?php _e('Device IP','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_ip" id="add_device_form_ip" class="form-control required" value="<?php echo (isset($add_device_data_ip)) ? html_output(stripslashes($add_device_data_ip)) : ''; ?>" placeholder="10.7.0.10" />
		</div>
	</div>
	<div class="form-group">
		<label for="add_device_form_mask" class="col-sm-4 control-label"><?php _e('mask','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_mask" id="add_device_form_mask" class="form-control required" value="<?php echo (isset($add_device_data_mask)) ? html_output(stripslashes($add_device_data_mask)) : ''; ?>" placeholder="<?php _e("255.255.255.0",'cftp_admin'); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="add_device_form_supernode1" class="col-sm-4 control-label"><?php _e('Supernode','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_supernode1" id="add_device_form_supernode1" class="form-control required" value="<?php echo (isset($add_device_data_supernode1)) ? html_output(stripslashes($add_device_data_supernode1)) : ''; ?>" placeholder="<?php _e("ip:port",'cftp_admin'); ?>" />
		</div>
	</div>	
	<div class="form-group">
		<label for="add_device_form_supernode2" class="col-sm-4 control-label"><?php _e('Backup Supernode','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_supernode2" id="add_device_form_supernode2" class="form-control required" value="<?php echo (isset($add_device_data_supernode2)) ? html_output(stripslashes($add_device_data_supernode2)) : ''; ?>" placeholder="<?php _e("ip:port",'cftp_admin'); ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="add_device_form_domain" class="col-sm-4 control-label"><?php _e('Domain','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_domain" id="add_device_form_domain" class="form-control" value="<?php echo (isset($add_device_data_supernode2)) ? html_output(stripslashes($add_device_data_domain)) : ''; ?>" placeholder="<?php _e("?.dajana.cn",'cftp_admin'); ?>" />
		</div>
	</div>			
	<div class="form-group">
		<label for="add_device_form_contact" class="col-sm-4 control-label"><?php _e('Contact','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_contact" id="add_device_form_contact" class="form-control" value="<?php echo (isset($add_device_data_contact)) ? html_output(stripslashes($add_device_data_contact)) : ''; ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="add_device_form_phone" class="col-sm-4 control-label"><?php _e('Phone','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_phone" id="add_device_form_phone" class="form-control" value="<?php echo (isset($add_device_data_phone)) ? html_output(stripslashes($add_device_data_phone)) : ''; ?>" />
		</div>
	</div>	
	<div class="form-group">
		<label for="add_device_form_email" class="col-sm-4 control-label"><?php _e('E-mail','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_email" id="add_device_form_email" class="form-control" value="<?php echo (isset($add_device_data_email)) ? html_output(stripslashes($add_device_data_email)) : ''; ?>" />
		</div>
	</div>
	<div class="form-group">
		<label for="add_device_form_address" class="col-sm-4 control-label"><?php _e('Address','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="add_device_form_address" id="add_device_form_phone" class="form-control" value="<?php echo (isset($add_device_data_address)) ? html_output(stripslashes($add_device_data_address)) : ''; ?>" />
		</div>
	</div>	
		<?php
			if ($extra_fields == true) {
		?>
			<div class="form-group">
				<label for="add_device_form_level" class="col-sm-4 control-label"><?php _e('Role','cftp_admin'); ?></label>
				<div class="col-sm-8">
					<select name="add_device_form_level" id="add_device_form_level" class="form-control">
						<option value="9" <?php echo (isset($add_device_data_level) && $add_device_data_level == '9') ? 'selected="selected"' : ''; ?>><?php echo USER_ROLE_LVL_9; ?></option>
						<option value="8" <?php echo (isset($add_device_data_level) && $add_device_data_level == '8') ? 'selected="selected"' : ''; ?>><?php echo USER_ROLE_LVL_8; ?></option>
						<option value="7" <?php echo (isset($add_device_data_level) && $add_device_data_level == '7') ? 'selected="selected"' : ''; ?>><?php echo USER_ROLE_LVL_7; ?></option>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label for="add_device_form_maxusersize" class="col-sm-4 control-label"><?php _e('Max. usersize','cftp_admin'); ?></label>
				<div class="col-sm-8">
					<div class="input-group">
						<input type="text" name="add_device_form_maxusersize" id="add_device_form_maxusersize" class="form-control" value="<?php echo (isset($add_device_data_maxusersize)) ? html_output(stripslashes($add_device_data_maxusersize)) : ''; ?>" />
						<span class="input-group-addon"></span>
					</div>
					<p class="field_note"><?php _e("Set to 0 to use the default system limit",'cftp_admin'); ?> (<?php echo MAX_FILESIZE; ?> )</p>
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-8 col-sm-offset-4">
					<label for="add_device_form_active">
						<input type="checkbox" name="add_device_form_active" id="add_device_form_active" <?php echo (isset($add_device_data_active) && $add_device_data_active == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Active (user can log in)','cftp_admin'); ?>
					</label>
				</div>
			</div>

			<?php
				if ( $device_form_type == 'new_device' ) {
			?>

					<div class="form-group">
						<div class="col-sm-8 col-sm-offset-4">
							<label for="add_device_form_notify_account">
								<input type="checkbox" name="add_device_form_notify_account" id="add_device_form_notify_account" <?php echo (isset($add_device_data_notify_account) && $add_device_data_notify_account == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Send welcome email','cftp_admin'); ?>
							</label>
						</div>
					</div>
			<?php
				}
			?>

		<?php
			}
		?>

	<div class="inside_form_buttons">
		<button type="submit" name="submit" class="btn btn-wide btn-primary"><?php echo $submit_value; ?></button>
	</div>

	<?php
		if ($device_form_type == 'new_device') {
			$msg = __('This account information will be e-mailed to the address supplied above','cftp_admin');
			echo system_message('info',$msg);
		}
	?>
</form>
