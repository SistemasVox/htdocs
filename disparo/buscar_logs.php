<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
set_time_limit(0);
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
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao conectar ao banco de dados: " . $e->getMessage() , ]);
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
function gerarProximaDataHoraDisponivel($dataInicio, $horaInicio, $horaFim) {
	$dataAtual = new DateTime($dataInicio);
	$diaDaSemana = $dataAtual->format("N");
	$horaAtual = $dataAtual->format("H:i");
	// Verifica se ainda há tempo disponível no dia atual
	if ($diaDaSemana >= 1 && $diaDaSemana <= 5 && (($horaAtual < $horaFim && $horaAtual > $horaInicio) || ($horaInicio == $horaFim && $horaAtual == $horaInicio))) {
		// Converter o horário de início e fim em timestamps
		$horaInicioTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaAtual);
		$horaFimTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaFim);
	}
	else {
		// Encontrar o próximo dia útil
		do {
			$dataAtual->add(new DateInterval("P1D")); // Adicionar um dia
			$diaDaSemana = $dataAtual->format("N");
		} while ($diaDaSemana == 6 || $diaDaSemana == 7); // Continuar adicionando dias até que seja um dia útil
		// Converter o horário de início e fim em timestamps
		$horaInicioTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaInicio);
		$horaFimTimestamp = strtotime($dataAtual->format("Y-m-d") . " " . $horaFim);
	}
	// Gerar um timestamp aleatório dentro do intervalo
	$timestampAleatorio = mt_rand($horaInicioTimestamp, $horaFimTimestamp);
	// Converter o timestamp aleatório em uma data e hora formatada
	$proximaDataHoraDisponivel = new DateTime("@" . $timestampAleatorio);
	$proximaDataHoraDisponivel->setTimezone($dataAtual->getTimezone()); // Ajustar o fuso horário
	return $proximaDataHoraDisponivel->format("Y-m-d H:i:s");
}
function ajustarDataEnvio($conn, $horaInicio, $horaFim, $intervaloMinimo, $acao) {
	try {
		$sql = $acao === "rebobinar" ? "SELECT * FROM logs_envio WHERE enviado = 0 AND hora <= NOW()" : "SELECT * FROM logs_envio WHERE enviado = 0 AND hora > NOW()";
		$stmt = $conn->query($sql);
		$logsParaAjustar = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$numLogsParaAjustar = count($logsParaAjustar);
		if ($numLogsParaAjustar == 0) {
			$message = $acao === "rebobinar" ? "Não foi necessário rebobinar, nenhum log atrasado encontrado." : "Não foi necessário avançar, nenhum log encontrado.";
			return json_encode(["success" => true, "message" => $message]);
		}
		// Gera todas as datas possíveis
		$dataAtual = new DateTime();
		$dataInicio = $dataAtual->format("Y-m-d");
		$datas = [];
		$tentativas = 0;
		while (count($datas) < $numLogsParaAjustar) {
			$dataEnvio = gerarProximaDataHoraDisponivel($dataInicio, $horaInicio, $horaFim);
			if (empty($datas) || abs(strtotime(end($datas)) - strtotime($dataEnvio)) >= $intervaloMinimo) {
				$datas[] = $dataEnvio;
			}
			else {
				$tentativas++;
			}
			if ($tentativas >= 100) {
				$dataInicio = date("Y-m-d", strtotime($dataInicio . " +1 day"));
				$tentativas = 0;
			}
		}
		// Embaralha as datas para distribuí-las aleatoriamente
		shuffle($datas);
		// Loop para atualizar os logs
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

function consultarStatusMensagens($conn) {
	$sqlStatus = "SELECT
                    SUM(CASE WHEN enviado = 0 AND hora < NOW() THEN 1 ELSE 0 END) AS rebobinar_count,
                    SUM(CASE WHEN enviado = 0 AND hora > NOW() THEN 1 ELSE 0 END) AS enviar_count,
                    SUM(CASE WHEN enviado = 0 THEN 1 ELSE 0 END) AS total_idle_logs,
                    SUM(CASE WHEN enviado = 1 THEN 1 ELSE 0 END) AS enviado_count,
                    MIN(CASE WHEN enviado = 0 AND hora > NOW() THEN hora END) AS proximo_envio
                 FROM logs_envio";

	$stmt = $conn->query($sqlStatus);
	return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verificarStatus($conn) {
	try {
		$status = [];
		$status[] = formatarDataHora(json_decode(getDataServidor($conn))->message);

		// Consultar o status das mensagens diretamente no banco de dados
		$statusMensagens = consultarStatusMensagens($conn);

		if ($statusMensagens["rebobinar_count"] > 0) {
			$status[] = "Banco de Logs precisa ser rebobinado";
			$status[] = "Quantidade de logs a serem rebobinados: " . $statusMensagens["rebobinar_count"];
		}

		if ($statusMensagens["enviar_count"] > 0) {
			$status[] = "Banco de Logs possui mensagens na fila de envio e pode ser avançado";
			$status[] = "Quantidade de clientes enviando mensagens: " . $statusMensagens["enviar_count"];
			// Calcular quanto tempo falta para a próxima mensagem ser enviada
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

		// Consulta para obter a quantidade de novos Logs aguardando
		$sqlClientesEmEspera = "SELECT COUNT(*) AS total
                                FROM clientes c
                                WHERE NOT EXISTS (
                                    SELECT 1 FROM logs_envio l WHERE l.cnpj = c.cnpj
                                )";
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
	// Montar os dados para a requisição POST
	$data = ["tel" => $numero];
	// Converter o array em uma string JSON
	$jsonData = json_encode($data);
	// Iniciar o cURL
	$ch = curl_init();
	// Definir a URL do endpoint da API
	$url = "http://localhost/zap/WhatsAppApi.php";
	curl_setopt($ch, CURLOPT_URL, $url);
	// Definir que queremos enviar uma requisição POST
	curl_setopt($ch, CURLOPT_POST, true);
	// Definir os dados a serem enviados na requisição POST como JSON
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
	// Definir o cabeçalho Content-Type para indicar que estamos enviando JSON
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
	// Definir que queremos o retorno da requisição
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Executar a requisição e armazenar a resposta
	$response = curl_exec($ch);
	// Fechar a requisição
	curl_close($ch);
	// Decodificar a resposta JSON como um array associativo
	$responseArray = json_decode($response, true);
	// Verificar a resposta
	if (isset($responseArray["success"]) && $responseArray["success"] === true) {
		return true;
	}
	else {
		return false;
	}
}
function adicionarCliente($conn) {
	try {
		// Recupere todos os clientes
		$stmt = $conn->query("SELECT * FROM clientes");
		$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$clientesAdicionados = 0;
		foreach ($clientes as $cliente) {
			$nome = $cliente["nome"];
			$cnpj = $cliente["cnpj"];
			$numero = $cliente["contato"];
			$numero = formatarNumero($numero);
			// Verifique se o cliente já existe na tabela logs_envio
			$stmt = $conn->prepare("SELECT * FROM logs_envio WHERE cnpj = :cnpj");
			$stmt->execute(["cnpj" => $cnpj]);
			$clienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$clienteExistente) {
				// Consultar se o cliente tem WhatsApp
				$hasWhatsApp = consultarWhatsApp($numero);
				// Verificar se o cliente tem WhatsApp
				if ($hasWhatsApp) {
					// Se o cliente tem WhatsApp, adicione-o na tabela logs_envio
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
	// Remove qualquer coisa que não seja um número
	$num = preg_replace("/[^0-9]/", "", $num);
	// Se o número é 0800, apenas retorna o número
	if (substr($num, 0, 4) == "0800") {
		return $num;
	}
	// Remover o prefixo "0" do DDD se ele existir
	if (substr($num, 0, 1) == "0") {
		$num = substr($num, 1);
	}
	// Pegar o oitavo dígito da direita para a esquerda
	$oitoDigito = substr($num, -8, 1);
	// Se o oitavo dígito for 2 ou 3, é um número de telefone fixo
	if ($oitoDigito == "2" || $oitoDigito == "3") {
		return $num;
	}
	// Se não for, é um número de celular e precisa ter 9 dígitos após o DDD
	else {
		// Se o número tem menos de 11 dígitos (contando o DDD), adicionamos o prefixo "9"
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
	$sql = "SELECT id FROM Mensagem m
            INNER JOIN MensagemServico ms ON m.id = ms.id_mensagem
            WHERE ms.id_servico = :idServico
            ORDER BY RAND()
            LIMIT 1";

	$stmt = $conn->prepare($sql);
	$stmt->bindParam(":idServico", $idServico, PDO::PARAM_INT);
	$stmt->execute();

	return $stmt->fetch(PDO::FETCH_ASSOC) ["id"];
}

function atacar($conn) {
	$servicos = $_POST["servicos"] ?? [];

	if (empty($servicos)) {
		return json_encode(["success" => false, "message" => "Nenhum serviço foi selecionado para o ataque.", ]);
	}

	// Consultar o status das mensagens diretamente no banco de dados
	$statusMensagens = consultarStatusMensagens($conn);
	if ($statusMensagens["enviar_count"] == 0) {
		return json_encode(["success" => false, "message" => "Não há mensagens a serem enviadas no momento.", ]);
	}

	// Preparar declarações de banco de dados
	$stmtAtualizarMensagens = $conn->prepare("UPDATE logs_envio SET id_mensagem = :id_mensagem WHERE id = :id_log;");
	$stmtSelecionarLogs = $conn->prepare("SELECT id FROM logs_envio WHERE (id_mensagem IS NULL OR id_mensagem = '' OR id_mensagem = '0');");
	$stmtSelecionarLogs->execute();

	$atualizacoesRealizadas = 0;
	while ($idLog = $stmtSelecionarLogs->fetchColumn()) {
		// Obter o ID da mensagem aleatória para associar ao log
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
		// Consulta para buscar os logs ordenados pela data mais próxima da hora atual
		$sql = "SELECT * FROM logs_envio ORDER BY enviado ASC, ABS(TIMESTAMPDIFF(SECOND, hora, NOW())) ASC";
		$stmt = $conn->query($sql);
		$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return json_encode(["logs" => $logs]);
	}
	catch(PDOException $e) {
		return json_encode(["success" => false, "message" => "Erro ao buscar os logs: " . $e->getMessage() , ]);
	}
}

try {
	$conn = conectarBancoDados($host, $dbName, $username, $password);
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add_clientes") {
		// Lógica para adicionar clientes
		echo adicionarCliente($conn);
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "atacar") {
		// Obtenha os serviços do banco de dados
		echo atacar($conn);
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] === "servicos") {
		// Obtenha os serviços do banco de dados
		echo getServicos($conn);
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
		// Obtenha os dados do POST
		$horaInicio = $_POST["hora_inicio"];
		$horaFim = $_POST["hora_fim"];
		$intervaloMinimo = $_POST["intervalo_minimo"];
		if ($_POST["action"] === "rebobinar") {
			// Rebobine a data de envio dos logs
			echo ajustarDataEnvio($conn, $horaInicio, $horaFim, $intervaloMinimo, "rebobinar");
		}
		elseif ($_POST["action"] === "avancar") {
			// Avance a data de envio dos logs
			echo ajustarDataEnvio($conn, $horaInicio, $horaFim, $intervaloMinimo, "avançar");
		}
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] === "clientes-sem-logs") {
		// Busque os clientes que não possuem logs
		echo buscarClientesSemLogs($conn);
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] === "status") {
		// Verifique o status do banco de dados
		echo verificarStatus($conn);
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] === "data_servidor") {
		// Obtenha a data do servidor do banco de dados
		echo getDataServidor($conn);
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] === "logs") {
		// Consulta para buscar os logs ordenados pela data mais próxima da hora atual
		echo buscarLogs($conn);
	}
	elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "atualizar") {
		// Obtenha os dados do POST
		$id = $_POST["id"];
		$nome = $_POST["nome"];
		$cnpj = $_POST["cnpj"];
		$numero = $_POST["numero"];
		$mensagem = $_POST["mensagem"];
		$enviado = $_POST["enviado"];
		// Atualize o registro na tabela de logs
		echo atualizarRegistro($conn, $id, $nome, $cnpj, $numero, $mensagem, $enviado);
	}
}
catch(Exception $e) {
	return json_encode(["success" => false, "message" => $e->getMessage() ]);
}