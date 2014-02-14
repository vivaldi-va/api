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

		$count = 0;
		// add all the products to an array
		while ($row = $result->fetch_assoc()) {
			$count++;
			$row['product_name'] = utf8_encode($row['product_name']);
			$listArr['products'][$row['list_item_id']] = $row;
		}

		$listArr['attrs']['count'] = $count;

		$this->returnModel['success']	= true;
		$this->returnModel['data']		= $listArr;
		return $this->returnModel;
	}


	/**
	 * Add a product to a user list
	 * - Check if the product is already in the list
	 * - If so, increase the quantity and add a message indicating as much to the return model.
	 * - Otherwise, insert a new product to the list products table.
	 * 
	 * @param [type] $product
	 */
	public function addToList($productId) {

		$quantity = 1;
		$listId = $this->_getUserListId();
		
		
		if ($listItem = $this->_listHasProduct($productId, $listId)) {
			$quantity = intval($listItem['quantity']) + 1;
			if(!$this->_updateQuantity($listItem['id'], $quantity)) {
				return $this->returnModel;
			}


		} else {
			
			$sql = "INSERT INTO shoppinglistproducts (id, shoppinglistID, productID, quantity) VALUES (null, $listId, $productId, $quantity)";
			
			if(!$this->_query($sql)) {
				return $this->returnModel;
			}
		}

		$this->returnModel['success'] = true;
		return $this->returnModel;
	}


	/**
	 * Public wrapper for updateQuantity, returns the model instead of booleans
	 * 
	 * @param  int $listItemId
	 * @param  int $quantity
	 * @return {array}
	 */
	public function updateQuantity($listItemId, $quantity) {
		if($this->_updateQuantity($listItemId, $quantity)) {
			$this->returnModel['success'] = true;
			$this->returnModel['message'] = "QUANTITY_UPDATED";
		} 

		return $this->returnModel;
	}


	private function _updateQuantity($listItemId, $quantity) {
		if(!$this->_query("UPDATE shoppinglistproducts SET quantity=$quantity WHERE shoppinglistproducts.id = $listItemId")) {
			return false;
		} else {
			return true;
		}
	}



	public function removeFromList($listItemId) {
		if($this->_query("DELETE FROM shoppinglistproducts WHERE shoppinglistproducts.id = $listItemId")) {
			$this->returnModel['success'] = true;
		} 

		return $this->returnModel;
	}



	/**
	 * Using the product ID, check the user's list
	 * If the product is in the list, return the list item info.
	 * 
	 * @param  [type] $productId
	 * @return [type]
	 */
	private function _listHasProduct($productId, $listId) {
		if(!$result = $this->_query("SELECT * FROM shoppinglistproducts WHERE productID = $productId AND shoppingListID = $listId")) {
			return false;
		} 


		if($result->num_rows > 0) {
			return $result->fetch_assoc();
		} else {
			return false;
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

	public static function getUserListId() {
		return _getUserListId();
	}



}