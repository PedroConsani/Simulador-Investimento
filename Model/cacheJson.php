<?php
function carregarDadosCache($tipoDado){
    /*Recebe um tipo de dado string que está sendo procurado.
    Retorna dados que estão armazenados em cache se existe, caso contrário retorna null.
    */ 
    $arquivo = __DIR__ . "/../Dados/dados_$tipoDado.json";

    if (file_exists($arquivo)) {
        /*Verifica se o arquivo para essa procura existe.
        Caso o arquivo exista, os dados serão obtidos a partir dele e não da API.
        */ 
        $conteudo = file_get_contents($arquivo);
        $dados = json_decode($conteudo, true);
        
        //Garante que $dados é uma array, pois json_decode pode retornar null, ocasionará em erro ao tentar 
        //percorrer o array.
        if (!is_array($dados)) {
            $dados = [];
        }
        return $dados;
    }
    else {
        return null; // Retorna null se o arquivo não existir
    }
}
function criarCacheJson($tipoDado, $url){
    /*Recebe um tipo de dado string e um dado array.
    Cria um arquivo json com o nome do tipo de dado e salva o dado nesse arquivo.
    */ 
    $arquivo = __DIR__ . "/../Dados/dados_$tipoDado.json";
    $response = file_get_contents($url);
    $novoDados = json_decode($response, true);
    foreach($novoDados as $dado){
        $dados[] = $dado;
        }
    //Criam um arquivo.json com o nome da pesquisa desse arquivo.
    file_put_contents($arquivo, json_encode($novoDados, JSON_PRETTY_PRINT));
    return $novoDados;
} 
?>