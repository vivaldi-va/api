<?php

require_once './lib/constants.php';
require_once './bootstrap.php';
require_once './User.php';

class Product extends Bootstrap {

	public function getProductInfo($id) {

		if(!$id) {
			$this->returnModel['error'] = "NO_PRODUCT";
			return $this->returnModel;
		}

		$sql = "SELECT 
		products.id AS product_id,
		products.name AS product_name,
		products.picUrl AS product_thumb,
		products.barcode AS barcode,
		products.categoryID
		FROM products
		WHERE
		products.id = $id";

		if(!$result = $this->_query($sql)) {
			return $this->returnModel;
		}

		$product = $result->fetch_assoc();
		$product['product_name'] = utf8_encode($product['product_name']);
		$this->returnModel['data'] = $product;
		$this->returnModel['success'] = true;
		return $this->returnModel;
	}

}