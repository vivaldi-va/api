<?php

include_once './lib/constants.php';
include_once './bootstrap.php';


class User extends Bootstrap {

	var $userId;

	/**
	 * Check cookie variables for an active session
	 * 
	 * @return array or json if from http
	 */
	public function session() {

		$userInfo = array(
				"name" => null,
				"email" => null
			);

		// check cookies, if cookie for user ID and secret is set, 
		// validate the secret matches the server secret
		if(isset($_COOKIE[COOKIE_NAME_IDENT]) && isset($_COOKIE[COOKIE_NAME_TOKEN]))	 {
			
			// return bad auth if secret doesnt match
			if($_COOKIE[COOKIE_NAME_TOKEN] !== SECRET) {

				$this->returnModel['error'] = "AUTH_FAIL";
				
			} else {

				$this->userId = $_COOKIE[COOKIE_NAME_IDENT];
				$user = null;

				// if getting user info failed, return model loaded with errors
				if(!$user = $this->_getUserInfo($this->userId)) {
					return $this->returnModel;
				}


				// by now, user should be authenticated
				$this->returnModel['success'] 	= true;
				$this->returnModel['message'] 	= "User session found";
				$this->returnModel['data']		= $user;
			}

			//return $this->returnModel;
		} else {
			$this->returnModel['error'] = "NO_AUTH";
		}

		return $this->returnModel;
	}

	/**
	 * Login the user, 
	 * function checks the user credentials supplied to it 
	 * as well as validates whether the user exists
	 * then re-encrypts the supplied password with the salt and compares it against the stored password hash
	 * if all this checks out it should then grab the user's data, strip it of all non-relevant information
	 * and add the user's statistics to it before finally returning it along with a success or error indicator
	 * 
	 * @param  array $data an array of POST data supplied from the login form.
	 * @return array The return model loaded with errors or data and a success indication
	 */
	public function login($data) {

		// check if a session already exists
		$session = $this->session();
		if ($session['success']===1) {
			$this->returnModel['success'] = true;
		} else {

			// check missing credentials
			if(!isset($data['email'])) {
				$this->returnModel['error'] = "NO_EMAIL";
			} else if(!isset($data['password'])) {
				$this->returnModel['error'] = "NO_PASS";
			} else {

				$user = null;
				// credentials ok, moving right along
				// to making sure they are real

				// get the user's id by the email, 
				// if the user is not found, determine that the user
				// does in fact not exist at all (woah)
				if(!$user = $this->_getUserInfo($data['email'])) {
					$this->returnModel['error'] = "NO_USER";
				} else {
					// validate password
					
					$salt				= $user['salt'];
					$storedPassHash		= $user['passhash'];
					$enteredPassword	= $data['password'];

					if(md5( md5($enteredPassword) . md5($salt) ) === $storedPassHash) {
						// password ok, set cookies and return success
						
						setcookie(COOKIE_NAME_IDENT, $user['id'], time()+COOKIE_EXPIRE, COOKIE_PATH);
						setcookie(COOKIE_NAME_TOKEN, SECRET, time()+COOKIE_EXPIRE, COOKIE_PATH);

						$returnModel['success'] = true;


					} else {
						$this->returnModel['error'] = "BAD_PASS";
					}
				}

			}
		}


		return $this->returnModel;
	}

	/**
	 * Register a new user
	 * TODO: determine which user attributes to use as optional attributes for user account creation
	 * 
	 * 
	 * @param  string $email
	 * @param  string $name
	 * @param  string $password
	 * @return array or json
	 */
	public function register($email, $name, $password) {

	}


	/**
	 * get the user information by the user's ID or email
	 *
	 * @param  {string|int} $ident
	 * @return [type]
	 */
	private function _getUserInfo($ident) {

		if (gettype($ident) === "integer") {
			$sql = "SELECT id, email, firstname, lastname, passhash, salt FROM users WHERE id=$ident";
		} else {
			$sql = "SELECT id, email, firstname, lastname, passhash, salt FROM users WHERE email=\"$ident\"";
		}

		if ($result = $this->_query($sql)) {
			if($result->num_rows<1) {
				$this->returnModel['error'] = "NO_USER";
			}
			$row = $result->fetch_row();
			return $row[0];
		} else {
			return $result;
		}
	}


/**
 * Get user statistics
 * 
 * @param  string $email
 * @return {array|boolean} array with stats info or boolean if query fails (to indicate the function should return the set return model loaded with error things)
 */
	private function _getStats($email) {

		$sql = "SELECT sum(shoppingListProductsHistory.saved) AS total_saved, count(DISTINCT token) AS shopping_trips, sum(shoppingListProductsHistory.price) AS total_spent
			FROM users, shoppinglists, shoppingListProductsHistory
			WHERE users.email = \"$email\"
			AND shoppinglists.userID = users.id
			AND shoppingListProductsHistory.shoppingListID = shoppinglists.id
			GROUP BY shoppinglists.id";

		if(!$result = $this->_query($sql)) {
			return false;
		}

		$row = $result->fetch_row()
		return $row[0];

	}


	/**
	 * get the passhash and salt for a specified user id
	 * 
	 * @return boolean/array
	 */
	private function _getUserPass() {
		if(!$result = $this->_query("SELECT passhash, salt FROM users WHERE id = $this->userId")) {
			return false;
		} else {
			$row = $result->fetch_row;
			return $row[0];
		}
	}

	/**
	 * using the User class, get the email from the checkSession function, then
	 * query using the email contained in the resulting user info to find the database
	 * id for the user;
	 *
	 * @return int: userId
	 */
	protected function _getActiveUserId($email) {

		$res = $this->_query("SELECT id FROM users WHERE email = \"$email\"");
		$row = $res->fetch_row();
		return $row[0];
	}


}
