<?php 
class Utilizador {
    private $username;
    private $senha;

    // saldos separados: real para operações "reais" e papel para o modo de treino
    private $saldoReal;
    private $saldoJogo;

    /**
     * @param string $username
     * @param string $senha
     * @param float $saldoReal   valor inicial em dinheiro real (padrão 0)
     */
    public function __construct($username, $senha = '', $saldoReal = 0.0) {
        $this->username   = $username;
        $this->senha = $senha;
        $this->saldoReal  = $saldoReal;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getSenha() {
        return $this->senha;
    }

    // getters/setters para os saldos
    public function getSaldoReal() {
        return $this->saldoReal;
    }

    /********************
     * Funções estáticas
     ********************/
    private static function getStoragePath() {
        return __DIR__ . "/../../data/utilizadores.json";
    }

    private static function loadUsers() {
        //Retorna um array de utilizadores a partir do utilizadores.json.
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

    private static function saveUsers(array $users) {
        //Recebe um array com dados do utilizador e salva no utilizadores.json.
        $file = self::getStoragePath();
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
    }

    public static function findUser(string $username) {
        $users = self::loadUsers();
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                return $u;
            }
        }
        return null;
    }

    public static function register(string $username, string $senha): bool {
        // não permite duplicados
        if (self::findUser($username) !== null) {
            return false;
        }
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $users = self::loadUsers(); //Carrega os utlizadores registrados
        //Adiciona o utilizador novo no proximo índice do array e salva em utilizadores.json
        $users[] = [
            'username' => $username,
            'password' => $hash,
            'saldo' => 0.0,
            'historico' => []
        ];
        self::saveUsers($users);
        return true;
    }

    public static function authenticate(string $username, string $senha) {
        $u = self::findUser($username);
        if ($u && password_verify($senha, $u['password'])) {
            // retornar objeto utilizador com saldo carregado
            $saldo = isset($u['saldo']) ? floatval($u['saldo']) : 0.0;
            $user = new Utilizador($u['username'], '', $saldo);
            return $user;
        }
        return false;
    }

    public static function atualizarSaldo(string $username, float $novoSaldoReal) {
        //Atualiza o saldo do utilizador no arquivo JSON.
        $users = self::loadUsers();
        foreach ($users as &$u) {
            if ($u['username'] === $username) {
                $u['saldo'] =$novoSaldoReal;
                break;
            }
        }
        self::saveUsers($users);
    }

    public static function adicionarHistorico(string $username, array $entrada) {
        $users = self::loadUsers();
        foreach ($users as &$u) {
            if ($u['username'] === $username) {
                if (!isset($u['historico']) || !is_array($u['historico'])) {
                    $u['historico'] = [];
                }
                $u['historico'][] = $entrada;
                break;
            }
        }
        self::saveUsers($users);
    }
}
?>