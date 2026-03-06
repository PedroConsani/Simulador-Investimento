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
    
    //session_start();
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

        if (carregarDadosCache("info_empresa_$symbol")) {
            $dados = carregarDadosCache("info_empresa_$symbol");
        } else {
            $dados = criarCacheJson("info_empresa_$symbol", $url);
        }
        if (!empty($dados)) {
            $currentPrices[$symbol] = floatval($dados[0]['price']);
        } else {
            $currentPrices[$symbol] = 0;
        }
    }

    // Processar atualização de preços
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_precos'])) {
        // Limpar cache e buscar novos preços
        foreach (array_keys($carteira) as $symbol) {
            $arquivoCache = __DIR__ . '/investimento/data_' . $symbol . '.json';
            if (file_exists($arquivoCache)) {
                unlink($arquivoCache); // Deletar arquivo de cache
            }
        }
        // Recarregar página para buscar novos preços
        echo "<script>window.reload()</script>";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vender'])) {

        $symbol = $_POST['symbol'];
        $quant_venda = intval($_POST['quant_venda']);
        $resultado = processarVenda($username, $symbol, $quant_venda);
      
    }

    // Processar venda
    function processarVenda($username, $symbol, $quant_venda) {
        // Lógica para processar a venda, atualizar saldo e histórico
        // Retorna um array com o resultado da operação
        global $saldo;
        global $carteira;
        if ($quant_venda > 0 && isset($carteira[$symbol]) && $carteira[$symbol]['quantidade'] >= $quant_venda) {
            $preco_atual = $currentPrices[$symbol] ?? 0;
            $valor_venda = $preco_atual * $quant_venda;
            $novo_saldo =  $saldo + $valor_venda;

            Utilizador::atualizarSaldo($username, $novo_saldo);
            Historico::adicionarVenda($username, $carteira[$symbol]['nome'], $symbol, $quant_venda, $preco_atual);
            $dadosAtualizados = Utilizador::findUser($username);
            $_SESSION["utilizador"] = new Utilizador($dadosAtualizados["username"], "", $novo_saldo);
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
         return [
            'success' => true,
            'valor_venda' => $valor_venda,
            'novo_saldo' => $novo_saldo
        ];

    }
    ?>

    <div class="section">
        <h2>Saldo Atual</h2>
        <p>$<?php echo number_format($saldo,2); ?></p>
    </div>

    <!--Portfólio Atual-->
    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="margin: 0;">Portfólio Atual</h2>
            <?php if (!empty($carteira)): ?>
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="atualizar_precos" style="padding: 8px 16px; background: #2a5298; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">🔄 Atualizar Preços</button>
                </form>
            <?php endif; ?>
        </div>
        <?php if (empty($carteira)): ?>
            <p>Nenhuma ação no portfólio.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Símbolo</th>
                    <th>Nome</th>
                    <th>Quantidade</th>
                    <th>Preço Atual</th>
                    <th>Valor Atual</th>
                    <th>Rendimento</th>
                    <th>Ação</th>
                </tr>
                <?php foreach ($carteira as $symbol => $data): 
                    $quant = $data['quantidade'];
                    $preco_medio = $quant > 0 ? $data['total_investido'] / $quant : 0;
                    $preco_atual = $currentPrices[$symbol] ?? 0;
                    $valor_atual = $preco_atual * $quant;
                    $investido = $data['total_investido'];
                    $rendimento = $valor_atual - $investido;
                ?>
                <tr>
                    <td><?php echo $symbol; ?></td>
                    <td><?php echo $data['nome']; ?></td>
                    <td><?php echo $quant; ?></td>
                    <td>$ <?php echo number_format($preco_atual, 2); ?></td>
                    <td>$ <?php echo number_format($valor_atual, 2); ?></td>
                    <td>$ <?php echo number_format($rendimento, 2); ?> (<?php echo $investido > 0 ? number_format(($rendimento / $investido) * 100, 2) . '%' : '0%'; ?>)</td>
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

    <!--Histórico Transações-->
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
                    <td>$ <?php echo number_format($trans['preco'] ?? 0, 2); ?></td>
                    <td>$ <?php echo number_format(($trans['preco'] ?? 0) * $trans['quantidade'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    </div>

</body>
</html>