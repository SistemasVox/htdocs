<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'database.php';

function generateGame() {
	$N = 15;
	$jogo = [];
	while (count($jogo) !== $N) {
		$jogo = [];
		for ($i = 0;$i < $N;$i++) {
			$jogo[] = zero(getNr(1, 25));
		}
		$jogo = array_values(array_unique($jogo));
	}
	sort($jogo);
	return $jogo;
}

function getNr($min, $max) {
	return rand($min, $max);
}

function zero($n) {
	return str_pad($n, 2, "0", STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"])) {
	if ($_GET["action"] === "generate") {
		$jogo = generateGame();
		$results = ["jogo" => $jogo];
		header('Content-Type: application/json');
		echo json_encode($results);
	}
}

/* function findValidGame() {
  $validGame = null;

  try {
    // Estabelecer a conexão com o banco de dados
    $conn = getConnection();

    // Consultar os últimos 100 jogos
    $stmt = $conn->prepare('SELECT dezenas FROM loto ORDER BY concurso DESC LIMIT 100');
    $stmt->execute();
    $ultimosJogos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Calcular as estatísticas dos últimos 100 jogos
    $estatisticas = calcularEstatisticas($ultimosJogos);

    while (!$validGame) {
      $game = generateGame();

      // Verificar se o jogo atende aos critérios desejados com base nas estatísticas dos últimos 100 jogos
      if (verificarQuantidadeSequencia($game) <= $estatisticas['quantidadeSequencia'] &&
          verificarQuantidadeDezenas($game, $estatisticas['quantidadeDezenas']) &&
          verificarQuantidadeFinal($game, $estatisticas['quantidadeFinais'])) {
        $validGame = $game;
      }
    }
  } catch (PDOException $e) {
    echo 'Erro ao conectar ao banco de dados: ' . $e->getMessage();
    return false;
  } finally {
    $conn = null; // Fechar a conexão com o banco de dados
  }

  return $validGame;
}

function calcularEstatisticas($ultimosJogos) {
  $quantidadeSequencia = 0;
  $quantidadeDezenas = [];
  $quantidadeFinais = [];

  foreach ($ultimosJogos as $jogo) {
    $quantidadeSequencia += verificarQuantidadeSequencia($jogo);

    $dezenas = verificarQuantidadeDezenas($jogo);
    foreach ($dezenas as $primeiroDigito => $quantidade) {
      if (!isset($quantidadeDezenas[$primeiroDigito])) {
        $quantidadeDezenas[$primeiroDigito] = 0;
      }
      $quantidadeDezenas[$primeiroDigito] += $quantidade;
    }

    $finais = verificarQuantidadeFinal($jogo);
    foreach ($finais as $ultimoDigito => $quantidade) {
      if (!isset($quantidadeFinais[$ultimoDigito])) {
        $quantidadeFinais[$ultimoDigito] = 0;
      }
      $quantidadeFinais[$ultimoDigito] += $quantidade;
    }
  }

  return [
    'quantidadeSequencia' => $quantidadeSequencia,
    'quantidadeDezenas' => $quantidadeDezenas,
    'quantidadeFinais' => $quantidadeFinais
  ];
}


function verificarQuantidadeDezenas($jogo) {
  $dezenas = array();
  foreach ($jogo as $dezena) {
    $primeiroDigito = substr($dezena, 0, 1);
    if (!isset($dezenas[$primeiroDigito])) {
      $dezenas[$primeiroDigito] = 1;
    } else {
      $dezenas[$primeiroDigito]++;
    }
  }
  
  return $dezenas;
}

function verificarQuantidadeFinal($jogo) {
  $finais = array();
  foreach ($jogo as $dezena) {
    $ultimoDigito = substr($dezena, -1);
    if (!isset($finais[$ultimoDigito])) {
      $finais[$ultimoDigito] = 1;
    } else {
      $finais[$ultimoDigito]++;
    }
  }
  
  return $finais;
}

function verificarQuantidadeSequencia($jogo) {
  $quantidadeSequencia = 0;
  for ($i = 1; $i < count($jogo); $i++) {
    $numeroAtual = intval($jogo[$i]);
    $numeroAnterior = intval($jogo[$i - 1]);
    
    if ($numeroAtual === $numeroAnterior + 1) {
      $quantidadeSequencia++;
    }
  }
  
  return $quantidadeSequencia;
}
 */