<?php
	
	use \All\Page;

	$app->get('/', function() {
    
		$page = new Page();

		$page->setTpl("index");

	});
?>