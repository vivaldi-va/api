<?php

require_once '../../../include/product_functions.php';

global $product_functions;

$listItemId = $_REQUEST['listid'];

if ($product_functions->removeFromList($listItemId)) {
	exit(json_encode(array("success" => 1)));
}
else {
	exit(json_encode(array("error" => "removing product failed")));
}

