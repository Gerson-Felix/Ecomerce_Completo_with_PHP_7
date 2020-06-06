<?php
	
	use \All\Page;
	use \All\Models\Product;

	$app->get('/', function() {
    	
		$products = Product::listAll();

		$page = new Page();

		$page->setTpl("index", [
			'products'=>Product::checkList($products)
		]);

	});
?>