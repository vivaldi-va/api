<?php

class Bootstrap {

	protected $returnModel = array(
		"success" => false,
		"error" => null,
		"message" => null,
		"data" => null
		);

	


	/**
	 * handles database connections and sql queries to the database
	 * Returns the result object if successful
	 * If unsuccessful it sets the return model and returns false 
	 * 	 
	 *
	 * @param string $sql
	 * @return result object or array containing debug/error messages
	 */
	protected function _query($sql) {


		// connect to database with mysqli
		$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

		// if connection error, return error message
		if($db->connect_errno) {
			$this->returnModel['error'] = $db->connect_error;
			return false;
		}

		
		// check if the result succeeded, if not return false
		// to the calling method, the idea being to return a trail of 
		// false back to the method that responds to the http request
		// and once it gets to this method, it should return the errors set in the 
		// query method
		if(!$result = $db->query($sql)) {
			$this->returnModel['error'] = "SERVER_ERROR";
			$this->returnModel['message'] = $db->error;

			return false;
		}

		/*
		 * if everything works up to this point, just return the result
		* model returning will be handled by the public function
		*/
		$this->insertId = $db->insert_id;
		return $result;

		$db->close();

	}

	protected function _makeDb() {
		return new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	}
}