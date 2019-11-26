<?php
/**
 * Uploading files, step 2
 *
 * This file handles all the uploaded files, whether you are
 * coming from the "Upload from computer" or "Find orphan files"
 * pages. The only difference is from which POST array it takes
 * the information to list the available files to process.
 *
 * It can display up tp 3 tables:
 * One that will list all the files that were brought in from
 * the first step. One with the confirmed uploaded and assigned
 * files, and a possible third one with the ones that failed.
 *
 * @package ProjectSend
 * @subpackage Upload
 */
define('IS_FILE_EDITOR', true);

$load_scripts	= array(
						'datepicker',
						'footable',
						'chosen',
						'ckeditor'
					);

$allowed_levels = array(9,8,7,0);
require_once('sys.includes.php');

$active_nav = 'files';
$debug_message ="";

$page_title = __('Upload files', 'cftp_admin');
//include('header.php');

define('CAN_INCLUDE_FILES', true);
?>

<?php
/**
 * Get the user level to determine if the uploader is a
 * system user or a client.
 */
$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
$headers = apache_request_headers();
$username =  isset($headers["Authorization"]) ? $headers["Authorization"] : '';
if($username == '') {
    header("HTTP/1.0 408 no username ".$chunk);
    die("username must not empty");
}

$current_level = get_current_user_level();

$work_folder = UPLOADED_FILES_FOLDER;

/** Coming from the web uploader */
if(isset($_POST['finished_files'])) {
	$uploaded_files = array_filter($_POST['finished_files']);
}
if(isset($_POST['finished_names'])) {
        $uploaded_names = array_filter($_POST['finished_names']);
}
/** Coming from upload by FTP */
if ( isset($_POST['add'] ) ) {
	$uploaded_files = $_POST['add'];
}

/**
 * A hidden field sends the list of failed files as a string,
 * where each filename is separated by a comma.
 * Here we change it into an array so we can list the files
 * on a separate table.
 */
if(isset($_POST['upload_failed'])) {
	$upload_failed_hidden_post = array_filter(explode(',',$_POST['upload_failed']));
}
/**
 * Files that failed are removed from the uploaded files list.
 */
if(isset($upload_failed_hidden_post) && count($upload_failed_hidden_post) > 0) {
	foreach ($upload_failed_hidden_post as $failed) {
		$delete_key = array_search($failed, $uploaded_files);
		unset($uploaded_files[$delete_key]);
	}
}

/** Define the arrays */
$upload_failed = array();
$move_failed = array();

/**
 * $empty_fields counts the amount of "name" fields that
 * were not completed.
 */
$empty_fields = 0;

/** Fill the users array that will be used on the notifications process */
$users = array();
$statement = $dbh->prepare("SELECT id, name, level FROM " . TABLE_USERS . " ORDER BY name ASC");
$statement->execute();
$statement->setFetchMode(PDO::FETCH_ASSOC);
while( $row = $statement->fetch() ) {
	$users[$row["id"]] = $row["name"];
	if ($row["level"] == '0') {
		$clients[$row["id"]] = $row["name"];
	}
}

/** Fill the groups array that will be used on the form */
$groups = array();
$statement = $dbh->prepare("SELECT id, name FROM " . TABLE_GROUPS . " ORDER BY name ASC");
$statement->execute();
$statement->setFetchMode(PDO::FETCH_ASSOC);
while( $row = $statement->fetch() ) {
	$groups[$row["id"]] = $row["name"];
}

/** Fill the categories array that will be used on the form */
$categories = array();
$get_categories = get_categories();

/**
 * Make an array of file urls that are on the DB already.
 */
$statement = $dbh->prepare("SELECT DISTINCT url FROM " . TABLE_FILES . " WHERE uploader like '".$username."'");
$statement->execute();
$statement->setFetchMode(PDO::FETCH_ASSOC);
while( $row = $statement->fetch() ) {
	$urls_db_files[] = $row["url"];
}

// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/**
 * A posted form will include information of the uploaded files
 * (name, description and client).
 */

    $fileName = $_FILES['myFile']['name'];
    $fileSize = $_FILES['myFile']['size'];
    $fileTmpName  = $_FILES['myFile']['tmp_name'];
    $fileType = $_FILES['myFile']['type'];
    $filePath = $_POST['filepath'];
    $lastModified = $_POST['lastModified'];
 

	//if (isset($_POST['submit'])) {
          if (!empty($fileName)) {
		/**
		 * Get the ID of the current client that is uploading files.
		 */
		$global_user = $username;
		if ($current_level == 0) {
			$client_my_info = get_client_by_username($global_user);
			$client_my_id = $client_my_info["id"];
		}

		$n = 0;

		//foreach ($_FILES['myFile'] as $file) {
			$n++;
			
			if(!empty($fileTmpName)) {
				/**
				* If the uploader is a client, set the "client" var to the current
				* uploader username, since the "client" field is not posted.
				*/
				if ($current_level == 0) {
					$file['assignments'] = 'c'.$global_user;
				}

				$this_upload = new PSend_Upload_File();

				if (!in_array($fileName,$urls_db_files)) {
					$safe_name = $this_upload->safe_rename($fileName);
					//$file['file'] = $this_upload->safe_rename($file['file']);
				}
				//$location = $work_folder.'/'.$file['file'];
				$location = $fileTmpName;
				if(file_exists($location)) {
					/**
					 * If the file isn't already on the database, rename/chmod.
					 */
					//if (!in_array($file['file'],$urls_db_files)) {
					if (!in_array($safe_name,$urls_db_files)) {
						$move_arguments = array(
						'uploaded_name'		=> $location,
						'filename'		=> $safe_name,
						);
						$upload_move		= $this_upload->upload_move($move_arguments);
						$new_filename		= $upload_move['filename_disk'];
						$original_filename	= $upload_move['filename_original'];
					}
					else {
						$new_filename = $fileName;
					}
					if (!empty($new_filename)) {
						$delete_key = array_search($file['name'], $uploaded_files);
                        			//$delete_key = array_search($file['file'], $uploaded_files);
						unset($uploaded_files[$delete_key]);
                        
                        			$new_filename = basename($new_filename);
                        			$original_filename = basename($original_filename);
						$name = pathinfo($fileName,PATHINFO_FILENAME);
						/**
						 * Unassigned files are kept as orphans and can be related
						 * to clients or groups later.
						 */

						/** Add to the database for each client / group selected */
						$add_arguments = array(
									'file_disk'		=> $new_filename,
									'file_original'	=> $fileName,
									'name'	=> $name,
                                                                        'filepath' => $filePath,
                                                                        'lastmodified' => $lastModified,
									'description'	=> $file['description'],
									'uploader'		=> $global_user,
									'uploader_id'	=> $client_my_id,//CURRENT_USER_ID,
									);

						/** Set notifications to YES by default */
						$send_notifications = true;

						if (!empty($file['hidden'])) {
							$add_arguments['hidden'] = $file['hidden'];
							$send_notifications = false;
						}

						if (!empty($file['assignments'])) {
							$add_arguments['assign_to'] = $file['assignments'];
							$assignations_count	= count($file['assignments']);
						}
						else {
							$assignations_count	= '0';
						}

						/** Uploader is a client */
						if ($current_level == 0) {
							$add_arguments['assign_to'] = array('c'.$client_my_id);
							$add_arguments['hidden'] = '0';
							$add_arguments['uploader_type'] = 'client';
							$add_arguments['expires'] = '0';
							$add_arguments['public'] = '0';
						}
						else {
							$add_arguments['uploader_type'] = 'user';
							if (!empty($file['expires'])) {
								$add_arguments['expires'] = '1';
								$add_arguments['expiry_date'] = $file['expiry_date'];
							}
							if (!empty($file['public'])) {
								$add_arguments['public'] = '1';
							}
						}

						if (!in_array($new_filename,$urls_db_files)) {
							$add_arguments['add_to_db'] = true;
						}

						/**
						 * 1- Add the file to the database
						 */
						$process_file = $this_upload->upload_add_to_database($add_arguments);
						//header("HTTP/1.0 405 after add db id=".$client_my_id);
						//die();
						if($process_file['database'] == true) {
							$add_arguments['new_file_id']	= $process_file['new_file_id'];
							$add_arguments['all_users']		= $users;
							$add_arguments['all_groups']	= $groups;
							/**
							 * 2- Add the assignments to the database
							 */
							$process_assignment = $this_upload->upload_add_assignment($add_arguments);

							/**
							 * 3- Add the assignments to the database
							 */
							$categories_arguments = array(
											'file_id'		=> $process_file['new_file_id'],
											'categories'	=> !empty( $file['categories'] ) ? $file['categories'] : '',
										);
							$this_upload->upload_save_categories( $categories_arguments );

							/**
							 * 4- Add the notifications to the database
							 */
							if ($send_notifications == true) {
								$process_notifications = $this_upload->upload_add_notifications($add_arguments);
							}
							/**
							 * 5- Mark is as correctly uploaded / assigned
							 */
							$upload_finish[$n] = array(
										'file_id'		=> $add_arguments['new_file_id'],
										'file'			=> $file['file'],
										'name'			=> htmlspecialchars($file['name']),
										'description'	=> htmlspecialchars($file['description']),
										'new_file_id'	=> $process_file['new_file_id'],
										'assignations'	=> $assignations_count,
										'public'		=> !empty( $add_arguments['public'] ) ? $add_arguments['public'] : 0,
										'public_token'	=> !empty( $process_file['public_token'] ) ? $process_file['public_token'] : null,
										);
							if (!empty($file['hidden'])) {
								$upload_finish[$n]['hidden'] = $file['hidden'];
							}
						}
					}
				}else {
					header("HTTP/1.0 405 upload file not exists");
					die();
				}
			}
			else {
				//$empty_fields++;
 				header("HTTP/1.0 401 no file found username ".$username);
				die("username missing");
			}
		//}
	}
	header("Content-Type: application/json");
	die('{"id" : '.$process_file["new_file_id"].',"url" : "'.$new_filename.'","public_token" : "'.$process_file["public_token"].'","uploader" : "'.$global_user.'"}');
?>
