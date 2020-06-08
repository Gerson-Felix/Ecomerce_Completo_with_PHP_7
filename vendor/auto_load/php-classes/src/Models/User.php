<?php  
	namespace All\Models;

	use \All\Model;
	use \All\Mailer;
	use \All\DB\Sql;
	
	class User extends Model
	{
		const SESSION = "user";
		const SECRET = "GCT_Ecommerce_Se";
		const SECRET_IV = "GCT_Ecommerce_IV";
		const ERROR = "UserError";
		const ERROR_REGISTER = "UserErrorRegister";
		const SUCESS = "UserSucess";


		public static function getFromSession()
		{
			$user = new User();

			if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0)
			{
				$user->setData($_SESSION[User::SESSION]);	
			}

			return $user;
		}

		public static function checkLogin($inadmin = true)
		{
			if (!isset($_SESSION[User::SESSION]) 
				|| !$_SESSION[User::SESSION] 
				|| !(int)$_SESSION[User::SESSION]["iduser"] > 0)
			{
				return false;
			}
			else
			{
				if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true)
				{
					return true;
				} elseif ($inadmin === false) {
					return true;
				}
				else
				{
					return false;
				}
			}
		}


		public static function login ($login, $password)
		{
			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE deslogin = :LOGIN", [
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
			if (!User::checkLogin($inadmin))
			{
				if ($inadmin) {
					header("Location: /admin/login");
				}
				else
				{
					header("Location: /login");
				}
				exit();
			}
		}

		public static function logout($inadmin = true)
		{
			$_SESSION[User::SESSION] = null;
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
				":desperson" => $this->getdesperson(),
				":deslogin" =>$this->getdeslogin(),
				":despassword" => User::getPasswordHash($this->getdespassword()),
				":desemail" => $this->getdesemail(),
				":nrphone" => $this->getnrphone(),
				":inadmin" => $this->getinadmin()
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
				":iduser"=> $this->getiduser(),
				":desperson" => $this->getdesperson(),
				":deslogin" =>$this->getdeslogin(),
				":despassword" => User::getPasswordHash($this->getdespassword()),
				":desemail" => $this->getdesemail(),
				":nrphone" => $this->getnrphone(),
				":inadmin" => $this->getinadmin()
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

		public static function getForgot($email, $inadmin = true)
		{
			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :desemail", [
				":desemail"=>$email
			]);

			if (count($results) == 0)
			{
				throw new \Exception("Não foi possível recupera a senha");
			}
			else
			{
				$data = $results[0];

				$resultsRecover = $sql->select("CALL sp_userspasswordsrecoveries_create (:iduser, :desip)", [
					":iduser"=>$data['iduser'],
					":desip"=>$_SERVER["REMOTE_ADDR"]
				]);

				if (count($resultsRecover) === 0)
				{
					throw new Exception("Error Processing Request", 1);
				}
				else
				{
					$dataRecover = $resultsRecover[0];

					$code = base64_encode(openssl_encrypt($dataRecover['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV)));

					if ($inadmin === true)
					{
						$link = "http://gct-dev.ao/admin/forgot/reset?code=$code";
					}
					else
					{
						$link = "http://gct-dev.ao/forgot/reset?code=$code";
					}
					

					$mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir Senha do E-Commerce", "forgot", [
						"name"=>$data['desperson'],
						"link"=>$link
					]);

					$mailer->send();

					return $data;
				}
			}
		}

		public static function validForgotDecrypt($code)
		{
			$code = base64_decode($code);

			$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));



			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a INNER JOIN tb_users b using(iduser) INNER JOIN tb_persons c using(idperson) WHERE a.idrecovery = :idrecovery AND a.dtrecovery IS NULL AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();", [
				":idrecovery"=>$idrecovery
			]);

			if (count($results) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha");
				
			}
			else
			{
				return $results[0];
			}
		}

		public static function setForgotUsed($idrecovery)
		{
			$sql = new Sql();

			$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", [
				":idrecovery"=>$idrecovery
			]);
		}

		public function setPassword($password)
		{
			$sql = new Sql();

			$sql->query("UPDATE tb_users SET despassword = :pass WHERE iduser = :iduser", [
				":pass"=>$password,
				":iduser"=>$this->getValues()['iduser']
			]);
		}

		public static function setError($msg)
		{
			$_SESSION[User::ERROR] = $msg;
		}

		public static function getError()
		{
			$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

			User::clearError();

			return $msg;
		}

		public static function clearError()
		{
			$_SESSION[User::ERROR] = NULL;
		}

		public static function setErrorRegister($msg)
		{
			$_SESSION[User::ERROR_REGISTER] = $msg;
		}

		public static function getErrorRegister()
		{
			$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

			User::clearError();

			return $msg;
		}

		public static function getPasswordHash ($password)
		{
			return password_hash($password, PASSWORD_DEFAULT, ['cost'=>12]);
		}

		public static function checkLoginExist ($login)
		{
			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
				':deslogin'=>$login
			]);

			return (count($results) > 0);
		}
	}
?>