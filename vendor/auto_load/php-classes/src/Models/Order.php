<?php
	namespace All\Models;

	use \All\DB\Sql;
	use \All\Model;

	class Order extends Model
	{
		const SUCESS = "Order-Sucess";
		const ERROR = "Order-Error";

		public function save()
		{
			$sql = new Sql();

			$results = $sql->select("CALL sp_orders_save (:idorder, :idcart, :iduser, :idstatus, :vltotal)", [
				':idorder'=>$this->getidorder(),
				':idcart'=>$this->getidcart(),
				':iduser'=>$this->getiduser(),
				':idstatus'=>$this->getidstatus(),
				':vltotal'=>$this->getvltotal()
			]);

			if (count($results[0]) > 0)
			{
				$this->setData($results[0]);
			}
		} 

		public function get ($idorder)
		{
			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_orders a INNER JOIN tb_ordersstatus b USING (idstatus) INNER JOIN tb_carts c USING (idcart) INNER JOIN tb_users d ON d.iduser = a.iduser INNER JOIN tb_persons e ON e.idperson = d.idperson WHERE a.idorder = :idorder", [
				':idorder'=>$idorder
			]);

			if (count($results[0]) > 0)
			{
				$this->setData($results[0]);
			}
		}

		public function listAll()
		{
			$sql = new Sql();

			return $sql->select("SELECT * FROM tb_orders a INNER JOIN tb_ordersstatus b USING (idstatus) INNER JOIN tb_carts c USING (idcart) INNER JOIN tb_users d ON d.iduser = a.iduser INNER JOIN tb_persons e ON e.idperson = d.idperson ORDER BY a.dtregister DESC");
		}

		public function delete()
		{
			$sql = new Sql();

			$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
				':idorder'=>$this->getidorder()
			]);
		}

		public function getCart():Cart
		{
			$cart = new Cart();

			$cart->get((int)$this->getidcart());

			return $cart;
		}

		public static function setError($msg)
		{
			$_SESSION[Order::ERROR] = $msg;
		}

		public static function getError()
		{
			$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

			Order::clearError();

			return $msg;
		}

		public static function clearError()
		{
			$_SESSION[Order::ERROR] = NULL;
		}

		public static function setSuccess($msg)
		{
			$_SESSION[Order::SUCESS] = $msg;
		}

		public static function getSuccess()
		{
			$msg = (isset($_SESSION[Order::SUCESS]) && $_SESSION[Order::SUCESS]) ? $_SESSION[Order::SUCESS] : '';

			Order::clearError();

			return $msg;
		}

		public static function clearSucess()
		{
			$_SESSION[Order::SUCESS] = NULL;
		}
	}
?>