<?php
require_once '../../../include/product_functions.php';

global $product_functions;

$post = json_decode(file_get_contents('php://input'), true);

if (!isset($post['price'])) {
	exit(json_encode(array("error" => "no price entered")));
}
elseif (!isset($post['productid'])) {
	exit(json_encode(array("error" => "no product selected")));
}
elseif (!isset($post['shopid'])) {
	exit(json_encode(array("error" => "no shop selected")));
}

$productID = $post['productid'];
$shopID = $post['shopid'];
$price = $post['price'];

$price =  str_replace(',', '.', $price);
//exit($price.", ".$);

exit($product_functions->setProductPrice($productID, $shopID, $price));