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

// Função para retornar a conexão com o banco de dados
function getConnection() {
  global $host, $dbName, $username, $password;

  try {
    // Conexão com o banco de dados usando PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
  } catch (PDOException $e) {
    echo 'Erro ao conectar ao banco de dados: ' . $e->getMessage();
    return null;
  }
}

// Restante do código...

?>
