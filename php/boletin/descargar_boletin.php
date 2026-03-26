<?php
include '../conesion.php';
include '../config.php';
include './helpers.php';
session_start();
exigir_inicio_sesion();

if (!boletin_modulo_disponible($con)) {
    http_response_code(500);
    exit('Modulo de boletin no disponible.');
}

$id_usuario = (int)($_SESSION['id_persona'] ?? 0);
$tipos = obtener_tipos_usuario($con, $id_usuario);

$id_periodo = (int)($_GET['id_periodo'] ?? 0);
$id_curso = (int)($_GET['id_curso'] ?? 0);
$id_alumno = (int)($_GET['id_alumno'] ?? 0);
$version_req = (int)($_GET['version'] ?? 0);

if ($id_periodo <= 0 || $id_curso <= 0 || $id_alumno <= 0) {
    http_response_code(400);
    exit('Parametros invalidos.');
}

$estado = boletin_obtener_curso_periodo($con, $id_curso, $id_periodo);
if (!$estado || (string)$estado['estado'] !== 'publicado') {
    http_response_code(403);
    exit('El boletin aun no esta publicado para ese curso/periodo.');
}

if (!boletin_usuario_puede_descargar($con, $id_usuario, $tipos, $id_curso, $id_alumno)) {
    http_response_code(403);
    exit('No tenes permisos para descargar ese boletin.');
}

$pdf = null;
if ($version_req > 0) {
    $pdf = db_fetch_one(
        $con,
        "SELECT id_boletin_pdf, id_periodo, id_curso, id_alumno, version, ruta_pdf, hash_sha256, generado_en
         FROM boletin_pdf_historial
         WHERE id_periodo = ? AND id_curso = ? AND id_alumno = ? AND version = ?
         LIMIT 1",
        'iiii',
        [$id_periodo, $id_curso, $id_alumno, $version_req]
    );
    if (!$pdf) {
        http_response_code(404);
        exit('No existe esa version para el boletin solicitado.');
    }
}
if (!$pdf) {
    $pdf = boletin_ultimo_pdf_alumno($con, $id_periodo, $id_curso, $id_alumno);
}
if (!$pdf) {
    $version = max(1, (int)($estado['version_publicada'] ?? 1));
    $generado = boletin_generar_pdf_alumno($con, $id_periodo, $id_curso, $id_alumno, $version, $id_usuario);
    if (!$generado) {
        http_response_code(500);
        exit('No se pudo generar el PDF.');
    }
    $pdf = boletin_ultimo_pdf_alumno($con, $id_periodo, $id_curso, $id_alumno);
}

if (!$pdf) {
    http_response_code(500);
    exit('No se encontro el PDF solicitado.');
}

$periodo_ref = boletin_periodo_por_id($con, $id_periodo);
$anio_ciclo = (int)($periodo_ref['anio'] ?? 0);

$ruta_rel = ltrim((string)$pdf['ruta_pdf'], '/');
if (
    !str_starts_with($ruta_rel, 'boletines_archivo/')
    || strpos($ruta_rel, '..') !== false
    || strpos($ruta_rel, "\0") !== false
) {
    http_response_code(500);
    exit('Ruta de archivo invalida.');
}

$base_archivo = realpath(dirname(__DIR__, 2) . '/boletines_archivo');
$ruta_abs = realpath(dirname(__DIR__, 2) . '/' . $ruta_rel);
if (
    !$base_archivo
    || !$ruta_abs
    || !is_file($ruta_abs)
    || !str_starts_with($ruta_abs, $base_archivo . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);
    exit('Archivo no encontrado en servidor.');
}

$nombre_descarga = 'boletin_anual_ciclo_' . ($anio_ciclo > 0 ? $anio_ciclo : 's_ciclo')
    . '_curso_' . $id_curso
    . '_alumno_' . $id_alumno
    . '_corte_p' . $id_periodo
    . '_v' . (int)$pdf['version'] . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombre_descarga . '"');
header('Content-Length: ' . filesize($ruta_abs));
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($ruta_abs);
exit;
