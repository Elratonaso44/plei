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
$id_operador = (int)($_SESSION['id_persona'] ?? 0);
$volver = trim((string)($_POST['volver'] ?? 'php/listados/lista_personas.php'));

$rutas_permitidas = [
    'php/listados/lista_personas.php',
    'php/listados/lista_docentes.php',
    'php/listados/lista_preceptores.php',
    'php/listados/lista_alumnos.php',
];
if (!in_array($volver, $rutas_permitidas, true)) {
    $volver = 'php/listados/lista_personas.php';
}

if ($id_persona <= 0) {
    redirigir($volver . '?estado=err&msg=' . urlencode('Solicitud inválida.'));
}

if (!columna_bd_existe($con, 'personas', 'activo')) {
    redirigir($volver . '?estado=err&msg=' . urlencode('Falta migración de producción. Ejecutá migracion_005_produccion.sql.'));
}

$persona = db_fetch_one(
    $con,
    "SELECT id_persona, COALESCE(activo, 1) AS activo
     FROM personas
     WHERE id_persona = ?
     LIMIT 1",
    'i',
    [$id_persona]
);
if (!$persona) {
    redirigir($volver . '?estado=err&msg=' . urlencode('La persona seleccionada no existe.'));
}
if ((int)($persona['activo'] ?? 1) === 1) {
    redirigir($volver . '?estado=err&msg=' . urlencode('La persona ya está activa.'));
}

$stmt = mysqli_prepare(
    $con,
    "UPDATE personas
     SET activo = 1,
         inactivado_en = NULL,
         inactivado_por = NULL
     WHERE id_persona = ?"
);
if (!$stmt) {
    redirigir($volver . '?estado=err&msg=' . urlencode('No se pudo preparar la reactivación.'));
}
mysqli_stmt_bind_param($stmt, 'i', $id_persona);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    redirigir($volver . '?estado=err&msg=' . urlencode('No se pudo reactivar la persona.'));
}

registrar_auditoria_boletin($con, [
    'tipo_evento' => 'usuario_reactivado',
    'entidad' => 'persona',
    'id_actor' => $id_operador,
    'id_objetivo' => $id_persona,
    'payload' => ['modulo' => $volver]
]);

redirigir($volver . '?estado=ok&msg=' . urlencode('Persona reactivada correctamente.'));
