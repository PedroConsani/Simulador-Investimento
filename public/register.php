<?php
require_once __DIR__ . "/../src/Model/utilizador.php";
session_start();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($username === '' || $senha === '') {
        $message = 'Por favor, preencha todos os campos.';
    } else {
        if (Utilizador::register($username, $senha)) {
            $message = 'Cadastro efetuado com sucesso. Agora faça login.';
            // opcional: redirecionar para login
            header('Location: login.php?registered=1');
            exit;
        } else {
            $message = 'Nome de usuário já existe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar</title>
</head>
<body>
    <h1>Registrar</h1>
    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form action="" method="post">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <br/>
        <label for="senha">Senha:</label>
        <input type="password" name="senha" id="senha" required>
        <br/>
        <button type="submit">Cadastrar</button>
    </form>
    <p>Já tem conta? <a href="login.php">Entrar</a></p>
</body>
</html>