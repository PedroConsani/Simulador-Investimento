<?php
require_once __DIR__ . "/../src/Model/utilizador.php";
session_start();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($username === '' || $senha === '') {
        $message = 'Preencha usuário e senha.';
    } else {
        $user = Utilizador::authenticate($username, $senha);
        if ($user) {
            $_SESSION['utilizador'] = $user;
            // redirecionar para página principal ou área restrita
            header('Location: index.php');
            exit;
        } else {
            $message = 'Credenciais inválidas.';
        }
    }
} elseif (isset($_GET['registered'])) {
    $message = 'Cadastro realizado. Já pode entrar.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin-top: 0; }
        .login-container { max-width: 400px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .login-container h1 { color: #1e3c72; text-align: center; }
        .login-container form { display: flex; flex-direction: column; gap: 15px; }
        .login-container label { font-weight: 600; color: #333; }
        .login-container input[type="text"],
        .login-container input[type="password"] { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .login-container input[type="submit"] { padding: 10px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .login-container input[type="submit"]:hover { opacity: 0.9; }
        .login-container p { text-align: center; }
        .login-container a { color: #2a5298; text-decoration: none; }
        .login-container a:hover { text-decoration: underline; }
        .message { color: #d32f2f; text-align: center; margin-bottom: 15px; font-weight: 600; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="login-container">
            <h1>Entrar</h1>
        <?php if ($message): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <label for="username">Login:</label>
            <input type="text" name="username" id="username" required>
            <label for="senha">Senha:</label>
            <input type="password" name="senha" id="senha" required>
            <input type="submit" value="Entrar">
        </form>
        <p>Não tem conta? <a href="register.php">Cadastre-se</a></p>
    </div>
</body>
</html>