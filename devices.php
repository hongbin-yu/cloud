<?php
/**
 * Show the list of current users.
 *
 * @package		ProjectSend
 * @subpackage	Users
 *
 */
$footable_min = true; // delete this line after finishing pagination on every table
$load_scripts	= array(
						'footable',
					); 

$allowed_levels = array(9);
require_once('sys.includes.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if(!check_for_admin()) {
    return;
}

$active_nav = 'devices';

$page_title = __('Manage devices','cftp_admin');
include('header.php');
?>

<div class="col-xs-12">
<?php

	/**
	 * Apply the corresponding action to the selected users.
	 */
	if(isset($_GET['action'])) {
		/** Continue only if 1 or more users were selected. */
		if(!empty($_GET['batch'])) {
			$selected_devices = $_GET['batch'];
			$devices_to_get = implode( ',', array_map( 'intval', array_unique( $selected_devices ) ) );

			/**
			 * Make a list of users to avoid individual queries.
			 */
			$sql_device = $dbh->prepare( "SELECT id, name FROM " . TABLE_DEVICES . " WHERE FIND_IN_SET(id, :devices)" );
			$sql_device->bindParam(':devices', $devices_to_get);
			$sql_device->execute();
			$sql_device->setFetchMode(PDO::FETCH_ASSOC);
			while ( $data_device = $sql_device->fetch() ) {
				$all_devices[$data_device['id']] = $data_device['name'];
			}


			$my_info = get_user_by_username(get_current_user_username());
			$affected_devices = 0;

			switch($_GET['action']) {
				case 'activate':
					/**
					 * Changes the value on the "active" column value on the database.
					 * Inactive users are not allowed to log in.
					 */
					foreach ($selected_devices as $work_device) {
						$this_device = new DeviceActions();
						$hide_device = $this_user->change_device_active_status($work_device,'1');
					}
					$msg = __('The selected users were marked as active.','cftp_admin');
					echo system_message('ok',$msg);
					$log_action_number = 27;
					break;

				case 'deactivate':
					/**
					 * Reverse of the previous action. Setting the value to 0 means
					 * that the user is inactive.
					 */
					foreach ($selected_devices as $work_device) {
						/**
						 * A user should not be able to deactivate himself
						 */
						if ($work_device != $my_info['id']) {
							$this_device = new DeviceActions();
							$hide_device = $this_device->change_device_active_status($work_device,'0');
							$affected_devices++;
						}
						else {
							$msg = __('You cannot deactivate your own account.','cftp_admin');
							echo system_message('error',$msg);
						}
					}

					if ($affected_devices > 0) {
						$msg = __('The selected users were marked as inactive.','cftp_admin');
						echo system_message('ok',$msg);
						$log_action_number = 28;
					}
					break;

				case 'delete':		
					foreach ($selected_devices as $work_device) {
						/**
						 * A user should not be able to delete himself
						 */
						if ($work_device != $my_info['id']) {
							$this_device = new DeviceActions();
							$delete_device = $this_device->delete_device($work_device);
							$affected_users++;
						}
						else {
							$msg = __('You cannot delete your own account.','cftp_admin');
							echo system_message('error',$msg);
						}
					}
					
					if ($affected_devices > 0) {
						$msg = __('The selected devices were deleted.','cftp_admin');
						echo system_message('ok',$msg);
						$log_action_number = 16;
					}
				break;
			}

			/** Record the action log */
			foreach ($selected_devices as $device) {
				$new_log_action = new LogActions();
				$log_action_args = array(
										'action' => $log_action_number,
										'owner_id' => CURRENT_USER_ID,
										'affected_account_name' => $all_devices[$device]
									);
				$new_record_action = $new_log_action->log_action_save($log_action_args);
			}
		}
		else {
			$msg = __('Please select at least one device.','cftp_admin');
			echo system_message('error',$msg);
		}
	}

	$params	= array();

	$cq = "SELECT * FROM " . TABLE_DEVICES . " WHERE level != '0'";

	/** Add the search terms */	
	if ( isset( $_GET['search'] ) && !empty( $_GET['search'] ) ) {
		$cq .= " AND (name LIKE :name OR device_id LIKE :device OR email LIKE :email)";
		$no_results_error = 'search';

		$search_terms		= '%'.$_GET['search'].'%';
		$params[':name']	= $search_terms;
		$params[':device_id']	= $search_terms;
		$params[':email']	= $search_terms;
	}

	/** Add the role filter */	
	if ( isset( $_GET['role'] ) && $_GET['role'] != 'all' ) {
		$cq .= " AND level=:level";
		$no_results_error = 'filter';

		$params[':level']	= $_GET['role'];
	}
	
	/** Add the active filter */	
	if ( isset( $_GET['active'] ) && $_GET['active'] != '2' ) {
		$cq .= " AND active = :active";
		$no_results_error = 'filter';

		$params[':active']	= (int)$_GET['active'];
	}

	/**
	 * Add the order.
	 * Defaults to order by: name, order: ASC
	 */
	$cq .= sql_add_order( TABLE_DEVICES, 'name', 'asc' );

	/**
	 * Pre-query to count the total results
	*/
	$count_sql = $dbh->prepare( $cq );
	$count_sql->execute($params);
	$count_for_pagination = $count_sql->rowCount();

	/**
	 * Repeat the query but this time, limited by pagination
	 */
	$cq .= " LIMIT :limit_start, :limit_number";
	$sql = $dbh->prepare( $cq );

	$pagination_page			= ( isset( $_GET["page"] ) ) ? $_GET["page"] : 1;
	$pagination_start			= ( $pagination_page - 1 ) * RESULTS_PER_PAGE;
	$params[':limit_start']		= $pagination_start;
	$params[':limit_number']	= RESULTS_PER_PAGE;

	$sql->execute( $params );
	$count = $sql->rowCount();
