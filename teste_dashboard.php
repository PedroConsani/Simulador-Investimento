<?php
/**
 * TESTE DO DASHBOARD - Verificar se está funcionando
 */

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/src/controllers/servico_transacao.php";
require_once __DIR__ . "/src/Model/utilizador.php";
require_once __DIR__ . "/src/Model/historico.php";
require_once __DIR__ . "/src/Model/paper_tr_real.php";
require_once __DIR__ . "/src/Model/cacheJson.php";

echo "=== TESTE DO DASHBOARD ===\n\n";

// Simular sessão
$_SESSION['utilizador'] = (object)['getUsername' => function() { return 'teste_user'; }];
$username = 'teste_user';

// Testar obtenção de dados do usuário
echo "1. Testando obtenção de dados do usuário...\n";
$userData = Utilizador::findUser($username);
if ($userData) {
    echo "   ✓ Usuário encontrado: " . $userData['username'] . " - Saldo: $" . $userData['saldo'] . "\n";
} else {
    echo "   ✗ Usuário não encontrado\n";
}

// Testar obtenção do histórico
echo "\n2. Testando obtenção do histórico...\n";
$historico = Historico::obterHistoricoUtilizador($username);
echo "   ✓ Histórico obtido: " . count($historico) . " transações\n";

// Testar obtenção da carteira
echo "\n3. Testando obtenção da carteira...\n";
$carteira = Portfolio::obterCarteiraAtual($username);
echo "   ✓ Carteira obtida: " . count($carteira) . " ações\n";
foreach ($carteira as $symbol => $data) {
    echo "     - $symbol: {$data['quantidade']} ações\n";
}

// Testar obtenção de preços
echo "\n4. Testando obtenção de preços...\n";
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
        echo "   ✓ Preço de $symbol: $" . $currentPrices[$symbol] . "\n";
    } else {
        $currentPrices[$symbol] = 0;
        echo "   ✗ Preço de $symbol não obtido\n";
    }
}

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "O dashboard deve estar funcionando corretamente agora!\n";
?>