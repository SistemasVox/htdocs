<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para carregar as variáveis de ambiente do arquivo .env
function loadEnv($path)
{
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Carregar variáveis de ambiente do arquivo .env
$dotenvPath = __DIR__ . '/.env';
loadEnv($dotenvPath);

// Obter as credenciais do banco de dados do arquivo .env
$host = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Requisição GET - Buscar as configurações

        $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
        $configuracoes = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($configuracoes) {
            // Retorna as configurações como resposta JSON
            header('Content-Type: application/json');
            echo json_encode($configuracoes);
        } else {
            // Se não houver configurações encontradas, retorna um objeto vazio
            echo json_encode([]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Requisição POST - Atualizar as configurações

        // Receber as configurações via POST em formato JSON
        $configuracoesJSON = file_get_contents('php://input');
        $configuracoes = json_decode($configuracoesJSON, true);

        // Verificar se as configurações estão no formato correto
        if ($configuracoes === null || !is_array($configuracoes)) {
            $response = ['success' => false, 'message' => 'Formato inválido das configurações JSON.'];
            echo json_encode($response);
            exit;
        }

        // Atualizar as informações no banco de dados
        $stmtUpdate = $pdo->prepare("UPDATE configuracoes SET quantidade_por_dia = ?, hora_inicio = ?, hora_fim = ?, intervalo_minimo = ?");
        $stmtUpdate->execute([$configuracoes['quantidade_por_dia'], $configuracoes['hora_inicio'], $configuracoes['hora_fim'], $configuracoes['intervalo_minimo']]);

        $response = ['success' => true, 'message' => 'Configurações atualizadas com sucesso!'];
        echo json_encode($response);
    } else {
        $response = ['success' => false, 'message' => 'Método de requisição não suportado.'];
        echo json_encode($response);
    }
} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'Erro ao processar a requisição: ' . $e->getMessage()];
    echo json_encode($response);
}
?>
