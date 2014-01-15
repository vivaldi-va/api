<?php
/*
 * call checkLogin function in includes/session.php
 * return a boolean value as to whether the
 * session is still active
 */

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













if ($session->checkLogin()) {
	$userRow = _getUserInfo($session->email);
	$statsRow = _getUserStats($session->email);
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
			"logged_in" => 1,
			"user" => $userInfoModel
	);

} else {
	$return = array(
			"logged_in" => 0,
			"error" => "login failed"
	);
}






exit(json_encode($return));