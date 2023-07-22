<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Função de conexão com o banco de dados
function connectDatabase() {
    $servername = "disparo.techsuper.com.br";
    $username = "whaticket";
    $password = "Yn4lfgCjhahkDSbz3ZcpTUgw3wnkK+HajhggfhKcvmc=";
    $database = "whaticket";
    $conn = null;

    try {
        $conn = new mysqli($servername, $username, $password, $database);

        if ($conn->connect_errno) {
            throw new Exception("Erro na conexão com o banco de dados: " . $conn->connect_error, $conn->connect_errno);
        }
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . "<br>";
        echo "Código do erro: " . $e->getCode() . "<br>";
    }

    return $conn;
}

function verificarContaWhatsApp($tel) {
    // Conexão com o banco de dados
    $conn = connectDatabase();

    // Consulta para obter os IDs dos serviços WhatsApp com status CONNECTED //
    $sql = "SELECT id FROM Whatsapps WHERE status = 'CONNECTED'";
    $result = $conn->query($sql);

    $ids = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row["id"];
        }
    } else {
        // Caso não haja serviço WhatsApp conectado, encerre a função e retorne falso //
        $conn->close();
        return false;
    }

    // Escolher um ID aleatoriamente //
    $randomIndex = array_rand($ids);
    $whatsappId = $ids[$randomIndex];

    // URL e Token da API //
    $url = "http://disparo.techsuper.com.br:8080/api/messages/send"; // URL da API
    $token = "b0a21769-cf8a-4e2b-a4bd-dfec4e38a94f"; // Token de autenticação da API

    // Dados para o envio da mensagem vazia //
    $data = [
        "number" => "55" . $tel, // Número de telefone do destinatário
        "body" => "", // Corpo vazio da mensagem
        "whatsappId" => $whatsappId // ID do serviço WhatsApp a ser utilizado
    ];

    $header = [
        "Authorization: Bearer $token", // Cabeçalho de autorização com o token de autenticação
        "Content-Type: application/json" // Tipo de conteúdo definido como JSON
    ];

    // Mecanismo de envio da requisição para a API //
    $cURL = curl_init($url);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, $header);
    curl_setopt($cURL, CURLOPT_POST, true);
    curl_setopt($cURL, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($cURL);
    curl_close($cURL);

    $conn->close();

    if (empty($response)) {
        return true; // O número possui conta no WhatsApp
    } else {
        return false; // O número não possui conta no WhatsApp
    }
}

$numero = "34991509513";
$resultado = verificarContaWhatsApp($numero);

if ($resultado) {
    echo "O número $numero possui WhatsApp.";
} else {
    echo "O número $numero NÃO possui WhatsApp.";
}
?>
