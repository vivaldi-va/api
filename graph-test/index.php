<?php
require_once '../../include/constants.php';

if (! $connection = mysql_connect ( DB_SERVER, DB_USER, DB_PASS )) {
	exit ( json_encode ( array (
			"error" => "database query failed" 
	) ) );
}
mysql_select_db ( DB_NAME, $connection ) or die ( "conn error: " . mysql_error () );
mysql_set_charset ( 'utf8', $connection );

header ( 'Content-type: application/json' );
header ( "access-control-allow-origin: *" );


$graphData = array(
	"labels" => array(),
	"data" => array(
				
	)
);

//exit('poop');

function makeLabels() {
	$currentDate = date('m-d-Y');
		
		
	// aray for graph labels
	$labels = array();
		
	// make labels for the 7 previous days
	for($i = 1; $i<=7;$i++) {
		$labelDate = date('Y-m-d', time() - 60*60*24*$i);
		array_unshift($labels, $labelDate);
		//exit(json_encode($labelDate));
	}
		
	//exit(json_encode($dbArray['created']));
	//exit(json_encode($labels));
	
	return $labels;
		
}

function getLocations($locationReference) {
	$query = "select  " . TBL_SHOPS . ".id,  " . TBL_SHOPS . ".name AS shopName,  " . TBL_SHOPS . ".address,  " . TBL_SHOPS . ".city,  " . TBL_SHOPS . ".latitude,  " . TBL_SHOPS . ".longitude,  " . TBL_CHAINS . ".name AS chainName
		
		FROM " . TBL_SHOPS . ", " . TBL_CHAINS . " 
		WHERE (" . TBL_SHOPS . ".name LIKE \"%$locationReference%\" OR address LIKE \"%$locationReference%\" OR zipcode LIKE \"%$locationReference%\" OR city LIKE \"%$locationReference%\") AND 
		" . TBL_SHOPS . ".chainID = " . TBL_CHAINS . ".id";

		
		
	if (!$result = mysql_query($query)) {
		if(DEBUG_MODE)
			die(mysql_error());
		else 
			return false;
	} elseif (mysql_num_rows($result) == 0) {
		return false;
	}
	
	$foundLocations = array();
	
	while ($dbArray = mysql_fetch_assoc($result)) {
		$foundLocations[] = $dbArray;
	}
	return $foundLocations;		
}

function getPriceTimeGraph($locationName, $prodID) {
	$prod = TBL_PRODUCTS;
	$pri = TBL_PRICES;
	$s = TBL_SHOPS;
	$c = TBL_CHAINS;
	
	
	$selloArray = array(); 
	
	
	foreach (getLocations("sello") as $location) {
		array_push($selloArray, $location['chainName']);
	} 
	
	//exit(json_encode($selloArray));
	
	
	
	
	$pricesArray = array(
		""
	);
	
	$query = "SELECT 
	$pri.price, $pri.created, $s.name, $c.name AS chainName 
	FROM $prod, $pri, $s, $c 
	WHERE 
		$pri.productID = $prod.id 
		AND $prod.id = $prodID 
		AND $pri.shopID = $s.id 
		AND $c.id = $s.chainID 
		AND $s.name = \"$locationName\" 
		ORDER BY $pri.created Desc
	";
	
	
	
	if(!$result = mysql_query($query)) {
		exit(json_encode(array("success" => 0, "error" => mysql_error())));
	} else {
		while($dbArray = mysql_fetch_assoc($result)) {
			
			
			
			
			/*
			 * for each of the price variables retrieved from the database
			 * compare the date to see if it matches the label, if so
			 * create a value for it, else dont.
			
			foreach ($labels as $dateConstraint) {
						
			} */
			
			array_push($pricesArray, $dbArray);
		}
	}
	
	
	return $pricesArray;
}


function getCheckoutgraph() {
	
}

function getNumLists() {
	
	$result=mysql_fetch_array(mysql_query("SELECT COUNT(id) FROM ".TBL_SHOPPING_LISTS));
	//exit($result[0]);
	return $result[0];
}


// Get total lists value
function listValue($location, $timestamp=false) {


	if(!$timestamp) {
		$timestamp = date('Y-m-d H:i:s');
	}


	$array = array();
	$prod = TBL_PRODUCTS;
	$pri = TBL_PRICES;
	$s = TBL_SHOPS;
	$c = TBL_CHAINS;
	$slp = TBL_SHOPPING_LIST_PRODUCTS;

	$query = "SELECT SUM($pri.price) AS sum, $c.name
	FROM $prod, $pri, $s, $c, $slp
	WHERE
	$slp.productID = $prod.id
	AND $pri.productID = $prod.id
	AND $s.name LIKE \"%$location%\"
	AND $c.id = $s.chainID
	AND $pri.shopID = $s.id
	AND $pri.created <= \"$timestamp\"
	GROUP BY $s.id";

	//exit($query);
	
	//return $query;

	if (!$result = mysql_query($query)) {
		die(mysql_error());
	} else {
		while($dbArray = mysql_fetch_assoc($result)) {
			//exit(json_encode($dbArray));

			array_push($array, $dbArray);
		}
	}

	return $array;
}


function getStoreGrossPredictions($time, $location="sello") {
	
	$prod = TBL_PRODUCTS;
	$pri = TBL_PRICES;
	$s = TBL_SHOPS;
	$c = TBL_CHAINS;
	$slp = TBL_SHOPPING_LIST_PRODUCTS;
	
		
	
	
	
	//return listValue($location, $time);
	
	
	
	// Get average list value
	$numLists = getNumLists();
	
	$avgListPrice = array();
	
	$debug = array();
	
	//exit(json_encode(listValue($location)));
	foreach(listValue($location, $time) AS $listSumArr) {
		//array_push($debug, $listSumArr);
		$avgListPrice[$listSumArr['name']] = $listSumArr['sum']/$numLists;
	}
	
	//exit($debug);
	
	
	// Calc shop value
	
	$calculatedPriceArr = array();
	/*
	 * pre-defined figurs for the number of weekly shoppers at
	 * each chain
	 */
	$prismaNumber = 8000;
	$cityMarketNumber = 8000;
	$kMarketNumber = 800;
	$alepaNumber = 800;
	$siwaNumber = 800;
	$valNumber = 800;
	$kSuperMarketNumber = 1600;
	$sMarketNumber = 1600;
	
	$timesShoppingPerWeek = 3.2;
	
	
	foreach($avgListPrice AS $chainName => $avgValue) {
		switch ($chainName) {
			case 'K-Citymarket':
				$calculatedPriceArr[$chainName] = $avgValue*$cityMarketNumber;
				break;
			case 'K-Market':
				$calculatedPriceArr[$chainName] = $avgValue*$kMarketNumber;
				break;
			case 'Prisma': 
				$calculatedPriceArr[$chainName] = $avgValue*$prismaNumber;
				break;
			case 'Alepa':
				$calculatedPriceArr[$chainName] = $avgValue*$alepaNumber;
				break;
			
			case 'S-Market':
				$calculatedPriceArr[$chainName] = $avgValue*$sMarketNumber;
				break;
			
			case 'K-Supermarket':
				$calculatedPriceArr[$chainName] = $avgValue*$kSuperMarketNumber;
				break;
			
		}
		
		$calculatedPriceArr[$chainName] *= $timesShoppingPerWeek;
		
		
	} 
	
	//exit(json_encode($calculatedPriceArr));
	
	return $avgListPrice;
}






function calcListValueGraph($location="sello", $timeScale=30) {
	//exit('gross store pred');
	
	$currentDate = date('m-d-Y');
	//exit($currentDate);
	
	// aray for graph labels
	$labels = array();
	$data = array();
	
	//exit(floor($timeScale/7));
	
	
	$debug = array();
	
	
	
	/*
	 * split the timescale (set at 2 months) to 
	 * weeks
	 */
	
	$timeSplit = 4;
	for($i = floor($timeScale/$timeSplit); $i >= 0; $i--) {
	
		
		$labelDate = date('Y-m-d', time() - 60*60*24*($i*$timeSplit));
	
		$timestamp = date('Y-m-d H:i:s', time() - 60*60*24*($i*$timeSplit));
		array_push($debug, getStoreGrossPredictions($timestamp));
		array_push($labels, $timestamp);
		
		/*
		foreach(getStoreGrossPredictions($timestamp, $location) AS $chain => $avgPrice) {
			
			
			if(!isset($data[$chain])) {
				$data[$chain] = array();
				
			}
			
			array_push($data[$chain], $avgPrice);			
		}
	
	
		//exit($labelDate);
		array_push($labels, $labelDate);
		//array_push($data, )
		 * 
		 */
	}
	
	//exit(json_encode($debug));
	exit(json_encode(array("data" => array($labels, $data), "debug" => $debug)));
	
	// make labels for the 7 previous days
}


function getMarketShare($keywords=array("valio", "arla", "rainbow", "pirkka")) {
	
	$orSelect = "";
	$numWords = count($keywords);
	
	$outArr = array(
			"legend" => array(),
			"data" => array(),
			"debug" => array(),
			"averages" => array()
	);
	
	
	$i=0;
	
	$colours=array('#F7464A', '#E2EAE9', '#D4CCC5', '#949FB1', '#4D5360');
	
	foreach($keywords as $keyword) {
		
		$count=0;
		if ($i < 5) {
			
			//array_push($outArr, array('value' => $count, color => $colours[$i]));
			
			
			$query = "SELECT quantity, name
				FROM shoppinglistproducts, products
				WHERE
				shoppinglistproducts.productID = products.id 
					AND products.name LIKE \"%$keyword%\"";
				
			if (!$result = mysql_query($query)) {
				//die(mysql_error());
				exit(json_encode(
					array("error" => "database query failed")
				));
			} else {
				while($dbArr = mysql_fetch_assoc($result)) {
					$count += $dbArr['quantity'];	
				}
			}
			
			//exit($query);
			
			//array_push($outArr['data'], $count);
			array_push($outArr['debug'], $i);
			array_push($outArr['data'], array('value' => $count, 'color' => $colours[$i]));
			array_push($outArr['legend'], array("keyword" => $keyword, "value" => $count, "color" => $colours[$i]));
			$i++;
		}
	}
	
	$sum = 0;
	$averages = array();
	foreach($outArr['data'] as $dataArr) {
		$sum += $dataArr['value'];
	}
	
	foreach ($outArr['data'] as $key => $dataArr) {
		$outArr['legend'][$key]['percent'] = round(($dataArr['value']/$sum)*100, 2);
	}
	
	//array_push($outArr)
	
	exit(json_encode(
		$outArr
	));
	
	
	
}



function getDayCheckoutTotal($today, $offset=1) {
	$today = date ( 'Y-m-d H:i:s', strtotime($today) );
	$yesterday = date ( 'Y-m-d H:i:s', strtotime($today) - 60 * 60 * (24*$offset) );
	
	//echo $today . " " . $yesterday . "\n";
	$query = "SELECT sum(price) AS total, shoppingListID, token, created
	FROM shoppingListProductsHistory
	WHERE token IS NOT NULL
	AND created < \"$today\"
	AND created > \"$yesterday\"
	GROUP BY token";
	
	
	
	
	if (! $result = mysql_query ( $query )) {
		exit ( json_encode ( array (
				"error" => "checkout total failed for day " . $today 
		) ) );
	} else {
		return $dbArray = mysql_fetch_assoc ( $result );
	}
}
function getDailyCheckoutTotal($timeframe=30, $offset=7) {
	
	
	$dataArray = array(
		"labels" => array(),
		"datasets" => array(
			
		)
	);
	
	array_push($dataArray['datasets'], array("data" => array(),
			"fillColor" => 	"rgba(220,220,220,0.5)",
			"strokeColor" => "rgba(220,220,220,1)",
			"pointColor" => "rgba(220,220,220,1)",
			"pointStrokeColor" => "#fff"));
	
	/**
	 * Get the checkout total for a particular day
	 * @param unknown $today: datestamp for the day in question, only Y-M-D to allow setting time to 00:00:00
	 */
	
	
	
	//print_r($dataArray['datasets']);
	
	//echo $timeframe;
	for ($i=floor($timeframe/$offset); $i >= 0; $i--) {
		
		$time = date('Y-m-d', time()-60*60*24*($i*$offset));
		//echo $time."\n";
		//print_r( getDayCheckoutTotal($time));
		array_push($dataArray['labels'], $time);
		
		$checkoutArr = getDayCheckoutTotal($time, $offset);
		if (!$checkoutArr) {
			$checkoutTotal = 0;
		} else {
			$checkoutTotal = round($checkoutArr['total'], 2);
		}
		
		//$data = array("data" => )
		
		array_push($dataArray['datasets'][0]['data'], $checkoutTotal);	
	}
	
	
	exit(json_encode($dataArray));
	
	
	
}


function getSetCheckoutQuantity($set, $today, $offset) {
	
	
	/*
	 * loop through products in set and collect product IDs
	*/
	//exit(json_encode($set));
	$setIDs = array();
	foreach($set['data'] as $k => $product) {
		//exit(json_encode($set['data']));
		//exit(json_decode($product['id']));
		//exit(json_encode($product['id']));
		//$id=$product['id'];
		//exit(json_encode(isset($product['id'])));
		if(isset($product['id'])) {
			array_push($setIDs, $product["id"]);
		}
	}
	//exit(json_encode($setIDs));
	
	
	$idAndString = "(".TBL_PRODUCTS.".id = ". implode(" OR ".TBL_PRODUCTS.".id = ", $setIDs) . ")";
	//exit($idAndString);
	
	// start date for timestamp
	$today = date ( 'Y-m-d H:i:s', strtotime($today) );
	// offset date for timestamp
	$yesterday = date ( 'Y-m-d H:i:s', strtotime($today) - 60 * 60 * (24*$offset) );
	//exit($yesterday);
	//echo $today . " " . $yesterday . "\n";
	$query = "SELECT sum(quantity) AS total
	FROM shoppingListProductsHistory, products
	WHERE
	shoppingListProductsHistory.created <= \"$today\"
	AND shoppingListProductsHistory.created > \"$yesterday\"
	AND products.id = shoppingListProductsHistory.ProductID
	AND " . $idAndString;
	//return $query;
	//exit($query);
	if (!$result = mysql_query($query)) {
		exit(json_encode(array("error" => "FAIL", "message" => mysql_error())));	
	}
	
	//exit(json_encode(mysql_fetch_assoc($result)));
	
	//$sumQuant;
	/*while($row = mysql_fetch_assoc($result)) {
		//exit(json_encode($row['total']));
		$sumQuant = $row['total'];
	}*/
	$row = mysql_fetch_assoc($result);
	$sumQuant = intval($row['total']);
	//exit(json_encode($sumQuant));
	return $sumQuant;
}


function calcSetCheckouts($sets) {
	
	$timeframe = 30;
	$offset = 7;
	
	
	$dataArray = array(
			"debug" => array(),
			"labels" => array(),
			"datasets" => array(
						
			)
	);
	
	$graphColours=array(
		"0,68,124",
		"247,70,74",
		"0,169,79",
		"176, 96, 16"
	);
	
	
	
	
	
	/*
	 * make the labels
	 */
	for ($i=floor($timeframe/$offset); $i >= 0; $i--) {
		//exit($i);
		$time = date('Y-m-d', time()-60*60*24*($i*$offset));
		//exit($time);
		//echo $time."\n";
		//print_r( getDayCheckoutTotal($time));
		array_push($dataArray['labels'], $time);
	
		/*
		 * loop through sets
		*/
		
		//$s=0;
		//exit(json_encode(array_keys($graphColours)));
		
	}
	$sArr = array();
	//exit(json_encode($sets));
	
	/*
	 * for each set...
	 */
	for($s=3;$s>=0;$s--) {
		//array_push($sArr, $s);

		if(isset($sets[$s])) {
			$set = $sets[$s];
			/*
			 * init the data array for each set 
			 * as well as the colour config and such
			 */
			
			//exit(json_encode($dataArray['datasets']));
			
			
			/*
			 * calc total for each time label
			 */
			
			$totalsArray = array();
			foreach($dataArray['labels'] as $key => $time) {
				$setTotal = getSetCheckoutQuantity($set, $time, $offset);
				if($setTotal == null) {
					$setTotal = 0;
				}
				array_push($totalsArray, $setTotal);
			}

			array_push($dataArray['datasets'],
				array(
				"data" => $totalsArray,
				"name" => $set['name'],
				"fillColor" => 	"rgba($graphColours[$s],0.25)",
				"strokeColor" => "rgba($graphColours[$s],1)",
				"pointColor" => "rgba($graphColours[$s],1)",
				"pointStrokeColor" => "#fff"));
			//exit(json_encode($dataArray['datasets']));
		
		}
		
		
		
	
	}
	
	
	//exit(json_encode($sArr));
	//exit(json_encode($dataArray['debug']));
	exit(json_encode($dataArray));
	
}


//exit(json_encode(array($_REQUEST, $_POST, $_GET)));


//exit(json_encode($_REQUEST));

switch ($_REQUEST['graph']) {
	case 'market': 
		getMarketShare(json_decode(file_get_contents('php://input')));
		break;
	case 'gross':
		calcListValueGraph();
		break;
	case 'dailycheckout':
		getDailyCheckoutTotal();
		break;
	case 'setcompare' :
		calcSetCheckouts(json_decode(file_get_contents('php://input'), true));
	default:
		exit(json_encode(array('error' => "no graph selected")));
}









$data = json_decode(file_get_contents('php://input'));
//exit(file_get_contents('php://input'));

getMarketShare($data);

//exit(json_encode(getStoreGrossPredictions()));
//exit(json_encode(calcListValueGraph()));

if (!isset($_REQUEST['productid'])) {
	exit(json_encode(array("success" => 0, "error" => "no product ID")));
} elseif(!isset($_REQUEST['location'])) {
	exit(json_encode(array("success" => 0, "error" => "no location")));
} else {
	
	
	
	exit(json_encode(getPriceTimeGraph($_REQUEST['location'], $_REQUEST['productid'])));
	//exit(json_encode(getPriceTimeGraph($_REQUEST['location'], $_REQUEST['productid'])));
}