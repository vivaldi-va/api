<?php

require_once '../../include/constants.php';

class Location {
	
	var $user;
	var $email;
	
	public function Location($user) {
		$this->email = $user;
		$this->user = $this->_getUserId();
	}
	
	public function getSavedLocations() {
		
		$locationsArr = array();
		
		$sql = "SELECT shops.id, chains.name as chain, shops.name as name, 
	  			 shops.address, shops.city, shops.latitude, shops.longitude, shops.zipcode 
				FROM locations, shops, chains
				WHERE
					chains.id = shops.chainID AND
					locations.shopid = shops.id AND
					locations.userid = $this->user AND
					locations.active = 1";
		//echo $sql;
		$res = $this->_query($sql);
		
		
		while($row = $res->fetch_assoc()) {
			array_push($locationsArr, $row);
		}
		
		//print_r($locationsArr);
		return $locationsArr;
		
	}
	
	public function saveLocation($id) {
		
	 $sql = "INSERT INTO locations (id, userid, shopid, active) VALUES(null, $this->user, $id, 1)";	
	
	 
	 if(!$res = $this->_query($sql)) {
	 	return "adding location failed";
	 }
	 
	 return true; 
		
	}	
	
	public function removeSavedLocation($id) {
		$sql = "UPDATE locations SET active = 0 WHERE userid = $this->user AND shopid = $id";
		if(!$res = $this->_query($sql)) {
			return "removing location failed";
		}
		
		return true;
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
			//return $returnModel;
			return false;
		}
	
		//$sql = mysqli_real_escape_string($db, $sql);
		//echo $sql;
		// if database query fails, return query error
		if(!$result = $db->query($sql)) {
			$returnModel['error'] = $db->error;
			return false;
		}
	
		$db->close();
		/*
		 * if everything works up to this point, just return the result
		* model returning will be handled by the public function
		*/
		return $result;
	
	
	
	}
}

/**
 * Function to handle returning of JSON data
 * @param array $model
 */
function done($model) {
	exit(json_encode($model));
}


$returnModel = array(
	"success" => 0,
	"error" => false,
	"message" => null,
	"data" => null
);


if(isset($_REQUEST['user']) || empty($_REQUEST['user'])) {
	$user = $_REQUEST['user'];
} else {
	$returnModel['error'] = "User is not defined";
	done($returnModel);
}

if(isset($_REQUEST['query']) || empty($_REQUEST['query'])) {
	$query = $_REQUEST['query'];
} else {
	$returnModel['error'] = "No query, what should I do?";
	done($returnModel);
}

$location = new Location($user);

switch ($query) {
	case 'get':
		$returnModel['data'] = $location->getSavedLocations();
		$returnModel['success'] = true;
		done($returnModel);
		break;
		
		
	case 'search':
		if(isset($_REQUEST['term']) || empty($_REQUEST['term'])) {
			$term = $_REQUEST['term'];
		} else {
			$returnModel['error'] = "Nothing to search for...";
			done($returnModel);
		}
		
		$returnModel['data'] = $location->searchLocations($term);
		$returnModel['success'] = true;
		done($returnModel);
		break;
		 
		
	case 'save':
		if(isset($_REQUEST['id']) || empty($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
		} else {
			$returnModel['error'] = "No location ID given";
			done($returnModel);
		}
		
		//$returnModel['data'] = $location->saveLocation($id);
		
		$res = $location->saveLocation($id);
		if ($res === true) {
			$returnModel['success'] = true;
		} else {
			$returnModel['error'] = $res;
		}
		done($returnModel);
		break;
		
		
	case 'removesaved':
		if(isset($_REQUEST['id']) || empty($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
		} else {
			$returnModel['error'] = "No location ID given";
			done($returnModel);
		}
		
		//$returnModel['data'] = $location->saveLocation($id);
		
		$res = $location->removeSavedLocation($id);
		if ($res === true) {
			$returnModel['success'] = true;
		} else {
			$returnModel['error'] = $res;
		}
		done($returnModel);
		break;
		
		
		
}












