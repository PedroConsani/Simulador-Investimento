<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/utilizador.php";
require_once __DIR__ . "/historico.php";
class Portfolio {
    private $utilizador_id;
    private $symbol;
    private $nomeEmpresa;
    private $quantidade;
    private $precoMedio;

    public function __construct($utilizador_id, $symbol, $nomeEmpresa, $quantidade, $precoMedio) {
        $this->utilizador_id = $utilizador_id;
        $this->symbol = $symbol;
        $this->nomeEmpresa = $nomeEmpresa;
        $this->quantidade = $quantidade;
        $this->precoMedio = $precoMedio;
    }

    public static function obterCarteiraAtual($username){
        $historico = Historico::obterHistoricoUtilizador($username);
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
                if (isset($carteira[$symbol])) {
                    $carteira[$symbol]['quantidade'] -= $quant;
                    $carteira[$symbol]['total_investido'] -= ($trans['preco'] ?? 0) * $quant;
                }
            }
        }
        // Remove ações com quantidade zero ou negativa
        foreach ($carteira as $sym => $info) {
            if ($info['quantidade'] <= 0) {
                unset($carteira[$sym]);
            }
        }
        return $carteira;
    }
}
?>