<?php
/**
 *  Call the required system files
 */
$allowed_levels = array(9,8,7,0);
require_once('sys.includes.php');
//include('header.php');

define('CAN_INCLUDE_FILES', true);
/**
 * If there is no valid session/user block the upload of files
 */
/*
if (! check_for_session() ) {
	die();
}
*/
$username =  isset($_REQUEST["username"]) ? $_REQUEST["username"] : '';
if($username == '') {
    header("HTTP/1.0 403 no username");
    die("username must not empty");
}else {
    $client_my_info = get_client_by_username($username);
    $client_id = $client_my_info["id"];
    $CURRENT_USER_ID = $client_id;
    $CURRENT_USER_USERNAME = $username;
    $current_level = 0;
    $CURRENT_USER_LEVEL = $current_level; 
}


/**
 * upload.php
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */
// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Settings
$targetDir = UPLOADED_FILES_FOLDER;

$cleanupTargetDir = true; // Remove old files
$maxFileAge = 5 * 3600; // Temp file age in seconds

@set_time_limit(UPLOAD_TIME_LIMIT);

// Uncomment this one to fake upload time
// usleep(5000);

// Get parameters
$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
//$fileName = isset($_REQUEST["myFile"]) ? $_REQUEST["myFile"] : '';
if(!$_FILES['myFile']) {
   header("HTTP/1.0 103 myFile missing");
   die();
}
    $fileName = $_FILES['myFile']['name'];
    $fileSize = $_FILES['myFile']['size'];
    $fileTmpName  = $_FILES['myFile']['tmp_name'];
    $fileType = $_FILES['myFile']['type'];
    //$fileExtension = strtolower(end(explode('.',$fileName)));
 //header("HTTP/1.0 103 Invalid Extension ".$fileName);
 //die();
$this_file = new PSend_Upload_File();
// Rename the file
$file_original = pathinfo($fileName,PATHINFO_FILENAME);
$fileName = $this_file->safe_rename($fileName);

$location = $targetDir.$fileName;
// Validate file has an acceptable extension
$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
$allowedExt = explode(',', $options_values['allowed_file_types'] );
if ( false === CAN_UPLOAD_ANY_FILE_TYPE ) {
    if (!in_array($fileExt, $allowedExt)) {
        header("HTTP/1.0 104 Invalid Extension ".$fileName);
        die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Invalid Extension."}, "id" : "id"}');
    };
}

if(empty($_FILES['myFile'])) {

	header("HTTP/1.0 405 myFile missing");
	die("must be a file to upload");
}
if(empty($fileTmpName)) {

        header("HTTP/1.0 406 myFile tmp_name missing");
        die("must be a file to upload");
}

/**
* 1- Add the file to the database
*/

//$move_arguments = array(
//		'uploaded_name'		=> $_FILES['myFile']['tmp_name'],
//		'filename'		=> $fileName,
//		);
$makehash= sha1($username);
$filename_on_disk = time().'-'.$makehash.'-'.$fileName;
$path = UPLOADED_FILES_FOLDER.'/'.$filename_on_disk;
//header("HTTP/1.0 106 move ".$filename_on_disk);
// die();
if(move_uploaded_file($fileTmpName, $path)) {
       chmod($path, 0644);

//header("HTTP/1.0 105 move ".$makehash);
// die();
//$upload_move	= $this_upload->upload_move($move_arguments);
 //header("HTTP/1.0 106 move ".$filename_on_disk);
 //die();

//if($upload_move) {
$new_filename = $filename_on_disk;
$add_arguments = array(
                      'file_disk'             => $new_filename,
                      'file_original' => $file_original,
                      'name'                  => $file_original,
                      'description'   => '',
                      'uploader'              => $username,
                      'uploader_id'   => $client_id,
                        );

                      $add_arguments['assign_to'] = array($client_id);
                      $add_argumets['hidden'] = '0';
                      $add_arguments['uploader_type'] = 'client';
                      $add_arguments['expires'] = '0';
                      $add_arguments['public'] = '0';
		      $add_arguments['add_to_db'] = true;
  //header("HTTP/1.0 105 add database ".TABLE_FILES);
  //die();
  
        $process_file = $this_upload->upload_add_to_database($add_arguments);
  
	header("HTTP/1.0 200 ok");
	die('{"file_disk" : "'.$fileName.'", "name" : "'.$file_original.'","client_id" : '.$client_id.',"location" :"'.$location.'"}');

}else {
     header("HTTP/1.0 406 move fail : from ".$fileTmpName." to ".$path);
     die("move fail");
}
// Make sure the fileName is unique but only if chunking is disabled
if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
	$ext = strrpos($fileName, '.');
	$fileName_a = substr($fileName, 0, $ext);
	$fileName_b = substr($fileName, $ext);

	$count = 1;
	while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
		$count++;

	$fileName = $fileName_a . '_' . $count . $fileName_b;
}

$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

// Create target dir
if (!file_exists($targetDir))
	@mkdir($targetDir);

// Remove old temp files	
if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
	while (($file = readdir($dir)) !== false) {
		$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

		// Remove temp file if it is older than the max age and is not the current file
		if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
			@unlink($tmpfilePath);
		}
	}

	closedir($dir);
} else
	die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
	

// Look for the content type header
if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
	$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

if (isset($_SERVER["CONTENT_TYPE"]))
	$contentType = $_SERVER["CONTENT_TYPE"];

// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
if (strpos($contentType, "multipart") !== false) {
	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
		// Open temp file
		$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
		if ($out) {
			// Read binary input stream and append it to temp file
			$in = fopen($_FILES['file']['tmp_name'], "rb");

			if ($in) {
				while ($buff = fread($in, 4096))
					fwrite($out, $buff);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			fclose($in);
			fclose($out);
			@unlink($_FILES['file']['tmp_name']);
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
	} else
		die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
} else {
	// Open temp file
	$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
	if ($out) {
		// Read binary input stream and append it to temp file
		$in = fopen("php://input", "rb");

		if ($in) {
			while ($buff = fread($in, 4096))
				fwrite($out, $buff);
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

		fclose($in);
		fclose($out);
	} else
		die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
}

// Check if file has been uploaded
if (!$chunks || $chunk == $chunks - 1) {
	// Strip the temp .part suffix off 
	rename("{$filePath}.part", $filePath);
}

//rotate image
$img_formats = array('jpg','pjpeg','jpeg');
$exif = (array)null;
if (in_array($fileExt,$img_formats)) {
    $exif = exif_read_data($filePath);

    if (!empty($exif['Orientation'])) {
        $image   = imagecreatefromjpeg($filePath);
        switch ($exif['Orientation']) {
            case 3:
                $image_p = imagerotate($image, 180, 0);
        	imagejpeg($image_p, $filePath);
                break;

            case 6:
                $image_p = imagerotate($image, -90, 0);
        	imagejpeg($image_p, $filePath);
                break;

            case 8:
                $image_p = imagerotate($image, 90, 0);
        	imagejpeg($image_p, $filePath);
                break;
        }

    }

}

// Return JSON-RPC response
die('{"jsonrpc" : "2.0", "result" : null, "id" : "id","FileName":"'.$file_original.'", "NewFileName" : "'.$fileName.'"}');
//die('{"jsonrpc" : "2.0", "result" : null, "id" : "id","FileName":"'.$file_original.'", "NewFileName" : "'.$fileName.'","latitude": '.$latitude.',"longitude":'.$longitude.'}');

