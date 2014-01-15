<?php

require '../../include/session.php';

global $session;


function _getUserInfo($email) {
	$db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
	
	
	
	if($db->connect_errno) {
		return(array("error" => $db->connect_error)); 
	}
	
	if(!$res = $db->query("SELECT email, firstname, lastname, city, gender, birthday FROM users WHERE email LIKE \"%$email%\"")) {
		return array("error" => "could not get user info for $email");
	}
	
	$row = $res->fetch_assoc();
	
	
	return $row;
	
}


function _getUserStats($email) {
	$db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
	if($db->connect_errno) {
		return(array("error" => $db->connect_error));
	
	}
	
	
	$query = "select sum(shoppingListProductsHistory.saved) as total_saved, count(DISTINCT token) as shopping_trips, sum(shoppingListProductsHistory.price) as total_spent
from users, shoppinglists, shoppingListProductsHistory
where users.email = \"$email\"
AND shoppinglists.userID = users.id
AND shoppingListProductsHistory.shoppingListID = shoppinglists.id
GROUP BY shoppinglists.id
	";
	
	if(!$res = $db->query($query)) {
		return array("error" => "could not get total saved");
	}
	
	$saved = $res->fetch_array();
	
	
	return $saved;
}


// User info model
$userInfoModel = array(
		"email" => null,
		"name" => null,
		"stats" => array(
				"total_spent" => 0,
				"total_saved" => 0,
				"shopping_trips" => 0
		),
		"city" => null,
		"birthday" => null,
		"gender" => null
);


$return = array(
		"logged_in" => false,
		"error" => "login failed" 
		);

if ($session->logged_in)
{
	$return['logged_in'] = true;
}

$email = "";
$pass = "";
$remember = false;

$data = json_decode(file_get_contents('php://input'), true);
//exit($data);

//exit(json_encode(array("test" => $_GET['test'])));
//echo $data['email'];
if (isset($data['email'])) {
	$email = $data['email'];
	//exit(json_encode(array("message" => "email found")));	
}
else {
	$return['error'] = "no email";
	exit(json_encode($return));	
}

if (isset($data['password'])) {
	$pass = $data['password'];
	//exit(json_encode(array("message" => "pass found")));
}
else {
	$return['error'] = "no password";
	exit(json_encode($return));
}

//exit(json_encode($data));

$remember = true;









$loginRes = $session->login($email, $pass, $remember);

if ($loginRes['success']) {
	$userRow = _getUserInfo($email);
	$statsRow = _getUserStats($email);
	// add user info to model
	$userInfoModel['email'] = $userRow['email'];
	$userInfoModel['name'] = $userRow['firstname'] ;
	if(!empty($userRow['lastname']))
		 $userInfoModel['name'] .= " " . $row['lastname'];
	
	$userInfoModel['city'] = $userRow['city'];
	$userInfoModel['birthday'] = $userRow['birthday'];
	$userInfoModel['gender'] = $userRow['gender'];
	$userInfoModel['stats']['total_saved'] = $statsRow['total_saved'];
	$userInfoModel['stats']['shopping_trips'] = $statsRow['shopping_trips'];
	$userInfoModel['stats']['total_spent'] = $statsRow['total_spent'];
	
	$return = array(
			"success" => 1,
			"user" => $userInfoModel,
			
	);
	
} else {
	//$error = json_decode($loginRes);
	$return = json_decode($loginRes);
}



exit(json_encode($return));

