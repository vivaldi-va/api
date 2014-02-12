<?php

require_once './lib/constants.php';
require_once './bootstrap.php';
require_once './User.php';

class Product extends Bootstrap {

	public function getProductInfo($id) {

		if(!$id) {
			$this->returnModel['error'] = "NO_PRODUCT";
			return $this->returnModel;
		}

		$sql = "SELECT 
		products.id AS product_id,
		products.name AS product_name,
		products.picUrl AS product_thumb,
		products.barcode AS barcode,
		products.categoryID
		FROM products
		WHERE
		products.id = $id;";

		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		$product = $result->fetch_assoc();
		$product['product_name'] = utf8_encode($product['product_name']);
		$this->returnModel['data'] = $product;
		$this->returnModel['success'] = true;
		return $this->returnModel;
	}

	public function getSearchResult($term, $limit) {
		$db 		= $this->_makeDb();
		$results 	= array();
		$term 		= mysqli_real_escape_string($db, $term);
		$db->close();

		$sql = "SELECT 
		products.id AS product_id,
		products.name AS product_name,
		products.picUrl AS product_thumb,
		products.barcode AS barcode,
		products.categoryID
		FROM products
		WHERE products.name LIKE \"%$term%\" 
		OR products.barcode LIKE \"%$term%\"
		LIMIT $limit;"; 


		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		if($result->num_rows===0) {
			$this->returnModel['error'] = "NO_RESULTS";
			return $this->returnModel;	
		}

		while($row = $result->fetch_assoc()) {
			$row['product_name'] = utf8_encode($row['product_name']);
			array_push($results, $row);
		}

		$this->returnModel['data'] = $results;
		$this->returnModel['success'] = true;
		return $this->returnModel;
	}

	public function getPrices($productId, $latitude, $longitude) {

		// check product exists
		if(!$this->_productExists($productId)) {
			$this->returnModel['error'] = "NO_PRODUCT";
			return $this->returnModel;
		}
		
		// check latitude & longitude are set

		if(!$latitude || empty($latitude)) {
			$this->returnModel['error'] = "NO_COORD_LAT";
			return $this->returnModel;
		}

		if(!$longitude || empty($longitude)) {
			$this->returnModel['error'] = "NO_COORD_LONG";
			return $this->returnModel;
		}


		$userId			= User::getActiveUserId();
		$locationClass	= new Location();
		
		$locationIdArr	= array();
		$locationsArr	= array();		
		$pricesArr		= array();
		$dataArr		= array();


		// check for any saved locations

		if($savedLocations = $locationClass->_getSavedLocations($userId)) {
			foreach($savedLocations as $location) {
				array_push($locationIdArr, $location['id']);
				array_push($locationsArr, $location);
			}
		}
		
		// if there are less than 5 saved locations 

		if(count($locationIdArr)<5) {

			// get closest locations until you have 5 overall
			$surroundingLocations = $locationClass->_getSurroundingStores($latitude, $longitude, 5);
			$i = 0;
			while(count($locationIdArr)<5) {
				array_push($locationIdArr, $surroundingLocations[$i]['id']);
				array_push($locationsArr, $surroundingLocations[$i]);
				$i++;
			}
		}


		// get the product prices at all the found locations




		$locationsInString = implode(',', $locationIdArr);
		$sql = "SELECT 
			p1.ID as price_id, p1.productID as product_id, p1.shopID as shop_id, p1.created, p1.price
			FROM prices p1
			INNER JOIN
				(SELECT * FROM prices  
				WHERE shopID IN ($locationsInString) AND productId = $productId
				ORDER BY created DESC
				) p2
			ON p1.ID = p2.ID
			GROUP BY p1.productId, p1.shopID";


		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		while($row = $result->fetch_assoc()) {
			array_push($pricesArr, $row);
		}
		
		//return $locationsArr;
		foreach($locationsArr as $locationKey => $location) {

			$priceItemArr = array(
							"shop_id"		=> $location['id'],
							"shop_chain"	=> $location['shop_chain'],
							"shop_location"	=> $location['shop_location'],
							"distance" 		=> isset($location['distance']) ? $location['distance'] : null,
							"price"			=> null
						);

			foreach($pricesArr as $priceKey => $price) {

				//print_r(array($price, $location));

				if($price['shop_id'] == $location['id']) {
					$priceItemArr['price']  = $price['price'];
				}

			}

			array_push($dataArr, $priceItemArr);
		}

		$this->returnModel['success']	= true;
		$this->returnModel['data']		= $dataArr;
		return $this->returnModel;
	}


	private function _productExists($productId) {
		if(!$result = $this->_query("SELECT id FROM products WHERE id=$productId")) {
			return false;
		}

		if($result->num_rows<1) {
			return false;
		}

		return true;
	}


}