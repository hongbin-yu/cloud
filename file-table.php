<?php
/*
*	query table files and generate json file
*
*/

$allowed_levels = array(9,8,7,0);
require_once('sys.includes.php');

$headers = apache_request_headers();
$username =  isset($headers["Authorization"]) ? $headers["Authorization"] : 'hongbinyu';
if($username == '') {
    header("HTTP/1.0 401 No authorization ");
    //die("username must not empty");
}
header("HTTP/1.0 200");
header("Content-Type: application/json");
$work_folder = UPLOADED_FILES_FOLDER;

$maxId = 0;

if(isset($_GET["maxId"])) {
    $maxId = $_GET['maxId'];
}
/**
 * Make an array of file urls that are on the DB already.
 */
$statement = $dbh->prepare("SELECT * FROM " . TABLE_FILES . " WHERE uploader like '".$username."' and id > ".$maxId);
$statement->execute();
$statement->setFetchMode(PDO::FETCH_ASSOC);

$numResults = $statement->rowCount();
$counter = 0;
echo "[";
while( $row = $statement->fetch() ) {
	$location = $work_folder.$row['url'];
	if(file_exists($location)) {
		$size = filesize($location);
        	if($count > 0)
                echo ",\r\n";
		echo '{ "id" : '.$row["id"].',';
        	echo '"url" : "'.$row["url"].'",';
        	echo '"original_url" : "'.$row["original_url"].'",';
        	echo '"filename" : "'.$row["filename"].'",';
                echo '"filepath" : "'.$row["filepath"].'",';
        	echo '"description" : "'.$row["description"].'",';
                echo '"lastModified" : "'.$row["lastmodified"].'",';
        	echo '"timestamp" : "'.$row["timestamp"].'",';
        	echo '"size" : '.$size.',';
		echo '"uploader" : "'.$row["uploader"].'",';
        	echo '"expires" : '.$row["expires"].',';
        	echo '"expiry_date" : "'.$row["expiry_date"].'",';
        	echo '"public_allow" : '.$row["public_allow"].',';
        	echo '"public_token" : "'.$row["public_token"].'"}';
                $count++;

	}
}
echo "]";
//die('{"id": "test"}');

?>
