<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Empresa: <?php echo $_GET["symbol"] ?? "Desconhecida"; ?></title>
</head>
<body>
    <?php
    require_once __DIR__ . "../../config/config.php";
    if (isset($_GET["symbol"])) {
        $symbol = $_GET["symbol"];
        $url = "https://financialmodelingprep.com/stable/profile?symbol=$symbol&apikey=".API_KEY;
        $response = file_get_contents($url);
        $dados = json_decode($response, true);
        $arquivo = __DIR__ . "../../Dados/infoEmpresa_$symbol.json";
        file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT));

        foreach ($dados as $empresa){
            echo "<li> Nome da Empresa: " . $empresa["companyName"] . "</li>";
            echo "<li> Preço da Ação: " . $empresa["price"] . "</li>";
            echo "Descrição: " . $empresa["description"];
        }

        

    }
    ?>
</body>
</html>