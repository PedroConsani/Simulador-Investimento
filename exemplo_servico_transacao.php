<?php
/**
 * EXEMPLO DE USO DO SERVIÇO DE TRANSAÇÕES
 *
 * Este arquivo demonstra como usar a classe ServicoTransacao
 * para processar compras e vendas de ações de forma centralizada.
 */

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/src/controllers/servico_transacao.php";

// Simula uma sessão de usuário (em produção, isso vem da sessão real)
$_SESSION['utilizador'] = (object)['getUsername' => function() { return 'teste_user'; }];
$username = 'teste_user';

echo "<h1>Exemplo de Uso do Serviço de Transações</h1>";

// ==========================================
// 1. EXEMPLO DE COMPRA DE AÇÕES
// ==========================================

echo "<h2>1. Compra de Ações</h2>";

// Dados da transação
$symbol = 'AAPL';
$nomeEmpresa = 'Apple Inc';
$quantidade = 10;
$preco = 150.50;

// Processa a compra
$resultadoCompra = ServicoTransacao::processarCompra(
    $username,
    $symbol,
    $nomeEmpresa,
    $quantidade,
    $preco
);

echo "<pre>";
echo "Resultado da Compra:\n";
print_r($resultadoCompra);
echo "</pre>";

// ==========================================
// 2. EXEMPLO DE VENDA DE AÇÕES
// ==========================================

echo "<h2>2. Venda de Ações</h2>";

// Dados da venda
$symbolVenda = 'AAPL';
$quantidadeVenda = 5;
$precoVenda = 155.00;

// Processa a venda
$resultadoVenda = ServicoTransacao::processarVenda(
    $username,
    $symbolVenda,
    $quantidadeVenda,
    $precoVenda
);

echo "<pre>";
echo "Resultado da Venda:\n";
print_r($resultadoVenda);
echo "</pre>";

// ==========================================
// 3. VALIDAÇÃO DE TRANSAÇÃO
// ==========================================

echo "<h2>3. Validação de Transação</h2>";

// Valida uma compra
$validacaoCompra = ServicoTransacao::validarTransacao(
    'compra',
    $username,
    'TSLA',
    100,  // quantidade
    200   // preço
);

echo "<pre>";
echo "Validação de Compra:\n";
print_r($validacaoCompra);
echo "</pre>";

// ==========================================
// 4. OBTENÇÃO DE INFORMAÇÕES DE AÇÃO
// ==========================================

echo "<h2>4. Informações de Ação via API</h2>";

// Obtém informações da ação
$infoAcao = ServicoTransacao::obterInfoAcao('MSFT');

echo "<pre>";
echo "Informações da MSFT:\n";
print_r($infoAcao);
echo "</pre>";

// ==========================================
// 5. CÁLCULO DE ESTATÍSTICAS
// ==========================================

echo "<h2>5. Cálculo de Estatísticas de Transação</h2>";

// Simula dados de uma transação de venda
$transacaoExemplo = [
    'username' => $username,
    'tipo' => 'venda',
    'symbol' => 'AAPL',
    'quantidade' => 5,
    'preco' => 155.00
];

// Calcula estatísticas
$estatisticas = ServicoTransacao::calcularEstatisticasTransacao($transacaoExemplo);

echo "<pre>";
echo "Estatísticas da Transação:\n";
print_r($estatisticas);
echo "</pre>";

// ==========================================
// 6. EXEMPLO DE USO EM CONTROLADOR
// ==========================================

echo "<h2>6. Exemplo de Uso em Controlador</h2>";

/**
 * Exemplo de como usar em um controlador web
 */
function exemploControladorCompra() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar'])) {
        $symbol = $_POST['symbol'];
        $quantidade = floatval($_POST['quantidade']);
        $preco = floatval($_POST['preco']);
        $username = $_SESSION['utilizador']->getUsername();

        // Valida primeiro
        $validacao = ServicoTransacao::validarTransacao('compra', $username, $symbol, $quantidade, $preco);

        if (!$validacao['valido']) {
            // Retorna erros
            return [
                'success' => false,
                'erros' => $validacao['erros']
            ];
        }

        // Processa a compra
        $resultado = ServicoTransacao::processarCompra($username, $symbol, '', $quantidade, $preco);

        // Atualiza sessão
        if ($resultado['success']) {
            $dadosAtualizados = Utilizador::findUser($username);
            $_SESSION['utilizador'] = new Utilizador($dadosAtualizados['username'], '', $dadosAtualizados['saldo']);
        }

        return $resultado;
    }
}

echo "<p>Veja o código fonte deste arquivo para exemplos completos de uso.</p>";
echo "<p>O ServicoTransacao centraliza toda a lógica de transações, facilitando manutenção e reutilização.</p>";
?>