<?php

require_once '../../include/constants.php';
require_once '../../include/session.php';

global $session;

/* Make connection to database */
		$connection = mysql_connect(DB_SERVER, DB_USER, DB_PASS) or die(__LINE__ . mysql_error());
		mysql_select_db(DB_NAME, $connection) or die(__LINE__ . mysql_error());
		mysql_set_charset('utf8', $connection);



$token = $_REQUEST['token'];
$product = $_REQUEST['productid'];
$listId = $_REQUEST['listid'];
$shopId = $_REQUEST['shopid'];
$time = date('Y-m-d H:i:s');
$price = $_REQUEST['price'];
$quantity = $_REQUEST['quantity'];
$saved = $_REQUEST['saved'];

if (!$price || $price == null) {
	$price = 0;
}

if (!$saved || $saved == null) {
	$saved = 0;
}

function _message($message, $success=1) {
	return json_encode(array(
			"message" => $message,
			"success" => $success
	));
}

/**
 * Check whether the item is in the product history table, 
 * if so that means it's been checked out.
 * @return boolean: true if it finds item, else false.
 */
function _checkIfExists($token, $list, $product) {
	$query = "SELECT * FROM " . TBL_SHOPPING_LIST_PRODUCTS_HISTORY . " WHERE 
			ProductID = $product AND 
			shoppingListID = $list AND token = \"$token\"";
	
	
	
	if ($result = mysql_query($query)) {

		
		if (mysql_num_rows($result) >= 1) {
			return true;
		}
		else {
			return false;
		}

	} else {
		exit(_message(mysql_error()));
	}
}
/**
 * insert the checked out item to the table.
 * @return boolean
 */
function _addToHistory($token, $listId, $product, $quantity, $time, $price, $saved, $shop) {
	$query = "INSERT INTO " . TBL_SHOPPING_LIST_PRODUCTS_HISTORY . " 
			 (id, token, shoppingListId, ProductID, quantity, created, price, saved, shopID)
			VALUES (NULL, \"$token\", $listId, $product, $quantity, \"$time\", $price, $saved, $shop)";
	
	if ($result = mysql_query($query)) {
		return true;
	} 
	/*else {
		exit(_message(mysql_error()));
	}*/

	return false;
}


function _removeFromHistory($token, $listId, $product) {
	$query = "DELETE FROM " . TBL_SHOPPING_LIST_PRODUCTS_HISTORY . "
	WHERE token = \"$token\" AND ProductID = $product AND shoppingListID = $listId";
	
	if ($result = mysql_query($query)) {
		return true;
	}
	
	return false;	
}


// exit(json_encode(array(
// 	"token" => $token,
// 		"product" => $product,
// 		"list" => $listId,
// 		"shop" => $shopId,
// 		"time" => $time
// )));


//exit(_addToHistory());
if (_checkIfExists($token, $listId, $product)) 
{	
	//exit(_message("exists"));
	if (_removeFromHistory($token, $listId, $product)) {
		exit(json_encode(array(
			'success' => 1,
			'message' => 'product un-checked out',
			'checked' => 0
		)));
	} else {
		exit(_message("could not un-checkout product", 0));
	}
} 
else {
	//exit(_message("doesnt exist"));
	if (_addToHistory($token, $listId, $product, $quantity, $time, $price, $saved, $shopId)) {
		exit(json_encode(array(
			'success' => 1,
			'message' => 'product checked out',
			'checked' => 1
		)));
	} else {
		exit(_message("could not checkout product", 0));
	}
}

