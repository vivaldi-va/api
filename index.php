<?php
include_once './src/Epi.php';
include_once './User.php';
Epi::init('api');
// Epi::setSetting('exceptions', true);

/*
* We create 3 normal routes (think of these are user viewable pages).
* We also create 2 api routes (this of these as data methods).
* The beauty of the api routes are they can be accessed natively from PHP
* or remotely via HTTP.
* When accessed over HTTP the response is json.
* When accessed natively it's a php array/string/boolean/etc.
*/


getApi()->get('/', 'apiRoot', EpiApi::external);
getApi()->get('/session', 'apiSession', EpiApi::external);
getApi()->post('/login', 'apiLogin', EpiApi::external);
getApi()->post('/register', 'apiRegister', EpiApi::external);

getRoute()->run();



function apiRoot() {
	return array("Congratulations, you've found the API for ostosnero");
}


function apiSession() {
	$user = new User();

	return $user->session();
}

function apiLogin() {
	$data = json_decode(file_get_contents('php://input'), true);
	$user = new User();
	return $user->login($data);
}

function apiRegister() {
	$data = json_decode(file_get_contents('php://input'), true);
	$user = new User();
	return $user->register($data);
}

function apiPost() {
	$data = json_decode(file_get_contents('php://input'), true);
	return $data;
}