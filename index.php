<?php
require_once './src/Epi.php';
require_once './User.php';
require_once './Lists.php';
require_once './Product.php';
require_once './Location.php';
require_once './Sort.php';
Epi::init('api');

//Epi::setSetting('exceptions', true);

/*
* We create 3 normal routes (think of these are user viewable pages).
* We also create 2 api routes (this of these as data methods).
* The beauty of the api routes are they can be accessed natively from PHP
* or remotely via HTTP.
* When accessed over HTTP the response is json.
* When accessed natively it's a php array/string/boolean/etc.
*/


getApi()->get('/', 													'apiRoot', 					EpiApi::external);
getApi()->get('/user/session', 										'apiSession', 				EpiApi::external);
getApi()->post('/user/login', 										'apiLogin', 				EpiApi::external);
getApi()->post('/user/register', 									'apiRegister', 				EpiApi::external);
getApi()->get('/user/logout', 										'apiLogout', 				EpiApi::external);
getApi()->get('/user', 												'apiGetActiveUserId', 		EpiApi::internal);		// PRIVATE		

getApi()->get('/product/(\d+)', 									'apiGetProduct', 			EpiApi::external);		// product/:productId
getApi()->get('/product/prices/(\d+)/(\d+\.\d+)/(\d+\.\d+)',		'apiGetProductPrices',		EpiApi::external);		// product/prices/:productId/:latitude/:longitude
getApi()->get('/product/prices/update/(\d+)/(\d+)/(\d+([.,]\d+)?)',	'apiUpdatePrice', 			EpiApi::external);		// product/prices/update/:productId/:shopId/:price

getApi()->get('/list', 												'apiGetList', 				EpiApi::external);
getApi()->get('/list/add/(\d+)', 									'apiAddToList', 			EpiApi::external);		// list/add/:productId
getApi()->get('/list/quantity/(\d+)/(\d+)', 						'apiListQuantity',			EpiApi::external);		// list/quantity/:listItemId/:newQuantity
getApi()->get('/list/remove/(\d+)', 								'apiListRemove',			EpiApi::external);		// list/remove/:listItemId
getApi()->get('/list/sort/(\d+\.\d+)/(\d+\.\d+)',					'apiListSort',				EpiApi::external);		// list/sort/:latitude/:longitude
getApi()->get('/list/sort',											'apiListSort',				EpiApi::external);		// list/sort

getApi()->get('/search/(.+)', 										'apiSearch',				EpiApi::external);		// search/:term

getApi()->get('/location/(\d+\.\d+)/(\d+\.\d+)', 					'apiClosestLocations',		EpiApi::external);		// location/:latitude/:longitude
getApi()->get('/location/saved',									'apiSavedLocations',		EpiApi::external);		
getApi()->get('/location/add/(\d+)',								'apiAddLocation',			EpiApi::external);		// location/add/:shopId

getApi()->get('/user/id', 'apiTestUserId', EpiApi::external);


getRoute()->run();



function _getPostData() {
	return json_decode(file_get_contents('php://input'), true);
}

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


function apiGetProductPrices($productId, $latitude, $longitude) {
	$product = new Product();
	return $product->getPrices($productId, $latitude, $longitude);
}

function apiUpdatePrice($productId, $shopId, $price) {
	$product = new Product();
	return $product->updatePrice($productId, $shopId, $price);
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

function apiListQuantity($listItemId, $quantity) {
	$list = new Lists();
	return $list->updateQuantity($listItemId, $quantity);
}

function apiListRemove($listItemId) {
	$list = new Lists();
	return $list->removeFromList($listItemId);
}

function apiListSort($lat=null, $long=null) {
	$sort = new Sort($lat, $long);
	return $sort->getShopLists();
}


function apiSearch($term, $limit = 50) {
	$product = new Product();
	//return array($term, $limit);
	return $product->getSearchResult($term, $limit);
}


function apiClosestLocations($latitude, $longitude, $num=20) {

	$location = new Location();
	return $location->getSurroundingStores($latitude, $longitude, $num);
}

function apiSavedLocations() {
	$location = new Location();
	return $location->getSavedLocations();
}

function apiAddLocation($shopId) {
	$location = new Location();
	return $location->saveLocation($shopId);
}


function apiGetActiveUserId() {
	return User::getActiveUserId();
}

function apiTestUserId() {
	return getApi()->invoke('/user');
}

