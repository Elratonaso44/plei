<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    redirigir('php/listados/lista_preceptores.php');
}
verificar_csrf();

$id_persona = (int)($_POST['id'] ?? 0);
if ($id_persona <= 0) {
    redirigir('php/listados/lista_preceptores.php?estado=err&msg=' . urlencode('Solicitud inválida.'));
}

$id_operador = (int)($_SESSION['id_persona'] ?? 0);
if ($id_persona === $id_operador) {
    redirigir('php/listados/lista_preceptores.php?estado=err&msg=' . urlencode('No podés eliminar tu propio usuario desde este listado.'));
}

if (!persona_tiene_tipo($con, $id_persona, 'preceptor')) {
    redirigir('php/listados/lista_preceptores.php?estado=err&msg=' . urlencode('La persona seleccionada no tiene tipo preceptor.'));
}

$sentencia = mysqli_prepare($con, 'DELETE FROM personas WHERE id_persona = ? LIMIT 1');
if (!$sentencia) {
    redirigir('php/listados/lista_preceptores.php?estado=err&msg=' . urlencode('No se pudo preparar la eliminación.'));
}

mysqli_stmt_bind_param($sentencia, 'i', $id_persona);
$ok = mysqli_stmt_execute($sentencia);
$errno = mysqli_errno($con);
mysqli_stmt_close($sentencia);

if ($ok) {
    redirigir('php/listados/lista_preceptores.php?estado=ok&msg=' . urlencode('Preceptor eliminado correctamente.'));
}

if ($errno === 1451) {
    redirigir('php/listados/lista_preceptores.php?estado=err&msg=' . urlencode('No se pudo eliminar: el preceptor tiene datos relacionados activos.'));
}

redirigir('php/listados/lista_preceptores.php?estado=err&msg=' . urlencode('No se pudo eliminar el preceptor.'));
