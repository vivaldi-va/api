<?php
//http://maps.googleapis.com/maps/api/geocode/json?latlng=60.165184467461124,24.9442247373565&sensor=true

$lat = $_REQUEST['lat'];
$long = $_REQUEST['long'];

$locationInfoStringURL = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$long&sensor=true";

$locationInfo = json_decode(file_get_contents($locationInfoStringURL), true);
//print_r($locationInfo);
$address = $locationInfo['results'][0]['address_components'][1]['long_name']. ' ' . $locationInfo['results'][0]['address_components'][0]['long_name']. ', ' . $locationInfo['results'][0]['address_components'][2]['long_name'];

echo $address;

