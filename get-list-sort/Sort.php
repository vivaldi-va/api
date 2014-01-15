<?php

require_once '../../include/constants.php';

class Sort {
	
	var $email = null;
	var $user = null;
	var $coords = null;
	
	
	public function Sort($email, $coords) {
		$this->email = $email;		
		$this->coords = $coords;	
	}
	
	/**
	 * Split the user list between stores
	 */
	public function getSplitList() {
		$this->user = $this->_getUserId();
		
		//$stores = $this->_getStores();
		//return $stores;
		
		return $this->_getListProducts();
	}
	
	/**
	 * Return list of all stores and list of products and their prices
	 * as well as total store price
	 */
	public function getStorePrices() {
		$this->user = $this->_getUserId();
		return $this->_getListPrices();
	}
	
	/**
	 * get stores from either user-defined stores or locations
	 * or the user's coordinates if they are supplied
	 */
	private function _getStores() {
		
		$storeArr = array();	// array of shop ids
		$storeInfoArr = array();
		if($this->coords) {
			foreach($nearLocations = $this->_getNearbyLocations() as $id) {
				array_push($storeArr, $id);
			} 
		}

		/*
		 * determine if any locations were found
		 */
		if($savedLocations = $this->_getSavedLocations()) {
			foreach($savedLocations as $storeId) {
				array_push($storeArr, $storeId);
			}
		} 
		
		if(empty($storeArr)) {
			return array("error" => "no locations found");
		}
		
		
		
		$storeInfoArr = $this->_getShopInfo($storeArr);
		
		
		return $storeInfoArr;
	}
	
	private function _getListProducts() {
		
		$productArr = [];
		
		// get list ID
		$listIdRes = $this->_query("select id from shoppinglists where shoppinglists.userID = $this->user order by id limit 1");
		$listId = $listIdRes->fetch_row();
		
		// get list products
		$sql = "SELECT
				" . TBL_PRODUCTS . ".id AS productID,
				" . TBL_PRODUCTS . ".name,
				" . TBL_PRODUCTS . ".picUrl,
				" . TBL_PRODUCTS . ".barcode,
				" . TBL_PRODUCTS . ".categoryID,
				" . TBL_SHOPPING_LIST_PRODUCTS . ".id AS listItemID,
				" . TBL_SHOPPING_LIST_PRODUCTS . ".quantity
				FROM
				" . TBL_PRODUCTS . ", " . TBL_SHOPPING_LISTS . ", " . TBL_SHOPPING_LIST_PRODUCTS . ", " . TBL_USERS . "
				WHERE
				" . TBL_USERS . ".id = " . TBL_SHOPPING_LISTS . ".userID AND
				" . TBL_USERS . ".id = $this->user AND
				" . TBL_SHOPPING_LIST_PRODUCTS . ".shoppinglistID = " . TBL_SHOPPING_LISTS . ".id AND
				" . TBL_SHOPPING_LISTS . ".id = $listId[0] AND
				" . TBL_SHOPPING_LIST_PRODUCTS . ".productID = " . TBL_PRODUCTS . ".id
				ORDER BY " . TBL_SHOPPING_LIST_PRODUCTS . ".id DESC";
		
		$res = $this->_query($sql);
		while($row = $res->fetch_assoc()) {
			array_push($productArr, $row);
		}
		
		return $productArr;
	}
	
	
	private function _getListPrices() {
		
		
		
		// get the shops and prices relavent to the user
		$shopsArr = $this->_getStores();
		$productArr = $this->_getListProducts();
		
		$shopIdArr = array();
		$productIdArr = array();
		
		
		// bootstrap the return model
		$returnArr = array(
				"shops" => array(),
				"attrs" => array(
						"num_list_products" => 0
				)
		);
		
		// give each shop an array in the return object
		foreach($shopsArr as $arr) {
			//return $shopsArr;
			$returnArr['shops'][$arr['id']] = array(
				"attrs" => array(
					"total" => 0,
					"distance" => null,
					"num_products" => 0,
					"num_missing_products" => 0,
					"worth" => 0
				),
				"products" => array()
			);
			array_push($shopIdArr, $arr['id']);
		}
		
		foreach($productArr as $arr) {
			array_push($productIdArr, $arr['productID']);
		}
		
		// count the user's shopping list products
		$returnArr['attrs']['num_list_products'] = count($productIdArr);
		
		// format the IN string groups for shops and products 
		// to be used in SQL query
		$shopInStr = "IN (" . implode(', ', $shopIdArr) . ")";
		
		$prodInStr = "IN (" . implode(', ', $productIdArr) . ")";
		
		
		
		$sql = "SELECT 
			p1.ID, p1.productId, p1.shopID, p1.created, p1.price
			FROM prices p1
			INNER JOIN
			(SELECT * FROM prices  
			WHERE shopID $shopInStr AND productId $prodInStr
			ORDER BY created DESC
			) p2
			ON p1.ID = p2.ID
			GROUP BY p1.productId, p1.shopID";
		//echo $sql;
		$res = $this->_query($sql);
		
		while($row = $res->fetch_assoc()) {
			$returnArr['shops'][$row['shopID']]['attrs']['total'] += $row['price'];
			$returnArr['shops'][$row['shopID']]['attrs']['num_products'] += 1;
			array_push($returnArr['shops'][$row['shopID']]['products'], $row);
		}
		
		foreach($returnArr['shops'] as $id => $arr) {
			//echo $arr['attrs']['num_products'] > 0;
			if($arr['attrs']['num_products'] > 0) {
				
				$worth = ($returnArr['attrs']['num_list_products'] * $arr['attrs']['num_products']) / $arr['attrs']['total'];
				//echo $worth . "\r\n";
			} else {
				$worth = 0;
			}
			$returnArr['shops'][$id]['attrs']['worth'] = $worth;
		}
		
		
		
		return $returnArr;
		
	}
	
	
	
	
	
