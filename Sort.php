<?php

require_once './lib/constants.php';
require_once './bootstrap.php';
require_once './User.php';
require_once './Lists.php';

class Sort extends Bootstrap {
	
	var $user	= null;
	var $coords	= null;
	var $list	= null;
	var $token;
	
	
	
	public function Sort($lat, $long) {
		$this->coords	= array("latitude" => $lat, "longitude" => $long);
		$this->user		= User::getActiveUserId();
		$this->list		= null;
		$this->token	= uniqid();
	}
	
	/**
	 * Split the user list between stores
	 */
	public function getSplitList() {
		
		
		$priceData = $this->_getListPrices();
		$listTotal = 0;
		
		$sanitizedProductArr = array();
		$optimizedArr = array("attrs" => array("total" => 0, "num_products" => 0), "shops" => array());
		$shopArrs = array();
		
		// loop through shops
		foreach($priceData['shops'] as $shopId => $shopArr) {
			
			$shopTotal = 0;
			
			$shopArrs[$shopId] = array("shop_name" => $shopArr['attrs']['shop_name'], "chain" => $shopArr['attrs']['chain']);
			
			
			// Cycle through products in shop
			// add each product price and shopid as a value to an array, 
			// with the product id as a key
			foreach($shopArr['products'] as $prodArr) {
				
				// if the product array in sanitized object hasnt been 
				// created, create it.
				if(!isset($sanitizedProductArr[$prodArr['productId']])) {
					
					$sanitizedProductArr[$prodArr['productId']] = array();
				} 
				
				array_push($sanitizedProductArr[$prodArr['productId']], array("price" => $prodArr['price'], "shopID" => $prodArr['shopID']));
				
			}
		}
		
		
		
		// for each product in the sanitized array,
		// find the lowest price and push into optimized array
		foreach($sanitizedProductArr as $productID => $arr) {
			$_lowestPrice = null;
			$_lowestPriceShop = null;
			
			// cycle each price for product
			// compare against previous price
			// if lower, update _lowestPrice
			foreach($arr as $priceArr) {
				$price = $priceArr['price'];
				$shop = $priceArr['shopID'];
				
				if ($price < $_lowestPrice || !$_lowestPrice) {
					$_lowestPrice = $price;
					$_lowestPriceShop = $shop;
				}
				
			}
			
			// create the shop lists while we're at it
			if(!isset($optimizedArr['shops'][$_lowestPriceShop])) {
				$optimizedArr['shops'][$_lowestPriceShop] = array("attrs" => array("total" => 0, "num_products" => 0, "shop_name" => $shopArrs[$_lowestPriceShop]['shop_name'], "chain" =>$shopArrs[$_lowestPriceShop]['chain'], "shop_id" => $_lowestPriceShop), "products"=>array());
			}
			
			$optimizedArr['shops'][$_lowestPriceShop]['attrs']['num_products']++;
			$optimizedArr['shops'][$_lowestPriceShop]['attrs']['total']+=$_lowestPrice;
			$optimizedArr['attrs']['num_products']++;
			$optimizedArr['attrs']['total']+=$_lowestPrice;
			$optimizedArr['attrs']['token']=$this->token;
			
			
			array_push($optimizedArr['shops'][$_lowestPriceShop]['products'], array("productId" => $productID, "price" => $_lowestPrice));
			
		}
		
		return $optimizedArr;
		
	}
	
