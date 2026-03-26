<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    redirigir('php/listados/lista_materias.php');
}
verificar_csrf();

$id_materia = (int)($_POST['id'] ?? 0);
if ($id_materia <= 0) {
    redirigir('php/listados/lista_materias.php?estado=err&msg=' . urlencode('Solicitud inválida.'));
}

if (materia_tiene_historial_boletin($con, $id_materia)) {
    redirigir('php/listados/lista_materias.php?estado=err&msg=' . urlencode('No se puede eliminar la materia porque tiene historial de boletín.'));
}

$sentencia = mysqli_prepare($con, 'DELETE FROM materias WHERE id_materia = ? LIMIT 1');
if (!$sentencia) {
    redirigir('php/listados/lista_materias.php?estado=err&msg=' . urlencode('No se pudo preparar la eliminación.'));
}

mysqli_stmt_bind_param($sentencia, 'i', $id_materia);
$ok = mysqli_stmt_execute($sentencia);
$errno = mysqli_errno($con);
$afectadas = mysqli_stmt_affected_rows($sentencia);
mysqli_stmt_close($sentencia);

if ($ok && $afectadas > 0) {
    redirigir('php/listados/lista_materias.php?estado=ok&msg=' . urlencode('Materia eliminada correctamente.'));
}

if ($errno === 1451) {
    redirigir('php/listados/lista_materias.php?estado=err&msg=' . urlencode('No se puede eliminar la materia porque tiene datos relacionados.'));
}

redirigir('php/listados/lista_materias.php?estado=err&msg=' . urlencode('No se pudo eliminar la materia.'));
