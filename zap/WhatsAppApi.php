<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

require_once 'database.php';

function loadEnv($path)
{
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line)
    {
        if (strpos($line, "=") !== false)
        {
            list($key, $value) = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key))
            {
                putenv("$key=$value");
            }
        }
    }
}

// Carregar variáveis de ambiente do arquivo .env
$dotenvPath = __DIR__ . "/.env";
loadEnv($dotenvPath);

$postData = file_get_contents('php://input');

if ($postData !== false) {
    $data = json_decode($postData, true) ?? [];
    
    if (isset($data['tel']) && is_string($data['tel']) && trim($data['tel']) !== '') {
        $tel = $data['tel'];
        
        try {
            $resultado = verificarContaWhatsApp($tel);
            
            echo json_encode(["success" => $resultado, "msg" => $resultado ? "" : "O número não possui conta no WhatsApp"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Corpo da requisição POST inválido']);
}

function verificarContaWhatsApp($tel)
{
    $conn = connectDatabase();
    
    $sql = "SELECT id FROM Whatsapps WHERE status = 'CONNECTED'";
    $result = $conn->query($sql);
    
    $ids = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row["id"];
        }
    } else {
        $conn->close();
        throw new Exception('Nenhum serviço WhatsApp está conectado');
    }
    
    $randomIndex = array_rand($ids);
    $whatsappId = $ids[$randomIndex];
    
    $url = getenv('API_URL');
    $token = getenv('API_TOKEN');
    
    $data = ["number" => "55" . $tel, "body" => "", "whatsappId" => $whatsappId];
    
    $header = ["Authorization: Bearer $token", "Content-Type: application/json"];
    
    $cURL = curl_init($url);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, $header);
    curl_setopt($cURL, CURLOPT_POST, true);
    curl_setopt($cURL, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($cURL);
    
    if (curl_errno($cURL)) {
        $errorMsg = curl_error($cURL);
        curl_close($cURL);
        $conn->close();
        throw new Exception("Erro na requisição: $errorMsg");
    }
    
    curl_close($cURL);
    $conn->close();
    
    return empty($response);
}
?>
