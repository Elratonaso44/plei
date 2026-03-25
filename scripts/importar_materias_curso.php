#!/usr/bin/env php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script se ejecuta solo por terminal.\n");
    exit(1);
}

$opciones = getopt('', ['id_curso:', 'input:', 'dry-run']);
$idCurso = isset($opciones['id_curso']) ? (int)$opciones['id_curso'] : 0;
$input = isset($opciones['input']) ? (string)$opciones['input'] : '';
$dryRun = array_key_exists('dry-run', $opciones);

if ($idCurso <= 0 || $input === '') {
    fwrite(STDERR, "Uso: php scripts/importar_materias_curso.php --id_curso=8 --input=scripts/data/materias_6a_electromecanica.txt [--dry-run]\n");
    exit(1);
}

if (!is_file($input) || !is_readable($input)) {
    fwrite(STDERR, "No se puede leer el archivo de entrada: {$input}\n");
    exit(1);
}

require __DIR__ . '/../php/conesion.php';
require __DIR__ . '/../php/config.php';

function normalizar_espacios(string $texto): string
{
    $texto = trim($texto);
    return preg_replace('/\s+/u', ' ', $texto) ?? '';
}

function quitar_acentos(string $texto): string
{
    $resultado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    return $resultado !== false ? $resultado : $texto;
}

function clave_materia_normalizada(string $materia): string
{
    $materia = normalizar_espacios($materia);
    $materia = quitar_acentos($materia);
    $materia = mb_strtolower($materia, 'UTF-8');
    return normalizar_espacios($materia);
}

$curso = db_fetch_one(
    $con,
    "SELECT c.id_curso, c.grado, s.seccion, m.moda
     FROM cursos AS c
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
     WHERE c.id_curso = ?
     LIMIT 1",
    "i",
    [$idCurso]
);

if (!$curso) {
    fwrite(STDERR, "El curso {$idCurso} no existe.\n");
    exit(1);
}

$lineas = file($input, FILE_IGNORE_NEW_LINES);
if ($lineas === false) {
    fwrite(STDERR, "No se pudo leer el archivo de entrada.\n");
    exit(1);
}

$existentes = db_fetch_all(
    $con,
    "SELECT nombre_materia FROM materias WHERE id_curso = ?",
    "i",
    [$idCurso]
);

$clavesExistentes = [];
foreach ($existentes as $fila) {
    $clave = clave_materia_normalizada((string)($fila['nombre_materia'] ?? ''));
    if ($clave !== '') {
        $clavesExistentes[$clave] = true;
    }
}

$totales = [
    'leidas' => 0,
    'insertadas' => 0,
    'duplicadas' => 0,
    'invalidas' => 0,
];

$detalles = [
    'insertadas' => [],
    'duplicadas' => [],
    'invalidas' => [],
];

$stmtInsert = mysqli_prepare(
    $con,
    "INSERT INTO materias (nombre_materia, turno, grupo, id_curso)
     VALUES (?, 'N/A', 1, ?)"
);
if (!$stmtInsert) {
    fwrite(STDERR, "No se pudo preparar el INSERT de materias.\n");
    exit(1);
}

foreach ($lineas as $numeroLinea => $lineaCruda) {
    $linea = normalizar_espacios((string)$lineaCruda);
    if ($linea === '' || str_starts_with($linea, '#')) {
        continue;
    }

    $totales['leidas']++;
    $nombreMateria = $linea;
    $clave = clave_materia_normalizada($nombreMateria);

    if ($clave === '') {
        $totales['invalidas']++;
        $detalles['invalidas'][] = "Línea " . ($numeroLinea + 1) . ": vacía tras normalización";
        continue;
    }

    if (mb_strlen($nombreMateria, 'UTF-8') > 50) {
        $totales['invalidas']++;
        $detalles['invalidas'][] = "Línea " . ($numeroLinea + 1) . ": excede 50 caracteres ({$nombreMateria})";
        continue;
    }

    if (isset($clavesExistentes[$clave])) {
        $totales['duplicadas']++;
        $detalles['duplicadas'][] = $nombreMateria;
        continue;
    }

    if (!$dryRun) {
        mysqli_stmt_bind_param($stmtInsert, "si", $nombreMateria, $idCurso);
        if (!mysqli_stmt_execute($stmtInsert)) {
            $totales['invalidas']++;
            $detalles['invalidas'][] = "Línea " . ($numeroLinea + 1) . ": error SQL al insertar ({$nombreMateria})";
            continue;
        }
    }

    $clavesExistentes[$clave] = true;
    $totales['insertadas']++;
    $detalles['insertadas'][] = $nombreMateria;
}

mysqli_stmt_close($stmtInsert);

echo "========================================\n";
echo "Importación de materias por curso\n";
echo "Curso: {$curso['grado']}° {$curso['seccion']} - {$curso['moda']} (id_curso={$idCurso})\n";
echo "Modo: " . ($dryRun ? "DRY-RUN (sin inserciones)" : "REAL (inserta datos)") . "\n";
echo "Archivo: {$input}\n";
echo "========================================\n";
echo "Leídas: {$totales['leidas']}\n";
echo "Insertadas: {$totales['insertadas']}\n";
echo "Duplicadas: {$totales['duplicadas']}\n";
echo "Inválidas: {$totales['invalidas']}\n";

if ($detalles['insertadas'] !== []) {
    echo "\nMaterias insertadas:\n";
    foreach ($detalles['insertadas'] as $materia) {
        echo " - {$materia}\n";
    }
}

if ($detalles['duplicadas'] !== []) {
    echo "\nMaterias duplicadas (omitidas):\n";
    foreach ($detalles['duplicadas'] as $materia) {
        echo " - {$materia}\n";
    }
}

if ($detalles['invalidas'] !== []) {
    echo "\nFilas inválidas:\n";
    foreach ($detalles['invalidas'] as $detalle) {
        echo " - {$detalle}\n";
    }
}

echo "\nProceso finalizado.\n";
