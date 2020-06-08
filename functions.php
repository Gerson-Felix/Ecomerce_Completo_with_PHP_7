<?php
	use \All\Models\User;

	function formatPrice(float $vlprice)
	{
		return number_format($vlprice, 2, ",", ".");
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
?>