<?php
// Função para carregar as variáveis de ambiente do arquivo .env
function loadEnv($path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, "=") !== false) {
            list($key, $value) = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Carregar variáveis de ambiente do arquivo .env
$dotenvPath = __DIR__ . "/.env";
loadEnv($dotenvPath);

// Obter as credenciais do banco de dados do arquivo .env
$host = getenv("DB_HOST");
$dbName = getenv("DB_NAME");
$username = getenv("DB_USERNAME");
$password = getenv("DB_PASSWORD");

function conectarBancoDados($host, $dbName, $username, $password) {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro ao conectar ao banco de dados: " . $e->getMessage()]);
        exit; // Encerra a execução do script após exibir o erro
    }
}

// Endpoint para carregar todas as mensagens
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_mensagens'])) {
    $conn = conectarBancoDados($host, $dbName, $username, $password);

    if ($conn instanceof PDO) {
        $query = "SELECT * FROM mensagem";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($mensagens);
    } else {
        echo $conn; // Retorna o erro JSON caso a conexão tenha falhado
    }
}

// Endpoint para carregar todos os serviços
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_servicos'])) {
    $conn = conectarBancoDados($host, $dbName, $username, $password);

    if ($conn instanceof PDO) {
        $query = "SELECT * FROM servico";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($servicos);
    } else {
        echo $conn; // Retorna o erro JSON caso a conexão tenha falhado
    }
}

// Endpoint para associar uma mensagem a um serviço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem_id']) && isset($_POST['servico_id'])) {
    $conn = conectarBancoDados($host, $dbName, $username, $password);

    if ($conn instanceof PDO) {
        $mensagem_id = $_POST['mensagem_id'];
        $servico_id = $_POST['servico_id'];

        // Verificar se a associação já existe
        $query = "SELECT COUNT(*) AS num_rows FROM mensagemservico WHERE id_mensagem = :mensagem_id AND id_servico = :servico_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mensagem_id', $mensagem_id);
        $stmt->bindParam(':servico_id', $servico_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['num_rows'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Associação já existe.']);
        } else {
            // Faz a inserção apenas se a associação não existir
            $query = "INSERT INTO mensagemservico (id_mensagem, id_servico) VALUES (:mensagem_id, :servico_id)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':mensagem_id', $mensagem_id);
            $stmt->bindParam(':servico_id', $servico_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $stmt->errorInfo()]);
            }
        }
    } else {
        echo json_encode(["success" => false, "message" => "Erro ao conectar ao banco de dados."]);
    }
}

// Endpoint para carregar todas as associações entre mensagens e serviços
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_mensagem_servico'])) {
    $conn = conectarBancoDados($host, $dbName, $username, $password);

    if ($conn instanceof PDO) {
        $query = "SELECT * FROM mensagemservico";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $associacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($associacoes);
    } else {
        echo $conn; // Retorna o erro JSON caso a conexão tenha falhado
    }
}

// Endpoint para obter a quantidade de mensagens por serviço
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_mensagem_servico_quantidade'])) {
    $conn = conectarBancoDados($host, $dbName, $username, $password);

    if ($conn instanceof PDO) {
        $query = "SELECT id_servico, COUNT(DISTINCT id_mensagem) AS quantidade FROM mensagemservico GROUP BY id_servico";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $quantidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($quantidades);
    } else {
        echo $conn; // Retorna o erro JSON caso a conexão tenha falhado
    }
}
// Endpoint para carregar o nome de um serviço pelo ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_servico_nome']) && isset($_GET['id'])) {
    $conn = conectarBancoDados($host, $dbName, $username, $password);

    if ($conn instanceof PDO) {
        $servico_id = $_GET['id'];

        $query = "SELECT descricao FROM servico WHERE id = :servico_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':servico_id', $servico_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode($result['descricao']);
        } else {
            echo json_encode(['error' => 'Serviço não encontrado']);
        }
    } else {
        echo json_encode(['error' => 'Erro ao conectar ao banco de dados']);
    }
}