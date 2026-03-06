<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Empresa: <?php echo $_GET["symbol"] ?? "Desconhecida"; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin-top: 0; }
        .details-container { max-width: 1200px; margin: 30px auto; padding: 20px; }
        .details-container h2 { color: #1e3c72; margin-bottom: 10px; }
        .details-container p { margin: 10px 0; }
        .details-container hr { margin: 20px 0; border: none; border-top: 1px solid #ccc; }
        .buy-form { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; }
        .buy-form label { display: block; margin-bottom: 10px; font-weight: 600; color: #333; }
        .buy-form input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; }
        .buy-form button { padding: 10px 20px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .buy-form button:hover { opacity: 0.9; }
        .info-box { background: #e8f4f8; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #2a5298; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../public/navbar.php'; ?>
    <div class="details-container">

    <?php
    require_once __DIR__ . "/../../config/config.php";
    require_once __DIR__ . "/cacheJson.php";
    require_once __DIR__ . "/utilizador.php";
    require_once __DIR__ . "/historico.php";
    //session_start();

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
            echo "<p>Preço por ação: $ <span id='preco'>$preco</span></p>";
            echo "<p>" . $empresa["description"] . "</p>";

            echo "<hr>";

            // FORMULÁRIO DE COMPRA
            echo '<div class="buy-form">';
            echo '<form method="POST">';
            echo '<label>Quantidade de Ações:</label>';
            echo '<input type="number" id="quantidade" name="quantidade" min="1" required>';
            echo '<p>Total: $ <span id="total"> 0.00 </span></p>';
            echo '<button type="submit" name="comprar">Comprar</button>';
            echo '</form>';
            echo '</div>';
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
                    $custo = $preco * $quant;
                    if ($saldoAtual < $custo) {
                        echo "<p>Saldo insuficiente. Custo: $$custo, Saldo: $$saldoAtual</p>";
                    } else {
                        $novoSaldo = $saldoAtual - $custo;
                        // Atualiza saldo e histórico
                        Utilizador::atualizarSaldo($username, $novoSaldo);
                        Historico::adicionarCompra($username, $empresa['companyName'] ?? '', $symbol, $quant, $preco);
                        // Atualiza o objeto na sessão
                        $dadosAtualizados = Utilizador::findUser($username);
                        $_SESSION['utilizador'] = new Utilizador($dadosAtualizados['username'], '', $dadosAtualizados['saldo']);
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
        const compra = document.querySelector("button[name='comprar']");

        // Atualiza o total conforme a quantidade é digitada
        inputQtd.addEventListener("input", function() {
            const quantidade = parseInt(inputQtd.value) || 0;
            const total = preco * quantidade;
            totalSpan.innerText = total.toFixed(2);
        });

        compra.addEventListener("click", function(){
           setTimeout(function(){
                window.location.reload();
           },10) 
        })
    </script>
    </div>
</body>

</html>