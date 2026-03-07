<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paper Trading Real</title>
    <style>
        body { font-family: Arial, sans-serif; margin-top: 0; }
        .search-container { max-width: 1200px; margin: 30px auto; padding: 20px; }
        .search-container form { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-container input { padding: 10px; border: 1px solid #ddd; border-radius: 4px; flex: 1; }
        .search-container button { padding: 10px 20px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .search-container button:hover { opacity: 0.9; }
        .search-container h2 { color: #1e3c72; }
        .search-container ul { list-style: none; padding: 0; }
        .search-container li { padding: 10px; margin: 5px 0; background: #f5f5f5; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
        .search-container a { color: #2a5298; text-decoration: none; padding: 5px 10px; background: #e8f4f8; border-radius: 3px; }
        .search-container a:hover { text-decoration: underline; background: #d0e8f2; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    <div class="search-container">
        <form action="" method="GET">
            <input type="text" name="q" placeholder="Pesquisar..." />
            <button type="submit">Buscar</button>
        </form>

  <?php
    require_once __DIR__ . "/../config/config.php";
    require_once __DIR__ . "/../src/model/cacheJson.php";

    $dados = [];

    if (isset($_GET["q"])) {
        $nomeEmpresa = $_GET["q"];
        $arquivo = __DIR__ . "/../Dados/dados_$nomeEmpresa.json";
        $existe = false;


        if (carregarDadosCache($nomeEmpresa) && $_SESSION["utilizador"]->getUsername() == "admin") {
            $dados = carregarDadosCache($nomeEmpresa);
        } else {
            $url = "https://financialmodelingprep.com/stable/search-name?query=$nomeEmpresa&apikey=".API_KEY;
            $dados = criarCacheJson($nomeEmpresa, $url);
        }
        
        //Filtra as ações para aquelas somente comercializadas na NASDAQ, evitando que haja duplicata de ações.
        foreach ($dados as $key => $value) {
            if ($value["exchange"] != "NASDAQ") {
                unset($dados[$key]);
            }
        }
        //Exibe informações para o utilizador
        if (count($dados) > 0) {
            echo "<h2>Resultados para: " . $nomeEmpresa . "</h2>";
            echo "<ul>";
            foreach ($dados as $empresa) {
                echo "<li>" . $empresa["name"] . " (" . $empresa["symbol"] . ")";
                //Caso o utilizador clicar em Ver detalhes, será redirecionado para a página detalhes.php
                echo "<a href = 'detalhes_acao.php?symbol=" . $empresa["symbol"] . "'> Ver detalhes</a>";
            }
            echo "</ul>";
        } else {
            echo "<p>Nenhuma empresa encontrada para: " . $nomeEmpresa . "</p>";
        }
    }
  ?>
    </div>
</body>
</html>