<?php
	use \All\Models\User;
	use \All\Models\Cart;

	function formatPrice(float $vlprice)
	{
		return number_format($vlprice, 2, ",", ".");
	}

	function formatDate($date)
	{
		return date('d/m/Y', strtotime($date));
	}

	function checkLogin($inadmin = true)
	{
		return User::checkLogin($inadmin);
	}

	function getUsername()
	{
		$user = User::getFromSession();

		return $user->getdesperson();
	}

	function getCartNrQtd()
	{
		$cart = Cart::getFromSession();

		$totals = $cart->getProductsTotals();

		return $totals['nrqtd'];
	}

	function getCartVltotal()
	{
		$cart = Cart::getFromSession();

		$totals = $cart->getProductsTotals();

		return ($totals['vlprice'] != '') ? formatPrice($totals['vlprice']) : 0.0;
	}

	function getNomeAdmin()
	{
		$user = User::getFromSession();

		return $user->getdeslogin();
	}
?>