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

/**
 * Make an array of file urls that are on the DB already.
 */
$statement = $dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE active = 1 order by id");
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
                echo '"username" : "'.$row["user"].'",';
        	echo '"name" : "'.$row["name"].'"}';
                $count++;

	}
}
echo "]";
//die('{"id": "test"}');

?>
