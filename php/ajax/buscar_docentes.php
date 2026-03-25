<?php
include '../conesion.php';
include '../config.php';
session_start();

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_persona'])) {
    http_response_code(403);
    echo '[]';
    exit;
}

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);
$es_preceptor = in_array('preceptor', $tipos_usuario, true);

if (!$es_admin && !$es_preceptor) {
    http_response_code(403);
    echo '[]';
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$limit = (int)($_GET['limit'] ?? 20);
$offset = (int)($_GET['offset'] ?? 0);

if ($limit <= 0) {
    $limit = 20;
}
$limit = min($limit, 20);
$offset = max(0, $offset);

if (strlen($q) < 2) {
    echo '[]';
    exit;
}

$like = valor_like($q);

$docentes = db_fetch_all(
    $con,
    "SELECT DISTINCT p.id_persona AS id,
            CONCAT(p.apellido, ', ', p.nombre) AS label,
            CONCAT('DNI ', p.dni) AS extra
     FROM personas AS p
     INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
     WHERE LOWER(tp.tipo) = 'docente'
       AND (
           CAST(p.dni AS CHAR) LIKE ? ESCAPE '\\\\'
        OR p.apellido LIKE ? ESCAPE '\\\\'
        OR p.nombre LIKE ? ESCAPE '\\\\'
        OR CONCAT(p.apellido, ' ', p.nombre) LIKE ? ESCAPE '\\\\'
        OR CONCAT(p.nombre, ' ', p.apellido) LIKE ? ESCAPE '\\\\'
       )
     ORDER BY p.apellido ASC, p.nombre ASC
     LIMIT ? OFFSET ?",
    'sssssii',
    [$like, $like, $like, $like, $like, $limit, $offset]
);

$resultado = array_map(
    static fn(array $fila): array => [
        'id' => (int)$fila['id'],
        'label' => (string)$fila['label'],
        'extra' => (string)$fila['extra'],
    ],
    $docentes
);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
