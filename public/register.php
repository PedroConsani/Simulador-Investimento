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
    <style>
        body { font-family: Arial, sans-serif; margin-top: 0; }
        .register-container { max-width: 400px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .register-container h1 { color: #1e3c72; text-align: center; }
        .register-container form { display: flex; flex-direction: column; gap: 15px; }
        .register-container label { font-weight: 600; color: #333; }
        .register-container input[type="text"],
        .register-container input[type="password"] { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .register-container button { padding: 10px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .register-container button:hover { opacity: 0.9; }
        .register-container p { text-align: center; }
        .register-container a { color: #2a5298; text-decoration: none; }
        .register-container a:hover { text-decoration: underline; }
        .message { color: #4CAF50; text-align: center; margin-bottom: 15px; font-weight: 600; }
        .message.error { color: #d32f2f; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="register-container">
            <h1>Registrar</h1>
        <?php if ($message): ?>
            <p class="message<?php echo (strpos($message, 'sucesso') === false ? ' error' : ''); ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            <label for="senha">Senha:</label>
            <input type="password" name="senha" id="senha" required>
            <button type="submit">Cadastrar</button>
        </form>
        <p>Já tem conta? <a href="login.php">Entrar</a></p>
    </div>
</body>
</html>