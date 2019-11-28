<?php
/**
 * Allows to hide, show or delete the files assigend to the
 * selected client.
 *
 * @package ProjectSend
 */
$footable_min = true; // delete this line after finishing pagination on every table
$load_scripts	= array(
						'footable',
					); 

$allowed_levels = array(9,8,7,0);
require_once('sys.includes.php');

$active_nav = 'files';

$headers = apache_request_headers();

$current_level = get_current_user_level();
if (isset($headers['Authorization'])) {
	$username = $headers['Authorization'];
	$file_id = $_GET['batch'];
	$file_name = isset($_GET["file_name"])?$_GET["file_name"]:$POST['file_name'];
	$client_id = $_GET['modify_id'];
	$name = $_GET['name'];
	$description = $_GET['description'];
	$action = isset($_GET['action'])?$_GET['action']:$_POST['action'];
        $global_user = $username;
        $global_account = get_user_by_username($global_user);
	if(isset($_GET['file_id']))
		$file_id = $_GET['file_id'];
	$file = get_file_by_id($file_id);
	$location = UPLOADED_FILES_FOLDER . $file_name;
	if(empty($file) || !file_exists($location)) {
		die('{"error":"File does not exists!"}');
	}else if($file['uploader'] != $global_account['username']) {
		die('{"error":"uploader not match:'.$file["uploader"].'"}');
	}else {
              switch($action) {
                  case 'delete':
        		if ($file['url'] != $file_name) 
                		die('{"error":"File name not match:'.$file["url"].'"}');
      			$sql = $dbh->prepare("DELETE FROM " . TABLE_FILES . " WHERE id = :file_id");
              		$sql->bindParam(':file_id', $file_id, PDO::PARAM_INT);
              		$sql->execute();
              	/**
              	* Use the id and uri information to delete the file.
              	*
              	* @see delete_file_from_disk
              	*/
              		delete_file_from_disk(UPLOADED_FILES_FOLDER . $file_name);
			$log_action_number = 12;
                	$new_log_action = new LogActions();
                	$log_action_args = array(
                                       'action' => $log_action_number,
                                       'owner_id' => $global_account['id'],
                                       'affected_file' => $file_id,
                                       'affected_file_name' => $file_name
                                        );
                 	if (!empty($name_for_actions)) {
                        	   $log_action_args['affected_account_name'] = $name_for_actions;
                           	$log_action_args['get_user_real_name'] = true;
                    	}
                 	$new_record_action = $new_log_action->log_action_save($log_action_args);

                	$msg = __('The selected files were deleted.','cftp_admin');
                	die('{"ok":"'.$msg.'"}');
			break;
		case 'assign':
                                $sql = "INSERT INTO " . TABLE_FILES_RELATIONS . "  (file_id, client_id,hidden) VALUES(:file_id,:client_id,0)";
                                $statement = $dbh->prepare($sql);
                                $statement->bindParam(':file_id', $file_id, PDO::PARAM_INT);
                                $statement->bindParam(':client_id', $client_id, PDO::PARAM_INT);
                                $statement->execute();
			die('{"ok":"assign"}');
			break;
		case 'unassign':
                                $sql = "DELETE FROM " . TABLE_FILES_RELATIONS . " WHERE file_id = :file_id AND client_id = :modify_id";
                                $statement = $dbh->prepare($sql);
                                $statement->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            			$statement->bindParam(':modify_id', $client_id, PDO::PARAM_INT);
                                $statement->execute();
			die('{"ok":"unassign"}');
			break;
		case 'update':
			        $sql = "UPDATE " . TABLE_FILES . "  SET filename=:filename, description=:description WHERE id = :id";
                                $statement = $dbh->prepare($sql);
                                $statement->bindParam(':filename', $name, PDO::PARAM_STR);
                                $statement->bindParam(':description', $description, PDO::PARAM_STR);
                                $statement->bindParam(':id', $file_id, PDO::PARAM_INT);
                                $statement->execute();
			die('{"ok":"updated"}');
			break;
		default:
			die('{"ok":"action unknown"}');
		}
	}//else {
	//	$msg = __('Some files could not be deleted.','cftp_admin');
	//	die('{"error":"'.$msg.'","url":"'.$file_name.'"}');
	//}

}else {
	header("http 1.0 400 ");
	die('{"error":"No username"}');
}
/**
 * Used to distinguish the current page results.
 * Global means all files.
 * Client or group is only when looking into files
 * assigned to any of them.
 */
