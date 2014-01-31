<?php

require_once './lib/constants.php';
require_once './bootstrap.php';
require_once './User.php';

class Lists extends Bootstrap {


	public function getUserList() {

		$user		= new User();
		$listArr	= array("attrs" => array("name" => null, "count" => 0), "products" => array());
		$listId;

		$user->session();

		if(!$userId = $user->userId) {
			$this->returnModel['error'] = "NO_SESSION";
			return $this->returnModel;
		}

		// get the user's list ID, because legacy idea was multiple lists and so forth
		if($result = $this->_query("SELECT id FROM shoppinglists WHERE shoppinglists.userID = $userId")) {
			$row = $result->fetch_row();
			$listId = $row[0];
		} else {
			return $this->returnModel;
		}

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

		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		while ($row = $result->fetch_assoc()) {
			$listArr['products'][$row['list_item_id']] = $row;
		}

		$this->returnModel['success']	= true;
		$this->returnModel['data']		= $listArr;
		return $this->returnModel;
	}

}