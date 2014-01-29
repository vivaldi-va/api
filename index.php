<?php
chdir('.');
include_once './src/Epi.php';
include_once './User.php';
Epi::setPath('base', './src');
Epi::init('api');

/*
* We create 3 normal routes (think of these are user viewable pages).
* We also create 2 api routes (this of these as data methods).
* The beauty of the api routes are they can be accessed natively from PHP
* or remotely via HTTP.
* When accessed over HTTP the response is json.
* When accessed natively it's a php array/string/boolean/etc.
*/
getApi()->get('/session', 'apiSession', EpiApi::external);
getApi()->post('/login', 'apiLogin', EpiApi::external);
getApi()->post('/post', 'apiPost', EpiApi::external);
getRoute()->run();


function apiSession() {
	$user = new User();
	return $user->session();
}

function apiLogin() {
	$data = file_get_contents('php://input');
	$user = new User();
	return $user->login($data);
}

function apiPost() {
	$data = file_get_contents('php://input');
	return $data;
}