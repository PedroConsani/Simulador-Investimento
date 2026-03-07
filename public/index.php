<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body> <?php include_once __DIR__ . '/navbar.php'; ?>
    <div style="max-width: 1200px; margin: 0 auto;">    <?php
    require_once __DIR__ . "/../src/Model/utilizador.php";
    //session_start();
    if (isset($_SESSION['utilizador']) && $_SESSION['utilizador'] instanceof Utilizador) {
        $user = $_SESSION['utilizador'];
        echo "<h1>Bem-vindo, " . htmlspecialchars($user->getUsername()) . "!</h1>";
        if ($user->getUsername() === "admin") {
            echo "<p><a href='/investimento/src/Model/admin.php'>Área de Administração</a></p>";
        }
    } else {
        // Limpar sessão corrompida
        unset($_SESSION['utilizador']);
        echo "<h1>Bem-vindo ao site de investimentos!</h1>";
    }
    ?>
    </div>
</body>
</html>