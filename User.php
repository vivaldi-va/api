<?php

require_once './lib/constants.php';
require_once './bootstrap.php';


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
		$session = $this->_checkSession();
		if ($session['success']===1) {
			$this->returnModel['success'] = true;
		} else {


			// check missing credentials
			if(!isset($data['email'])) {
				$this->returnModel['error'] = "NO_EMAIL";
			} else if(!isset($data['password'])) {
				$this->returnModel['error'] = "NO_PASS";
			} else {

				$user		= null;
				
				$db			= $this->_makeDb();
				$email		= mysqli_real_escape_string($db, $data['email']);
				$password	= mysqli_real_escape_string($db, $data['password']);
				$db->close();

				// credentials ok, moving right along
				// to making sure they are real

				// get the user's id by the email, 
				// if the user is not found, determine that the user
				// does in fact not exist at all (woah)
				if(!$user = $this->_getUserInfo($email)) {
					$this->returnModel['error'] = "NO_USER";
				} else {
					// validate password
					
					$salt				= $user['salt'];
					$storedPassHash		= $user['passhash'];
					$enteredPassword	= $password;

					if(md5( md5($enteredPassword) . md5($salt) ) === $storedPassHash) {
						// password ok, set cookies and return success
						
						setcookie(COOKIE_NAME_IDENT, $user['id'], time()+COOKIE_EXPIRE, COOKIE_PATH);
						setcookie(COOKIE_NAME_TOKEN, SECRET, time()+COOKIE_EXPIRE, COOKIE_PATH);

						$this->returnModel['success'] = true;


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
	 * @param  array $data post data from registration from
	 * @return {array}
	 */
	public function register($data) {
		
		// =|===========
		// 	| Validation
		// 	|
		
		// is email set?
		if(!isset($data['email']) || strlen($data['email']) == 0) {
			$this->returnModel['error'] = "NO_EMAIL";
			return $this->returnModel;
		} 
		// is password set?
		if(!isset($data['password']) || strlen($data['password']) == 0) {
			$this->returnModel['error'] = "NO_PASS";
			return $this->returnModel;
		} 
		
		
		// is password greater than 6 characters?
		if(strlen($data['password']) < 6) {
			$this->returnModel['error'] = "PASS_TOO_SHORT";
		}

		// is the name there?
		if(!isset($data['name']) || strlen($data['name']) == 0) {
			$this->returnModel['error'] = "NO_NAME";
			return $this->returnModel;
		} 		
		
		// prevent sql injections
		$db			= $this->_makeDb();
		$email		= mysqli_real_escape_string($db, $data['email']);
		$password	= mysqli_real_escape_string($db, $data['password']);
		$name		= mysqli_real_escape_string($db, $data['name']);

		// is the email taken?
		if($this->_getUserInfo($email)) {
			$this->returnModel['error'] = "EMAIL_EXISTS";
			return $this->returnModel;
		}

		// create a salt
		$salt = uniqid();
		
		// hash the password with salt
		$passHash = md5( md5($data['password']) . md5($salt) );

		// REG IP
		$ip = $_SERVER['REMOTE_ADDR'];
		
		// insert the data into the database
		$sql = "INSERT INTO users (id, email, passhash, salt, created, firstname, lastname, userlevel, last_login_date, reg_ip, last_login_ip, must_validate, facebook) 
				VALUES(null, \"$email\", \"$passHash\", \"$salt\", CURRENT_TIMESTAMP, \"$name\", \"\", 0, CURRENT_TIMESTAMP, \"$ip\", \"$ip\", 1, 0)";
		
		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		$this->returnModel['error']		= null;	// clear errors
		$this->returnModel['success']	= true;

		return $this->returnModel;
	}


	/**
	 * Log the user out by destroying their cookies, thereby killing the session
	 * return true in any case since it's always going to result in the user 
	 * being logged out either here or at the front-end.
	 * 
	 * @return array return model
	 */
	public function logout() {
		// unset cookies
		if(isset($_COOKIE[COOKIE_NAME_IDENT]) && isset($_COOKIE[COOKIE_NAME_TOKEN])) {
			setcookie(COOKIE_NAME_IDENT, "", time()-COOKIE_EXPIRE, COOKIE_PATH);
			setcookie(COOKIE_NAME_TOKEN, "", time()-COOKIE_EXPIRE, COOKIE_PATH);
		}
		$this->returnModel['success'] = true;
		return $this->returnModel;
	}



	private function _checkSession() {
		return $this->session();
	}

	/**
	 * get the user information by the user's ID or email
	 *
	 * @param  {string|int} $ident
	 * @return {array|boolean} array of user info or false if no user found
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
			$row = $result->fetch_assoc();
			return $row;
		} else {
			return false;
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

		$row = $result->fetch_row();
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
