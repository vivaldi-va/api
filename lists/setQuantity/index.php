<?php
require_once '../../../include/product_functions.php';

global $product_functions;
$return = array();

$quantity = $_REQUEST['quantity'];
$listItemID = $_REQUEST['listid'];
//exit($quantity . " " . $listItemID);
exit(json_encode($product_functions->setListItemQuantity($listItemID, $quantity)));