$results_type = 'global';

/**
 * The client's id is passed on the URI.
 * Then get_client_by_id() gets all the other account values.
 */
if (isset($_GET['client_id'])) {
	$this_id = $_GET['client_id'];
	$this_client = get_client_by_id($this_id);
	/** Add the name of the client to the page's title. */
	if(!empty($this_client)) {
		$page_title .= ' '.__('for client','cftp_admin').' '.html_entity_decode($this_client['name']);
		$search_on = 'client_id';
		$name_for_actions = $this_client['username'];
		$results_type = 'client';
	}
}

/**
 * The group's id is passed on the URI also.
 */
if (isset($_GET['group_id'])) {
	$this_id = $_GET['group_id'];


	$sql_name = $dbh->prepare("SELECT name from " . TABLE_GROUPS . " WHERE id=:id");
	$sql_name->bindParam(':id', $this_id, PDO::PARAM_INT);
	$sql_name->execute();							

	if ( $sql_name->rowCount() > 0) {
		$sql_name->setFetchMode(PDO::FETCH_ASSOC);
		while( $row_group = $sql_name->fetch() ) {
			$group_name = $row_group["name"];
		}
		/** Add the name of the client to the page's title. */
		if(!empty($group_name)) {
			$page_title .= ' '.__('for group','cftp_admin').' '.html_entity_decode($group_name);
			$search_on = 'group_id';
			$name_for_actions = html_entity_decode($group_name);
			$results_type = 'group';
		}
	}
}

/**
 * Filtering by category
 */
