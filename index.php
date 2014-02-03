<?php
require_once './src/Epi.php';
require_once './User.php';
require_once './Lists.php';
require_once './Product.php';
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


getApi()->get('/', 					'apiRoot', 				EpiApi::external);
getApi()->get('/user/session', 		'apiSession', 			EpiApi::external);
getApi()->post('/user/login', 		'apiLogin', 			EpiApi::external);
getApi()->post('/user/register', 	'apiRegister', 			EpiApi::external);
getApi()->get('/user/logout', 		'apiLogout', 			EpiApi::external);
getApi()->get('/user', 				'apiGetActiveUserId', 	EpiApi::internal);		// PRIVATE		
getApi()->get('/list', 				'apiGetList', 			EpiApi::external);
getApi()->get('/product/(\d+)', 	'apiGetProduct', 		EpiApi::external);		// product/:productId
getApi()->get('/list/add/(\d+)', 	'apiAddToList', 		EpiApi::external);		// list/add/:productId

getApi()->get('/user/id', 'apiTestUserId', EpiApi::external);


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

function apiLogout() {
	$user = new User();
	return $user->logout();
}

/**
 * Get a user's list
 * 
 * @return [type]
 */
function apiGetList() {
	$list = new Lists();
	return $list->getUserList();
}


/**
 * Get information on a product by it's id
 * 
 * @param  int $id product ID
 * @return {array|json string}
 */
function apiGetProduct($id) {
	if(!$id) {
		$id = null;
	}

	$product = new Product();
	return $product->getProductInfo($id);
}

/**
 * Add a product to the active user's list.
 * 
 * @param  int $id product ID
 * @return {array|json string}
 */
function apiAddToList($id) {
	 $list 		= new Lists();
	 return $list->addToList($id);
}

function apiGetActiveUserId() {
	return User::getActiveUserId();
}

function apiTestUserId() {
	return getApi()->invoke('/user');
}

