<?php
/**
 * ProjectSend (previously cFTP) is a free, clients-oriented, private file
 * sharing web application.
 * Clients are created and assigned a username and a password. Then you can
 * upload as much files as you want under each account, and optionally add
 * a name and description to them. 
 *
 * ProjectSend is hosted on Google Code.
 * Feel free to participate!
 *
 * @link		http://code.google.com/p/clients-oriented-ftp/
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU GPL version 2
 * @package		ProjectSend
 *
 */
?>
 
		
            <form action="loginorregister.php" name="login_admin" role="form" id="login_form">
				<input type="hidden" name="do" value="login">
				<fieldset>
					<div class="form-group">
						<label for="username"><?php _e('Username','cftp_admin'); ?> / <?php _e('E-mail','cftp_admin'); ?></label>
						<input type="text" name="username" id="username" value="<?php if (isset($sysuser_username)) { echo htmlspecialchars($sysuser_username); } ?>" class="form-control" autofocus />
					</div>

					<div class="form-group">
						<label for="password"><?php _e('Password','cftp_admin'); ?></label>
						<input type="password" name="password" id="password" class="form-control" />
					</div>

					<div class="form-group">
						<label for="language"><?php _e('Language','cftp_admin'); ?></label>
						<select name="language" id="language" class="form-control">
							<?php
								// scan for language files
								$available_langs = get_available_languages();
								foreach ($available_langs as $filename => $lang_name) {
							?>
									<option value="<?php echo $filename;?>" <?php echo ( LOADED_LANG == $filename ) ? 'selected' : ''; ?>>
										<?php
											echo $lang_name;
											if ( $filename == SITE_LANG ) {
												echo ' [' . __('default','cftp_admin') . ']';
											}
										?>
									</option>
							<?php
								}
							?>
						</select>
					</div>


					<!--label for="login_form_remember">
						<input type="checkbox" name="login_form_remember" id="login_form_remember" value="on" />
						<?php _e('Remember me','cftp_admin'); ?>
					</label-->

					<div class="inside_form_buttons">
						<button type="submit" id="submit" class="btn btn-wide btn-primary"><?php echo $login_button_text; ?></button>
					</div>

					<div class="social-login">
						<?php if(GOOGLE_SIGNIN_ENABLED == '1'): ?>
							<a href="<?php echo $auth_url; ?>" name="Sign in with Google" class="google-login"><img src="<?php echo BASE_URI; ?>img/google/btn_google_signin_light_normal_web.png" alt="Google Signin" /></a>
						<?php endif; ?>
					</div>
				</fieldset>
			</form>

			<div class="login_form_links">
				<p id="reset_pass_link"><?php _e("Forgot your password?",'cftp_admin'); ?> <a href="<?php echo BASE_URI; ?>reset-password.php"><?php _e('Set up a new one.','cftp_admin'); ?></a></p>
				<?php
					if (CLIENTS_CAN_REGISTER == '1') {
				?>
						<p id="register_link"><?php _e("Don't have an account yet?",'cftp_admin'); ?> <a href="<?php echo BASE_URI; ?>register.php"><?php _e('Register as a new client.','cftp_admin'); ?></a></p>
				<?php
					} else {
				?>
						<p><?php _e("This server does not allow self registrations.",'cftp_admin'); ?></p>
						<p><?php _e("If you need an account, please contact a server administrator.",'cftp_admin'); ?></p>
				<?php
					}
				?>
			</div>


