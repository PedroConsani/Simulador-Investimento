<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
    require_once __DIR__ . "/../src/Model/utilizador.php";
    session_start();
    if (isset($_SESSION['utilizador']) && $_SESSION['utilizador'] instanceof Utilizador) {
        $user = $_SESSION['utilizador'];
        echo "<h1>Bem-vindo, " . htmlspecialchars($user->getUsername()) . "!</h1>";
        echo "<p>Saldo: " . number_format($user->getSaldoReal(), 2, ',', '.') . "</p>";
        if ($user->getUsername() === "admin") {
            echo "<p><a href='/investimento/src/Model/admin.php'>Área de Administração</a></p>";
        }
    } else {
        // Limpar sessão corrompida
        unset($_SESSION['utilizador']);
        echo "<h1>Bem-vindo ao site de investimentos!</h1>";
    }
    ?>
    <nav>
        <?php if (!isset($_SESSION['utilizador'])): ?>
            <a href="login.php"><li>Login</li></a>
            <a href="register.php"><li>Registro</li></a>
        <?php else: ?>
            <a href="logout.php"><li>Logout</li></a>
        <?php endif; ?>
        <a href="../src/Model/paper_tr_jogo.php"><li>Paper Trading Passado</li></a>
        <a href="../src/Model/paper_tr_real.php"><li>Paper Trading Real</li></a>
    </nav>
</body>
</html>