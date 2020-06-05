<?php  
	namespace All\Models;

	use \All\Model;
	use \All\DB\Sql;
	
	class User extends Model
	{
		const SESSION = "user";

		public static function login ($login, $password)
		{
			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", [
				":LOGIN" => $login
			]);

			if (count($results) === 0) 
			{
				throw new \Exception("User não Encontrado!");
			}

			$data = $results[0];

			if (password_verify($password, $data["despassword"]) === true)
			{
				$user = new User();

				$user->setData($data);

				$_SESSION[User::SESSION] = $user->getValues();

				return $user;
			}
			else
			{
				 throw new \Exception("Password Inválida!");
			}
		}

		public static function verifyLogin($inadmin = true)
		{
			if (!isset($_SESSION[User::SESSION]) 
				|| !$_SESSION[User::SESSION] 
				|| !(int)$_SESSION[User::SESSION]["iduser"] > 0
				|| (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin)
			{
				header("Location: /admin/login");
				exit();
			}
		}

		public static function logout($inadmin = true)
		{
			$_SESSION[User::SESSION] = NULL;
		}

		public static function listAll()
		{
			$sql = new Sql();

			return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
		}

		public function save()
		{
			$sql = new Sql();

			$results = $sql->select("CALL sp_users_save (:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
				":desperson" => $this->getValues()['desperson'],
				":deslogin" =>$this->getValues()['deslogin'],
				":despassword" => $this->getValues()['despassword'],
				":desemail" => $this->getValues()['desemail'],
				":nrphone" => $this->getValues()['nrphone'],
				":inadmin" => $this->getValues()['inadmin']
			]);

			$this->setData($results[0]);
		}

		public function get ($iduser)
		{
			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING (idperson) WHERE a.iduser = :iduser", [
				":iduser"=>$iduser
			]);

			$this->setData($results[0]);
		}

		public function update()
		{
			$sql = new Sql();

			$results = $sql->select("CALL sp_usersupdate_save (:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
				":iduser"=> $this->getValues()['iduser'],
				":desperson" => $this->getValues()['desperson'],
				":deslogin" =>$this->getValues()['deslogin'],
				":despassword" => $this->getValues()['despassword'],
				":desemail" => $this->getValues()['desemail'],
				":nrphone" => $this->getValues()['nrphone'],
				":inadmin" => $this->getValues()['inadmin']
			]);

			$this->setData($results[0]);
		}

		public function delete()
		{
			$sql = new Sql();

			$sql->query("CALL sp_users_delete (:iduser)", [
				":iduser"=>$this->getValues()['iduser']
			]);
		}
	}
?>