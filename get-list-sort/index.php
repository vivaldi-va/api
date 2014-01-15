<?php
/**
 * controller file for handling sort functions
 */

require_once '../../include/constants.php';
require_once 'Sort.php';


 
$intent = null;

$returnModel = array(
	"success" => 0,
	"error" => false,
	"message" => null,
	"data" => null
);

/**
 * Function to handle returning of JSON data
 * @param array $model
 */
function done($model) {
	exit(json_encode($model));
}


/*
 * check email has been supplied
 */
if(!isset($_REQUEST['email']) || empty($_REQUEST['email'])) {
	$returnModel['error'] = "no user identification supplied";
	done($returnModel);
}


/*
 * determine whether to get split list or individual store lists
 */
if ( isset($_REQUEST['intent']) && !empty($_REQUEST['intent'] )) {
	
	/*
	 * initialize the variables here for easy reference
	 */
	
	
	$email = $_REQUEST['email'];
	$intent = $_REQUEST['intent'];	// make the intent easier to use with php var
	$coords = null; 				// the coordinates object (optional)
	$sortResult = null; 			// the result object to pass back to front-end via JSON
	
	/*
	 * validate coordinates if they're supplied
	 */
	if(isset($_REQUEST['lat']) && isset($_REQUEST['long'])) {
		
		/*
		 * return error if coords are invalid or missing
		 */
		if(empty($_REQUEST['lat']) || empty($_REQUEST['long']) || $_REQUEST['lat'] === 0 || $_REQUEST['long'] === 0) {
			$returnModel['error'] = "missing coordinates";
			$returnModel['message'] = "latitude: " . $_REQUEST['lat'] . " - longitude: " . $_REQUEST['long'];
			done($returnModel);
		}
		
		/*
		 * if coords are valid, create an array
		 */
		$coords = array("lat" => $_REQUEST['lat'], "long" => $_REQUEST['long']);
		
		// COORD VALIDATION DONE
	}
	
	

	$sort = new Sort($email, $coords);		// make a new instance of the Sort class
	switch($intent) {
		
		case 'split':
			$sortResult = $sort->getSplitList(); 
			if(isset($sortResult['error'])) {
				$returnModel['error'] = $sortResult['error'];
				done($returnModel);
			}
			$returnModel['message'] = "split list generation successful";
			break;
			
			
		case 'single':
			$sortResult = $sort->getStorePrices();
			$returnModel['message'] = "store lists generated successfully";
			break;
			
		/*
		 * if the intent is not valid return error
		 */ 
		default:
			$returnModel['error'] = "invalid intent \"$intent\"";
			done($returnModel);
			break;
	}
	
	
	$returnModel['success'] = 1;
	$returnModel['data'] = $sortResult;
	done($returnModel);
	
	
	
} 




/*
 * if no intent specified, return error
 */
else 
{
	$returnModel['error'] = "no intent specified";
	done($returnModel);
}





