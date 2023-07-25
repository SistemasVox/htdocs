<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
set_time_limit(0);
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
$dotenvPath = __DIR__ . "/.env";
loadEnv($dotenvPath);
$host = getenv("DB_HOST");
$dbName = getenv("DB_NAME");
$username = getenv("DB_USERNAME");
$password = getenv("DB_PASSWORD");
function conectarBancoDados($host, $dbName, $username, $password) {
	try {
		$conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $username, $password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $conn;
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao conectar ao banco de dados: " . $e->getMessage() ]);
	}
}
function atualizarRegistro($conn, $id, $nome, $cnpj, $numero, $mensagem, $enviado) {
	try {
		$sql = "UPDATE logs_envio SET nome = :nome, cnpj = :cnpj, numero = :numero, id_mensagem = :mensagem, enviado = :enviado WHERE id = :id";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(":nome", $nome);
		$stmt->bindParam(":cnpj", $cnpj);
		$stmt->bindParam(":numero", $numero);
		$stmt->bindParam(":mensagem", $mensagem);
		$stmt->bindParam(":enviado", $enviado);
		$stmt->bindParam(":id", $id);
		$stmt->execute();
		return json_encode(["success" => true, "message" => "Registro atualizado com sucesso!", ]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao atualizar o registro: " . $e->getMessage() , ]);
	}
}
function getDataServidor($conn) {
	try {
		$sql = "SELECT NOW() AS data_servidor";
		$stmt = $conn->query($sql);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$dataServidor = $result["data_servidor"];
		return json_encode(["success" => true, "message" => $dataServidor]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao obter a data do servidor: " . $e->getMessage() , ]);
	}
}
function formatarDataHora($dataHora) {
	$partes = explode(" ", $dataHora);
	$data = implode("/", array_reverse(explode("-", $partes[0])));
	$hora = substr($partes[1], 0, 8);
	return $data . " " . $hora;
}
function extrairDataDeRespostaJson($respostaJson) {
	$resposta = json_decode($respostaJson, true);
	if ($resposta["success"]) {
		return $resposta["message"];
	}
	else {
		throw new Exception($resposta["message"]);
	}
}
function gerarProximaDataHoraDisponivel($dataInicio, $horaInicio, $horaFim) {
	$dataAtual = new DateTime($dataInicio);
	$diaDaSemana = $dataAtual->format("N");
	$horaAtual = $dataAtual->format("H:i");
	if ($diaDaSemana >= 1 && $diaDaSemana <= 5 && (($horaAtual < $horaFim && $horaAtual > $horaInicio) || ($horaInicio == $horaFim && $horaAtual == $horaInicio))) {
		$horaInicioTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaAtual);
		$horaFimTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaFim);
	}
	else {
		do {
			$dataAtual->add(new DateInterval("P1D"));
			$diaDaSemana = $dataAtual->format("N");
		} while ($diaDaSemana == 6 || $diaDaSemana == 7);
		$horaInicioTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaInicio);
		$horaFimTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaFim);
	}
	$timestampAleatorio = mt_rand($horaInicioTimestamp, $horaFimTimestamp);
	$proximaDataHoraDisponivel = new DateTime("@" . $timestampAleatorio);
	$proximaDataHoraDisponivel->setTimezone($dataAtual->getTimezone());
	return $proximaDataHoraDisponivel->format("Y-m-d H:i:s");
}
function ajustarDataEnvio($conn, $horaInicio, $horaFim, $intervaloMinimo, $acao, $quantidade_por_dia) {
	try {
		$dataServidor = extrairDataDeRespostaJson(getDataServidor($conn));
		$sql = $acao === "rebobinar" ? "SELECT * FROM logs_envio WHERE enviado = 0 AND hora <= NOW()" : "SELECT * FROM logs_envio WHERE enviado = 0 AND hora > NOW()";
		$stmt = $conn->query($sql);
		$logsParaAjustar = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$numLogsParaAjustar = count($logsParaAjustar);
		if ($numLogsParaAjustar == 0) {
			$message = $acao === "rebobinar" ? "Não foi necessário rebobinar, nenhum log atrasado encontrado." : "Não foi necessário avançar, nenhum log encontrado.";
			return json_encode(["success" => true, "message" => $message]);
		}
		$dataAtual = new DateTime($dataServidor);
		$dataInicio = $dataAtual->format("Y-m-d");
		$datas = [];
		$logsParaHoje = 0;
		$tentativas = 0;
		while (count($datas) < $numLogsParaAjustar) {
			if ($logsParaHoje >= $quantidade_por_dia || $tentativas >= 100) {
				$dataInicio = date("Y-m-d", strtotime($dataInicio . " +1 day"));
				$logsParaHoje = 0;
				$tentativas = 0;
			}
			$dataEnvio = gerarProximaDataHoraDisponivel($dataInicio, $horaInicio, $horaFim);
			if (empty($datas) || abs(strtotime(end($datas)) - strtotime($dataEnvio)) >= $intervaloMinimo * 60) {
				$datas[] = $dataEnvio;
				$logsParaHoje++;
				$tentativas = 0;
			}
			else {
				$tentativas++;
			}
		}
		shuffle($datas);
		foreach ($logsParaAjustar as $key => $row) {
			$id = $row["id"];
			$dataEnvio = array_pop($datas);
			$sqlUpdate = "UPDATE logs_envio SET hora = :dataEnvio WHERE id = :id";
			$stmtUpdate = $conn->prepare($sqlUpdate);
			$stmtUpdate->bindParam(":dataEnvio", $dataEnvio);
			$stmtUpdate->bindParam(":id", $id);
			$stmtUpdate->execute();
		}
		return json_encode(["success" => true, "message" => "Datas de envio atualizadas com sucesso!", ]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao atualizar as datas de envio: " . $e->getMessage() , ]);
	}
}
function buscarClientesSemLogs($conn) {
	try {
		$sql = "SELECT c.nome, c.cnpj, c.contato FROM clientes c LEFT JOIN logs_envio l ON c.cnpj = l.cnpj WHERE l.cnpj IS NULL";
		$stmt = $conn->query($sql);
		$clientesSemLogs = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$clientesSemLogs[] = $row;
		}
		return json_encode(["clientesSemLogs" => $clientesSemLogs]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao buscar os clientes sem logs: " . $e->getMessage() , ]);
	}
}
function consultarStatusMensagens($conn) {
	$sqlStatus = "SELECT\n                    SUM(CASE WHEN enviado = 0 AND hora < NOW() THEN 1 ELSE 0 END) AS rebobinar_count,\n                    SUM(CASE WHEN enviado = 0 AND hora > NOW() THEN 1 ELSE 0 END) AS enviar_count,\n                    SUM(CASE WHEN enviado = 0 THEN 1 ELSE 0 END) AS total_idle_logs,\n                    SUM(CASE WHEN enviado = 1 THEN 1 ELSE 0 END) AS enviado_count,\n                    MIN(CASE WHEN enviado = 0 AND hora > NOW() THEN hora END) AS proximo_envio\n                 FROM logs_envio";
	$stmt = $conn->query($sqlStatus);
	return $stmt->fetch(PDO::FETCH_ASSOC);
}
function verificarStatus($conn) {
	try {
		$status = [];
		$status[] = formatarDataHora(json_decode(getDataServidor($conn))->message);
		$statusMensagens = consultarStatusMensagens($conn);
		if ($statusMensagens["rebobinar_count"] > 0) {
			$status[] = "Banco de Logs precisa ser rebobinado";
			$status[] = "Quantidade de logs a serem rebobinados: " . $statusMensagens["rebobinar_count"];
		}
		if ($statusMensagens["enviar_count"] > 0) {
			$status[] = "Banco de Logs possui mensagens na fila de envio e pode ser avançado";
			$status[] = "Quantidade de clientes enviando mensagens: " . $statusMensagens["enviar_count"];
			$agora = new DateTime();
			$proximoEnvio = new DateTime($statusMensagens["proximo_envio"]);
			$diferenca = $agora->diff($proximoEnvio);
			$status[] = "Tempo restante para a próxima mensagem ser enviada: " . $diferenca->format("%H:%I:%S");
		}
		if ($statusMensagens["total_idle_logs"] == 0) {
			$status[] = "Banco de Logs está ocioso";
		}
		if ($statusMensagens["enviado_count"] > 0) {
			$status[] = "Mensagens enviadas com sucesso: " . $statusMensagens["enviado_count"];
		}
		$sqlClientesEmEspera = "SELECT COUNT(*) AS total\n                                FROM clientes c\n                                WHERE NOT EXISTS (\n                                    SELECT 1 FROM logs_envio l WHERE l.cnpj = c.cnpj\n                                )";
		$stmtClientesEmEspera = $conn->query($sqlClientesEmEspera);
		$totalClientesEmEspera = $stmtClientesEmEspera->fetch(PDO::FETCH_ASSOC) ["total"];
		if ($totalClientesEmEspera > 0) {
			$status[] = "Quantidade de novos clientes aguardando: " . $totalClientesEmEspera;
		}
		return json_encode(["status" => $status]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao verificar o status do banco de dados: " . $e->getMessage() , ]);
	}
}
function consultarWhatsApp($numero) {
	$data = ["tel" => $numero];
	$jsonData = json_encode($data);
	$ch = curl_init();
	$url = "http://localhost/zap/WhatsAppApi.php";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	$responseArray = json_decode($response, true);
	if (isset($responseArray["success"]) && $responseArray["success"] === true) {
		return true;
	}
	else {
		return false;
	}
}
function adicionarCliente($conn) {
	try {
		$stmt = $conn->query("SELECT * FROM clientes");
		$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$clientesAdicionados = 0;
		foreach ($clientes as $cliente) {
			$nome = $cliente["nome"];
			$cnpj = $cliente["cnpj"];
			$numero = $cliente["contato"];
			$numero = formatarNumero($numero);
			$stmt = $conn->prepare("SELECT * FROM logs_envio WHERE cnpj = :cnpj");
			$stmt->execute(["cnpj" => $cnpj]);
			$clienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$clienteExistente) {
				$hasWhatsApp = consultarWhatsApp($numero);
				if ($hasWhatsApp) {
					$stmt = $conn->prepare("INSERT INTO logs_envio (nome, cnpj, numero, hora, enviado) VALUES (:nome, :cnpj, :numero, '1970-01-01 00:00:00', 0)");
					$stmt->bindParam(":nome", $nome);
					$stmt->bindParam(":cnpj", $cnpj);
					$stmt->bindParam(":numero", $numero);
					$stmt->execute();
					$clientesAdicionados++;
				}
			}
		}
		return json_encode(["success" => true, "message" => "Foram adicionados $clientesAdicionados clientes com sucesso.", ]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao adicionar cliente: " . $e->getMessage() , ]);
	}
}
function formatarNumero($num) {
	$num = preg_replace("/[^0-9]/", "", $num);
	if (substr($num, 0, 4) == "0800") {
		return $num;
	}
	if (substr($num, 0, 1) == "0") {
		$num = substr($num, 1);
	}
	$oitoDigito = substr($num, -8, 1);
	if ($oitoDigito == "2" || $oitoDigito == "3") {
		return $num;
	}
	else {
		if (strlen($num) < 11) {
			$num = substr($num, 0, 2) . "9" . substr($num, 2);
		}
	}
	return $num;
}
function getServicos($conn) {
	$query = "SELECT id, descricao FROM Servico";
	$statement = $conn->query($query);
	if ($statement) {
		$servicos = $statement->fetchAll(PDO::FETCH_ASSOC);
		return json_encode(["success" => true, "message" => $servicos]);
	}
	else {
		return json_encode(["success" => false, "message" => "Erro ao buscar os serviços.", ]);
	}
}
function getMensagemAleatoriaServico($conn, $idServico) {
	$sql = "SELECT id FROM Mensagem m\n            INNER JOIN MensagemServico ms ON m.id = ms.id_mensagem\n            WHERE ms.id_servico = :idServico\n            ORDER BY RAND()\n            LIMIT 1";
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(":idServico", $idServico, PDO::PARAM_INT);
	$stmt->execute();
	return $stmt->fetch(PDO::FETCH_ASSOC) ["id"];
}
function atacar($conn) {
	$servicos = $_POST["servicos"];
	if (empty($servicos)) {
		return json_encode(["success" => false, "message" => "Nenhum serviço foi selecionado para o ataque.", ]);
	}
	$statusMensagens = consultarStatusMensagens($conn);
	if ($statusMensagens["enviar_count"] == 0) {
		return json_encode(["success" => false, "message" => "Não há mensagens a serem enviadas no momento.", ]);
	}
	$stmtAtualizarMensagens = $conn->prepare("UPDATE logs_envio SET id_mensagem = :id_mensagem WHERE id = :id_log;");
	$stmtSelecionarLogs = $conn->prepare("SELECT id FROM logs_envio WHERE (id_mensagem IS NULL OR id_mensagem = '' OR id_mensagem = '0');");
	$stmtSelecionarLogs->execute();
	$atualizacoesRealizadas = 0;
	while ($idLog = $stmtSelecionarLogs->fetchColumn()) {
		$idServicoAleatorio = $servicos[array_rand($servicos) ];
		$idMensagem = getMensagemAleatoriaServico($conn, $idServicoAleatorio);
		$stmtAtualizarMensagens->bindValue(":id_mensagem", $idMensagem, PDO::PARAM_INT);
		$stmtAtualizarMensagens->bindValue(":id_log", $idLog, PDO::PARAM_INT);
		if ($stmtAtualizarMensagens->execute()) {
			$atualizacoesRealizadas++;
		}
	}
	return json_encode(["success" => $atualizacoesRealizadas > 0, "message" => $atualizacoesRealizadas > 0 ? "Foram atualizadas $atualizacoesRealizadas mensagens em logs_envio." : "Não há mensagens para atualizar em logs_envio.", ]);
}
function buscarLogs($conn) {
	try {
		$sql = "SELECT * FROM logs_envio ORDER BY enviado ASC, ABS(TIMESTAMPDIFF(SECOND, hora, NOW())) ASC";
		$stmt = $conn->query($sql);
		$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return json_encode(["logs" => $logs]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao buscar os logs: " . $e->getMessage() , ]);
	}
}
function bloqueioEstaTravadoParaEsteUsuario($conn, $nomeDaFuncao) {
	$sql = "SELECT COUNT(*) AS count FROM bloqueios WHERE nome_funcao = :nome_funcao";
	$stmt = $conn->prepare($sql);
	$stmt->bindValue(':nome_funcao', $nomeDaFuncao);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row['count'] > 0;
}
function travarBloqueioParaEsteUsuario($conn, $nomeDaFuncao) {
	$sql = "INSERT INTO bloqueios (nome_funcao) VALUES (:nome_funcao)";
	$stmt = $conn->prepare($sql);
	$stmt->bindValue(':nome_funcao', $nomeDaFuncao);
	$stmt->execute();
}
function liberarBloqueioParaEsteUsuario($conn, $nomeDaFuncao) {
	$sql = "DELETE FROM bloqueios WHERE nome_funcao = :nome_funcao";
	$stmt = $conn->prepare($sql);
	$stmt->bindValue(':nome_funcao', $nomeDaFuncao);
	$stmt->execute();
}
try {
	$conn = conectarBancoDados($host, $dbName, $username, $password);
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
		if ($_POST["action"] === "add_clientes") {
			if (bloqueioEstaTravadoParaEsteUsuario($conn, 'adicionarCliente')) {
				echo json_encode(["success" => false, "message" => "Função já está em execução, por favor, espere até que a tarefa atual seja concluída."]);
			}
			else {
				travarBloqueioParaEsteUsuario($conn, 'adicionarCliente');
				echo adicionarCliente($conn);
				liberarBloqueioParaEsteUsuario($conn, 'adicionarCliente');
			}
		}
		elseif ($_POST["action"] === "atacar") {
			if (bloqueioEstaTravadoParaEsteUsuario($conn, 'atacar')) {
				echo json_encode(["success" => false, "message" => "Função já está em execução, por favor, espere até que a tarefa atual seja concluída."]);
			}
			else {
				travarBloqueioParaEsteUsuario($conn, 'atacar');
				echo atacar($conn);
				liberarBloqueioParaEsteUsuario($conn, 'atacar');
			}
		}
		elseif ($_POST["action"] === "rebobinar" || $_POST["action"] === "avancar") {
			if (bloqueioEstaTravadoParaEsteUsuario($conn, 'ajustarDataEnvio')) {
				echo json_encode(["success" => false, "message" => "Função já está em execução, por favor, espere até que a tarefa atual seja concluída."]);
			}
			else {
				travarBloqueioParaEsteUsuario($conn, 'ajustarDataEnvio');
				$horaInicio = $_POST["hora_inicio"];
				$horaFim = $_POST["hora_fim"];
				$intervaloMinimo = $_POST["intervalo_minimo"];
				$quantidadePorDia = $_POST["quantidade_por_dia"];
				if ($_POST["action"] === "rebobinar") {
					echo ajustarDataEnvio($conn, $horaInicio, $horaFim, $intervaloMinimo, "rebobinar", $quantidadePorDia);
				}
				elseif ($_POST["action"] === "avancar") {
					echo ajustarDataEnvio($conn, $horaInicio, $horaFim, $intervaloMinimo, "avancar", $quantidadePorDia);
				}
				liberarBloqueioParaEsteUsuario($conn, 'ajustarDataEnvio');
			}
		}
		elseif ($_POST["action"] === "atualizar") {
			$id = $_POST["id"];
			$nome = $_POST["nome"];
			$cnpj = $_POST["cnpj"];
			$numero = $_POST["numero"];
			$mensagem = $_POST["mensagem"];
			$enviado = $_POST["enviado"];
			echo atualizarRegistro($conn, $id, $nome, $cnpj, $numero, $mensagem, $enviado);
		}
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"])) {
		if ($_GET["action"] === "servicos") {
			echo getServicos($conn);
		}
		elseif ($_GET["action"] === "clientes-sem-logs") {
			echo buscarClientesSemLogs($conn);
		}
		elseif ($_GET["action"] === "status") {
			echo verificarStatus($conn);
		}
		elseif ($_GET["action"] === "data_servidor") {
			echo getDataServidor($conn);
		}
		elseif ($_GET["action"] === "logs") {
			echo buscarLogs($conn);
		}
	}
}
catch(Exception $e) {
	liberarBloqueioParaEsteUsuario($conn, 'ajustarDataEnvio');
	liberarBloqueioParaEsteUsuario($conn, 'adicionarCliente');
	liberarBloqueioParaEsteUsuario($conn, 'atacar');
	return json_encode(["success" => false, "message" => $e->getMessage() ]);
}

