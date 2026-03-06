<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Portfólio</title>
    <style>
        body { font-family: Arial, sans-serif; margin-top: 0; padding: 20px; }
        .section { margin-bottom: 30px; border: 1px solid #ccc; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .sell-form { display: inline; }
        .sell-form input { width: 60px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div style="max-width: 1200px; margin: 0 auto;">
        <h1>Dashboard do Portfólio</h1>

    <?php
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../src/Model/utilizador.php';
    require_once __DIR__ . '/../src/Model/historico.php';
    require_once __DIR__ . '/../src/Model/cacheJson.php';
    require_once __DIR__ . '/../src/Model/utilizador.php';
    session_start();
    if (!isset($_SESSION['utilizador'])) {
        header('Location: login.php');
        exit;
    }

    $user = $_SESSION['utilizador'];
    $username = $user->getUsername();
    $userData = Utilizador::findUser($username);
    $saldo = isset($userData['saldo']) ? $userData['saldo']: 0.0;
    $historico = $userData['historico'] ?? [];

    // Montagem da carteira com base no histórico de transações
    $carteira = [];
    foreach ($historico as $trans) {
        $symbol = $trans['symbol'];
        $quant = intval($trans['quantidade']);
        if ($trans['tipo'] === 'compra') {
            if (!isset($carteira[$symbol])) {
                $carteira[$symbol] = ['quantidade' => 0, 'total_investido' => 0, 'nome' => $trans['nomeEmpresa']];
            }
            $carteira[$symbol]['quantidade'] += $quant;
            $carteira[$symbol]['total_investido'] += ($trans['preco'] ?? 0) * $quant;
        } elseif ($trans['tipo'] === 'venda') {
            $carteira[$symbol]['quantidade'] -= $quant;
            $carteira[$symbol]['total_investido'] -= ($trans['preco'] ?? 0) * $quant;
            if ($carteira[$symbol]['quantidade'] <= 0) {
                unset($carteira[$symbol]);
            }
        }
    }

    // Obter preços atuais
    $currentPrices = [];
    foreach ($carteira as $symbol => $data) {
        $url = "https://financialmodelingprep.com/stable/profile?symbol=$symbol&apikey=" . API_KEY;
        //$dados = json_decode(file_get_contents($url), true);
        if (carregarDadosCache("infoEmpresa_$symbol")) {
            $dados = carregarDadosCache("infoEmpresa_$symbol");
        } else {
            $dados = criarCacheJson("infoEmpresa_$symbol", $url);
        }
        if (!empty($dados)) {
            $currentPrices[$symbol] = floatval($dados[0]['price']);
        } else {
            $currentPrices[$symbol] = 0;
        }
    }

    // Processar venda
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vender'])) {
        $symbol = $_POST['symbol'];
        $quant_venda = intval($_POST['quant_venda']);
        if ($quant_venda > 0 && isset($carteira[$symbol]) && $carteira[$symbol]['quantidade'] >= $quant_venda) {
            $preco_atual = $currentPrices[$symbol] ?? 0;
            $valor_venda = $preco_atual * $quant_venda;
            $novo_saldo = $saldo + $valor_venda;
            Utilizador::atualizarSaldo($username, $novo_saldo);
            Historico::adicionarVenda($username, $carteira[$symbol]['nome'], $symbol, $quant_venda, $preco_atual);
            $_SESSION['utilizador']->setSaldoReal($novo_saldo);
            $saldo = $novo_saldo;
            // Recalcular carteira
            $carteira[$symbol]['quantidade'] -= $quant_venda;
            if ($carteira[$symbol]['quantidade'] <= 0) {
                unset($carteira[$symbol]);
            }
            echo "<p>Venda efetuada com sucesso! Recebido: $$valor_venda. Novo saldo: $$novo_saldo</p>";
        } else {
            echo "<p>Erro na venda: quantidade inválida ou insuficiente.</p>";
        }
    }
    ?>

    <div class="section">
        <h2>Saldo Atual</h2>
        <p>$<?php echo $saldo; ?></p>
    </div>

    <div class="section">
        <h2>Portfólio Atual</h2>
        <?php if (empty($carteira)): ?>
            <p>Nenhuma ação no portfólio.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Símbolo</th>
                    <th>Nome</th>
                    <th>Quantidade</th>
                    <th>Preço Médio</th>
                    <th>Valor Atual</th>
                    <th>Rendimento</th>
                    <th>Ação</th>
                </tr>
                <?php foreach ($carteira as $symbol => $data): 
                    $quant = $data['quantidade'];
                    $preco_medio = $quant > 0 ? $data['total_investido'] / $quant : 0;
                    var_dump("total_investido: ", $data["total_investido"]);
                    $preco_atual = $currentPrices[$symbol] ?? 0;
                    $valor_atual = $preco_atual * $quant;
                    $investido = $data['total_investido'];
                    $rendimento = $valor_atual - $investido;
                ?>
                <tr>
                    <td><?php echo $symbol; ?></td>
                    <td><?php echo $data['nome']; ?></td>
                    <td><?php echo $quant; ?></td>
                    <td>$ <?php echo $preco_medio; ?></td>
                    <td>$ <?php echo $valor_atual; ?></td>
                    <td>$ <?php echo $rendimento; ?> (<?php echo $investido > 0 ? ($rendimento / $investido) * 100 . '%' : '0%'; ?>)</td>
                    <td>
                        <form class="sell-form" method="POST">
                            <input type="hidden" name="symbol" value="<?php echo $symbol; ?>">
                            <input type="number" name="quant_venda" min="1" max="<?php echo $quant; ?>" required>
                            <button type="submit" name="vender">Vender</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Histórico de Transações</h2>
        <?php if (empty($historico)): ?>
            <p>Nenhuma transação realizada.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Símbolo</th>
                    <th>Nome</th>
                    <th>Quantidade</th>
                    <th>Preço</th>
                    <th>Total</th>
                </tr>
                <?php foreach ($historico as $trans): ?>
                <tr>
                    <td><?php echo $trans['data']; ?></td>
                    <td><?php echo ucfirst($trans['tipo']); ?></td>
                    <td><?php echo $trans['symbol']; ?></td>
                    <td><?php echo $trans['nomeEmpresa']; ?></td>
                    <td><?php echo $trans['quantidade']; ?></td>
                    <td>$ <?php echo $trans['preco'] ?? 0; ?></td>
                    <td>$ <?php echo $trans['preco'] ?? 0 * $trans['quantidade']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    </div>

</body>
</html>