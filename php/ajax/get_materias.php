<?php
include '../conesion.php';
include '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id_persona'])) {
    http_response_code(403);
    echo '[]';
    exit;
}

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$id_curso = (int)($_GET['curso'] ?? 0);
if ($id_curso <= 0) {
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

$materias = db_fetch_all(
    $con,
    "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo,
            COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)) AS grupos_texto
     FROM materias AS m
     LEFT JOIN (
         SELECT id_materia, GROUP_CONCAT(id_grupo ORDER BY id_grupo SEPARATOR ',') AS grupos_txt
         FROM materias_x_grupo
         GROUP BY id_materia
     ) AS mg ON mg.id_materia = m.id_materia
     WHERE id_curso = ?
     ORDER BY nombre_materia ASC",
    'i',
    [$id_curso]
);

$salida = array_map(
    static function (array $fila): array {
        $texto = (string)($fila['grupos_texto'] ?? '');
        $grupos = array_values(array_filter(
            array_map('intval', explode(',', $texto)),
            static fn($g) => $g > 0
        ));
        $fila['grupos'] = $grupos;
        return $fila;
    },
    $materias
);

echo json_encode($salida);
