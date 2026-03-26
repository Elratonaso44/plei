<?php

$app_env = strtolower(trim((string)(getenv('APP_ENV') ?: 'development')));

$db_host = getenv('PLEI_DB_HOST');
$db_name = getenv('PLEI_DB_NAME');
$db_user = getenv('PLEI_DB_USER');
$db_pass = getenv('PLEI_DB_PASS');

if ($app_env !== 'production') {
    if ($db_host === false || $db_host === '') {
        $db_host = 'localhost';
    }
    if ($db_name === false || $db_name === '') {
        $db_name = 'plei_db';
    }
    if ($db_user === false || $db_user === '') {
        $db_user = 'root';
    }
    if ($db_pass === false) {
        $db_pass = '';
    }
} else {
    $faltantes = [];
    if ($db_host === false || $db_host === '') {
        $faltantes[] = 'PLEI_DB_HOST';
    }
    if ($db_name === false || $db_name === '') {
        $faltantes[] = 'PLEI_DB_NAME';
    }
    if ($db_user === false || $db_user === '') {
        $faltantes[] = 'PLEI_DB_USER';
    }
    if ($db_pass === false || $db_pass === '') {
        $faltantes[] = 'PLEI_DB_PASS';
    }
    if ($faltantes !== []) {
        error_log('PLEI DB Config Error (production): faltan variables ' . implode(', ', $faltantes));
        http_response_code(500);
        die('Configuracion de base de datos incompleta para produccion.');
    }
    if (strtolower(trim((string)$db_user)) === 'root') {
        error_log('PLEI DB Config Error (production): usuario root no permitido.');
        http_response_code(500);
        die('Configuracion de base de datos no segura para produccion.');
    }
}

$con = mysqli_connect((string)$db_host, (string)$db_user, (string)$db_pass, (string)$db_name);
if (mysqli_connect_errno()) {
    error_log('PLEI DB Error: ' . mysqli_connect_error());
    http_response_code(503);
    die('El servicio no esta disponible en este momento. Intenta mas tarde.');
}
mysqli_set_charset($con, 'utf8mb4');
