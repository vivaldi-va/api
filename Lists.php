<?php

require_once './lib/constants.php';
require_once './bootstrap.php';
require_once './User.php';

class Lists extends Bootstrap {

	var $user;

	public function Lists() {
		$user = User::getActiveUserId();
	}

	/**
	 * Get the user's id from the user class, and using this select the first shoppinglist
	 * in the shoppinglist table. This is done because of the lack of multiple list support 
	 * for now and it removes the need to do filter the lists on the front-end.
	 *
	 * Once the list, or more importantly it's id, is retrieved, use this to get all the products associated 
	 * with it.
	 * 
	 * @return [type]
	 */
	public function getUserList() {

		
		$listArr	= array("attrs" => array("name" => null, "count" => 0), "products" => array());
		$listId = $this->_getUserListId();

		

		// get the list products from here
		$sql = "SELECT
			products.id AS product_id,
			products.name AS product_name,
			products.picUrl AS product_thumb,
			products.barcode AS barcode,
			products.categoryID,
			shoppinglistproducts.id AS list_item_id,
			shoppinglistproducts.quantity
		FROM products, shoppinglists, shoppinglistproducts, users
		WHERE users.id = shoppinglists.userID 
			AND shoppinglistproducts.shoppinglistID = shoppinglists.id 
			AND shoppinglists.id = $listId 
			AND shoppinglistproducts.productID = products.id
		ORDER BY shoppinglistproducts.id DESC";

		// if the result isn't retrieved, return the returnModel
		// since it's going to be loaded with errors.
		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		// add all the products to an array
		while ($row = $result->fetch_assoc()) {
			$row['product_name'] = utf8_encode($row['product_name']);
			$listArr['products'][$row['list_item_id']] = $row;
		}

		$this->returnModel['success']	= true;
		$this->returnModel['data']		= $listArr;
		return $this->returnModel;
	}


	/**
	 * Add a product to a user list
	 * @param [type] $product
	 */
	public function addToList($product) {

		$quantity = 1;

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
	}

	private function _getUserListId() {

		$userId = User::getActiveUserId();

		// get the user's list ID, because legacy idea was multiple lists and so forth
		if($result = $this->_query("SELECT id FROM shoppinglists WHERE shoppinglists.userID = $userId")) {
			$row = $result->fetch_row();
			return $row[0];
		} else {
			return false;
		}
	}



}