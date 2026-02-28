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
     * @param float $saldoJogo  valor inicial em dinheiro fictício para paper trading
     */
    public function __construct($username, $senha, $saldoReal = 0.0, $saldoJogo = 0.0) {
        $this->username   = $username;
        $this->senha      = $senha;
        $this->saldoReal  = $saldoReal;
        $this->saldoJogo = $saldoJogo;
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

    public function setSaldoReal($valor) {
        $this->saldoReal = $valor;
    }

    public function getSaldoJogo() {
        return $this->saldoJogo;
    }

    public function setSaldoJogo($valor) {
        $this->saldoJogo = $valor;
    }

    /********************
     * Funções estáticas
     ********************/
    private static function getStoragePath() {
        return __DIR__ . "/../utilizadores.json";
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
            'saldoReal' => 0.0,
            'saldoJogo' => 0.0
        ];
        self::saveUsers($users);
        return true;
    }

    public static function authenticate(string $username, string $senha) {
        $u = self::findUser($username);
        if ($u && password_verify($senha, $u['password'])) {
            // retornar objeto utiliador com saldos carregados
            $user = new Utilizador($u['username'], '', $u['saldoReal'], $u['saldoJogo']);
            return $user;
        }
        return false;
    }
}
?>