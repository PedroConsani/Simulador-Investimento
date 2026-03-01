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
    if (isset($_SESSION['utilizador'])) {
        echo "<h1>Bem-vindo, " . htmlspecialchars($_SESSION['utilizador']->getUsername()) . "!</h1>";
    } else {
        echo "<h1>Bem-vindo ao site de investimentos!</h1>";
    }
    ?>
    <nav>
            <a href="login.php"><li>Login</li></a>
            <a href="register.php"><li>Registro</li></a>
            <a href="../src/Model/paper_tr_jogo.php"><li>Paper Trading Passado</li></a>
            <a href="../src/Model/paper_tr_real.php"><li>Paper Trading Real</li></a>
    </nav>
</body>
</html>