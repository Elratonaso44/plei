<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    redirigir('php/listados/lista_cursos.php');
}
verificar_csrf();

$id_curso = (int)($_POST['id'] ?? 0);
if ($id_curso <= 0) {
    redirigir('php/listados/lista_cursos.php?estado=err&msg=' . urlencode('Solicitud inválida.'));
}

if (curso_tiene_historial_boletin($con, $id_curso)) {
    redirigir('php/listados/lista_cursos.php?estado=err&msg=' . urlencode('No se puede eliminar el curso porque tiene historial de boletín.'));
}

$sentencia = mysqli_prepare($con, 'DELETE FROM cursos WHERE id_curso = ? LIMIT 1');
if (!$sentencia) {
    redirigir('php/listados/lista_cursos.php?estado=err&msg=' . urlencode('No se pudo preparar la eliminación.'));
}

mysqli_stmt_bind_param($sentencia, 'i', $id_curso);
$ok = mysqli_stmt_execute($sentencia);
$errno = mysqli_errno($con);
$afectadas = mysqli_stmt_affected_rows($sentencia);
mysqli_stmt_close($sentencia);

if ($ok && $afectadas > 0) {
    redirigir('php/listados/lista_cursos.php?estado=ok&msg=' . urlencode('Curso eliminado correctamente.'));
}

if ($errno === 1451) {
    redirigir('php/listados/lista_cursos.php?estado=err&msg=' . urlencode('No se puede eliminar el curso porque tiene materias o asignaciones relacionadas.'));
}

redirigir('php/listados/lista_cursos.php?estado=err&msg=' . urlencode('No se pudo eliminar el curso.'));