	private function _getNearbyLocations() {
		$lat = $this->coords['lat'];
		$long = $this->coords['long'];
		$storeArr = array();
		$sql = "select shops.id, shops.name, chains.name as chain, shops.address, shops.latitude, shops.longitude, shops.city,
				truncate ((( 6371 * acos( cos( radians( $lat ) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians( $long ) ) + sin( radians( $lat ) ) * sin( radians( latitude ) ) ) )*1000),2) AS distance 
				from shops, chains
				where longitude is not null AND
				chains.id = shops.chainID 
				order by distance asc 
				LIMIT 0,5";
		
		if($res = $this->_query($sql)) {
				
			/*
			 * check if any locations for the user have been found
			*/
			if($res->num_rows > 0) {
				while($row = $res->fetch_assoc()) {
					array_push($storeArr, $row['id']);
				}
			} else {
				return false;
			}
				
		}
		
		return $storeArr;
	}
	
	
	/**
	 * get the list of locations the user has saved
	 * 
	 * @return array storeArr: list of shop IDs
	 */
	private function _getSavedLocations() {
		$storeArr = array();
		$sql = "SELECT shopid FROM locations WHERE userid = $this->user";
		
		if($res = $this->_query($sql)) {
			
			/*
			 * check if any locations for the user have been found
			 */
			if($res->num_rows > 0) {
				while($row = $res->fetch_assoc()) {
					array_push($storeArr, $row['shopid']);
				}
			} else {
				return false;
			}
			
		}
		
		
		
		
		return $storeArr;
	}
	
	
	
	
	
	
	
	private function _getShopInfo($idArr) {
		
		$idString = "shops.id = " . implode(' OR shops.id = ', $idArr);
		$storeInfoArr = array();
		
		$sql = "SELECT
		shops.id, shops.name, chains.name as chain, shops.address, shops.latitude, shops.longitude, shops.city
		FROM
		shops, chains
		WHERE
		($idString) AND
		shops.chainID = chains.id";
		
		//echo $sql;
		
		if ($res = $this->_query ( $sql )) {
			while ( $row = $res->fetch_assoc () ) {
				array_push ( $storeInfoArr, $row );
			}
		}
		
		return $storeInfoArr;
	}
	
	
	
	
	
	/**
	 * get the user's id
	 * 
	 * @return int userId
	 */
	private function _getUserId() {
		$sql = "SELECT id FROM users WHERE email = \"$this->email\"";
		$res = $this->_query($sql);
		$row = $res->fetch_assoc();
		return $row['id'];
	}
	
	
	private function _query($sql) {
		
		// model used to structure return data
		$returnModel = array(
			"error" => null,
			"success" => 0,
			"message" => "",
			"data" => null
		);
		
		// connect to database with mysqli
		$db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
		
		// if connection error, return error message
		if($db->connect_errno) {
			$returnModel['error'] = $db->connect_error;
			return $returnModel;
		}	
		
		// if database query fails, return query error
		if(!$result = $db->query($sql)) {
			$returnModel['error'] = $db->error;
		}
		
		$db->close();
		/*
		 * if everything works up to this point, just return the result
		 * model returning will be handled by the public function
		 */
		return $result;
		
		
		
	}
	
	
	
}
