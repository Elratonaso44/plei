<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol(['administrador', 'preceptor']);

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);

$q = trim((string)($_GET['q'] ?? ''));
$pagina = max(1, (int)($_GET['page'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$filtro_sql = '';
$tipos = '';
$parametros = [];

if ($q !== '') {
    $filtro_sql = " AND (
        CAST(c.grado AS CHAR) LIKE ? ESCAPE '\\\\'
        OR m.moda LIKE ? ESCAPE '\\\\'
        OR s.seccion LIKE ? ESCAPE '\\\\'
    )";
    $like = valor_like($q);
    $tipos .= 'sss';
    $parametros[] = $like;
    $parametros[] = $like;
    $parametros[] = $like;
}

if ($es_admin) {
    $from_sql = "
        FROM cursos AS c
        INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
        INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
        WHERE 1=1 $filtro_sql
    ";
} else {
    $from_sql = "
        FROM cursos AS c
        INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
        INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
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

$cursos = db_fetch_all(
    $con,
    "SELECT c.id_curso, c.grado, m.moda, s.seccion
     $from_sql
     ORDER BY c.grado ASC, s.seccion ASC
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
    <h2><i class="bi bi-calendar3-fill"></i> Lista de cursos</h2>

    <?php if ($msg !== ''): ?>
    <div class="<?php echo $estado === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
      <i class="bi <?php echo $estado === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
      <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <?php if (!$es_admin): ?>
    <div class="resultado-listado-meta">
      Vista filtrada por cursos a tu cargo. Solo administración puede eliminar cursos.
    </div>
    <?php endif; ?>

    <form method="get" class="barra-filtros-listado">
      <div class="filtro-input-wrap">
        <label for="q" class="form-label">Buscar por grado, modalidad o sección</label>
        <input id="q" type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ej: 6, Programación, A">
      </div>
      <div class="filtro-acciones-wrap">
        <button type="submit" class="btn-plei-submit btn-filtro">Buscar</button>
        <a href="lista_cursos.php" class="btn-plei-cancel btn-filtro">Limpiar</a>
      </div>
    </form>

    <div class="resultado-listado-meta">
      Mostrando <?php echo count($cursos); ?> de <?php echo $total; ?> resultados
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>Modalidad</th>
            <th>Grado</th>
            <th>Sección</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($cursos)): ?>
          <tr>
            <td colspan="4" class="text-center py-4">No se encontraron cursos con ese criterio.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($cursos as $cur): ?>
            <tr>
              <td><?php echo htmlspecialchars($cur['moda']); ?></td>
              <td><?php echo htmlspecialchars($cur['grado']); ?></td>
              <td><?php echo htmlspecialchars($cur['seccion']); ?></td>
              <td>
                <div class="acciones-tabla">
                  <a href="../modificaciones/editar_curso.php?id=<?php echo urlencode((string)$cur['id_curso']); ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                  <?php if ($es_admin): ?>
                  <form method="post" action="../modificaciones/eliminar_curso.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas eliminar este curso?');">
                    <?php campo_csrf(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$cur['id_curso']; ?>">
                    <button type="submit" class="btn btn-sm btn-table-del">Eliminar</button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_paginas > 1): ?>
    <nav class="paginador-listado" aria-label="Paginación cursos">
      <a class="btn-plei-cancel btn-pagina <?php echo $pagina <= 1 ? 'disabled' : ''; ?>" href="<?php echo $pagina <= 1 ? '#' : htmlspecialchars($url_pagina($pagina - 1)); ?>">Anterior</a>
      <span class="pagina-actual">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
      <a class="btn-plei-cancel btn-pagina <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>" href="<?php echo $pagina >= $total_paginas ? '#' : htmlspecialchars($url_pagina($pagina + 1)); ?>">Siguiente</a>
    </nav>
    <?php endif; ?>

    <div class="text-end mt-3">
      <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver al inicio</a>
    </div>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
