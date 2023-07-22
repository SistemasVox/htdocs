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
