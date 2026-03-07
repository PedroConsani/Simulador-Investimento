<?php
/**
 * Serviço de Transações - Centraliza toda lógica de compra e venda de ações
 *
 * Este arquivo contém métodos para:
 * - Processar compras de ações
 * - Processar vendas de ações
 * - Validar transações
 * - Atualizar saldos e históricos
 */

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../Model/utilizador.php";
require_once __DIR__ . "/../Model/historico.php";
require_once __DIR__ . "/../Model/portfolio.php";

class ServicoTransacao {

    /**
     * Processa uma compra de ações
     *
     * @param string $username Nome do usuário
     * @param string $symbol Símbolo da ação
     * @param string $nomeEmpresa Nome da empresa
     * @param float $quantidade Quantidade a comprar
     * @param float $preco Preço por ação
     * @return array Resultado da operação
     */
    public static function processarCompra($username, $symbol, $nomeEmpresa, $quantidade, $preco) {
        try {
            // Validações básicas
            if (empty($username) || empty($symbol) || $quantidade <= 0 || $preco <= 0) {
                return [
                    'success' => false,
                    'erro' => 'Dados inválidos para compra'
                ];
            }

            // Obtém dados do usuário
            $usuarioData = Utilizador::findUser($username);
            if (!$usuarioData) {
                return [
                    'success' => false,
                    'erro' => 'Usuário não encontrado'
                ];
            }

            $saldoAtual = floatval($usuarioData['saldo']);
            $custoTotal = $quantidade * $preco;

            // Verifica se há saldo suficiente
            if ($saldoAtual < $custoTotal) {
                return [
                    'success' => false,
                    'erro' => 'Saldo insuficiente',
                    'custo' => $custoTotal,
                    'saldo' => $saldoAtual
                ];
            }

            // Calcula novo saldo
            $novoSaldo = $saldoAtual - $custoTotal;

            // Registra a transação no histórico
            Historico::adicionarCompra($username, $nomeEmpresa, $symbol, $quantidade, $preco);

            // Atualiza o saldo do usuário
            Utilizador::atualizarSaldo($username, $novoSaldo);

            return [
                'success' => true,
                'tipo' => 'compra',
                'symbol' => $symbol,
                'quantidade' => $quantidade,
                'preco' => $preco,
                'custo_total' => $custoTotal,
                'saldo_anterior' => $saldoAtual,
                'novo_saldo' => $novoSaldo
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'erro' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Processa uma venda de ações
     *
     * @param string $username Nome do usuário
     * @param string $symbol Símbolo da ação
     * @param float $quantidade Quantidade a vender
     * @param float $preco Preço por ação
     * @return array Resultado da operação
     */
    public static function processarVenda($username, $symbol, $quantidade, $preco) {
        try {
            // Validações básicas
            if (empty($username) || empty($symbol) || $quantidade <= 0 || $preco <= 0) {
                return [
                    'success' => false,
                    'erro' => 'Dados inválidos para venda'
                ];
            }

            // Obtém dados do usuário
            $usuarioData = Utilizador::findUser($username);
            if (!$usuarioData) {
                return [
                    'success' => false,
                    'erro' => 'Usuário não encontrado'
                ];
            }

            // Obtém a carteira atual do usuário
            $portfolio = Portfolio::obterCarteiraAtual($username);

            // Verifica se o usuário possui a ação e quantidade suficiente
            if (!isset($portfolio[$symbol]) || $portfolio[$symbol]['quantidade'] < $quantidade) {
                return [
                    'success' => false,
                    'erro' => 'Quantidade insuficiente em carteira',
                    'possui' => $portfolio[$symbol]['quantidade'] ?? 0,
                    'tentou_vender' => $quantidade
                ];
            }

            $saldoAtual = floatval($usuarioData['saldo']);
            $valorVenda = $quantidade * $preco;
            $novoSaldo = $saldoAtual + $valorVenda;

            // Registra a transação no histórico
            Historico::adicionarVenda($username, $portfolio[$symbol]['nome'], $symbol, $quantidade, $preco);

            // Atualiza o saldo do usuário
            Utilizador::atualizarSaldo($username, $novoSaldo);

            return [
                'success' => true,
                'tipo' => 'venda',
                'symbol' => $symbol,
                'quantidade' => $quantidade,
                'preco' => $preco,
                'valor_venda' => $valorVenda,
                'saldo_anterior' => $saldoAtual,
                'novo_saldo' => $novoSaldo
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'erro' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtém informações de uma ação via API
     *
     * @param string $symbol Símbolo da ação
     * @return array|null Dados da ação ou null se erro
     */
    public static function obterInfoAcao($symbol) {
        try {
            if (empty($symbol)) {
                return null;
            }

            $url = "https://financialmodelingprep.com/stable/profile?symbol=" . urlencode($symbol) . "&apikey=" . API_KEY;

            // Tenta obter do cache primeiro
            $cacheFile = __DIR__ . "/../../data/info_empresa_{$symbol}.json";
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) { // Cache de 1 hora
                $dados = json_decode(file_get_contents($cacheFile), true);
                if ($dados) {
                    return $dados[0];
                }
            }

            // Busca na API
            $response = file_get_contents($url);
            if ($response === false) {
                return null;
            }

            $dados = json_decode($response, true);
            if (empty($dados)) {
                return null;
            }

            // Salva no cache
            file_put_contents($cacheFile, json_encode($dados));

            return $dados[0];

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Valida se uma transação pode ser executada
     *
     * @param string $tipo Tipo da transação ('compra' ou 'venda')
     * @param string $username Nome do usuário
     * @param string $symbol Símbolo da ação
     * @param float $quantidade Quantidade
     * @param float $preco Preço
     * @return array Resultado da validação
     */
    public static function validarTransacao($tipo, $username, $symbol, $quantidade, $preco) {
        $erros = [];

        // Validações básicas
        if (empty($username)) {
            $erros[] = 'Usuário não informado';
        }

        if (empty($symbol)) {
            $erros[] = 'Símbolo da ação não informado';
        }

        if ($quantidade <= 0) {
            $erros[] = 'Quantidade deve ser maior que zero';
        }

        if ($preco <= 0) {
            $erros[] = 'Preço deve ser maior que zero';
        }

        // Validações específicas por tipo
        if ($tipo === 'compra') {
            $usuarioData = Utilizador::findUser($username);
            if ($usuarioData) {
                $saldoAtual = floatval($usuarioData['saldo']);
                $custoTotal = $quantidade * $preco;
                if ($saldoAtual < $custoTotal) {
                    $erros[] = "Saldo insuficiente. Necessário: R$ " . number_format($custoTotal, 2, ',', '.') . ", Disponível: R$ " . number_format($saldoAtual, 2, ',', '.');
                }
            } else {
                $erros[] = 'Usuário não encontrado';
            }
        } elseif ($tipo === 'venda') {
            $carteira = Portfolio::obterCarteiraAtual($username);
            if (!isset($carteira[$symbol]) || $carteira[$symbol]['quantidade'] < $quantidade) {
                $possui = $carteira[$symbol]['quantidade'] ?? 0;
                $erros[] = "Quantidade insuficiente. Você possui {$possui} ações de {$symbol}, tentou vender {$quantidade}";
            }
        }

        return [
            'valido' => empty($erros),
            'erros' => $erros
        ];
    }

    /**
     * Calcula estatísticas de uma transação
     *
     * @param array $transacao Dados da transação
     * @return array Estatísticas calculadas
     */
    public static function calcularEstatisticasTransacao($transacao) {
        $stats = [
            'lucro_prejuizo' => 0,
            'percentual' => 0,
            'valor_investido' => 0,
            'valor_atual' => 0
        ];

        if ($transacao['tipo'] === 'venda') {
            // Para vendas, calcula lucro/prejuízo baseado no histórico de compras
            $historico = Historico::obterTransacoesPorSymbol($transacao['username'], $transacao['symbol']);

            $totalInvestido = 0;
            $quantidadeTotal = 0;

            foreach ($historico as $item) {
                if ($item['tipo'] === 'compra') {
                    $totalInvestido += $item['quantidade'] * $item['preco'];
                    $quantidadeTotal += $item['quantidade'];
                }
            }

            if ($quantidadeTotal > 0) {
                $precoMedio = $totalInvestido / $quantidadeTotal;
                $valorRecebido = $transacao['quantidade'] * $transacao['preco'];
                $valorInvestido = $transacao['quantidade'] * $precoMedio;

                $stats['lucro_prejuizo'] = $valorRecebido - $valorInvestido;
                $stats['percentual'] = $valorInvestido > 0 ? (($stats['lucro_prejuizo'] / $valorInvestido) * 100) : 0;
                $stats['valor_investido'] = $valorInvestido;
                $stats['valor_atual'] = $valorRecebido;
            }
        }

        return $stats;
    }
}
?>
