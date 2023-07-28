<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
set_time_limit(0);
require_once('api.php');
// Definindo a URL da nossa API
$apiUrl = "http://localhost/lf/api.php";

/* echo "Testando a obtenção de todos os jogos...\n";
$data = ['action' => 'get_games'];
$response = sendGetRequest($apiUrl, $data);
print_r($response);

echo "Testando a obtenção de um jogo específico...\n";
$data = [
    'action' => 'get_game',
    'id' => 1
];
$response = sendGetRequest($apiUrl, $data);
print_r($response); */

/* echo "Testando a obtenção de estatísticas de números pares e ímpares...\n";
$data = [
    'action' => 'even_odd_stats',
    'type' => 'par' // ou 'impar' dependendo do que você quer testar
];
$response = sendGetRequest($apiUrl, $data);
print_r(json_decode($response, true)); // Converte a resposta JSON em um array PHP para facilitar a leitura

// Função para enviar uma requisição GET
function sendGetRequest($url, $data) {
    $url = sprintf("%s?%s", $url, http_build_query($data));
    $result = file_get_contents($url);
    return $result;
} */
/* // Exemplo de uso com o objeto JSON fornecido
$objetoJson = json_decode('{"concurso":1,"data_concurso":"2003-09-29","dezenas":["02","03","05","06","09","10","11","13","14","16","18","20","23","24","25"]}', true);

$estatisticas = calcularEstatisticas($objetoJson);
print_r($estatisticas);
 */

/* // Obtém todos os jogos do banco de dados
$games = json_decode(getGames($conn), true);

// Calcula as estatísticas de todos os jogos
$estatisticasDeTodosOsJogos = calcularEstatisticasDeTodosOsJogos($games, 1);

// Exibir os resultados
echo '<pre>';
print_r($estatisticasDeTodosOsJogos);
echo '</pre>'; */
		$games = json_decode(getGames($conn), true); // Decodifica o JSON para obter o array de jogos
		echo json_encode(calcularEstatisticasDeTodosOsJogos($games), true);
?>
