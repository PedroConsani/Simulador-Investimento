<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/utilizador.php";
require_once __DIR__ . "/portfolio.php";

class Historico
{
    /**
     * Adiciona uma compra ao histórico
     */
    public static function adicionarCompra($username, $nomeEmpresa, $symbol, $quantidade, $preco, $tipo = 'compra')
    {
        $user = Utilizador::findUser($username);
        if (!$user) {
            return false;
        }

        $db = Database::getInstance();
        $utilizador_id = $user['id'];
        $valor_total = $quantidade * $preco;

        $sql = "INSERT INTO historico_transacoes 
                (utilizador_id, tipo, symbol, nomeEmpresa, quantidade, preco, valor_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $db->execute($sql, [$utilizador_id, $tipo, $symbol, $nomeEmpresa, $quantidade, $preco, $valor_total]);
            
            // Atualiza o portfolio do utilizador
            Portfolio::atualizarPortfolio($utilizador_id, $symbol, $nomeEmpresa, $quantidade, $preco, $tipo);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Adiciona uma venda ao histórico
     */
    public static function adicionarVenda($username, $nomeEmpresa, $symbol, $quantidade, $preco)
    {
        return self::adicionarCompra($username, $nomeEmpresa, $symbol, $quantidade, $preco, 'venda');
    }

    /**
     * Retorna o histórico de transações de um utilizador
     */
    public static function obterHistoricoUtilizador($username)
    {
        $user = Utilizador::findUser($username);
        if (!$user) {
            return [];
        }

        $db = Database::getInstance();
        $sql = "SELECT * FROM historico_transacoes 
                WHERE utilizador_id = ? 
                ORDER BY data_transacao DESC";
        
        return $db->fetchAll($sql, [$user['id']]);
    }

    /**
     * Retorna o histórico de transações de um utilizador por ID
     */
    public static function obterHistoricoUtilizadorById($utilizador_id)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM historico_transacoes 
                WHERE utilizador_id = ? 
                ORDER BY data_transacao DESC";
        
        return $db->fetchAll($sql, [$utilizador_id]);
    }

    /**
     * Retorna todas as transações de um símbolo específico para um utilizador
     */
    public static function obterTransacoesPorSymbol($username, $symbol)
    {
        $user = Utilizador::findUser($username);
        if (!$user) {
            return [];
        }

        $db = Database::getInstance();
        $sql = "SELECT * FROM historico_transacoes 
                WHERE utilizador_id = ? AND symbol = ? 
                ORDER BY data_transacao DESC";
        
        return $db->fetchAll($sql, [$user['id'], $symbol]);
    }


    /**
     * Retorna estatísticas do histórico de um utilizador
     */
    public static function obterEstatisticas($username)
    {
        $user = Utilizador::findUser($username);
        if (!$user) {
            return null;
        }

        $db = Database::getInstance();
        
        $stats = [
            'total_compras' => 0,
            'total_vendas' => 0,
            'numero_transacoes' => 0,
            'valor_total_investido' => 0,
        ];

        // Total de transações
        $sql = "SELECT COUNT(*) as count FROM historico_transacoes WHERE utilizador_id = ?";
        $result = $db->fetchOne($sql, [$user['id']]);
        $stats['numero_transacoes'] = $result['count'] ?? 0;

        // Total compras
        $sql = "SELECT COUNT(*) as count FROM historico_transacoes 
                WHERE utilizador_id = ? AND tipo = 'compra'";
        $result = $db->fetchOne($sql, [$user['id']]);
        $stats['total_compras'] = $result['count'] ?? 0;

        // Total vendas
        $sql = "SELECT COUNT(*) as count FROM historico_transacoes 
                WHERE utilizador_id = ? AND tipo = 'venda'";
        $result = $db->fetchOne($sql, [$user['id']]);
        $stats['total_vendas'] = $result['count'] ?? 0;

        // Valor total investido (compras)
        $sql = "SELECT COALESCE(SUM(valor_total), 0) as total FROM historico_transacoes 
                WHERE utilizador_id = ? AND tipo = 'compra'";
        $result = $db->fetchOne($sql, [$user['id']]);
        $stats['valor_total_investido'] = floatval($result['total'] ?? 0);

        return $stats;
    }

    /**
     * Deleta uma transação (cuidado - atualiza portfolio)
     */
    public static function deletarTransacao($transacao_id): bool
    {
        $db = Database::getInstance();
        
        // Obtém detalhes da transação
        $sql = "SELECT * FROM historico_transacoes WHERE id = ?";
        $transacao = $db->fetchOne($sql, [$transacao_id]);

        if (!$transacao) {
            return false;
        }

        try {
            // Deleta a transação
            $sql = "DELETE FROM historico_transacoes WHERE id = ?";
            $db->execute($sql, [$transacao_id]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
