<?php
require_once('database.php');
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
set_time_limit(0);

$conn = getConnection();
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Falha ao conectar ao banco de dados."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    if ($_POST["action"] === "add_game") {
        $concurso = $_POST["concurso"];
        $data_concurso = $_POST["data_concurso"];
        $dezenas = $_POST["dezenas"];
        echo addGame($conn, $concurso, $data_concurso, $dezenas);
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"])) {
    if ($_GET["action"] === "get_games") {
        echo getGames($conn);
    } elseif ($_GET["action"] === "get_game") {
        $id = $_GET["id"];
        echo getGame($conn, $id);
    } elseif ($_GET["action"] === "even_odd_stats") {
        $type = $_GET["type"];
        echo getEvenOddStats($conn, $type);
    } elseif ($_GET["action"] === "get_total_games") {
        echo getTotalGames($conn);
    } elseif ($_GET["action"] === "get_last_concurso") {
        echo getLastConcurso($conn);
    } elseif ($_GET["action"] === "get_most_frequent_numbers") {
        echo getMostFrequentNumbers($conn);
    } elseif ($_GET["action"] === "get_least_frequent_numbers") {
        echo getLeastFrequentNumbers($conn);
    } elseif ($_GET["action"] === "get_number_range") {
        echo getNumberRange($conn);
    } elseif ($_GET["action"] === "get_average_numbers") {
        echo getAverageNumbers($conn);
    } elseif ($_GET["action"] === "get_even_odd_distribution") {
        echo getEvenOddDistribution($conn);
    } elseif ($_GET["action"] === "calcularEstatisticasDeTodosOsJogos") {
		$games = json_decode(getGames($conn), true); // Decodifica o JSON para obter o array de jogos
		echo json_encode(calcularEstatisticasDeTodosOsJogos($games), true);
	}
}

function getTotalGames($conn) {
    $query = "SELECT COUNT(*) AS total FROM loto";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $row["total"];

    return json_encode(["total" => $total]);
}


function getLastConcurso($conn) {
    $query = "SELECT * FROM loto ORDER BY concurso DESC LIMIT 1";
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return json_encode($row);
}

function getMostFrequentNumbers($conn, $concursoInicial = null, $concursoFinal = null, $limit = 5) {
    $sql = "SELECT dezenas FROM loto";
    $params = [];

    if ($concursoInicial !== null && $concursoFinal !== null) {
        $sql .= " WHERE concurso BETWEEN :concursoInicial AND :concursoFinal";
        $params[':concursoInicial'] = $concursoInicial;
        $params[':concursoFinal'] = $concursoFinal;
    } elseif ($concursoInicial !== null) {
        $sql .= " WHERE concurso >= :concursoInicial";
        $params[':concursoInicial'] = $concursoInicial;
    } elseif ($concursoFinal !== null) {
        $sql .= " WHERE concurso <= :concursoFinal";
        $params[':concursoFinal'] = $concursoFinal;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $numbers = array();

    foreach ($games as $game) {
        $dezenas = explode(',', $game["dezenas"]);
        foreach ($dezenas as $numero) {
            $numbers[$numero] = isset($numbers[$numero]) ? $numbers[$numero] + 1 : 1;
        }
    }

    arsort($numbers);
    $numbers = array_slice($numbers, 0, $limit, true);

    return json_encode($numbers);
}


function getLeastFrequentNumbers($conn, $concursoInicial = null, $concursoFinal = null, $limit = 5) {
    $sql = "SELECT dezenas FROM loto";
    $params = [];

    if ($concursoInicial !== null && $concursoFinal !== null) {
        $sql .= " WHERE concurso BETWEEN :concursoInicial AND :concursoFinal";
        $params[':concursoInicial'] = $concursoInicial;
        $params[':concursoFinal'] = $concursoFinal;
    } elseif ($concursoInicial !== null) {
        $sql .= " WHERE concurso >= :concursoInicial";
        $params[':concursoInicial'] = $concursoInicial;
    } elseif ($concursoFinal !== null) {
        $sql .= " WHERE concurso <= :concursoFinal";
        $params[':concursoFinal'] = $concursoFinal;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $numbers = array();

    foreach ($games as $game) {
        $dezenas = explode(',', $game["dezenas"]);
        foreach ($dezenas as $numero) {
            $numbers[$numero] = isset($numbers[$numero]) ? $numbers[$numero] + 1 : 1;
        }
    }

    uasort($numbers, function ($a, $b) {
        return $a - $b; // Ordenar por frequência em ordem crescente
    });

    $numbers = array_slice($numbers, 0, $limit, true);

    return json_encode($numbers);
}




function getNumberRange($conn) {
    $query = "SELECT dezenas FROM loto";
    $stmt = $conn->query($query);
    $ranges = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dezenas = explode(',', $row["dezenas"]);
        $range = max($dezenas) - min($dezenas);
        $ranges[] = $range;
    }

    $averageRange = array_sum($ranges) / count($ranges);

    return json_encode([
        "min" => min($ranges),
        "max" => max($ranges),
        "average" => $averageRange
    ]);
}

function getAverageNumbers($conn) {
    $query = "SELECT dezenas FROM loto";
    $stmt = $conn->query($query);
    $sums = 0;
    $totalNumbers = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dezenas = explode(',', $row["dezenas"]);
        foreach ($dezenas as $numero) {
            $sums += $numero;
            $totalNumbers++;
        }
    }

    $average = $sums / $totalNumbers;

    return json_encode(["average" => $average]);
}

