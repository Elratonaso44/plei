<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_inicio_sesion();

header('Content-Type: application/json');

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$id_curso = (int)($_GET['curso'] ?? 0);
$id_materia = (int)($_GET['materia'] ?? 0);

if ($id_curso <= 0 || $id_materia <= 0) {
    echo '[]';
    exit;
}

$tipos = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos, true);
$es_preceptor = in_array('preceptor', $tipos, true);

if (!$es_admin && !$es_preceptor) {
    http_response_code(403);
    echo '[]';
    exit;
}

$materia_valida = db_fetch_one(
    $con,
    "SELECT 1
     FROM materias
     WHERE id_materia = ? AND id_curso = ?
     LIMIT 1",
    'ii',
    [$id_materia, $id_curso]
);

if (!$materia_valida) {
    http_response_code(422);
    echo '[]';
    exit;
}

if (!$es_admin) {
    $preceptor_curso = db_fetch_one(
        $con,
        "SELECT 1
         FROM preceptor_x_curso
         WHERE id_persona = ? AND id_curso = ?
         LIMIT 1",
        'ii',
        [$id_persona, $id_curso]
    );
    if (!$preceptor_curso) {
        http_response_code(403);
        echo '[]';
        exit;
    }
}

$filtro_activo_alumno = condicion_persona_activa($con, 'p');
$alumnos = db_fetch_all(
    $con,
    "SELECT DISTINCT p.id_persona, p.nombre, p.apellido, p.dni
     FROM personas AS p
     INNER JOIN alumnos_x_curso AS axc ON axc.id_persona = p.id_persona
     INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
     WHERE LOWER(tp.tipo) = 'alumno'
       $filtro_activo_alumno
       AND axc.id_curso = ?
       AND p.id_persona NOT IN (
         SELECT id_persona FROM alumnos_x_materia WHERE id_materia = ?
       )
     ORDER BY p.apellido ASC, p.nombre ASC",
    'ii',
    [$id_curso, $id_materia]
);

echo json_encode($alumnos);
