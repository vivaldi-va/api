<?php
include ("../../include/database.php");
function getUncategorized($like = "avansera_all", $limit=false) {
	global $database;
	
	if($limit) {
		$limitStr = " limit $limit";
	} else {
		$limitStr = "";
	}
	
	if ($like == "avansera_all") {
		
		
		$result = mysql_query ( "select id, name, barcode, categoryID, created, picUrl from ostosnero.products where categoryid<999 order by id desc$limitStr" ) or trigger_error ( mysql_error () );
	} else {
		$othersearch = mysql_real_escape_string ( $like );
		
		if (strpos ( $like, ' ' )) {
			$name_search = preg_replace ( "/ /", "%' AND name like '%", $othersearch );
		} else {
			$name_search = mysql_real_escape_string ( $like );
		}
		
		$result = mysql_query ( "SELECT id, name, barcode, categoryID, created, picUrl from ostosnero.products WHERE (name LIKE '%" . $name_search . "%'
                                        OR barcode like '%" . $othersearch . "%'
                                        OR id like '%" . $othersearch . "%' )$limitStr" ) or trigger_error ( mysql_error () );
	}
	
	$array = array ();
	while ( $row = mysql_fetch_assoc ( $result ) ) {
		$row = array_map ( 'stripslashes', $row );
		$array [] = $row;
		// $array[] = array(stripslashes($row['id'], stripslashes($row['name'], stripslashes($row['barcode']), stripslashes($row['categoryID']), stripslashes($row['created']), stripslashes($ro$
	}
	return $array;
}

function compareKeywords($keywords, $wordArr) {
	foreach($keywords AS $keywordArr) {
		if (!$keywordsKey = array_search($wordArr['keyword'], $keywordArr)) {
			$wordArr['keyword'] = $word;
			array_push($keywords, $wordArr);
	
		} else {
			$keywords[$keywordsKey]['frequency']++;
		}
	}
	return $keywords;
}

function getFilterterms($parameter, $results) {
	$keywords = array();
	$frequency = array();
	$topTenArr = array();
	// for each of the result rows
	
	foreach($results AS $array) {
		$name = strtolower($array['name']);
		
		// explode the product name into keywords by space delimitaion
		$explode = explode(" ", $name);
		
		/*
		 * for each of the keywords, check if they have been 
		 * added into the keyword array. 
		 * If not, add them via push
		 */
		
		foreach($explode AS $word) 
		{
			if (preg_match('/(\w+)+/', $word) && $word != $parameter) {
					
				if($keywords[$word] == null) {
					$keywords[$word] = 1;
				} else {
					$keywords[$word]++;
				}
			}
			
			
		}
		
		// sort the array by freqency of occurance
		arsort($keywords);
			
		$i = 0;
		foreach($keywords AS $keyword => $freq) {
			if ($i < 10) {
				$topTenArr[$keyword] = $freq;
				$i++;
			} else {
				break;
			}
		}
		

	} 
	
	
	//exit(json_encode($keywords));
	arsort($topTenArr);
	$return = array();
	foreach($topTenArr AS $word => $freq) {
		$termArr = array("keyword" => $word, "frequency" => $freq);
		array_push($return, $termArr);	
	}	
	return $return;
}

$parameter = $_REQUEST ['term'];

header ( 'Content-type: application/json' );
header ( "access-control-allow-origin: *" );

if ($parameter == NULL) {
	$results ['status'] = 'FAILED';
	$results ['message'] = 'No search term provided';
	exit ( json_encode ( $results ) );
} else {
	$results = getUncategorized ( $parameter );
	
	if (isset($_REQUEST['filter'])) {
		$results = getUncategorized ( $parameter );
		$results['filter_terms'] = getFilterterms($parameter, $results);
	} else {
		$results = getUncategorized ( $parameter, 50 );
	}
	
	$encoded = json_encode ( $results );
	
	if ($debug == 1) {
		print $command . " " . $parameter . " ";
		var_dump ( $results );
	}
	exit ( $encoded );
}



