<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    redirigir('php/listados/lista_personas.php');
}
verificar_csrf();

$id_persona = (int)($_POST['id'] ?? 0);
if ($id_persona <= 0) {
    redirigir('php/listados/lista_personas.php?estado=err&msg=' . urlencode('Solicitud inválida.'));
}

$id_operador = (int)($_SESSION['id_persona'] ?? 0);
if ($id_persona === $id_operador) {
    redirigir('php/listados/lista_personas.php?estado=err&msg=' . urlencode('No podés eliminar tu propio usuario.'));
}

$persona_existe = db_fetch_one(
    $con,
    "SELECT id_persona FROM personas WHERE id_persona = ? LIMIT 1",
    'i',
    [$id_persona]
);
if (!$persona_existe) {
    redirigir('php/listados/lista_personas.php?estado=err&msg=' . urlencode('La persona seleccionada no existe.'));
}

$sentencia = mysqli_prepare($con, 'DELETE FROM personas WHERE id_persona = ? LIMIT 1');
if (!$sentencia) {
    redirigir('php/listados/lista_personas.php?estado=err&msg=' . urlencode('No se pudo preparar la eliminación.'));
}

mysqli_stmt_bind_param($sentencia, 'i', $id_persona);
$ok = mysqli_stmt_execute($sentencia);
$errno = mysqli_errno($con);
mysqli_stmt_close($sentencia);

if ($ok) {
    redirigir('php/listados/lista_personas.php?estado=ok&msg=' . urlencode('Persona eliminada correctamente.'));
}

if ($errno === 1451) {
    redirigir('php/listados/lista_personas.php?estado=err&msg=' . urlencode('No se pudo eliminar: la persona tiene datos relacionados activos.'));
}

redirigir('php/listados/lista_personas.php?estado=err&msg=' . urlencode('No se pudo eliminar la persona.'));
