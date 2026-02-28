<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paper Trading Passado</title>
</head>
<body>
    <form action="" method="GET">
    <input type="text" name="q" placeholder="Pesquisar..." />
    <button type="submit">Buscar</button>

  <?php
    require_once __DIR__ . "/../config/config.php";
    require_once __DIR__ . "/cacheJson.php";

    if (isset($_GET["q"])) {
        $nomeEmpresa = $_GET["q"];
        $arquivo = __DIR__ . "/../Dados/dados_$nomeEmpresa.json";
        $existe = false;

        if (carregarDadosCache($nomeEmpresa)) {
            $dados = carregarDadosCache($nomeEmpresa);
        } else {
            $url = "https://financialmodelingprep.com/stable/search-name?query=$nomeEmpresa&apikey=".API_KEY;
            $dados = criarCacheJson($nomeEmpresa, $url);
        }
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
                echo "<a href = 'detalhes.php?symbol=" . $empresa["symbol"] . "'> Ver detalhes</a>";
            }
            echo "</ul>";
        } else {
            echo "<p>Nenhuma empresa encontrada para: " . $nomeEmpresa . "</p>";
        }
  ?>
</form>
</body>
</html>