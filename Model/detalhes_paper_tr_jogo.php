<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogo Paper Trading - <?php echo $_GET["symbol"] ?? "Desconhecida"; ?></title>
</head>
<body>
    <nav>
        <a href="/investimento/index.php"><li>Home</li></a>
        <a href="paper_tr_passado.php"><li>Paper Trading Passado</li></a>
        <a href="paper_tr_real.php"><li>Paper Trading Real</li></a>
    </nav>

    <h1>Jogo Paper Trading</h1>
    <p>Compre ações e acompanhe seu desempenho no jogo de trading virtual!</p>
    <h2>Instruções:</h2>
    <ul>
        <li>Selecione a quantidade de ações que deseja comprar. Após, Selecione a data para se vender.</li>
        <li>Clique em "Simular" para verificar o desempenho das suas ações.</li>
        <li>A sua pontuação é calculada com base no lucro ou prejuízo obtido no jogo.</li>
    </ul>

    <form action="" method="post">
        <label for="quantidade">Quantidade de Ações da <?php echo $_GET["symbol"] ?? "Desconhecida"; ?>: </label>
        <input type="number" name="quantidade" id="quantidade" min="1">
        <button type="submit">Comprar</button>
    </form>
    <br>
    <br>

    <?php
    require_once __DIR__ . "../../config/config.php";
    require_once __DIR__ . "../../Model/cacheJson.php";
    if (isset($_GET["symbol"])) {
        $symbol = $_GET["symbol"];
        $url = "https://financialmodelingprep.com/stable/profile?symbol=$symbol&apikey=".API_KEY;
        if (carregarDadosCache("infoEmpresa_$symbol")){
            $dados = carregarDadosCache("infoEmpresa_$symbol");
        }
        else {
            $dados = criarCacheJson("infoEmpresa_$symbol", $url);
        }

        foreach ($dados as $empresa){
            echo "<li> Nome da Empresa: " . $empresa["companyName"] . "</li>";
            echo "<li> Preço da Ação: " . $empresa["price"] . "</li>";
            echo "Descrição: " . $empresa["description"];
        }       
    }
?>
</body>
</html>