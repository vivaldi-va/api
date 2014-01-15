<?php

require_once '../../include/product_functions.php';

global $product_functions;

$productID = $_GET['productID'];




if ($info = $product_functions->getProductDetails($productID)) {
	exit( json_encode($info));
}
else {
	exit(json_encode(array("error" => "product not found")));
}


