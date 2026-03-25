<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_inicio_sesion();

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);
$es_docente = in_array('docente', $tipos_usuario, true);

if (!$es_admin && !$es_docente) {
    http_response_code(403);
    exit('Acceso denegado. No tenés permisos para eliminar materiales.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verificar_csrf();

$id_material = (int)($_POST['id'] ?? 0);
$volver = trim((string)($_POST['volver'] ?? ''));
$ruta_retorno = 'php/listados/lista_materiax_docente.php';
if (
    $volver !== ''
    && strpos($volver, '..') === false
    && preg_match('/^php\/[a-zA-Z0-9_\/.-]+(\?[a-zA-Z0-9_=&-]*)?$/', $volver) === 1
) {
    $ruta_retorno = ltrim($volver, '/');
}
if ($id_material <= 0) {
    redirigir($ruta_retorno . (str_contains($ruta_retorno, '?') ? '&' : '?') . 'estado=err&msg=' . urlencode('Solicitud incompleta.'));
}

$material = db_fetch_one(
    $con,
    "SELECT id_material, id_materia, url
     FROM materiales
     WHERE id_material = ?
     LIMIT 1",
    'i',
    [$id_material]
);
if (!$material) {
    redirigir($ruta_retorno . (str_contains($ruta_retorno, '?') ? '&' : '?') . 'estado=err&msg=' . urlencode('El material seleccionado no existe.'));
}

$puede_borrar = $es_admin;
if (!$puede_borrar) {
    $material_docente = db_fetch_one(
        $con,
        "SELECT 1
         FROM docentes_x_materia AS dm
         WHERE dm.id_materia = ? AND dm.id_persona = ?
         LIMIT 1",
        'ii',
        [(int)$material['id_materia'], $id_persona]
    );
    $puede_borrar = (bool)$material_docente;
}

if (!$puede_borrar) {
    http_response_code(403);
    exit('Acceso denegado. No tenés permisos para eliminar ese material.');
}

$sentencia = mysqli_prepare($con, 'DELETE FROM materiales WHERE id_material = ? LIMIT 1');
if (!$sentencia) {
    redirigir($ruta_retorno . (str_contains($ruta_retorno, '?') ? '&' : '?') . 'estado=err&msg=' . urlencode('No se pudo preparar la eliminación del material.'));
}

mysqli_stmt_bind_param($sentencia, 'i', $id_material);
$ok = mysqli_stmt_execute($sentencia);
$errno = mysqli_errno($con);
$afectadas = mysqli_stmt_affected_rows($sentencia);
mysqli_stmt_close($sentencia);

if (!$ok || $afectadas <= 0) {
    if ($errno === 1451) {
        redirigir($ruta_retorno . (str_contains($ruta_retorno, '?') ? '&' : '?') . 'estado=err&msg=' . urlencode('No se pudo eliminar el material porque tiene datos relacionados.'));
    }
    redirigir($ruta_retorno . (str_contains($ruta_retorno, '?') ? '&' : '?') . 'estado=err&msg=' . urlencode('No se pudo eliminar el material.'));
}

$url_material = trim((string)$material['url']);
if (ruta_local_material_relativa_valida($url_material)) {
    eliminar_archivo_material_local_si_huerfano($con, $url_material, 0);
}

redirigir($ruta_retorno . (str_contains($ruta_retorno, '?') ? '&' : '?') . 'estado=ok&msg=' . urlencode('Material eliminado correctamente.'));
