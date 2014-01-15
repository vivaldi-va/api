<?php

require '../../include/session.php';

global $session;

//$email = $_GET['email'];
//$jsonInfo = json_decode($_POST['profile_data'], true);

$firstName = $_POST['first_name'];
$lastName = $_POST['last_name'];
$email = $_POST['email'];
$gender = $_POST['gender'];
$city = $_POST['city'];
$birthday = $_POST['birthday'];

$profileInfo = array("gender" => $gender, "city" => $city, "birthday" => $birthday);




// exit(json_encode($jsonInfo));
$pass = md5($firstName . $lastName . $email);
$response = array("success" => true);
$response['message'] = "";
//exit(json_encode($session->getIfUserExists($jsonInfo['email'])));
if (!$session->getIfUserExists($email)) 
{
	$response['message'] .= "email not found \n";
	//exit($jsonInfo['email'] . " " . $pass . " " . $jsonInfo['name']);
	if (!$regStatus = $session->register($email, $pass, $pass, $firstName, $lastName, true, $profileInfo)) 
	{
		$response['message'] .= "registering new user account \n";
		//exit($regStatus);
		$regStatus = json_decode($regStatus, true);
		$response['success'] = false;
		$response['error'] = $regStatus['error'];

		
	}
}
else {
	/*if (!$loginStatus = $session->login($jsonInfo['email'], $pass)) {
		//exit($loginStatus);
		exit($loginStatus);
		$loginStatus = json_decode($loginStatus, true);
		$response['success'] = false;
		$response['error'] = $loginStatus['error'];
	}*/
	$response['message'] .= "logging in w/ facebook \n";
	//exit(json_encode($facebookLogin = $session->facebookLogin($jsonInfo['email'])));
	if (!$facebookLogin = $session->facebookLogin($email)) {
		$response['success'] = false;
		$response['facebookAuth'] = false;
	}
	
}

exit(json_encode($response));


