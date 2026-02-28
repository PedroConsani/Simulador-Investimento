<?php
require_once __DIR__ . "/Model/utilizador.php";
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
            header('Location: index.html');
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
</head>
<body>
    <h1>Entrar</h1>
    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form action="" method = "post">
        <label for="username">Login: </label>
        <input type="text" name = "username" id = "username" required>
        <br/>
        <label for="senha">Senha: </label>
        <input type="password" name="senha" id="senha" required>
        <br/>
        <input type="submit" value="Entrar">
    </form>
    <p>Não tem conta? <a href="register.php">Cadastre-se</a></p>
</body>
</html>