<?php
require_once '../../../include/constants.php';

$connection = mysql_connect ( DB_SERVER, DB_USER, DB_PASS ) or die ( "conn error: " . mysql_error () );
mysql_select_db ( DB_NAME, $connection ) or die ( "conn error: " . mysql_error () );
mysql_set_charset ( 'utf8', $connection );




// echo json_encode(array("success" => $retval));

/**
 * ***************************************************************************************||
 * Add/Remove list-item functions
 * ***************************************************************************************||
 */


$quantity = 1;




$return = array (
		"success" => 0,
		"message" => "" 
);




function _getProductInfo($listID, $productID) {
	$slp = TBL_SHOPPING_LIST_PRODUCTS;
	$sl = TBL_SHOPPING_LISTS;
	$p = TBL_PRODUCTS;
	
	$q = "SELECT $p.id AS productID, $p.name, $slp.id AS listItemID, $p.barcode, $p.picUrl AS pic, $slp.quantity
	FROM  $p, $slp, $sl
	WHERE $p.id = $productID AND
	$slp.shoppingListID = $listID AND
	$slp.productID = $p.id AND
	$sl.id = $slp.shoppingListID";
	$result = mysql_query($q);
	if (!$result) {
		return false;
	} else {
		while($array = mysql_fetch_assoc($result)) {
			return $array;
		}
	}
	//return $result;
	//return mysql_fetch_assoc($result);
}




if (isset ( $_REQUEST ['listID'] ) && isset ( $_REQUEST ['productID'] )) {
	$listID = $_REQUEST ['listID'];
	$productID = $_REQUEST ['productID'];
} else {
	$return['message'] = "no listID or productID received";
	exit ( json_encode ( $return ) );
}


$q_checkForExistingItem = "SELECT shoppinglistproducts.id AS listItemID,
	shoppinglistproducts.productID AS listProductID,
	shoppinglistproducts.quantity AS quantity
	FROM shoppinglistproducts, shoppinglists
	WHERE shoppinglistproducts.productID = $productID AND
	shoppinglistproducts.shoppingListID = $listID AND
	shoppinglists.id = shoppinglistproducts.shoppingListID";

$result_checkForExistingItem = mysql_query ( $q_checkForExistingItem );

/*
 * IF item is already on the list Update the quantity by 1
 */
if ($result_checkForExistingItem && mysql_num_rows ( $result_checkForExistingItem ) > 0) {
	
	while ( $dbArray_checkForExistingItem = mysql_fetch_assoc ( $result_checkForExistingItem ) ) {
		$newQuantity = $dbArray_checkForExistingItem ['quantity'] + $quantity;
		$q_updateQuantity = "UPDATE shoppinglistproducts SET quantity = $newQuantity WHERE shoppinglistproducts.id = " . $dbArray_checkForExistingItem ['listItemID'];
		
		$result = mysql_query ( $q_updateQuantity );
		
		if (!$result) {
			$return['message'] = "could not update product quantity";
			exit(json_encode($return));
		}
		
		$return['data'] = _getProductInfo($listID, $productID);
		$return['message'] = "updated product quantity";
		$return['success'] = 1;
		exit(json_encode($return));
		
	}
	
	
	
	
} else {
	
	$q = "INSERT INTO " . TBL_SHOPPING_LIST_PRODUCTS . "(id, shoppinglistID, productID, quantity) VALUES (null, $listID, $productID, $quantity)";
	
	$result = mysql_query ( $q );
	if (!$result) {
		$return['message'] = "could not add product";
		exit(json_encode($return));
	}
	else 
	{
		
		$return['data'] = _getProductInfo($listID, $productID);
		$return['success'] = 1;
		$return['message'] = "product added and new data received";
		exit(json_encode($return));
	}
}