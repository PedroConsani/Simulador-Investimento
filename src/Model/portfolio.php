<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/utilizador.php";
require_once __DIR__ . "/historico.php";
class Portfolio
{
    private $utilizador_id;
    private $symbol;
    private $nomeEmpresa;
    private $quantidade;
    private $precoMedio;

    public function __construct($utilizador_id, $symbol, $nomeEmpresa, $quantidade, $precoMedio)
    {
        $this->utilizador_id = $utilizador_id;
        $this->symbol = $symbol;
        $this->nomeEmpresa = $nomeEmpresa;
        $this->quantidade = $quantidade;
        $this->precoMedio = $precoMedio;
    }

    public static function obterCarteiraAtual($username)
    {
        $user = Utilizador::findUser($username);
        if (!$user) {
            return [];
        }

        $db = Database::getInstance();
        $sql = "SELECT symbol, nomeEmpresa, quantidade, preco_medio, (quantidade * preco_medio) as total_investido
                FROM portfolio_usuario
                WHERE utilizador_id = ? AND quantidade > 0
                ORDER BY symbol ASC";

        $portfolio = $db->fetchAll($sql, [$user['id']]);

        $carteira = [];
        foreach ($portfolio as $acao) {
            $symbol = $acao['symbol'];
            $carteira[$symbol] = [
                'quantidade' => floatval($acao['quantidade']),
                'preco_medio' => floatval($acao['preco_medio']),
                'total_investido' => floatval($acao['total_investido']),
                'nome' => $acao['nomeEmpresa']
            ];
        }

        return $carteira;
    }
    /**
     * Retorna o portfolio completo de um utilizador
     */
    public static function obterPortfolioUtilizador($username)
    {
        $user = Utilizador::findUser($username);
        if (!$user) {
            return [];
        }

        $db = Database::getInstance();
        $sql = "SELECT * FROM portfolio_usuario 
                WHERE utilizador_id = ? AND quantidade > 0 
                ORDER BY symbol ASC";

        return $db->fetchAll($sql, [$user['id']]);
    }

    /**
     * Retorna o portfolio completo de um utilizador por ID
     */
    public static function obterPortfolioUtilizadorById($utilizador_id)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM portfolio_usuario 
                WHERE utilizador_id = ? AND quantidade > 0 
                ORDER BY symbol ASC";

        return $db->fetchAll($sql, [$utilizador_id]);
    }

    /**
     * Atualiza o portfolio após uma transação
     */
    public static function atualizarPortfolio($utilizador_id, $symbol, $nomeEmpresa, $quantidade, $preco, $tipo)
    {
        $db = Database::getInstance();

        if ($tipo === 'compra') {
            // Se é compra, verifica se já existe registro
            $sql = "SELECT * FROM portfolio_usuario WHERE utilizador_id = ? AND symbol = ?";
            $existing = $db->fetchOne($sql, [$utilizador_id, $symbol]);

            if ($existing) {
                // Atualiza quantidade e preço médio
                $qty_atual = floatval($existing['quantidade']);
                $preco_medio_atual = floatval($existing['preco_medio']);

                $nova_quantidade = $qty_atual + $quantidade;
                $novo_preco_medio = (($qty_atual * $preco_medio_atual) + ($quantidade * $preco)) / $nova_quantidade;

                $sql = "UPDATE portfolio_usuario 
                        SET quantidade = ?, preco_medio = ? 
                        WHERE utilizador_id = ? AND symbol = ?";
                $db->execute($sql, [$nova_quantidade, $novo_preco_medio, $utilizador_id, $symbol]);
            } else {
                // Insere novo registro
                $sql = "INSERT INTO portfolio_usuario 
                        (utilizador_id, symbol, nomeEmpresa, quantidade, preco_medio) 
                        VALUES (?, ?, ?, ?, ?)";
                $db->execute($sql, [$utilizador_id, $symbol, $nomeEmpresa, $quantidade, $preco]);
            }
        } elseif ($tipo === 'venda') {
            // Se é venda, reduz a quantidade
            $sql = "SELECT * FROM portfolio_usuario WHERE utilizador_id = ? AND symbol = ?";
            $existing = $db->fetchOne($sql, [$utilizador_id, $symbol]);

            if ($existing) {
                $qty_atual = floatval($existing['quantidade']);
                $nova_quantidade = $qty_atual - $quantidade;

                if ($nova_quantidade <= 0) {
                    // Deleta se quantidade fica zero ou negativa
                    $sql = "DELETE FROM portfolio_usuario WHERE utilizador_id = ? AND symbol = ?";
                    $db->execute($sql, [$utilizador_id, $symbol]);
                } else {
                    // Atualiza quantidade
                    $sql = "UPDATE portfolio_usuario 
                            SET quantidade = ? 
                            WHERE utilizador_id = ? AND symbol = ?";
                    $db->execute($sql, [$nova_quantidade, $utilizador_id, $symbol]);
                }
            }
        }
    }

    /**
     * Atualiza os preços médios de todas as ações no portfólio baseado nos preços atuais
     */
    public static function atualizarPrecosMedios($username, $currentPrices)
    {
        $user = Utilizador::findUser($username);
        if (!$user) {
            return ['sucesso' => false, 'mensagem' => 'Utilizador não encontrado'];
        }

        $db = Database::getInstance();
        $utilizador_id = $user['id'];

        // Obter o portfólio atual do utilizador
        $sql = "SELECT symbol, quantidade, preco_medio FROM portfolio_usuario 
                WHERE utilizador_id = ? AND quantidade > 0";
        $portfolio = $db->fetchAll($sql, [$utilizador_id]);

        $atualizadas = 0;
        foreach ($portfolio as $acao) {
            $symbol = $acao['symbol'];
            
            // Verificar se temos um preço atualizado para esta ação
            if (isset($currentPrices[$symbol])) {
                $novo_preco = floatval($currentPrices[$symbol]);
                $quantidade = floatval($acao['quantidade']);
                
                // Atualizar o preço médio na base de dados
                $updateSql = "UPDATE portfolio_usuario 
                             SET preco_medio = ? 
                             WHERE utilizador_id = ? AND symbol = ?";
                $db->execute($updateSql, [$novo_preco, $utilizador_id, $symbol]);
                $atualizadas++;
            }
        }

        return [
            'sucesso' => true, 
            'mensagem' => "Preços atualizados para $atualizadas ação(ões).",
            'quantidade' => $atualizadas
        ];
    }
}