if (isset($_GET['category'])) {
	$this_id = $_GET['category'];
	$this_category = get_category($this_id);
	/** Add the name of the client to the page's title. */
	if(!empty($this_category)) {
		$page_title .= ' '.__('on category','cftp_admin').' '.html_entity_decode($this_category['name']);
		$name_for_actions = $this_category['name'];
		$results_type = 'category';
	}
}


		/**
		 * Apply the corresponding action to the selected files.
		 */


	if(isset($_GET['action'])) {
			/** Continue only if 1 or more files were selected. */
		        if(!empty($_GET['batch'])) {
				$selected_files = array_map('intval',array_unique($_GET['batch']));
				$files_to_get = implode(',',$selected_files);
				/**
				 * Make a list of files to avoid individual queries.
				 * First, get all the different files under this account.
				 */
				$sql_distinct_files = $dbh->prepare("SELECT file_id FROM " . TABLE_FILES_RELATIONS . " WHERE FIND_IN_SET(id, :files)");
				$sql_distinct_files->bindParam(':files', $files_to_get);
				$sql_distinct_files->execute();
				$sql_distinct_files->setFetchMode(PDO::FETCH_ASSOC);
				
				while( $data_file_relations = $sql_distinct_files->fetch() ) {
					$all_files_relations[] = $data_file_relations['file_id']; 
					$files_to_get = implode(',',$all_files_relations);
				}
				
				/**
				 * Then get the files names to add to the log action.
				 */
				$sql_file = $dbh->prepare("SELECT id, filename FROM " . TABLE_FILES . " WHERE FIND_IN_SET(id, :files)");
				$sql_file->bindParam(':files', $files_to_get);
				$sql_file->execute();
				$sql_file->setFetchMode(PDO::FETCH_ASSOC);

				while( $data_file = $sql_file->fetch() ) {
					$all_files[$data_file['id']] = $data_file['filename'];
				}
				switch($_GET['action']) {
					case 'hide':
						/**
						 * Changes the value on the "hidden" column value on the database.
						 * This files are not shown on the client's file list. They are
						 * also not counted on the home.php files count when the logged in
						 * account is the client.
						 */
						foreach ($selected_files as $work_file) {
							$this_file = new FilesActions();
							$hide_file = $this_file->change_files_hide_status('1', $work_file, $_GET['modify_type'], $_GET['modify_id']);
						}
						$msg = __('The selected files were marked as hidden.','cftp_admin');
						die('{"ok","'.$msg.'"}');
						$log_action_number = 21;
						break;

					case 'show':
						/**
						 * Reverse of the previous action. Setting the value to 0 means
						 * that the file is visible.
						 */
						foreach ($selected_files as $work_file) {
							$this_file = new FilesActions();
							$show_file = $this_file->change_files_hide_status('0', $work_file, $_GET['modify_type'], $_GET['modify_id']);
						}
						$msg = __('The selected files were marked as visible.','cftp_admin');
						 die('{"ok","'.$msg.'"}');
						$log_action_number = 22;
						break;


					case 'unassign':
						/**
						 * Remove the file from this client or group only.
						 */
						foreach ($selected_files as $work_file) {
							$this_file = new FilesActions();
							$unassign_file = $this_file->unassign_file($work_file, $_GET['modify_type'], $_GET['modify_id']);
						}
						$msg = __('The selected files were unassigned from this client.','cftp_admin');
						 die('{"ok",".$msg."}');
						if ($search_on == 'group_id') {
							$log_action_number = 11;
						}
						elseif ($search_on == 'client_id') {
							$log_action_number = 10;
						}
						break;

					case 'delete':
						$delete_results	= array(
												'ok'		=> 0,
												'errors'	=> 0,
											);
						foreach ($selected_files as $index => $file_id) {
							$this_file		= new FilesActions();
							$delete_status	= $this_file->delete_files($file_id);

							if ( $delete_status == true ) {
								$delete_results['ok']++;
							}
							else {
								$delete_results['errors']++;
								unset($all_files[$file_id]);
							}
						}

						if ( $delete_results['ok'] > 0 ) {
							$msg = __('The selected files were deleted.','cftp_admin');
							 die('{"ok","'.$msg.'"}');
							$log_action_number = 12;
						}
						if ( $delete_results['errors'] > 0 ) {
							$msg = __('Some files could not be deleted.','cftp_admin');
							die('{"error":"'.$msg.'","CURRENT_USER_USERNAME":"'.$global_account["username"].'","user_level":'.$global_account["level"].'}');
						}
						break;
				}

				/** Record the action log */
				foreach ($all_files as $work_file_id => $work_file) {
					$new_log_action = new LogActions();
					$log_action_args = array(
								'action' => $log_action_number,
								'owner_id' => CURRENT_USER_ID,
								'affected_file' => $work_file_id,
								'affected_file_name' => $work_file
								);
					if (!empty($name_for_actions)) {
						$log_action_args['affected_account_name'] = $name_for_actions;
						$log_action_args['get_user_real_name'] = true;
					}
					$new_record_action = $new_log_action->log_action_save($log_action_args);
				}
			}
			else {
				$msg = __('Please select at least one file.','cftp_admin');
				die('{"error":"'.$msg.'"}');
			}
	}else {
		/**
		 * Global form action
		 */
		$query_table_files = true;

		if (isset($search_on)) {
			$params = array();
			$rq = "SELECT * FROM " . TABLE_FILES_RELATIONS . " WHERE $search_on = :id";
			$params[':id'] = $this_id;

			/** Add the status filter */	
			if (isset($_GET['hidden']) && $_GET['hidden'] != 'all') {
				$set_and = true;
				$rq .= " AND hidden = :hidden";
				$no_results_error = 'filter';
				
				$params[':hidden'] = $_GET['hidden'];
			}

			/**
			 * Count the files assigned to this client. If there is none, show
			 * an error message.
			 */
			$sql = $dbh->prepare($rq);
			$sql->execute( $params );
			
			if ( $sql->rowCount() > 0) {
				/**
				 * Get the IDs of files that match the previous query.
				 */
				$sql->setFetchMode(PDO::FETCH_ASSOC);
				while( $row_files = $sql->fetch() ) {
					$files_ids[] = $row_files['file_id'];
					$gotten_files = implode(',',$files_ids);
				}
			}
			else {
				$count = 0;
				$no_results_error = 'filter';
				$query_table_files = false;
			}
		}

		if ( $query_table_files === true ) {
			/**
			 * Get the files
			 */
			$params = array();
			
			/**
			 * Add the download count to the main query.
			 * If the page is filtering files by client, then
			 * add the client ID to the subquery.
			 */
			$add_user_to_query = '';
			if ( isset($search_on) && $results_type == 'client' ) {
				$add_user_to_query = "AND user_id = :user_id";
				$params[':user_id'] = $this_id;
			}
			$cq = "SELECT files.*, ( SELECT COUNT(file_id) FROM " . TABLE_DOWNLOADS . " WHERE " . TABLE_DOWNLOADS . ".file_id=files.id " . $add_user_to_query . ") as download_count FROM " . TABLE_FILES . " files";
	
			if ( isset($search_on) && !empty($gotten_files) ) {
				$conditions[] = "FIND_IN_SET(id, :files)";
				$params[':files'] = $gotten_files;
			}
	
			/** Add the search terms */	
			if(isset($_GET['search']) && !empty($_GET['search'])) {
				$conditions[] = "(filename LIKE :name OR description LIKE :description)";
				$no_results_error = 'search';
	
				$search_terms			= '%'.$_GET['search'].'%';
				$params[':name']		= $search_terms;
				$params[':description']	= $search_terms;
			}

			/**
			 * Filter by uploader
			 */	
			if(isset($_GET['uploader']) && !empty($_GET['uploader'])) {
				$conditions[] = "uploader = :uploader";
				$no_results_error = 'filter';
	
				$params[':uploader'] = $_GET['uploader'];
			}


			/**
			 * If the user is an uploader, or a client is editing his files
			 * only show files uploaded by that account.
			*/
			$current_level = get_current_user_level();
			if ($current_level == '7' || $current_level == '0') {
				$conditions[] = "uploader = :uploader";
				$no_results_error = 'account_level';
	
				$params[':uploader'] = $global_user;
			}
			
			/**
			 * Add the category filter
			 */
			if ( isset( $results_type ) && $results_type == 'category' ) {
				$files_id_by_cat = array();
				$statement = $dbh->prepare("SELECT file_id FROM " . TABLE_CATEGORIES_RELATIONS . " WHERE cat_id = :cat_id");
				$statement->bindParam(':cat_id', $this_category['id'], PDO::PARAM_INT);
				$statement->execute();
				$statement->setFetchMode(PDO::FETCH_ASSOC);
				while ( $file_data = $statement->fetch() ) {
					$files_id_by_cat[] = $file_data['file_id'];
				}
				$files_id_by_cat = implode(',',$files_id_by_cat);
	
				/** Overwrite the parameter set previously */
				$conditions[] = "FIND_IN_SET(id, :files)";
				$params[':files'] = $files_id_by_cat;
				
				$no_results_error = 'category';
			}
	
			/**
			 * Build the final query
			 */
			if ( !empty( $conditions ) ) {
				foreach ( $conditions as $index => $condition ) {
					$cq .= ( $index == 0 ) ? ' WHERE ' : ' AND ';
					$cq .= $condition;
				}
			}

			/**
			 * Add the order.
			 * Defaults to order by: date, order: ASC
			 */
			$cq .= sql_add_order( TABLE_FILES, 'timestamp', 'desc' );

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
	
			die('{"pagination_count": '.$count_for_pagination.',"condition":"'.$conddition.'"}');
			/** Debug query */
			//echo $cq;
			//print_r( $conditions );
		}
		else {
			$count_for_pagination = 0;
			die('{"page_count":'.$count_for_pagination.',"condition":"'.$condition.'"}');
		}

	}

?>
