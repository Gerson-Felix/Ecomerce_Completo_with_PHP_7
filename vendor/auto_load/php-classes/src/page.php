<?php  
	namespace All;

	use Rain\Tpl;

	//Classe Responsavel pela Renderização do HTML genérico do Sistema
	class Page
	{
		private $tpl;
		private $options = [];
		private $defaults = [
			"data" => []
		];

		//Função Redundante no Construtor e no setTpl
		private function setData ($data = [])
		{
			foreach ($data as $key => $value) {
				$this->$tpl->assign($key, $value);
			}
		}
		
		//Construtor Responsavel pelo Header (Cabeçalho) das Paginas HTML
		public function __construct($opts = [])
		{
			$this->options = array_merge($this->defaults, $opts);

			$config = array(
				"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/",
				"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache",
				"debug"         => false
			);

			Tpl::configure( $config );

			$this->tpl = new Tpl;


			//Chamada da Função setData
			$this->setData($this->options["data"]);

			$this->tpl->draw("header");

		}

		// setTpl responsável pelas Variações de conteudo no Body
		public function setTpl ($name, $data = [], $returnHTML = false)
		{

			//Chamada da Função setData
			$this->setData($data);

			return $this->tpl->draw($name, $returnHTML);
		}

		// Destrutor Responsavel pelo Footer (Rodapé) das Páginas HTML
		public function __destruct ()
		{
			$this->tpl->draw("footer");
		}
	}
?>