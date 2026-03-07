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
    <?php include_once __DIR__ . '/navbar.php'; ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <h1>Dashboard do Portfólio</h1>

    <?php
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../src/controllers/transacao_controller.php';
    require_once __DIR__ . '/../src/Model/utilizador.php';
    require_once __DIR__ . '/../src/Model/historico.php';
    require_once __DIR__ . '/../src/Model/portfolio.php';
    require_once __DIR__ . '/../src/Model/cacheJson.php';

    if (!isset($_SESSION['utilizador'])) {
        header('Location: login.php');
        exit;
    }

    $user = $_SESSION['utilizador'];
    $username = $user->getUsername();
    $userData = Utilizador::findUser($username);
    $saldo = isset($userData['saldo']) ? $userData['saldo']: 0.0;
    $historico = Historico::obterHistoricoUtilizador($username);

    // Montagem da carteira com base no histórico de transações
    $carteira = Portfolio::obterCarteiraAtual($username);

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

    // Processar compra
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar'])) {
        $symbol = strtoupper(trim($_POST['symbol_compra']));
        $quant_compra = floatval($_POST['quant_compra']);
        $preco_compra = floatval($_POST['preco_compra']);

        // Valida primeiro
        $validacao = ServicoTransacao::validarTransacao('compra', $username, $symbol, $quant_compra, $preco_compra);

        if (!$validacao['valido']) {
            $erros_compra = implode('<br>', $validacao['erros']);
            echo "<script>window.location.href = 'dashboard.php?compra_erro=1&mensagem=" . urlencode($erros_compra) . "';</script>";
            exit;
        }

        // Processa a compra
        $resultado = ServicoTransacao::processarCompra($username, $symbol, '', $quant_compra, $preco_compra);

        if ($resultado['success']) {
            echo "<script>window.location.href = 'dashboard.php?compra_sucesso=1&valor={$resultado['custo_total']}&saldo={$resultado['novo_saldo']}';</script>";
            exit;
        } else {
            echo "<script>window.location.href = 'dashboard.php?compra_erro=1&mensagem=" . urlencode($resultado['erro']) . "';</script>";
            exit;
        }
    }

    // Processar atualização de preços
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_precos'])) {
        // Redirecionar para recarregar com preços atualizados (cache será ignorado)
        echo "<script>window.location.href = 'dashboard.php?precos_atualizados=1';</script>";
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vender'])) {
        $symbol = $_POST['symbol'];
        $quant_venda = intval($_POST['quant_venda']);
        global $carteira;

        // Obtém informações da ação para o nome da empresa
        // $infoAcao = ServicoTransacao::obterInfoAcao($symbol);
        // $nomeEmpresa = $infoAcao['companyName'] ?? $carteira[$symbol]['nome'] ?? 'N/A';

        // Obtém preço atual
        $preco_atual = $currentPrices[$symbol] ?? 0;

        // Processa a venda usando o serviço
        $resultado = ServicoTransacao::processarVenda($username, $symbol, $quant_venda, $preco_atual);

        // Após processar, redirecionar para evitar reenvio do POST ao recarregar
        if ($resultado['success']) {
            $_SESSION['utilizador']->recarregarSessao(); // Atualiza o objeto na sessão com os novos dados do usuário
            echo "<script>window.location.href = 'dashboard.php?venda_sucesso=1&valor={$resultado['valor_venda']}&saldo={$resultado['novo_saldo']}';</script>";
            exit;
        } else {
            echo "<script>window.location.href = 'dashboard.php?venda_erro=1&mensagem=" . urlencode($resultado['erro']) . "';</script>";
            exit;
        }
    }

    // Verificar mensagens de sucesso/erro via GET
    $mensagem = '';
    if (isset($_GET['precos_atualizados'])) {
        $mensagem = "<div style='background: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 20px; border: 1px solid #bee5eb; border-radius: 4px;'>Preços atualizados com sucesso!</div>";
    } elseif (isset($_GET['compra_sucesso'])) {
        $valor = $_GET['valor'] ?? 0;
        $novo_saldo = $_GET['saldo'] ?? 0;
        $mensagem = "<div style='background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb; border-radius: 4px;'>Compra efetuada com sucesso! Investido: $" . number_format($valor, 2) . ". Novo saldo: $" . number_format($novo_saldo, 2) . "</div>";
    } elseif (isset($_GET['compra_erro'])) {
        $mensagem_erro = $_GET['mensagem'] ?? 'Erro na compra.';
        $mensagem = "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px;'>{$mensagem_erro}</div>";
    } elseif (isset($_GET['venda_sucesso'])) {
        $valor = $_GET['valor'] ?? 0;
        $novo_saldo = $_GET['saldo'] ?? 0;
        $mensagem = "<div style='background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb; border-radius: 4px;'>Venda efetuada com sucesso! Recebido: $" . number_format($valor, 2) . ". Novo saldo: $" . number_format($novo_saldo, 2) . "</div>";
    } elseif (isset($_GET['venda_erro'])) {
        $mensagem_erro = $_GET['mensagem'] ?? 'Erro na venda: quantidade inválida ou insuficiente.';
        $mensagem = "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px;'>{$mensagem_erro}</div>";
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
                    <th>Preço Médio</th>
                    <th>Valor Atual</th>
                    <th>Rendimento</th>
                    <th>Ação</th>
                </tr>
                <?php foreach ($carteira as $symbol => $data): 
                    $quant = $data['quantidade'];
                    print_r("Quantidade: " . $data["quantidade"]);
                    $total_investido = $data["total_investido"];
                    $preco_medio = $quant > 0 ? $total_investido / $quant : 0;
                    // $preco_atual = $currentPrices[$symbol] ?? 0;
                    $valor_atual = $preco_medio * $quant;
                    
                    $rendimento = $valor_atual - $total_investido;
                ?>
                <tr>
                    <td><?php echo $symbol; ?></td>
                    <td><?php echo $data['nome']; ?></td>
                    <td><?php echo $quant; ?></td>
                    <td>$ <?php echo number_format($preco_medio, 2); ?></td>
                    <td>$ <?php echo number_format($valor_atual, 2); ?></td>
                    <td>$ <?php echo number_format($valor_atual, 2); ?></td>
                    <td>$ <?php echo number_format($rendimento, 2); ?> (<?php echo $total_investido > 0 ? number_format(($rendimento / $total_investido) * 100, 2) . '%' : '0%'; ?>)</td>
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
                    <td><?php echo $trans['data_transacao']; ?></td>
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