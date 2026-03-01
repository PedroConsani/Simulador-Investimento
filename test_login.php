<?php
require_once __DIR__ . "/src/Model/utilizador.php";

// Teste: criar um utilizador "teste" com senha "teste123"
echo "<h2>Teste de Login</h2>";

// Verificar o utilizador "teste"
$username = "teste";
$passwd = "teste123";

// Se não existe, criar
$user = Utilizador::findUser($username);
if (!$user) {
    echo "<p>Criando utilizador teste...</p>";
    if (Utilizador::register($username, $passwd)) {
        echo "<p>Utilizador 'teste' criado com sucesso. Password: 'teste123'</p>";
    } else {
        echo "<p>Falha ao criar utilizador.</p>";
    }
} else {
    echo "<p>Utilizador 'teste' já existe.</p>";
}

// Tentar autenticar
echo "<h3>Tentando autenticar...</h3>";
$auth = Utilizador::authenticate($username, $passwd);
if ($auth) {
    echo "<p>✓ Autenticação bem-sucedida!</p>";
    echo "<p>Username: " . $auth->getUsername() . "</p>";
    echo "<p>Saldo: " . $auth->getSaldoReal() . "</p>";
} else {
    echo "<p>✗ Autenticação falhou.</p>";
}

// Mostrar utilizadores existentes
echo "<h3>Utilizadores no sistema:</h3>";
$all = Utilizador::findUser("") || true; // force to show all
$file = __DIR__ . "/data/utilizadores.json";
if (file_exists($file)) {
    $json = json_decode(file_get_contents($file), true);
    echo "<pre>";
    print_r($json);
    echo "</pre>";
}
?>
