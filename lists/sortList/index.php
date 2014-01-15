<?php
require_once '../../../include/product_functions.php';
require_once '../../../include/database.php';
require_once '../../../include/session.php';

global $product_functions, $session, $database;
//$return = array();

$lat = false;
$long = false;
$keyword = false;

$return = array('success' => false);


/**
 * Generate a random token to give each 'sort' a unique id to reffer to when
 * checking products out, or for displaying sort history and so forth.
 * @return string
 */
function token() {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$token = '';
	for ($i = 0; $i < 5; $i++)
	{
		$token .= $characters[rand(0, strlen($characters) - 1)];
	}
	
	return md5($token);
}





/*
 * Are the get variables set, 
 * if not, return an error
 */


//exit(json_encode(array($_REQUEST['lat'], $_REQUEST['long'], $_REQUEST['keyword'])));

if (isset($_REQUEST['lat']) && isset($_REQUEST['long']) && (empty($_REQUEST['keyword']) || $_REQUEST['keyword'] == null)) {
	$lat = $_REQUEST['lat'];
	$long = $_REQUEST['long'];
	//exit("lat 'n' long");
	$shopArray = $database->getShopList($lat, $long);
	//exit(preg_match('/^29.*/', $shopArray));
	if (preg_match('/^29.*/', $shopArray)) {
		$return['error'] = "no server connection";
		exit(json_encode($return));	
	}
}
elseif (isset($_REQUEST['keyword'])) {
	//exit("keyword'n");
	$keyword = $_REQUEST['keyword'];
	$shopArray = $database->selectLocations($keyword);
	if (preg_match('/^29.*/', $shopArray)) {
		$return['error'] = "no server connection";
		exit(json_encode($return));
	}
	
} else {
	$return['error'] = "no location info";
	exit(json_encode($return));
}
//exit(json_encode($shopArray));
//$shopArray = json_decode($_REQUEST['shops'], true);
//json_decode($shopArray, true);

/*if (isset($_REQUEST['location'])) 
{
	$location = json_decode($_REQUEST['location'], true);
}*/

$listID = $_REQUEST['listid'];

/*
if ($sortedList = $product_functions->sortList($location))
{
	echo json_encode($sortedList);
}
else 
{
	echo json_encode(array("success" => false));
}*/

// exit(json_encode($location));



/*
 * get the user list informtion as an array to be used to get the calculated savings
 */
$productInfo = $database->getUserListInfo($session->userid, $listID, $shopArray);
if (preg_match('/^29.*/', $productInfo)) {
	$return['error'] = "no server connection";
	exit(json_encode($return));
}
// exit(json_encode($productInfo));




if($productInfo)
{
	// exit(json_encode($productInfo));
	unset($productInfo['debug']);
	$saved = $product_functions->calcSavings($productInfo);
	// exit(json_encode($saved));
} else {
	$return['error'] = "could not get list info";
	exit(json_encode($return));
}

// Declare the array to be returned
$sortedArray = array();
$numberOfShops = 0;
// exit(json_encode($location));
// Declare a sub-array for each of the sort locations, using the shopID as the key

/*
 * set up the shops arrays with the attributes and default total price values
 */
foreach($shopArray AS $shopID => $shopArray)
{
	
	
	// Shop's array to hold prices and info
	$sortedArray['shops'][$shopArray['id']] = array();
	$sortedArray['shops'][$shopArray['id']]['attributes']['chainName'] = $shopArray['chainName'];
	$sortedArray['shops'][$shopArray['id']]['attributes']['chainLocation'] = $shopArray['shopName'];
	$sortedArray['shops'][$shopArray['id']]['attributes']['total'] = 0;
	$sortedArray['shops'][$shopArray['id']]['attributes']['id'] = $shopArray['id'];
}

// Sub-array for products that have no price
$sortedArray['noprice'] = array();

// Sum total of the sorted list
$sortedArray['attributes']['listtotal'] = 0;
$sortedArray['attributes']['totalsaved'] = 0;

// exit(json_encode($sortedArray));

/*
 * loop through the processed products and add them to their respective stores
 * as well as adding their prices and saving figures to the 
 * respective cumulative amounts.
 * 
 * if there is no price data for a product, add it to the 'noPrice' 
 * sub-array
 */
foreach($saved AS $listItemID => $array)
{
	if($array['minPrice'] === "n/a")
	{
		$sortedArray['noprice'][$listItemID] = array("id" => $array['id'], "listItemId" => $listItemID, "name" => $array['name'], "quantity" => $array['quantity']);
	}
	else
	{
		$sortedArray['shops'][$array['minPrice']['shopID']] ['listItems'][$listItemID] = array("id" => $array['id'], "listItemId" => $listItemID, "shopid" => $array['minPrice']['shopID'], "name" => $array['name'], "quantity" => $array['quantity'], "price" => $array['minPrice']['price'], "saved" => $array['savings']);
		$sortedArray['shops'][$array['minPrice']['shopID']] ['attributes']['total'] += $array['minPrice']['price'] * $array['quantity'];
		$sortedArray['attributes']['listtotal'] += $array['minPrice']['price'] * $array['quantity'];
		$sortedArray['attributes']['totalsaved'] += $array['savings'];
	}
}

/*
 * loop through the sorted array to remove the shops which dont have products
 * to save having the javascript do more work
 */

foreach($sortedArray['shops'] AS $shopId => $shopArray) {
	if (!$shopArray['listItems']) {
		unset($sortedArray['shops'][$shopId]);
	} 
	else {
		$sortedArray['attributes']['num_shops']++;
	}
} 



$sortedArray['attributes']['token'] = uniqid();





if(DEBUG_MODE)
{
	$dumpString = $database->dumpArray($sortedArray);
	$_SESSION['debug_info'] .= "<p>(" . __LINE__ . " ) sorted array: <br> <pre>$dumpString</pre></p>\n";
}

exit(json_encode($sortedArray));