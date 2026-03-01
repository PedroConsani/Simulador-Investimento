<?php
class Historico
{

    private static function getStoragePath()
    {
        // usa o ficheiro existente de utilizadores para guardar histórico em cada utilizador
        return __DIR__ . "/../../data/utilizadores.json";
    }

    private static function loadAllUsers()
    {
        $file = self::getStoragePath();
        if (!file_exists($file)) {
            return [];
        }
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        return $data;
    }

    private static function saveAllUsers(array $users)
    {
        $file = self::getStoragePath();
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
    }

    public static function adicionarCompra($username, $nomeEmpresa, $symbol, $quantidade, $preco)
    {
        $users = self::loadAllUsers();
        foreach ($users as &$u) {
            if ($u['username'] === $username) {
                if (!isset($u['historico']) || !is_array($u['historico'])) {
                    $u['historico'] = [];
                }
                $entrada = [
                    'tipo' => 'COMPRA',
                    'symbol' => $symbol,
                    'nomeEmpresa' => $nomeEmpresa,
                    'quantidade' => $quantidade,
                    'preco' => $preco,
                    'data' => date("Y-m-d H:i:s")
                ];
                $u['historico'][] = $entrada;
                break;
            }
        }
        self::saveAllUsers($users);
    }

    public static function adicionarVenda($username, $nomeEmpresa, $symbol, $quantidade, $preco)
    {
        $users = self::loadAllUsers();
        foreach ($users as &$u) {
            if ($u['username'] === $username) {
                if (!isset($u['historico']) || !is_array($u['historico'])) {
                    $u['historico'] = [];
                }
                $entrada = [
                    'tipo' => 'VENDA',
                    'symbol' => $symbol,
                    'nomeEmpresa' => $nomeEmpresa,
                    'quantidade' => $quantidade,
                    'preco' => $preco,
                    'data' => date("Y-m-d H:i:s")
                ];
                $u['historico'][] = $entrada;
                break;
            }
        }
        self::saveAllUsers($users);
    }
}
?>
