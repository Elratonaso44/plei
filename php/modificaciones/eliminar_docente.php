<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    redirigir('php/listados/lista_docentes.php');
}
verificar_csrf();

$id_persona = (int)($_POST['id'] ?? 0);
if ($id_persona <= 0) {
    redirigir('php/listados/lista_docentes.php?estado=err&msg=' . urlencode('Solicitud inválida.'));
}

if (!columna_bd_existe($con, 'personas', 'activo')) {
    redirigir('php/listados/lista_docentes.php?estado=err&msg=' . urlencode('Falta migración de producción. Ejecutá migracion_005_produccion.sql.'));
}

$id_operador = (int)($_SESSION['id_persona'] ?? 0);
if ($id_persona === $id_operador) {
    redirigir('php/listados/lista_docentes.php?estado=err&msg=' . urlencode('No podés inactivar tu propio usuario desde este listado.'));
}

if (!persona_tiene_tipo($con, $id_persona, 'docente')) {
    redirigir('php/listados/lista_docentes.php?estado=err&msg=' . urlencode('La persona seleccionada no tiene tipo docente.'));
}

$sentencia = mysqli_prepare(
    $con,
    "UPDATE personas
     SET activo = 0,
         inactivado_en = NOW(),
         inactivado_por = ?
     WHERE id_persona = ?
       AND COALESCE(activo, 1) = 1"
);
if (!$sentencia) {
    redirigir('php/listados/lista_docentes.php?estado=err&msg=' . urlencode('No se pudo preparar la inactivación.'));
}

mysqli_stmt_bind_param($sentencia, 'ii', $id_operador, $id_persona);
$ok = mysqli_stmt_execute($sentencia);
$afectadas = mysqli_stmt_affected_rows($sentencia);
mysqli_stmt_close($sentencia);

if ($ok && $afectadas > 0) {
    registrar_auditoria_boletin($con, [
        'tipo_evento' => 'usuario_inactivado',
        'entidad' => 'docente',
        'id_actor' => $id_operador,
        'id_objetivo' => $id_persona,
        'payload' => ['modulo' => 'lista_docentes']
    ]);
    redirigir('php/listados/lista_docentes.php?estado=ok&msg=' . urlencode('Docente inactivado correctamente.'));
}

redirigir('php/listados/lista_docentes.php?estado=err&msg=' . urlencode('No se pudo inactivar el docente.'));
