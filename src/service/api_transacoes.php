<?php
/**
 * API Exemplo - Gerenciamento de Transações
 * 
 * Este é um exemplo de como um controller pode ficar com o novo sistema
 * Use este como referência para atualizar seus controllers existentes
 */

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../src/Model/utilizador.php";
require_once __DIR__ . "/../src/Model/historico.php";
require_once __DIR__ . "/../src/helpers/exceptions.php";

header('Content-Type: application/json');

// Inicia sessão
session_start();

// Verifica autenticação
if (!isset($_SESSION['utilizador'])) {
    $response = new ApiResponse('error', 401);
    $response->setError('Não autenticado. Faça login primeiro.');
    $response->toJSON();
    exit;
}

$usuario = $_SESSION['utilizador'];
$username = $usuario->getUsername();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    switch ($action) {
        
        // ============= GET - CONSULTAR =============
        case 'getBanco':
            if ($method !== 'GET') {
                throw new ValidationException("Método não permitido");
            }
            
            $saldo = Utilizador::obterSaldo($username);
            $response = new ApiResponse('success', 200);
            $response->setData([
                'saldo' => $saldo,
                'saldo_formatado' => 'R$ ' . number_format($saldo, 2, ',', '.')
            ]);
            $response->toJSON();
            break;

        case 'getHistorico':
            if ($method !== 'GET') {
                throw new ValidationException("Método não permitido");
            }
            
            $historico = Historico::obterHistoricoUtilizador($username);
            $response = new ApiResponse('success', 200);
            $response->setData(['transacoes' => $historico]);
            $response->toJSON();
            break;

        case 'getPortfolio':
            if ($method !== 'GET') {
                throw new ValidationException("Método não permitido");
            }
            
            $portfolio = Historico::obterPortfolioUtilizador($username);
            $response = new ApiResponse('success', 200);
            $response->setData(['portfolio' => $portfolio]);
            $response->toJSON();
            break;

        case 'getEstatisticas':
            if ($method !== 'GET') {
                throw new ValidationException("Método não permitido");
            }
            
            $stats = Historico::obterEstatisticas($username);
            $response = new ApiResponse('success', 200);
            $response->setData($stats);
            $response->toJSON();
            break;

        // ============= POST - CRIAR =============
        case 'comprar':
            if ($method !== 'POST') {
                throw new ValidationException("Método não permitido");
            }
            
            // Validar dados recebidos
            $symbol = Validator::validateSymbol($_POST['symbol'] ?? '');
            $quantidade = Validator::validateAmount($_POST['quantidade'] ?? 0);
            $preco = Validator::validateAmount($_POST['preco'] ?? 0);
            $nomeEmpresa = $_POST['nomeEmpresa'] ?? 'N/A';
            
            // Calcular valor total
            $valorTotal = $quantidade * $preco;
            $saldoAtual = Utilizador::obterSaldo($username);
            
            // Validar saldo
            if ($saldoAtual < $valorTotal) {
                throw new ValidationException("Saldo insuficiente para essa compra");
            }
            
            // Realizar compra
            Historico::adicionarCompra($username, $nomeEmpresa, $symbol, $quantidade, $preco);
            Utilizador::removerSaldo($username, $valorTotal);
            
            $response = new ApiResponse('success', 200);
            $response->setData([
                'mensagem' => 'Compra realizada com sucesso!',
                'symbol' => $symbol,
                'quantidade' => $quantidade,
                'preco' => $preco,
                'valor_total' => $valorTotal,
                'novo_saldo' => Utilizador::obterSaldo($username)
            ]);
            $response->toJSON();
            break;

        case 'vender':
            if ($method !== 'POST') {
                throw new ValidationException("Método não permitido");
            }
            
            // Validar dados
            $symbol = Validator::validateSymbol($_POST['symbol'] ?? '');
            $quantidade = Validator::validateAmount($_POST['quantidade'] ?? 0);
            $preco = Validator::validateAmount($_POST['preco'] ?? 0);
            $nomeEmpresa = $_POST['nomeEmpresa'] ?? 'N/A';
            
            // Verificar se tem quantidade suficiente
            $portfolio = Historico::obterPortfolioUtilizador($username);
            $temQtde = false;
            foreach ($portfolio as $acao) {
                if ($acao['symbol'] === $symbol && floatval($acao['quantidade']) >= $quantidade) {
                    $temQtde = true;
                    break;
                }
            }
            
            if (!$temQtde) {
                throw new ValidationException("Quantidade insuficiente de $symbol no seu portfolio");
            }
            
            // Realizar venda
            $valorTotal = $quantidade * $preco;
            Historico::adicionarVenda($username, $nomeEmpresa, $symbol, $quantidade, $preco);
            Utilizador::adicionarSaldo($username, $valorTotal);
            
            $response = new ApiResponse('success', 200);
            $response->setData([
                'mensagem' => 'Venda realizada com sucesso!',
                'symbol' => $symbol,
                'quantidade' => $quantidade,
                'preco' => $preco,
                'valor_total' => $valorTotal,
                'novo_saldo' => Utilizador::obterSaldo($username)
            ]);
            $response->toJSON();
            break;

        case 'depositar':
            if ($method !== 'POST') {
                throw new ValidationException("Método não permitido");
            }
            
            $valor = Validator::validateAmount($_POST['valor'] ?? 0);
            
            Utilizador::adicionarSaldo($username, $valor);
            
            $response = new ApiResponse('success', 200);
            $response->setData([
                'mensagem' => 'Depósito realizado com sucesso!',
                'valor_depositado' => $valor,
                'novo_saldo' => Utilizador::obterSaldo($username)
            ]);
            $response->toJSON();
            break;

        case 'sacar':
            if ($method !== 'POST') {
                throw new ValidationException("Método não permitido");
            }
            
            $valor = Validator::validateAmount($_POST['valor'] ?? 0);
            $saldoAtual = Utilizador::obterSaldo($username);
            
            if ($saldoAtual < $valor) {
                throw new ValidationException("Saldo insuficiente");
            }
            
            Utilizador::removerSaldo($username, $valor);
            
            $response = new ApiResponse('success', 200);
            $response->setData([
                'mensagem' => 'Saque realizado com sucesso!',
                'valor_sacado' => $valor,
                'novo_saldo' => Utilizador::obterSaldo($username)
            ]);
            $response->toJSON();
            break;

        // ============= DEFAULT =============
        default:
            throw new ValidationException("Ação não encontrada: $action");
    }

} catch (ValidationException $e) {
    $response = new ApiResponse('error', 400);
    $response->setError($e->getMessage());
    $response->toJSON();
} catch (Exception $e) {
    $response = new ApiResponse('error', 500);
    $response->setError("Erro no servidor: " . $e->getMessage());
    $response->toJSON();
}
?>
