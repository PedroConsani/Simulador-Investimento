<?php
/**
 * Classe para tratamento de exceções e erros de base de dados
 */
class DatabaseException extends Exception {
    // Exceção customizada para erros de BD
}

class ValidationException extends Exception {
    // Exceção para validação de dados
}

/**
 * Classe para resposta padronizada de API
 */
class ApiResponse {
    private $data = [];
    private $error = null;
    private $status = 'success';
    private $code = 200;

    public function __construct($status = 'success', $code = 200) {
        $this->status = $status;
        $this->code = $code;
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    public function setError($error) {
        $this->error = $error;
        $this->status = 'error';
        return $this;
    }

    public function setCode($code) {
        $this->code = $code;
        return $this;
    }

    public function toArray() {
        return [
            'status' => $this->status,
            'code' => $this->code,
            'data' => $this->data,
            'error' => $this->error
        ];
    }

    public function toJSON() {
        header('Content-Type: application/json');
        echo json_encode($this->toArray());
    }
}

/**
 * Helper para validação de dados
 */
class Validator {
    public static function validateUsername($username) {
        if (empty($username) || strlen($username) < 3 || strlen($username) > 100) {
            throw new ValidationException("Username deve ter entre 3 e 100 caracteres");
        }
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            throw new ValidationException("Username contém caracteres inválidos");
        }
        return $username;
    }

    public static function validatePassword($password) {
        if (empty($password) || strlen($password) < 6) {
            throw new ValidationException("Senha deve ter pelo menos 6 caracteres");
        }
        return $password;
    }

    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Email inválido");
        }
        return $email;
    }

    public static function validateAmount($amount) {
        if (!is_numeric($amount) || floatval($amount) <= 0) {
            throw new ValidationException("Quantidade deve ser um valor positivo");
        }
        return floatval($amount);
    }

    public static function validateSymbol($symbol) {
        if (empty($symbol) || strlen($symbol) > 10 || !preg_match('/^[A-Z0-9]+$/', $symbol)) {
            throw new ValidationException("Símbolo da ação inválido");
        }
        return strtoupper($symbol);
    }
}
?>
