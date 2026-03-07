<?php
require_once __DIR__ . "/../../config/database.php";

class Utilizador {
    private $id;
    private $username;
    private $senha;
    private $saldoReal;

    /**
     * @param string $username
     * @param string $senha
     * @param float $saldoReal   valor inicial em dinheiro real (padrão 0)
     * @param int $id ID do utilizador na BD
     */
    public function __construct($username, $senha = '', $saldoReal = 0.0, $id = null) {
        $this->id = $id;
        $this->username = $username;
        $this->senha = $senha;
        $this->saldoReal = $saldoReal;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getSenha() {
        return $this->senha;
    }

    public function getSaldoReal() {
        return $this->saldoReal;
    }

    /**
     * Define novo saldo
     */
    public function setSaldoReal($saldo) {
        $this->saldoReal = $saldo;
    }

    /**
     * Encontra um utilizador pelo username
     *
     * Retorna um array associativo com os dados do usuário ou null
     * se não for encontrado.
     */
    public static function findUser(string $username) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM utilizadores WHERE username = ?";
        $result = $db->fetchOne($sql, [$username]);
        return $result !== false ? $result : null;
    }

    /**
     * Encontra um utilizador pelo ID
     *
     * Retorna null caso não exista o ID.
     */
    public static function findUserById(int $id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM utilizadores WHERE id = ?";
        $result = $db->fetchOne($sql, [$id]);
        return $result !== false ? $result : null;
    }

    /**
     * Registra um novo utilizador
     */
    public static function register(string $username, string $senha): bool {
        // Verifica se utilizador já existe
        if (self::findUser($username) !== null) {
            return false;
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $db = Database::getInstance();

        $sql = "INSERT INTO utilizadores (username, password, saldo) VALUES (?, ?, ?)";
        try {
            $db->execute($sql, [$username, $hash, 0.00]);
            return true;
        } catch (PDOException $e) {
            // código 1062 = duplicate entry (MySQL)
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                // Usuário já existia apesar da checagem anterior
                return false;
            }
            // outros erros devem ser propagados para facilitar debug durante desenvolvimento
            throw $e;
        }
    }

    /**
     * Autentica um utilizador
     */
    public static function authenticate(string $username, string $senha) {
        $u = self::findUser($username);
        if ($u && password_verify($senha, $u['password'])) {
            // Retorna objeto utilizador com dados da BD
            $user = new Utilizador(
                $u['username'],
                '',
                floatval($u['saldo']),
                intval($u['id'])
            );
            return $user;
        }
        return false;
    }

    /**
     * Atualiza o saldo do utilizador
     */
    public static function atualizarSaldo(string $username, float $novoSaldoReal) {
        $db = Database::getInstance();
        $sql = "UPDATE utilizadores SET saldo = ? WHERE username = ?";
        return $db->execute($sql, [$novoSaldoReal, $username]);
    }

    /**
     * Atualiza o saldo por ID de utilizador
     */
    public static function atualizarSaldoById(int $utilizadorId, float $novoSaldoReal) {
        $db = Database::getInstance();
        $sql = "UPDATE utilizadores SET saldo = ? WHERE id = ?";
        return $db->execute($sql, [$novoSaldoReal, $utilizadorId]);
    }

    /**
     * Obtém o saldo atual de um utilizador
     */
    public static function obterSaldo(string $username) {
        $user = self::findUser($username);
        return $user ? floatval($user['saldo']) : 0.0;
    }

    /**
     * Obtém o saldo por ID
     */
    public static function obterSaldoById(int $utilizadorId) {
        $user = self::findUserById($utilizadorId);
        return $user ? floatval($user['saldo']) : 0.0;
    }

    /**
     * Adiciona valor ao saldo (depósito)
     */
    public static function adicionarSaldo(string $username, float $valor) {
        $saldoAtual = self::obterSaldo($username);
        $novoSaldo = $saldoAtual + $valor;
        return self::atualizarSaldo($username, $novoSaldo);
    }

    /**
     * Adiciona valor ao saldo por ID (depósito)
     */
    public static function adicionarSaldoById(int $utilizadorId, float $valor) {
        $saldoAtual = self::obterSaldoById($utilizadorId);
        $novoSaldo = $saldoAtual + $valor;
        return self::atualizarSaldoById($utilizadorId, $novoSaldo);
    }

    /**
     * Remove valor do saldo (saque/transação)
     */
    public static function removerSaldo(string $username, float $valor) {
        $saldoAtual = self::obterSaldo($username);
        if ($saldoAtual < $valor) {
            return false; // Saldo insuficiente
        }
        $novoSaldo = $saldoAtual - $valor;
        return self::atualizarSaldo($username, $novoSaldo);
    }

    /**
     * Remove valor do saldo por ID (saque/transação)
     */
    public static function removerSaldoById(int $utilizadorId, float $valor) {
        $saldoAtual = self::obterSaldoById($utilizadorId);
        if ($saldoAtual < $valor) {
            return false; // Saldo insuficiente
        }
        $novoSaldo = $saldoAtual - $valor;
        return self::atualizarSaldoById($utilizadorId, $novoSaldo);
    }

    /**
     * Adiciona entrada ao histórico (DESCONTINUADO - usar Historico class)
     */
    public static function adicionarHistorico(string $username, array $entrada) {
        // Esta função é mantida para compatibilidade, mas usa a classe Historico
        require_once __DIR__ . "/historico.php";
        $user = self::findUser($username);
        if ($user) {
            Historico::adicionarCompra(
                $username,
                $entrada['nomeEmpresa'] ?? '',
                $entrada['symbol'],
                $entrada['quantidade'],
                $entrada['preco'],
                $entrada['tipo'] ?? 'compra'
            );
        }
    }

    /**
     * Retorna todos os utilizadores (apenas admin)
     */
    public static function getAllUsers() {
        $db = Database::getInstance();
        $sql = "SELECT id, username, saldo, data_criacao FROM utilizadores";
        return $db->fetchAll($sql);
    }

    /**
     * Deleta um utilizador e seu histórico
     */
    public static function deleteUser(string $username): bool {
        $user = self::findUser($username);
        if (!$user) {
            return false;
        }

        $db = Database::getInstance();
        try {
            // Deleta transações
            $sql = "DELETE FROM historico_transacoes WHERE utilizador_id = ?";
            $db->execute($sql, [$user['id']]);

            // Deleta portfolio
            $sql = "DELETE FROM portfolio_usuario WHERE utilizador_id = ?";
            $db->execute($sql, [$user['id']]);

            // Deleta utilizador
            $sql = "DELETE FROM utilizadores WHERE id = ?";
            $db->execute($sql, [$user['id']]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    public function recarregarSessao(){
        $dadosAtualizados = self::findUser($this->username);
        if ($dadosAtualizados) {
            $this->saldoReal = floatval($dadosAtualizados['saldo']);
            // Atualiza outros dados se necessário
        }
    }
}
?>