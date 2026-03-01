<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="post">
        <label for="nome_acao">Símbolo da Ação:</label>
        <input type="text" name="nome_acao" id="nome_acao">
        <button type="submit">Verificar</button>
    </form>
    <?php 
        require_once __DIR__ . "../../config/config.php"; //Acessa a constante API_KEY para não expor a chave ao ambiente de produção
        //isset() verifica se a variável foi definida e não é nula, ou seja, se o formulário foi submetido
        if(isset($_POST["nome_acao"])){
            $acao = $_POST["nome_acao"];
            $url = "https://financialmodelingprep.com/stable/profile?symbol=$acao&apikey=".API_KEY;
            $response = file_get_contents($url); //Requisição get
    // print_r($response);
            $dado = json_decode($response,true); //Transforma JSON em array php
            print_r($dado);
        }

    
    ?>


</body>
</html>
