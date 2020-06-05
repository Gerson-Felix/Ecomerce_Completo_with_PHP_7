<?php  
	namespace All;

	use \Rain\Tpl;

	//Classe Responsavel pela Renderização do HTML genérico do Sistema
	class Page
	{
		private $tpl;
		private $options = [];
		private $defaults = [
			"header"=>true,
			"footer"=>true,
			"data" => []
		];

		//Função Redundante no Construtor e no setTpl
		private function setData ($data = [])
		{
			foreach ($data as $key => $value) {
				$this->tpl->assign($key, $value);
			}
		}
		
		//Construtor Responsavel pelo Header (Cabeçalho) das Paginas HTML
		public function __construct($opts = [], $tpl_dir = "/views/")
		{
			$this->options = array_merge($this->defaults, $opts);

			$config = array(
				"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,
				"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache",
				"debug"         => false
			);

			Tpl::configure( $config );

			$this->tpl = new Tpl;


			//Chamada da Função setData
			$this->setData($this->options["data"]);

			if ($this->options["header"] === true) $this->tpl->draw("header");

		}

		// setTpl responsável pelas Variações de conteudo no Body
		public function setTpl ($name, $data = [], $returnHTML = false)
		{

			//Chamada da Função setData
			$this->setData($data);

			var_dump($this->tpl->draw($name, $returnHTML));

			exit();
		}

		// Destrutor Responsavel pelo Footer (Rodapé) das Páginas HTML
		public function __destruct ()
		{
			if ($this->options["footer"] === true) $this->tpl->draw("footer");
		}
	}
?>