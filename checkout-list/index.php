<?php

require '../../include/session.php';

global $session, $database;
		

$list = json_decode($_POST['list'], true);
$listID = $_POST['listID'];
/*
 * find the total saved and total cost of the checked out items
 */

$totalSaved = 0;
$totalCost = 0;
//exit(json_encode($list));
$pricesArray = array();
foreach ($list AS $prodID => $listItemArray)
{	
		
	$totalSaved += $listItemArray['saved'];
	$totalCost += $listItemArray['price'];
	
}
//exit(json_encode($pricesArray));
/*
 * build an array (to be exported as json, probably)
 */
$historyArray = array(
		"attributes" => array(
				"userID" => $session->userid,
				"listID" => $listID,
				"totalCost" => $totalCost,
				"totalSaved" => $totalSaved,
				"checkoutTime" => date('Y-m-d H:i:s')
				),
		"products" => $list
		);

//exit(json_encode($historyArray));

if (!$createHistory = $database->insertListHistory($historyArray))
{
	echo json_encode(array("success" => 0, "error" => "checkout failed"));				
}
else
{
	exit(json_encode($historyArray));
}

	