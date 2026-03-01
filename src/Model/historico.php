<?php
class Historico
{

    private static function getPath($username)
    {
        return __DIR__ . "/../Dados/historico/{$username}.json";
    }

    public static function adicionarCompra($username, $nomeEmpresa, $symbol, $quantidade, $preco)
    {

        $arquivo = self::getPath($username);

        if (file_exists($arquivo)) {
            $dados = json_decode(file_get_contents($arquivo), true);
        } else {
            $dados = [];
        }

        $dados[] = [
            "------Compra------",
            "symbol" => $symbol,
            "nomeEmpresa" => $nomeEmpresa,
            "quantidade" => $quantidade,
            "preco" => $preco,
            "dataCompra" => date("d-m-Y H:i:s")
        ];

        file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT));
    }
    public static function adicionarVenda($username, $nomeEmpresa, $symbol, $quantidade, $preco)
    {

        $arquivo = self::getPath($username);

        if (file_exists($arquivo)) {
            $dados = json_decode(file_get_contents($arquivo), true);
        } else {
            $dados = [];
        }

        $dados[] = [
            "------Venda------",
            "symbol" => $symbol,
            "nomeEmpresa" => $nomeEmpresa,
            "quantidade" => $quantidade,
            "preco" => $preco,
            "dataVenda" => date("d-m-Y H:i:s")
        ];

        file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT));
    }
}
?>