	/**
	 * Return list of all stores and list of products and their prices
	 * as well as total store price
	 * 
	 * @return array [description]
	 */
	public function getShopLists() {
		
		$shops;					// array of shop IDs
		$productsInList;		// array of products in user's shopping list and their info			
		$productsInListIdArr;	// array of shopping list product IDs
		$productPrices;			// array of prices for each product at each location
		$numListProducts;		// count of products in shopping lsit
		$numLocations;			// count of number of stores found
		
		$this->list = $this->_getUserListId();
		
		// initial model for array to return
		$returnArray = array(
			"shops" => array(),
			"attrs" => array(
				"num_list_products"	=> 0,
				"num_shops"			=> 0,
				"token"				=> $this->token,
				"list_id"			=> $this->list
				));
		
		
		$shops					= $this->_getStores(); 	// get locations to use in sort
		$numLocations			= count($shops);		// get products from the list
		$productsInList			= $this->_getListProducts();
		$productsInListIdArr	= array();
		



		/////////////////////////////////////////////////////////////////////////////////////
		// Fill the product ID array with product IDs from the products in shopping list //
		/////////////////////////////////////////////////////////////////////////////////////
		foreach($productsInList as $productArr) {
			array_push($productsInListIdArr, $productArr['productID']);
		}
		
		$productPrices = $this->_getListPrices($shops, $productsInListIdArr);	// get prices for each product
		$shopInfoArray = $this->_getShopInfo($shops);							// get the shop info for each location and add to end result array
		
		
		//////////////////////////////
		// Construct return array //
		//////////////////////////////
		
		foreach($productPrices as $shopId => $prices) {
			// create an object for each shop
			
			$numProducts	= 0;
			$totalPrice		= 0;
			$productsArr	= array();

			$returnArray['shops'][$shopId] = array(
				"products" => array(),
				"attrs" => array(
						"num_products"	=> 0, 
						"total_price"	=> 0,
						"shop_id"		=> $shopInfoArray[$shopId]['id'],
						"shop_name"		=>  utf8_encode($shopInfoArray[$shopId]['name']),
						"chain_name"	=> $shopInfoArray[$shopId]['chain'],
						"latitude"		=> $shopInfoArray[$shopId]['latitude'],
						"longitude"		=> $shopInfoArray[$shopId]['longitude'],
						"city"			=> $shopInfoArray[$shopId]['city'],
						"address"		=>  utf8_encode($shopInfoArray[$shopId]['address']),
						"distance"		=> null
				)
			);
			
			

			foreach($prices as $productId => $price) {
				
				$numProducts++;
				$totalPrice += $price;
				$productInfo = $productsInList[$productId];

				$productsArr[$productId] = array(
					"product_name"	=> utf8_encode($productInfo['name']),
					"product_id"	=> $productInfo['productID'],
					"barcode"		=> $productInfo['barcode'],
					"list_item_id"	=> $productInfo['listItemID'],
					"quantity"		=> $productInfo['quantity'],
					"category"		=> $productInfo['categoryID'],
					"pic_url"		=> $productInfo['picUrl'],
					"price"			=> $price
				);
				//$productsArr[$productId]['price'] = $price;
			}
			
			$returnArray['shops'][$shopId]['products']				= $productsArr;
			$returnArray['shops'][$shopId]['attrs']['num_products']	= $numProducts;
			$returnArray['shops'][$shopId]['attrs']['total_price']	= $totalPrice;
			
		}
		
		
		$returnArray['attrs']['num_shops']			= count($shops);
		$returnArray['attrs']['num_list_products']	= count($productsInListIdArr);
		//$lists['attrs']['token'] = $this->token;
		//return $lists;
		return $returnArray;
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



	/**
	 * get array store IDs from either user-defined stores or locations
	 * or the user's coordinates if they are supplied
	 * 
	 * @return array: array of store IDs
	 */
	private function _getStores() {
		
		$storeArr		= array();	// array of shop ids
		$storeInfoArr	= array();
		if($this->coords) {
			foreach($nearLocations = $this->_getNearbyLocations() as $id) {
				array_push($storeArr, $id);
			} 
		}

		// determine if any locations were found
		if($savedLocations = $this->_getSavedLocations()) {
			foreach($savedLocations as $storeId) {
				array_push($storeArr, $storeId);
			}
		} 
		
		if(empty($storeArr)) {
			return array("error" => "no locations found");
		}
		
		return $storeArr;
	}
	
	/**
	 * Get a list of 
	 * @return multitype:
	 */
	private function _getListProducts() {
		
		$productArr = array();
		
		$this->_query('SET CHARACTER SET utf8');
		// get list products
		$sql = "SELECT
				products.id AS productID,
				products.name,
				products.picUrl,
				products.barcode,
				products.categoryID,
				shoppinglistproducts.id AS listItemID,
				shoppinglistproducts.quantity
				FROM
				products, shoppinglists, shoppinglistproducts, users
				WHERE
				users.id = shoppinglists.userID AND
				users.id = $this->user AND
				shoppinglistproducts.shoppinglistID = shoppinglists.id AND
				shoppinglists.id = $this->list AND
				shoppinglistproducts.productID = products.id
				ORDER BY shoppinglistproducts.id DESC";
		//echo $sql;
		
		$res = $this->_query($sql);


		while($row = $res->fetch_assoc()) {
			$productArr[$row['productID']] = $row;
			//array_push($productArr, $row);
		}
		
	/* 	echo "<pre>";
		print_r($productArr);
		echo "</pre>"; */
		
		return $productArr;
	}
	
	
	/**
	 * Get all the prices for the supplied list of products from the shopping list
	 * at all the locations provided
	 * 
	 * @param array $shops: array of shop IDs
	 * @param array $products: array of product IDs
	 * @return array: array of product prices in each location
	 */
	private function _getListPrices($shops, $products) {
				
		// format the IN string groups for shops and products 
		// to be used in SQL query
		$shopInStr = "IN (" . implode(', ', $shops) . ")";
		$prodInStr = "IN (" . implode(', ', $products) . ")";
		
		$returnArr = array();
		
		
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
		
		$res = $this->_query($sql);
		
		
		while($row = $res->fetch_assoc()) {
			$shopId		= $row['shopID'];
			$productId	= $row['productId'];
			
			
			// create an object for the store if none exists
			if(!isset($returnArr[$shopId])) {
				$returnArr[$shopId] = array();
			}
			
			// add each price to store object
			// price will be parsed as a float and rounded to 2 decimal places
			$returnArr[$shopId][$productId] = round(floatval($row['price']), 2);
			
		}
		
		
		
		return $returnArr;
		
	}
	
	
	
	
	
	private function _getNearbyLocations() {

		$lat		= $this->coords['latitude'];
		$long		= $this->coords['longitude'];
		$storeArr	= array();

		$sql = "SELECT shops.id, shops.name, chains.name AS chain, shops.address, shops.latitude, shops.longitude, shops.city,
				truncate ((( 6371 * acos( cos( radians( $lat ) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians( $long ) ) + sin( radians( $lat ) ) * sin( radians( latitude ) ) ) )*1000),2) AS distance 
				FROM shops, chains
				WHERE longitude IS NOT null AND
				chains.id = shops.chainID 
				ORDER BY distance asc 
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
		$sql = "SELECT shopid FROM locations WHERE userid = $this->user AND active = 1";
		
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
	
	
	
	/**
	 * Get required info about each store supplied with the id array
	 * 
	 * @param array $idArr
	 * @return multitype:
	 */
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
				//array_push ( $storeInfoArr, $row );
				$storeInfoArr[$row['id']] = $row;
			}
		}
		
		return $storeInfoArr;
	}
	
}
