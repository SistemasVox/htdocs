<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once 'buscar_logs.php';

/* $horaInicio = '08:00:00';
$horaFim = '17:00:00';

$resultado = gerarProximaDataHoraDisponivel($horaInicio, $horaFim);

echo $resultado; */


echo formatarNumero('34991509519');
echo var_dump(consultarWhatsApp('34991509519'));