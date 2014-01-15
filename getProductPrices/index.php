<?php
require_once '../../include/database.php';

global $database;

/**
 * Get product prices for a set group of shops
 */


$lat = false;
$long = false;
$keyword = false;

$return = array('success' => false);
$return['debug'] = "";
$return['debug'] .= "start; ";
/*
 * Are the get variables set, 
 * if not, return an error
 */
if (isset($_GET['lat']) && isset($_GET['long']) && (empty($_GET['keyword']) || $_GET['keyword'] == null)) {
	$lat = $_GET['lat'];
	$long = $_GET['long'];
	$return['debug'] .= "lat 'n long; ";
	//exit("lat 'n' long");
	$shopArray = $database->getShopList($lat, $long);
	
}
elseif (empty($_GET['keyword']) || $_GET['keyword'] != false) {
	//exit("keyword'n");
	$keyword = $_GET['keyword'];
	$shopArray = $database->selectLocations($keyword);
	$return['debug'] .= "keyword'n; ";
} else {
	$return['error'] = "no location info";
	exit(json_encode($return));
}

//$shopArray = json_decode($_GET['shops'], true);
//json_decode($shopArray, true);

if (!$productID = $_GET['productID']) {
	$return['error'] = "no product ID";
	exit(json_encode($return));
}

//exit(json_encode($shopArray));

$pricesList = array();
$idsArray = array();
foreach ($shopArray AS $valuesArray) {
	$idsArray[] = $valuesArray['id'];
	$pricesList[] = $database->selectProductPrice($productID, $valuesArray['id']);
}

$return['success'] = true;

//exit(json_encode($return));

//print_r($shopArray);
//print_r($pricesList);


foreach ($pricesList as $key => $price) {
	//echo $price;
	if(!isset($price['shopName'])) {
		foreach ($shopArray as $shop) {
			if($shop['id'] == $price['shopID']) {
				$pricesList[$key]['shopName'] = $shop['shopName'];
				$pricesList[$key]['chainName'] = $shop['chainName'];
				$pricesList[$key]['productId'] = $_GET['productID'];
			} 			
		}
	}
}
exit(json_encode($pricesList));