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


	public function searchLocations($keywords) {
		$db			= $this->_makeDb();
		$keywords	= mysqli_real_escape_string($db, $keywords);
		$resultArr	= array();

		$sql = "SELECT  shops.id,  shops.name AS shop_location,  shops.address,  shops.city,  shops.latitude,  shops.longitude,  chains.name AS shop_chain
				FROM shops, chains 
				WHERE
					" . $this->_makeKeywordsGroups($keywords) . " 
					AND shops.chainID = chains.id";

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
			array_push($resultArr, $row);
		}

		$this->returnModel['data']		= $resultArr;
		$this->returnModel['success']	= true;
		return $this->returnModel;
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

	/**
	 * Add a location to the active locations list
	 * which will then be used for price location and sorting
	 * 
	 * @param  int 		$shopId The row ID for the shop to add
	 * @return array         	return model
	 */
	public function saveLocation($shopId) {
		$userId	= User::getActiveUserId();

		if($this->_userHasLocation($shopId)) {
			$this->returnModel['success'] = true;
			return $this->returnModel;
		} else {
			$sql = "INSERT INTO locations (id, userid, shopid, active) VALUES (null, $userId, $shopId, 1);";
			if(!$result = $this->_query($sql)) {
				return $this->returnModel;
			}

			$this->returnModel['success'] = true;
			return $this->returnModel;
		}

	}


	public function removeLocation($shopId) {
		$userId	= User::getActiveUserId();
		$sql 	= "UPDATE locations SET active = 0 WHERE userid = $userId AND shopid = $shopId";

		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		$this->returnModel['success'] = true;
		return $this->returnModel;
	}


	public static function locationInfo($latitude, $longitude) {
		$locationInfoStringURL = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&sensor=false";

		$locationInfo = json_decode(file_get_contents($locationInfoStringURL), true);
		$address = $locationInfo['results'][0]['address_components'][1]['long_name']. ' ' . $locationInfo['results'][0]['address_components'][0]['long_name']. ', ' . $locationInfo['results'][0]['address_components'][2]['long_name'];

		return $address;
	}

	/**
	 * check whether the user has a particular location saved already
	 * 
	 * @param  int 		$shopId Shop row ID
	 * @return array         	Return model
	 */
	private function _userHasLocation($shopId) {
		$userId = User::getActiveUserId();
		if(!$result = $this->_query("SELECT id FROM locations WHERE shopid = $shopId AND userid = $userId;")) {
			return false;
		}

		if($result->num_rows < 1) {
			return false;
		}

		return true;
	}


	private function _makeKeywordsGroups($keywordString) {
		$keywords	= explode('|', $keywordString);
		$sqlGroups	= array();

		// make sql group out of each keyword and put in array
		foreach($keywords as $keyword) {
			$sqlString = "(shops.name LIKE \"%$keyword%\" OR
			shops.address LIKE \"%$keyword%\" OR
			shops.city LIKE \"%$keyword%\" OR
			shops.zipcode LIKE \"%$keyword%\" OR
			chains.name LIKE \"%$keyword%\")";
			
			array_push($sqlGroups, $sqlString);
			
		}

		return implode(" AND ", $sqlGroups);
	}
	

}


