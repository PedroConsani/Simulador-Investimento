<?php
require_once __DIR__ . "/utilizador.php";
session_start();
if(isset($_SESSION["utilizador"])){
    $user = $_SESSION["utilizador"];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = floatval($_POST["valor"] ?? 0);
    if ($user && $valor > 0) {
        aumentarSaldo($user, $valor);
        // Atualizar o objeto na sessão, pois o saldo foi alterado. Busca no utilizador.json e atualiza
        //a sessão com o objeto atualizado para refletir o novo saldo
        $userData = Utilizador::findUser($user->getUsername());
        if ($userData) {
            $_SESSION['utilizador'] = new Utilizador($userData['username'], '', floatval($userData['saldo'] ?? 0));
        }
        header('Location: admin.php');
        exit;
    }
}

function aumentarSaldo($user, $valor){
    if (isset($_SESSION['utilizador'])) {
        // Salvar a alteração no arquivo JSON
        Utilizador::atualizarSaldo($user->getUsername(), $valor);
    }
}
    echo '<form action="admin.php" method="post">';
    echo "<p> Aumentar saldo de {$user->getUsername()} em: </p>";
    echo '<input type="number" name="valor" value="100">';
    echo "<button type='submit'>Aumentar Saldo</button>";
    echo '</form>';
?>