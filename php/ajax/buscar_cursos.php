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

if ($es_admin) {
    $cursos = db_fetch_all(
        $con,
        "SELECT c.id_curso AS id,
                CONCAT(c.grado, '° ', s.seccion, ' — ', mo.moda) AS label,
                CONCAT('Curso #', c.id_curso) AS extra
         FROM cursos AS c
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         WHERE (
               CAST(c.id_curso AS CHAR) LIKE ? ESCAPE '\\\\'
            OR CAST(c.grado AS CHAR) LIKE ? ESCAPE '\\\\'
            OR s.seccion LIKE ? ESCAPE '\\\\'
            OR mo.moda LIKE ? ESCAPE '\\\\'
         )
         ORDER BY c.grado ASC, s.seccion ASC, mo.moda ASC
         LIMIT ? OFFSET ?",
        'ssssii',
        [$like, $like, $like, $like, $limit, $offset]
    );
} else {
    $cursos = db_fetch_all(
        $con,
        "SELECT DISTINCT c.id_curso AS id,
                CONCAT(c.grado, '° ', s.seccion, ' — ', mo.moda) AS label,
                CONCAT('Curso #', c.id_curso) AS extra
         FROM cursos AS c
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
         WHERE pc.id_persona = ?
           AND (
               CAST(c.id_curso AS CHAR) LIKE ? ESCAPE '\\\\'
            OR CAST(c.grado AS CHAR) LIKE ? ESCAPE '\\\\'
            OR s.seccion LIKE ? ESCAPE '\\\\'
            OR mo.moda LIKE ? ESCAPE '\\\\'
           )
         ORDER BY c.grado ASC, s.seccion ASC, mo.moda ASC
         LIMIT ? OFFSET ?",
        'issssii',
        [$id_persona, $like, $like, $like, $like, $limit, $offset]
    );
}

$resultado = array_map(
    static fn(array $fila): array => [
        'id' => (int)$fila['id'],
        'label' => (string)$fila['label'],
        'extra' => (string)$fila['extra'],
    ],
    $cursos
);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
