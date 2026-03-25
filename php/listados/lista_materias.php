<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_inicio_sesion();

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);
$es_preceptor = in_array('preceptor', $tipos_usuario, true);

if (!$es_admin && !$es_preceptor) {
    http_response_code(403);
    exit('Acceso denegado. Solo administración y preceptoría pueden ver materias.');
}

$q = trim((string)($_GET['q'] ?? ''));
$pagina = max(1, (int)($_GET['page'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$filtro_sql = '';
$tipos = '';
$parametros = [];

if ($q !== '') {
    $filtro_sql = " AND (
        m.nombre_materia LIKE ? ESCAPE '\\\\'
        OR m.turno LIKE ? ESCAPE '\\\\'
        OR COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)) LIKE ? ESCAPE '\\\\'
        OR CAST(m.id_materia AS CHAR) LIKE ? ESCAPE '\\\\'
        OR CAST(c.grado AS CHAR) LIKE ? ESCAPE '\\\\'
        OR s.seccion LIKE ? ESCAPE '\\\\'
        OR mo.moda LIKE ? ESCAPE '\\\\'
    )";
    $like = valor_like($q);
    $tipos .= 'sssssss';
    for ($i = 0; $i < 7; $i++) {
        $parametros[] = $like;
    }
}

if ($es_admin) {
    $from_sql = "
        FROM materias AS m
        INNER JOIN cursos AS c ON c.id_curso = m.id_curso
        INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
        INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
        LEFT JOIN (
            SELECT id_materia, GROUP_CONCAT(id_grupo ORDER BY id_grupo SEPARATOR ',') AS grupos_txt
            FROM materias_x_grupo
            GROUP BY id_materia
        ) AS mg ON mg.id_materia = m.id_materia
        WHERE 1=1 $filtro_sql
    ";
} else {
    $from_sql = "
        FROM materias AS m
        INNER JOIN cursos AS c ON c.id_curso = m.id_curso
        INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
        INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
        LEFT JOIN (
            SELECT id_materia, GROUP_CONCAT(id_grupo ORDER BY id_grupo SEPARATOR ',') AS grupos_txt
            FROM materias_x_grupo
            GROUP BY id_materia
        ) AS mg ON mg.id_materia = m.id_materia
        INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
        WHERE pc.id_persona = ? $filtro_sql
    ";
    $tipos = 'i' . $tipos;
    array_unshift($parametros, $id_persona);
}

$total_row = db_fetch_one(
    $con,
    "SELECT COUNT(*) AS total $from_sql",
    $tipos,
    $parametros
);

$total = (int)($total_row['total'] ?? 0);
$total_paginas = max(1, (int)ceil($total / $por_pagina));
if ($pagina > $total_paginas) {
    $pagina = $total_paginas;
    $offset = ($pagina - 1) * $por_pagina;
}

$parametros_listado = $parametros;
$parametros_listado[] = $por_pagina;
$parametros_listado[] = $offset;

$materias = db_fetch_all(
    $con,
    "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, m.id_curso,
            COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)) AS grupos_texto,
            c.grado, s.seccion, mo.moda
     $from_sql
     ORDER BY c.grado ASC, s.seccion ASC, m.nombre_materia ASC
     LIMIT ? OFFSET ?",
    $tipos . 'ii',
    $parametros_listado
);

$parametros_base = [];
if ($q !== '') {
    $parametros_base['q'] = $q;
}
$url_pagina = static function (int $n) use ($parametros_base): string {
    $p = $parametros_base;
    $p['page'] = $n;
    return '?' . http_build_query($p);
};

$estado = trim((string)($_GET['estado'] ?? ''));
$msg = trim((string)($_GET['msg'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
  <div class="tarjeta-principal">
    <h2>Lista de materias</h2>

    <?php if ($msg !== ''): ?>
    <div class="<?php echo $estado === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
      <i class="bi <?php echo $estado === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
      <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <?php if ($es_preceptor && !$es_admin): ?>
    <div class="resultado-listado-meta">
      Vista filtrada por tus cursos a cargo. Podés editar materias de tu ámbito. La eliminación sigue siendo exclusiva de administración.
    </div>
    <?php endif; ?>

    <form method="get" class="barra-filtros-listado">
      <div class="filtro-input-wrap">
        <label for="q" class="form-label">Buscar por materia, turno, grupos, curso o ID</label>
        <input id="q" type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ej: Matemática, tarde, 2, 6° A">
      </div>
      <div class="filtro-acciones-wrap">
        <button type="submit" class="btn-plei-submit btn-filtro">Buscar</button>
        <a href="lista_materias.php" class="btn-plei-cancel btn-filtro">Limpiar</a>
      </div>
    </form>

    <div class="resultado-listado-meta">
      Mostrando <?php echo count($materias); ?> de <?php echo $total; ?> resultados
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Turno</th>
            <th>Grupos</th>
            <th>Curso</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($materias)): ?>
          <tr>
            <td colspan="6" class="text-center py-4">No se encontraron materias con ese criterio.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($materias as $materia): ?>
          <tr>
            <td><?php echo htmlspecialchars($materia['id_materia']); ?></td>
            <td><?php echo htmlspecialchars($materia['nombre_materia']); ?></td>
            <td><?php echo htmlspecialchars($materia['turno']); ?></td>
            <td><?php echo htmlspecialchars((string)$materia['grupos_texto']); ?></td>
            <td><?php echo htmlspecialchars($materia['grado'] . '° ' . $materia['seccion'] . ' ' . $materia['moda']); ?></td>
            <td>
              <?php if ($es_admin || $es_preceptor): ?>
              <div class="acciones-tabla">
                <a href="../modificaciones/editar_materia.php?id=<?php echo urlencode((string)$materia['id_materia']); ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                <?php if ($es_admin): ?>
                <form method="post" action="../modificaciones/eliminar_materia.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas eliminar esta materia?');">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)$materia['id_materia']; ?>">
                  <button type="submit" class="btn btn-sm btn-table-del">Eliminar</button>
                </form>
                <?php endif; ?>
              </div>
              <?php else: ?>
              <span class="texto-opcional">Solo lectura</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_paginas > 1): ?>
    <nav class="paginador-listado" aria-label="Paginación materias">
      <a class="btn-plei-cancel btn-pagina <?php echo $pagina <= 1 ? 'disabled' : ''; ?>" href="<?php echo $pagina <= 1 ? '#' : htmlspecialchars($url_pagina($pagina - 1)); ?>">Anterior</a>
      <span class="pagina-actual">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
      <a class="btn-plei-cancel btn-pagina <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>" href="<?php echo $pagina >= $total_paginas ? '#' : htmlspecialchars($url_pagina($pagina + 1)); ?>">Siguiente</a>
    </nav>
    <?php endif; ?>

    <div class="text-end mt-3">
      <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
    </div>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
