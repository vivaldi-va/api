<?php

require_once '../../include/database.php';
require_once '../../include/session.php';

global $database, $session;

/*
$stat = $_GET['stat'];

switch ($stat) {
	case 'savedHistory':
	
	exit(json_encode(array(
		"totalSaved" => $database->selectSavedTotal($session->userid))));
		
	
	default:
		exit(json_encode(array('error' => 'no stats requested')));
	break;
}
*/




function getTotalSaved() {
	global $session;
	$db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
	if($db->connect_errno) {
		return(array("error" => $db->connect_error)); 

	}
	
	
	$query = "SELECT sum(saved) as totalSaved FROM shoppinglists, shoppingListProductsHistory WHERE shoppinglists.userID = $session->userid AND shoppinglists.id = shoppingListProductsHistory.shoppingListID GROUP BY shoppingListProductsHistory.shoppingListID";
	
	if(!$res = $db->query($query)) {
		return array("error" => "could not get total saved");
	}
	
	$saved = $res->fetch_array();
	
	
	return array('total_saved' => $saved['totalSaved']);
}




if (isset($_REQUEST['stat'])) {
	switch ($_REQUEST['stat']) {
		case 'savedHistory':
			exit(json_encode(getTotalSaved()));
			break;	
	}
}