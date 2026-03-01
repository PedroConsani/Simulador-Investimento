<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Empresa: <?php echo $_GET["symbol"] ?? "Desconhecida"; ?></title>
</head>

<body>
    <nav>
        <a href="/investimento/public/index.php">
            <li>Home</li>
        </a>
        <a href="paper_tr_jogo.php">
            <li>Paper Trading Passado</li>
        </a>
        <a href="paper_tr_real.php">
            <li>Paper Trading Real</li>
        </a>
    </nav>

    <?php
    require_once __DIR__ . "/../../config/config.php";
    require_once __DIR__ . "/cacheJson.php";

    if (isset($_GET["symbol"])) {
        $symbol = $_GET["symbol"];
        $url = "https://financialmodelingprep.com/stable/profile?symbol=$symbol&apikey=" . API_KEY;
        if (carregarDadosCache("infoEmpresa_$symbol")) {
            $dados = carregarDadosCache("infoEmpresa_$symbol");
        } else {
            $dados = criarCacheJson("infoEmpresa_$symbol", $url);
        }

        foreach ($dados as $empresa) {

            $preco = $empresa["price"];

            echo "<h2>" . $empresa["companyName"] . "</h2>";
            echo "<p>Preço por ação: $ <span id='preco'>$preco</span></p>";;
            echo "<p>" . $empresa["description"] . "</p>";

            echo "<hr>";

            // FORMULÁRIO DE COMPRA

            echo '<form method="POST">';
            echo '<label>Quantidade de Ações:</label>';
            echo '<input type="number" id="quantidade" name="quantidade" min="1" required>';
            echo '<p>Total: $ <span id="total"> 0.00 </span></p>';
            echo '<button type="submit" name="comprar">Comprar</button>';
            echo '</form>';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantidade'])) {
            $quant = intval($_POST['quantidade']);
            if ($quant <= 0) {
                echo "<p>Quantidade inválida.</p>";
            } else {
                if (!isset($_SESSION['utilizador'])) {
                    echo "<p>Faça login para comprar ações.</p>";
                } else {
                    $username = $_SESSION['utilizador']->getUsername();
                    $usuarioData = Utilizador::findUser($username);
                    $saldoAtual = isset($usuarioData['saldo']) ? floatval($usuarioData['saldo']) : 0.0;
                    $custo = $precoAtual * $quant;
                    if ($saldoAtual < $custo) {
                        echo "<p>Saldo insuficiente. Custo: $custo, Saldo: $saldoAtual</p>";
                    } else {
                        $novoSaldo = $saldoAtual - $custo;
                        // Atualiza saldo e histórico
                        Utilizador::atualizarSaldo($username, $novoSaldo);
                        $entrada = [
                            'tipo' => 'compra',
                            'symbol' => $symbol,
                            'nomeEmpresa' => $empresa['companyName'] ?? '',
                            'quantidade' => $quant,
                            'preco' => $precoAtual,
                            'data' => date("Y-m-d H:i:s")
                        ];
                        Utilizador::adicionarHistorico($username, $entrada);
                        // Atualiza o objeto na sessão
                        $_SESSION['utilizador']->setSaldoReal($novoSaldo);
                        echo "<p>Compra efetuada com sucesso! Custo: $custo. Novo saldo: $novoSaldo</p>";
                    }
                }
            }
        }
    }
    ?>
    <script>
        const preco = parseFloat(document.getElementById("preco").innerText);
        const inputQtd = document.getElementById("quantidade");
        const totalSpan = document.getElementById("total");

        // Atualiza o total conforme a quantidade é digitada
        inputQtd.addEventListener("input", function() {
            const quantidade = parseInt(inputQtd.value) || 0;
            const total = preco * quantidade;
            totalSpan.innerText = total.toFixed(2);
        });
    </script>
</body>

</html>