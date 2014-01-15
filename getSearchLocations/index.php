<?php
require_once '../../include/constants.php';

$returnModel = array(
		"success" => 0,
		"error" => "",
		"message" => "",
		"data" => null
);

function done($return) {
	$return = json_encode($return);
	
	exit($return);
}



$db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);



if ($db->connect_errno) {
	$returnModel['error'] = $db->connect_error;
	done($returnModel);
}

if(isset($_REQUEST['term']) && !empty($_REQUEST['term'])) {
	$term = mysqli_real_escape_string($db, $_REQUEST['term']);
} else {
	$returnModel['error'] = "no term supplied";
	done($returnModel);
}

function makeSqlKeywordGroups($inputString) {
	$keywords = explode('|', $inputString);
	
	$sqlGroups = array();
	
	// make sql group out of each keyword and put in array
	foreach($keywords as $keyword) {
		$sqlString = "(shops.name LIKE \"%$keyword%\" OR
		shops.address LIKE \"%$keyword%\" OR
		shops.city LIKE \"%$keyword%\" OR
		shops.zipcode LIKE \"%$keyword%\" OR
		chains.name LIKE \"%$keyword%\")";
		
		array_push($sqlGroups, $sqlString);
		
	}
	
	// combine all the sql groups into a single string
	//exit(implode(" and ", $sqlGroups));
	return implode(" and ", $sqlGroups);
}
//makeSqlKeywordGroups($term);
$query = "SELECT shops.id, shops.name, chains.name as chain, shops.address, shops.latitude, shops.longitude, shops.city 
		FROM shops, chains 
		WHERE
			" . makeSqlKeywordGroups($term) . " 
			AND shops.chainID = chains.id";

$returnModel['debug'] = $query;


if(!$res = $db->query($query)) {
	$returnModel['error'] = $db->error;
	done($returnModel);
}

$data = array();
while ($row = $res->fetch_assoc()) {
	array_push($data, $row);
}

$returnModel['success'] = 1;
$returnModel['data'] = $data;
done($returnModel);

