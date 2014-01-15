<?php

require_once '../../include/database.php';

global $database;

$lat = $_GET['lat'];//$_POST['lat'];
$long = $_GET['long'];//$_POST['long'];

if (isset($_GET['keyword'])) {
	$keyword = $_GET['keyword'];
}
 
$num = 5;
$maxDistance = null;
if (isset($_GET['maxDistance'])) {
	$maxDistance = $_GET['maxDistance'];
}

if (isset($_GET['num'])) {
	$num = $_GET['num'];
}



if (isset($keyword) && $keyword != null) {
	exit(json_encode($database->selectLocations($keyword)));
}
exit(json_encode($database->getShopList($lat, $long, $num, $maxDistance)));





