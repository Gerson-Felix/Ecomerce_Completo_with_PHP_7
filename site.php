<?php
	
	use \All\Page;
	use \All\Models\Product;
	use \All\Models\Category;
	use \All\Models\Cart;
	use \All\Models\Address;
	use \All\Models\User;
	use \All\Models\Order;
	use \All\Models\OrderStatus;

	$app->get('/', function() {
    	
		$products = Product::listAll();

		$page = new Page();

		$page->setTpl("index", [
			'products'=>Product::checkList($products)
		]);

	});

	$app->get('/categories/:idcategory', function($idcategory){

		$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
		
		$category = new Category();

		$category->get((int)$idcategory);

		$pagination = $category->getProductsPage($page);

		$pages = [];

		for ($i=1; $i < $pagination['pages']; $i++) { 
			array_push($pages, [
				'link'=>'/categories/'.$category->getidcategory()."?page=".$i,
				'page'=>$i
			]);
		}

		$page = new Page();

		$page->setTpl("category", [
			'category'=>$category->getValues(),
			'products'=>$pagination['data'],
			'pages'=>$pages
		]);
	});

	$app->get('/products/:desurl', function($desurl){

		$product = new Product();

		$product->getFromURL($desurl);

		$page = new Page();

		$page->setTpl("product-detail", [

			'product'=>$product->getValues(),
			'categories'=>$product->getCategories()
		]);
	});

	$app->get('/cart', function(){

		$cart = Cart::getFromSession();

		$page = new Page();

		$page->setTpl("cart", [
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()
		]);

	});

	$app->get('/cart/:idproduct/add', function ($idproduct) {

		$product = new Product();

		$product->get((int)$idproduct);

		$cart = Cart::getFromSession();

		$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

		for ($i=0; $i < $qtd; $i++) { 
			$cart->addProduct($product);
		}

		header("Location: /cart");

		exit();
	});

	$app->get('/cart/:idproduct/minus', function ($idproduct) {

		$product = new Product();

		$product->get((int)$idproduct);

		$cart = Cart::getFromSession();

		$cart->removeProduct($product);

		header("Location: /cart");

		exit();
	});

	$app->get('/cart/:idproduct/remove', function ($idproduct) {

		$product = new Product();

		$product->get((int)$idproduct);

		$cart = Cart::getFromSession();

		$cart->removeProduct($product, true);

		header("Location: /cart");

		exit();
	});

	$app->get('/checkout', function () {

		User::verifyLogin(false);

		$cart = Cart::getFromSession();

		$page = new Page();

		$page->setTpl("checkout", [
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()
		]);
	});

	$app->post('/checkout', function (){

		User::verifyLogin(false);

		$user = User::getFromSession();

		$order = new Order();

		$cart = Cart::getFromSession();

		$totals = $cart->getProductsTotals();

		$order->setData([

			'idcart'=>$cart->getidcart(),
			'iduser'=>$user->getiduser(),
			'idstatus'=>OrderStatus::EM_ABERTO,
			'vltotal'=>$totals['vlprice']
		]);

		$order->save();

		header("Location: /order/".$order->getidorder());

		exit();
	});

	$app->get('/login', function () {

		$page = new Page();

		$page->setTpl("login", [
				'error'=>User::getError(),
				'errorRegister'=>User::getErrorRegister(),
				'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
			]);
	});

	$app->post('/login', function () {

		try 
		{
			User::login($_POST['login'], $_POST['password']);

			header("Location: /checkout");

			exit();
		} 
		catch (Exception $e) 
		{
			User::setError($e->getMessage());

			header("Location: /login");

			exit();
		}
	});

	$app->get('/logout', function () {

		User::logout();

		header("Location: /login");

		exit();
	});

	$app->post('/register', function () {

		$_SESSION['registerValues'] = $_POST;

		if (!isset($_POST['name']) || $_POST['name'] == '')
		{
			User::setErrorRegister("Preencha o seu nome");

			header("Location: /login");

			exit();
		}
		if (!isset($_POST['email']) || $_POST['email'] == '')
		{
			User::setErrorRegister("Preencha o seu email");

			header("Location: /login");

			exit();
		}
		if (!isset($_POST['password']) || $_POST['password'] == '')
		{
			User::setErrorRegister("Preencha o seu password");

			header("Location: /login");

			exit();
		}

		if (User::checkLoginExist($_POST['email']) === true)
		{
			User::setErrorRegister("Este endereço de email já está sendo usado por outro usuário");

			header("Location: /login");

			exit();
		}

		$user = new User();

		$user->setData([
			'inadmin'=>0,
			'deslogin'=>$_POST['email'],
			'desperson'=>$_POST['name'],
			'desemail'=>$_POST['email'],
			'despassword'=>$_POST['password'],
			'nrphone'=>$_POST['phone']
		]);

		$user->save();

		User::login($_POST['email'], $_POST['password']);

		header("Location: /checkout");

		exit();
	});

	$app->get('/forgot', function(){
		$page = new Page();

		$page->setTpl("forgot");
	});

	$app->post('/forgot', function(){

		$user = User::getForgot($_POST['email'], false);

		header("Location: /forgot/sent");

		exit();
	});

	$app->get('/forgot/sent', function(){

		$page = new Page();

		$page->setTpl("forgot-sent");		
	});

	$app->get('/forgot/reset', function(){

		$user = User::validForgotDecrypt($_GET["code"]);

		$page = new Page();

		$page->setTpl("forgot-reset", [
			"name"=>$user['desperson'],
			"code"=>$_GET['code']
		]);
	});

	$app->post('/forgot/reset', function(){
		
		$forgot = User::validForgotDecrypt($_POST["code"]);

		User::setForgotUsed($forgot['idrecovery']);

		$user = new User();

		$user->get((int)$forgot['iduser']);

		$password = password_hash($_POST['password'], PASSWORD_DEFAULT, [
			"cost"=>12
		]);

		$user->setPassword($password);

		$page = new Page();

		$page->setTpl("forgot-reset-success");
	});

	$app->get('/profile', function (){

		User::verifyLogin(false);

		$user = User::getFromSession();

		$page = new Page();

		$page->setTpl("profile", [
			'user'=>$user->getValues(),
			'profileMsg'=>User::getSucess(),
			'profileError'=>User::getError()
		]);

	});

	$app->post('/profile', function () {

		User::verifyLogin(false);

		if (!isset($_POST['desperson']) || $_POST['desperson'] === '')
		{
			User::setError("Preecha o seu nome");

			header("Location: /profile");

			exit();
		}

		if (!isset($_POST['desemail']) || $_POST['desemail'] === '')
		{
			User::setError("Preecha o seu email");

			header("Location: /profile");

			exit();
		}

		$user = User::getFromSession();

		if ($_POST['desemail'] != $user->getdesemail())
		{
			if (User::checkLoginExist($_POST['desemail']) === true)
			{
				User::setError("Este endereço de email já está cadastrado");

				header("Location: /profile");

				exit();
			}
		}

		$_POST['inadmin'] = $user->getinadmin();
		$_POST['despassword'] = $user->getdespassword();
		$_POST['deslogin'] = $_POST['desemail'];
		

		$user->setData($_POST);

		$user->save();

		User::setSucess("Dados Alterados com sucesso");

		header('Location: /profile');

		exit();
	});

	$app->get('/order/:idorder', function ($idorder) {

		User::verifyLogin(false); 

		$order = new Order();

		$order->get((int)$idorder);

		$page = new Page();

		$page->setTpl("payment", [

			'order'=>$order->getValues()
		]);
	});

	$app->get('/boleto/:idorder', function($idorder) {

		User::verifyLogin(false);

		$order = new Order();

		$order->get((int)$idorder);

		// DADOS DO BOLETO PARA O SEU CLIENTE
		$dias_de_prazo_para_pagamento = 10;
		$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
		$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
		$valor_cobrado = str_replace(",", ".",$valor_cobrado);
		$valor_boleto=number_format($valor_cobrado, 2, ',', '');

		$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
		$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
		$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
		$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
		$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
		$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

		// DADOS DO SEU CLIENTE
		$dadosboleto["sacado"] = $order->getdesperson();

		// INFORMACOES PARA O CLIENTE
		$dadosboleto["demonstrativo1"] = "Pagamento de Compra no E-commerce";
		$dadosboleto["demonstrativo2"] = "";
		$dadosboleto["demonstrativo3"] = "";
		$dadosboleto["instrucoes1"] = "";
		$dadosboleto["instrucoes2"] = "";
		$dadosboleto["instrucoes3"] = "";
		$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema E-commerce";

		// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
		$dadosboleto["quantidade"] = "";
		$dadosboleto["valor_unitario"] = "";
		$dadosboleto["aceite"] = "";		
		$dadosboleto["especie"] = "Akz";
		$dadosboleto["especie_doc"] = "";


		// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


		// DADOS DA SUA CONTA - ITAÚ
		$dadosboleto["agencia"] = ""; // Num da agencia, sem digito
		$dadosboleto["conta"] = "";	// Num da conta, sem digito
		$dadosboleto["conta_dv"] = ""; 	// Digito do Num da conta

		// DADOS PERSONALIZADOS - ITAÚ
		$dadosboleto["carteira"] = "";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

		// SEUS DADOS
		$dadosboleto["identificacao"] = "GCTecnology";
		$dadosboleto["cpf_cnpj"] = "";
		$dadosboleto["endereco"] = "Rua do Shopping Kinaxixi";
		$dadosboleto["cidade_uf"] = "Luanda";
		$dadosboleto["cedente"] = "";

		// NÃO ALTERAR!
		$path = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR."res".DIRECTORY_SEPARATOR."boletophp".DIRECTORY_SEPARATOR."include".DIRECTORY_SEPARATOR;

		require_once($path. "funcoes_itau.php");
		require_once($path. "layout_itau.php");
	});
?>