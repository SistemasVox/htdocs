<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once 'WhatsAppApi.php';

// Teste da requisição POST para WhatsAppApi.php
$data = ['tel' => '34991509519'];

// Codificamos os dados em formato JSON para o envio
$jsonData = json_encode($data);

// Iniciamos o cURL
$ch = curl_init();

// Definimos a URL do endpoint da API
curl_setopt($ch, CURLOPT_URL, "http://localhost/zap/WhatsAppApi.php");
// curl_setopt($ch, CURLOPT_URL, "http://buscarempresa.techsuper.com.br/zap/WhatsAppApi.php");

// Definimos que a transferência de dados é via POST
curl_setopt($ch, CURLOPT_POST, 1);

// Passamos os dados a serem enviados via POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

// Definimos o cabeçalho da requisição
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Definimos que queremos o retorno da requisição
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Executamos a requisição e armazenamos a resposta
$response = curl_exec($ch);

// Verificamos se ocorreu algum erro durante a requisição
if ($response === false) {
    echo "Erro na requisição: " . curl_error($ch) . "<br>";
    exit;
}

// Obtemos informações adicionais sobre a requisição
$info = curl_getinfo($ch);

// Fechamos a requisição
curl_close($ch);

// Imprimimos as informações de depuração
echo "Informações da requisição:<br>";
echo "URL: " . $info['url'] . "<br>";
echo "Código de resposta: " . $info['http_code'] . "<br>";
echo "Tempo total: " . $info['total_time'] . " segundos<br>";
echo "Dados enviados: " . $jsonData . "<br>";
echo "Resposta da API: " . $response . "<br>";

// Decodificar a resposta JSON para um objeto PHP
$responseObj = json_decode($response, true);

// Imprimimos a resposta
if ($responseObj !== null && isset($data['tel']) && $responseObj['success'] == true) {
    echo "POST: Sucesso! O número " . $data['tel'] . " possui uma conta do WhatsApp.<br>";
    echo "Resposta da requisição POST: " . $response . ".<br>";
} else {
    // Imprimimos a resposta
    echo "Falha! Resposta da requisição POST: " . $response . ".<br>";
}
