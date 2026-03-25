<?php
include './php/conesion.php';
include './php/config.php';
include './php/material_url.php';
session_start();
exigir_inicio_sesion();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit('Metodo no permitido.');
}

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$token_param = trim((string)($_GET['t'] ?? ''));
$id_material = 0;
$ruta_param = '';
$forzar_descarga = false;

if ($token_param !== '') {
    $token = material_token_validar($token_param, $id_persona);
    if (!$token) {
        http_response_code(403);
        exit('Token inválido o vencido.');
    }
    $id_material = (int)($token['id_material'] ?? 0);
    $forzar_descarga = (($token['accion'] ?? 'ver') === 'descargar');
} else {
    $id_material = (int)($_GET['id'] ?? 0);
    $ruta_param = ltrim(trim((string)($_GET['f'] ?? '')), '/');
    $forzar_descarga = (($_GET['descargar'] ?? '') === '1');
    if ($id_material <= 0 && $ruta_param === '') {
        http_response_code(400);
        exit('Solicitud incompleta.');
    }
}

$material = null;
if ($id_material > 0) {
    $material = db_fetch_one(
        $con,
        "SELECT mat.id_material, mat.id_materia, mat.url, m.id_curso
         FROM materiales AS mat
         INNER JOIN materias AS m ON m.id_materia = mat.id_materia
         WHERE mat.id_material = ?
         LIMIT 1",
        'i',
        [$id_material]
    );
} else {
    if (!es_ruta_local_material_valida($ruta_param)) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
    $material = db_fetch_one(
        $con,
        "SELECT mat.id_material, mat.id_materia, mat.url, m.id_curso
         FROM materiales AS mat
         INNER JOIN materias AS m ON m.id_materia = mat.id_materia
         WHERE mat.url = ?
         LIMIT 1",
        's',
        [$ruta_param]
    );
}

if (!$material) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$ruta_material = ltrim(trim((string)$material['url']), '/');
if (!es_ruta_local_material_valida($ruta_material)) {
    http_response_code(404);
    exit('Material con ruta inválida.');
}

if ($ruta_param !== '' && !hash_equals($ruta_material, $ruta_param)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$acceso_permitido = false;
$id_materia = (int)$material['id_materia'];
$id_curso = (int)$material['id_curso'];

if (in_array('administrador', $tipos_usuario, true)) {
    $acceso_permitido = true;
}

if (!$acceso_permitido && in_array('docente', $tipos_usuario, true)) {
    $es_docente_materia = db_fetch_one(
        $con,
        "SELECT 1
         FROM docentes_x_materia
         WHERE id_persona = ? AND id_materia = ?
         LIMIT 1",
        'ii',
        [$id_persona, $id_materia]
    );
    $acceso_permitido = (bool)$es_docente_materia;
}

if (!$acceso_permitido && in_array('preceptor', $tipos_usuario, true)) {
    $es_preceptor_curso = db_fetch_one(
        $con,
        "SELECT 1
         FROM preceptor_x_curso
         WHERE id_persona = ? AND id_curso = ?
         LIMIT 1",
        'ii',
        [$id_persona, $id_curso]
    );
    $acceso_permitido = (bool)$es_preceptor_curso;
}

if (!$acceso_permitido && in_array('alumno', $tipos_usuario, true)) {
    $es_alumno_materia = db_fetch_one(
        $con,
        "SELECT 1
         FROM alumnos_x_materia
         WHERE id_persona = ? AND id_materia = ?
         LIMIT 1",
        'ii',
        [$id_persona, $id_materia]
    );
    if ($es_alumno_materia) {
        $acceso_permitido = true;
    } else {
        $es_alumno_curso = db_fetch_one(
            $con,
            "SELECT 1
             FROM alumnos_x_curso
             WHERE id_persona = ? AND id_curso = ?
             LIMIT 1",
            'ii',
            [$id_persona, $id_curso]
        );
        $acceso_permitido = (bool)$es_alumno_curso;
    }
}

if (!$acceso_permitido) {
    http_response_code(403);
    exit('No tenés permisos para ver este material.');
}

$base_materiales = realpath(__DIR__ . '/materiales');
if ($base_materiales === false) {
    http_response_code(500);
    exit('No se pudo acceder al almacenamiento.');
}

$archivo_completo = realpath(__DIR__ . '/' . $ruta_material);
if ($archivo_completo === false || !is_file($archivo_completo)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$base_prefijo = rtrim($base_materiales, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strncmp($archivo_completo, $base_prefijo, strlen($base_prefijo)) !== 0) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$extension = strtolower(pathinfo($archivo_completo, PATHINFO_EXTENSION));
$nombre_archivo = basename($archivo_completo);
$tipos_mime = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $tipos_mime[$extension] ?? 'application/octet-stream';
$tipos_inline = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
$disposicion = (!$forzar_descarga && in_array($extension, $tipos_inline, true)) ? 'inline' : 'attachment';

header("Content-Type: $mime");
header("Content-Disposition: $disposicion; filename=\"" . rawurlencode($nombre_archivo) . "\"");
header('Content-Length: ' . filesize($archivo_completo));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
readfile($archivo_completo);
exit;