?>

	<div class="form_actions_left">
		<div class="form_actions_limit_results">
			<?php show_search_form('devices.php'); ?>

			<form action="devices.php" name="devices_filters" method="get" class="form-inline">
				<?php form_add_existing_parameters( array('active', 'role', 'action') ); ?>
				<div class="form-group group_float">
					<select name="role" id="role" class="txtfield form-control">
						<?php
							$roles_options = array(
													'all'	=> __('All roles','cftp_admin'),
													'9'		=> USER_ROLE_LVL_9,
													'8'		=> USER_ROLE_LVL_8,
													'7'		=> USER_ROLE_LVL_7,
												);
							foreach ( $roles_options as $val => $text ) {
						?>
								<option value="<?php echo $val; ?>" <?php if ( isset( $_GET['role'] ) && $_GET['role'] == $val ) { echo 'selected="selected"'; } ?>><?php echo $text; ?></option>
						<?php
							}
						?>
					</select>
				</div>

				<div class="form-group group_float">
					<select name="active" id="active" class="txtfield form-control">
						<?php
							$status_options = array(
													'2'		=> __('All statuses','cftp_admin'),
													'1'		=> __('Active','cftp_admin'),
													'0'		=> __('Inactive','cftp_admin'),
												);
							foreach ( $status_options as $val => $text ) {
						?>
								<option value="<?php echo $val; ?>" <?php if ( isset( $_GET['active'] ) && $_GET['active'] == $val ) { echo 'selected="selected"'; } ?>><?php echo $text; ?></option>
						<?php
							}
						?>
					</select>
				</div>
				<button type="submit" id="btn_proceed_filter_clients" class="btn btn-sm btn-default"><?php _e('Filter','cftp_admin'); ?></button>
			</form>
		</div>
	</div>

	<form action="devices.php" name="devices_list" method="get" class="form-inline">
		<?php form_add_existing_parameters(); ?>
		<div class="form_actions_right">
			<div class="form_actions">
				<div class="form_actions_submit">
					<div class="form-group group_float">
						<label class="control-label hidden-xs hidden-sm"><i class="glyphicon glyphicon-check"></i> <?php _e('Selected devices actions','cftp_admin'); ?>:</label>
						<select name="action" id="action" class="txtfield form-control">
							<?php
								$actions_options = array(
														'none'			=> __('Select action','cftp_admin'),
														'activate'		=> __('Activate','cftp_admin'),
														'deactivate'	=> __('Deactivate','cftp_admin'),
														'delete'		=> __('Delete','cftp_admin'),
													);
								foreach ( $actions_options as $val => $text ) {
							?>
									<option value="<?php echo $val; ?>"><?php echo $text; ?></option>
							<?php
								}
							?>
						</select>
					</div>
					<button type="submit" id="do_action" class="btn btn-sm btn-default"><?php _e('Proceed','cftp_admin'); ?></button>
				</div>
			</div>
		</div>
		<div class="clear"></div>

		<div class="form_actions_count">
			<p><?php _e('Found','cftp_admin'); ?>: <span><?php echo $count_for_pagination; ?> <?php _e('devices','cftp_admin'); ?></span></p>
		</div>

		<div class="clear"></div>

		<?php
			if (!$count) {
				switch ($no_results_error) {
					case 'search':
						$no_results_message = __('Your search keywords returned no results.','cftp_admin');
						break;
					case 'filter':
						$no_results_message = __('The filters you selected returned no results.','cftp_admin');
						break;
				}
				echo system_message('error',$no_results_message);
			}
			
			if ($count > 0) {
				/**
				 * Generate the table using the class.
				 */
				$table_attributes	= array(
											'id'		=> 'devices_tbl',
											'class'		=> 'footable table',
										);
				$table = new generateTable( $table_attributes );

				$thead_columns		= array(
											array(
												'select_all'	=> true,
												'attributes'	=> array(
																		'class'		=> array( 'td_checkbox' ),
																	),
											),
											array(
												'sortable'		=> true,
												'sort_url'		=> 'name',
												'sort_default'	=> true,
												'content'		=> __('Full name','cftp_admin'),
											),
											array(
												'sortable'		=> true,
												'sort_url'		=> 'device_id',
												'content'		=> __('Log in username','cftp_admin'),
												'hide'			=> 'phone',
											),
											array(
												'sortable'		=> true,
												'sort_url'		=> 'email',
												'content'		=> __('E-mail','cftp_admin'),
												'hide'			=> 'phone',
											),
											array(
												'sortable'		=> true,
												'sort_url'		=> 'level',
												'content'		=> __('Role','cftp_admin'),
												'hide'			=> 'phone',
											),
											array(
												'sortable'		=> true,
												'sort_url'		=> 'active',
												'content'		=> __('Status','cftp_admin'),
											),
											array(
												'sortable'		=> true,
												'sort_url'		=> 'max_user_size',
												'content'		=> __('Max. user size','cftp_admin'),
												'hide'			=> 'phone',
											),
											array(
												'sortable'		=> true,
												'sort_url'		=> 'timestamp',
												'sort_default'	=> true,
												'content'		=> __('Added on','cftp_admin'),
												'hide'			=> 'phone,tablet',
											),
											array(
												'content'		=> __('Actions','cftp_admin'),
												'hide'			=> 'phone',
											),
										);
				$table->thead( $thead_columns );

				$sql->setFetchMode(PDO::FETCH_ASSOC);
				while ( $row = $sql->fetch() ) {
					$table->add_row();

					/**
					 * Prepare the information to be used later on the cells array
					 * 1- Get the role name
					 */
					switch( $row["level"] ) {
						case '9': $role_name = USER_ROLE_LVL_9; break;
						case '8': $role_name = USER_ROLE_LVL_8; break;
						case '7': $role_name = USER_ROLE_LVL_7; break;
					}
					 
					/**
					 * 2- Get active status
					 */
					$status_hidden	= __('Inactive','cftp_admin');
					$status_visible	= __('Active','cftp_admin');
					$label			= ($row['active'] == 0) ? $status_hidden : $status_visible;
					$class			= ($row['active'] == 0) ? 'danger' : 'success';
					
					/**
					 * 3- Get account creation date
					 */
					$date = date( TIMEFORMAT_USE, strtotime( $row['timestamp'] ) );


					/**
					 * Add the cells to the row
					 */
					//if ( $row['id'] == 1 ) {
					//	$cell = array( 'content' => '' );
					//}
					//else {
						$cell = array(
									'checkbox'		=> true,
									'value'			=> $row["id"],
									);
					//}
					$tbody_cells = array(
											$cell,
											array(
													'content'		=> html_output( $row["name"] ),
												),
											array(
													'content'		=> html_output( $row["device_id"] ),
												),
											array(
													'content'		=> html_output( $row["email"] ),
												),
											array(
													'content'		=> $role_name,
												),
											array(
													'content'		=> '<span class="label label-' . $class . '">' . $label . '</span>',
												),
											array(
													'content'		=> ( $row["max_user_size"] == '0' ) ? __('Default','cftp_admin') : $row["max_user_size"] . 'mb',
												),
											array(
													'content'		=> $date,
												),
											array(
													'actions'		=> true,
													'content'		=>  '<a href="devices-edit.php?id=' . html_output( $row["id"] ) . '" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i><span class="button_label">' . __('Edit','cftp_admin') . '</span></a>' . "\n"
												),
										);

					foreach ( $tbody_cells as $cell ) {
						$table->add_cell( $cell );
					}
	
					$table->end_row();
				}

				echo $table->render();

				/**
				 * PAGINATION
				 */
				$pagination_args = array(
										'link'		=> 'devices.php',
										'current'	=> $pagination_page,
										'pages'		=> ceil( $count_for_pagination / RESULTS_PER_PAGE ),
									);
				
				echo $table->pagination( $pagination_args );
			}
		?>
	</form>
</div>

<?php
	include('footer.php');