<?php
require_once 'bootstrap.php';
require_once 'User.php';

class Location extends Bootstrap {

	public function getSurroundingStores($lat, $long, $num) {

		$shopsArr = array();

		$sql = "SELECT  shops.id,  shops.name AS shop_location,  shops.address,  shops.city,  shops.latitude,  shops.longitude,  chains.name AS shop_chain, 
		truncate ((( 6371 * acos( cos( radians( $lat ) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians( $long ) ) + sin( radians( $lat ) ) * sin( radians( latitude ) ) ) )*1000),2) AS distance 
		FROM shops, chains
		WHERE longitude is not null 
			AND	chains.id = shops.chainID 
		ORDER BY distance asc 
		LIMIT $num";


		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		if($result->num_rows<1) {
			$this->returnModel['error'] = "NO_RESULTS";
			return $this->returnModel;
		}

		while($row = $result->fetch_assoc()) {
			$row['shop_location']	= utf8_encode($row['shop_location']);
			$row['shop_chain']		= utf8_encode($row['shop_chain']);
			$row['city']			= utf8_encode($row['city']);
			$row['address']			= utf8_encode($row['address']);
			array_push($shopsArr, $row);
		}

		$this->returnModel['data']		= $shopsArr;
		$this->returnModel['success']	= true;

		return $this->returnModel;
	}



	public function _getSurroundingStores($lat, $long, $num) {
		$shopsArr = array();

		$sql = "SELECT  shops.id,  shops.name AS shop_location,  shops.address,  shops.city,  shops.latitude,  shops.longitude,  chains.name AS shop_chain, 
		truncate ((( 6371 * acos( cos( radians( $lat ) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians( $long ) ) + sin( radians( $lat ) ) * sin( radians( latitude ) ) ) )*1000),2) AS distance 
		FROM shops, chains
		WHERE longitude is not null 
			AND	chains.id = shops.chainID 
		ORDER BY distance asc 
		LIMIT $num";


		if(!$result = $this->_query($sql)) {
			return false;
		}

		if($result->num_rows<1) {
			return false;
		}

		while($row = $result->fetch_assoc()) {
			$row['shop_location']	= utf8_encode($row['shop_location']);
			$row['shop_chain']		= utf8_encode($row['shop_chain']);
			$row['city']			= utf8_encode($row['city']);
			$row['address']			= utf8_encode($row['address']);
			array_push($shopsArr, $row);
		}

		return $shopsArr;
	}



	public function getSavedLocations() {

		// check user has a session
		if(!$userId = User::getActiveUserId()) {
			$this->returnModel['error'] = "NO_SESSION";
			return $this->returnModel;
		}

		$locationsArr = array();
		
		$sql = "SELECT shops.id, chains.name as shop_chain, shops.name as shop_location, 
	  			 shops.address, shops.city, shops.latitude, shops.longitude, shops.zipcode 
				FROM locations, shops, chains
				WHERE chains.id = shops.chainID 
					AND locations.shopid = shops.id 
					AND locations.userid = $userId 
					AND locations.active = 1";
		
		
		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}
		
		if($result->num_rows<1) {
			$this->returnModel['error'] = "NO_RESULTS";
			return $this->returnModel;
		}
		
		while($row = $result->fetch_assoc()) {
			$row['shop_location']	= utf8_encode($row['shop_location']);
			$row['shop_chain']		= utf8_encode($row['shop_chain']);
			$row['city']			= utf8_encode($row['city']);
			$row['address']			= utf8_encode($row['address']);
			array_push($locationsArr, $row);
		}

		$this->returnModel['data']		= $locationsArr;
		$this->returnModel['success']	= true;
		return $this->returnModel;
	}

	public function _getSavedLocations($userId) {
		// check user has a session
		if(!$userId) {
			return false;
		}

		$locationsArr = array();
		
		$sql = "SELECT shops.id, chains.name as shop_chain, shops.name as shop_location, 
	  			 shops.address, shops.city, shops.latitude, shops.longitude, shops.zipcode 
				FROM locations, shops, chains
				WHERE chains.id = shops.chainID 
					AND locations.shopid = shops.id 
					AND locations.userid = $userId 
					AND locations.active = 1";
		
		
		if(!$result = $this->_query($sql)) {
			return false;
		}
		
		if($result->num_rows<1) {
			return false;
		}
		
		while($row = $result->fetch_assoc()) {
			$row['shop_location']	= utf8_encode($row['shop_location']);
			$row['shop_chain']		= utf8_encode($row['shop_chain']);
			$row['city']			= utf8_encode($row['city']);
			$row['address']			= utf8_encode($row['address']);
			array_push($locationsArr, $row);
		}

		return $locationsArr;
	}

}


