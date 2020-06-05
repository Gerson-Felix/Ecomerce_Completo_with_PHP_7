<?php  
	namespace All;

	class Model
	{
		private $values = [];

		public function __call($name, $args)
		{
			$method = substr($name, 0, 3);
			$fieldName = substr($name, 3, strlen($name));

			switch ($method) {
				case "get":
					$this->values[$fieldName];
					break;

				case "set":
					$this->values[$fieldName] = $args[0];
					break;
				
				default:
					throw new \Exception("Erro na Chamada dos getters and setters");
					break;
			}
		}

		public function setData($data = [])
		{
			foreach ($data as $key => $value) {
				$this->{"set".$key}($value);
			}
		}

		public function getValues()
		{
			return $this->values;
		}
	}
?>