function getEvenOddDistribution($conn) {
    $query = "SELECT dezenas FROM loto";
    $stmt = $conn->query($query);
    $evenCount = 0;
    $oddCount = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dezenas = explode(',', $row["dezenas"]);
        foreach ($dezenas as $numero) {
            if ($numero % 2 === 0) {
                $evenCount++;
            } else {
                $oddCount++;
            }
        }
    }

    return json_encode([
        "even" => $evenCount,
        "odd" => $oddCount
    ]);
}

function getEvenOddStats($conn, $type, $start = null, $end = null) {
    $sql = "SELECT concurso, dezenas FROM loto";
    $params = [];

    if ($start !== null && $end !== null) {
        $sql .= " WHERE concurso BETWEEN :start AND :end";
        $params[':start'] = $start;
        $params[':end'] = $end;
    } elseif ($start !== null) {
        $sql .= " WHERE concurso >= :start";
        $params[':start'] = $start;
    } elseif ($end !== null) {
        $sql .= " WHERE concurso <= :end";
        $params[':end'] = $end;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = array_fill(1, 25, 0);
    foreach ($games as $game) {
        $isConcursoEven = $game['concurso'] % 2 === 0;
        $dezenas = explode(",", $game['dezenas']);
        foreach ($dezenas as $dezena) {
            if ($type === 'all' || 
                ($type === 'par' && $isConcursoEven) || 
                ($type === 'impar' && !$isConcursoEven)) {
                $count[intval($dezena)] += 1;
            }
        }
    }

    $stats = [];
    foreach ($count as $number => $frequency) {
        if($frequency > 0) {
            $stats[] = [
                'number' => $number,
                'frequency' => $frequency
            ];
        }
    }

    usort($stats, function($a, $b) {
        return $b['frequency'] - $a['frequency'];
    });

    return json_encode($stats);
}

function getGames($conn) {
    $stmt = $conn->prepare("SELECT * FROM loto");
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($games as $index => $game) {
        $games[$index]['dezenas'] = explode(",", $game['dezenas']);
    }
    
    return json_encode($games);
}

function getGame($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM loto WHERE concurso = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($game) {
        $game['dezenas'] = explode(",", $game['dezenas']);
    }
    
    return json_encode($game);
}

function addGame($conn, $concurso, $data_concurso, $dezenas) {
    $stmt = $conn->prepare("INSERT INTO loto (concurso, data_concurso, dezenas) VALUES (:concurso, :data_concurso, :dezenas)");
    $stmt->bindParam(':concurso', $concurso);
    $stmt->bindParam(':data_concurso', $data_concurso);
    $stmt->bindParam(':dezenas', $dezenas);
    $stmt->execute();
    return json_encode(['status' => 'success', 'message' => 'Jogo adicionado com sucesso']);
}

function calcularEstatisticas($objetoJson) {
    $dezenas = $objetoJson['dezenas'];

    $dezena0 = array_filter($dezenas, function ($numero) {
        return strpos($numero, '0') === 0;
    });
    $dezena0 = count($dezena0);

    $dezena1 = array_filter($dezenas, function ($numero) {
        return strpos($numero, '1') === 0;
    });
    $dezena1 = count($dezena1);

    $dezena2 = array_filter($dezenas, function ($numero) {
        return strpos($numero, '2') === 0;
    });
    $dezena2 = count($dezena2);

    $sequencias = [];
    $sequenciaAtual = [];
    for ($i = 0; $i < count($dezenas) - 1; $i++) {
        if (intval($dezenas[$i + 1]) - intval($dezenas[$i]) === 1) {
            if (empty($sequenciaAtual)) {
                $sequenciaAtual[] = $dezenas[$i];
            }
            $sequenciaAtual[] = $dezenas[$i + 1];
        } else {
            if (count($sequenciaAtual) > 1) {
                $sequencias[] = implode(',', $sequenciaAtual);
            }
            $sequenciaAtual = [];
        }
    }

    $finais = [];
    foreach ($dezenas as $numero) {
        $finais[] = substr($numero, -1);
    }
    $finais = array_unique($finais);
    $finais = count($finais);

    $finaisRepetidos = calcularFinaisRepetidos($dezenas);

    return [
        'D0' => $dezena0,
        'D1' => $dezena1,
        'D2' => $dezena2,
        'Seq' => count($sequencias),
        'Fim' => $finais,
        'FimR' => $finaisRepetidos,
    ];
}

function calcularFinaisRepetidos($dezenas) {
    $finais = array();
    $finaisRepetidos = array();

    foreach ($dezenas as $numero) {
        $final = substr($numero, -1);
        if (in_array($final, $finais)) {
            $finaisRepetidos[] = $final;
        } else {
            $finais[] = $final;
        }
    }

    return $finaisRepetidos;
}


// Função para calcular as estatísticas de todos os jogos
function calcularEstatisticasDeTodosOsJogos($games) {
    $totalGames = count($games);

    $dezena0Count = array();
    $dezena1Count = array();
    $dezena2Count = array();
    $sequenciasCount = array();
    $finaisCount = array();
    $finaisRepetidosCount = array();

    foreach ($games as $game) {
        $estatisticas = calcularEstatisticas($game);
        $dezena0Count[$estatisticas['D0']] = isset($dezena0Count[$estatisticas['D0']]) ? $dezena0Count[$estatisticas['D0']] + 1 : 1;
        $dezena1Count[$estatisticas['D1']] = isset($dezena1Count[$estatisticas['D1']]) ? $dezena1Count[$estatisticas['D1']] + 1 : 1;
        $dezena2Count[$estatisticas['D2']] = isset($dezena2Count[$estatisticas['D2']]) ? $dezena2Count[$estatisticas['D2']] + 1 : 1;
        $sequenciasCount[$estatisticas['Seq']] = isset($sequenciasCount[$estatisticas['Seq']]) ? $sequenciasCount[$estatisticas['Seq']] + 1 : 1;

        // Verificar e contar os finais repetidos
        if (is_array($estatisticas['FimR'])) {
            foreach ($estatisticas['FimR'] as $finalRepetido) {
                $finaisRepetidosCount[$finalRepetido] = isset($finaisRepetidosCount[$finalRepetido]) ? $finaisRepetidosCount[$finalRepetido] + 1 : 1;
            }
        }

        if (is_array($estatisticas['Fim'])) {
            foreach ($estatisticas['Fim'] as $final) {
                $finaisCount[$final] = isset($finaisCount[$final]) ? $finaisCount[$final] + 1 : 1;
            }
        } else {
            $finaisCount[$estatisticas['Fim']] = isset($finaisCount[$estatisticas['Fim']]) ? $finaisCount[$estatisticas['Fim']] + 1 : 1;
        }
    }

    arsort($dezena0Count);
    arsort($dezena1Count);
    arsort($dezena2Count);
    arsort($sequenciasCount);
    arsort($finaisCount);
    arsort($finaisRepetidosCount);

    $d0MaisComum = array_slice($dezena0Count, 0, 5, true);
    $d1MaisComum = array_slice($dezena1Count, 0, 5, true);
    $d2MaisComum = array_slice($dezena2Count, 0, 5, true);
    $sequenciaMaisComum = array_slice($sequenciasCount, 0, 5, true);
    $finaisMaisComuns = array_slice($finaisCount, 0, 5, true);
    $finaisRepetidosMaisComuns = array_slice($finaisRepetidosCount, 0, 5, true);

    return [
        'D0MaisComum' => $d0MaisComum,
        'D1MaisComum' => $d1MaisComum,
        'D2MaisComum' => $d2MaisComum,
        'SequenciaMaisComum' => $sequenciaMaisComum,
        'FinaisMaisComuns' => $finaisMaisComuns,
        'FinaisRepetidosMaisComuns' => $finaisRepetidosMaisComuns,
    ];
}
?>
