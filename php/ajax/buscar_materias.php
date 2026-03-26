<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_inicio_sesion();

header('Content-Type: application/json; charset=UTF-8');

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);
$es_preceptor = in_array('preceptor', $tipos_usuario, true);

if (!$es_admin && !$es_preceptor) {
    http_response_code(403);
    echo '[]';
    exit;
}

$modo = strtolower(trim((string)($_GET['modo'] ?? 'curso')));
if ($modo !== 'curso' && $modo !== 'directo') {
    http_response_code(422);
    echo '[]';
    exit;
}

$id_curso = (int)($_GET['id_curso'] ?? 0);
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

if ($modo === 'curso' && $id_curso <= 0) {
    echo '[]';
    exit;
}

$like = valor_like($q);

$filtro_curso = '';
$join_permiso = '';
$tipos = '';
$parametros = [];

if (!$es_admin) {
    $join_permiso = ' INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso ';
    $filtro_curso .= ' AND pc.id_persona = ?';
    $tipos .= 'i';
    $parametros[] = $id_persona;
}

if ($modo === 'curso') {
    $filtro_curso .= ' AND c.id_curso = ?';
    $tipos .= 'i';
    $parametros[] = $id_curso;
}

if ($modo === 'curso') {
    $filtro_busqueda = " AND (
           m.nombre_materia LIKE ? ESCAPE '\\\\'
        OR m.turno LIKE ? ESCAPE '\\\\'
        OR COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)) LIKE ? ESCAPE '\\\\'
        OR CAST(m.id_materia AS CHAR) LIKE ? ESCAPE '\\\\'
    )";
    $tipos .= 'ssss';
    $parametros[] = $like;
    $parametros[] = $like;
    $parametros[] = $like;
    $parametros[] = $like;
} else {
    $filtro_busqueda = " AND (
           m.nombre_materia LIKE ? ESCAPE '\\\\'
        OR m.turno LIKE ? ESCAPE '\\\\'
        OR COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)) LIKE ? ESCAPE '\\\\'
        OR CAST(m.id_materia AS CHAR) LIKE ? ESCAPE '\\\\'
        OR CAST(c.grado AS CHAR) LIKE ? ESCAPE '\\\\'
        OR s.seccion LIKE ? ESCAPE '\\\\'
        OR mo.moda LIKE ? ESCAPE '\\\\'
    )";
    $tipos .= 'sssssss';
    for ($i = 0; $i < 7; $i++) {
        $parametros[] = $like;
    }
}

$tipos .= 'ii';
$parametros[] = $limit;
$parametros[] = $offset;

$materias = db_fetch_all(
    $con,
    "SELECT DISTINCT m.id_materia AS id,
            COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)) AS grupos_txt,
            CONCAT(m.nombre_materia, ' — ', c.grado, '° ', s.seccion, ' (', mo.moda, ') Turno ', m.turno) AS label,
            CONCAT('Grupos ', COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)), ' | Curso #', c.id_curso) AS extra
     FROM materias AS m
     INNER JOIN cursos AS c ON c.id_curso = m.id_curso
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     LEFT JOIN (
         SELECT id_materia, GROUP_CONCAT(id_grupo ORDER BY id_grupo SEPARATOR ',') AS grupos_txt
         FROM materias_x_grupo
         GROUP BY id_materia
     ) AS mg ON mg.id_materia = m.id_materia
     $join_permiso
     WHERE 1=1
     $filtro_curso
     $filtro_busqueda
     ORDER BY c.grado ASC, s.seccion ASC, m.nombre_materia ASC
     LIMIT ? OFFSET ?",
    $tipos,
    $parametros
);

$resultado = array_map(
    static function (array $fila): array {
        $texto_grupos = trim((string)($fila['grupos_txt'] ?? ''));
        $grupos = array_values(array_filter(
            array_map('intval', explode(',', $texto_grupos)),
            static fn($g) => $g > 0
        ));

        return [
            'id' => (int)$fila['id'],
            'label' => (string)$fila['label'],
            'extra' => (string)$fila['extra'],
            'grupos' => $grupos,
        ];
    },
    $materias
);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
