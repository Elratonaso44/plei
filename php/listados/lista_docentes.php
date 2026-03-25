<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_inicio_sesion();

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);
$es_preceptor = in_array('preceptor', $tipos_usuario, true);

if (!$es_admin && !$es_preceptor) {
    http_response_code(403);
    exit('Acceso denegado. Solo administración y preceptoría pueden ver docentes.');
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
        CAST(p.dni AS CHAR) LIKE ? ESCAPE '\\\\'
        OR p.nombre LIKE ? ESCAPE '\\\\'
        OR p.apellido LIKE ? ESCAPE '\\\\'
    )";
    $like = valor_like($q);
    $tipos .= 'sss';
    $parametros[] = $like;
    $parametros[] = $like;
    $parametros[] = $like;
}

if ($es_admin) {
    $from_sql = "
        FROM personas AS p
        INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
        INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
        WHERE LOWER(t.tipo) = 'docente' $filtro_sql
    ";
} else {
    $from_sql = "
        FROM personas AS p
        INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
        INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
        INNER JOIN docentes_x_materia AS dm ON dm.id_persona = p.id_persona
        INNER JOIN materias AS m ON m.id_materia = dm.id_materia
        INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = m.id_curso
        WHERE LOWER(t.tipo) = 'docente'
          AND pc.id_persona = ? $filtro_sql
    ";
    $tipos = 'i' . $tipos;
    array_unshift($parametros, $id_persona);
}

$total_row = db_fetch_one(
    $con,
    "SELECT COUNT(DISTINCT p.id_persona) AS total $from_sql",
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

$docentes = db_fetch_all(
    $con,
    "SELECT DISTINCT p.dni, p.apellido, p.nombre, p.id_persona
     $from_sql
     ORDER BY p.apellido ASC, p.nombre ASC
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
    <h2>Lista de docentes</h2>

    <?php if ($msg !== ''): ?>
    <div class="<?php echo $estado === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
      <i class="bi <?php echo $estado === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
      <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <?php if ($es_preceptor && !$es_admin): ?>
    <div class="resultado-listado-meta">
      Vista filtrada por tus cursos a cargo. Acciones administrativas deshabilitadas.
    </div>
    <?php endif; ?>

    <form method="get" class="barra-filtros-listado">
      <div class="filtro-input-wrap">
        <label for="q" class="form-label">Buscar por DNI, nombre o apellido</label>
        <input id="q" type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ej: 44123456, Ana, López">
      </div>
      <div class="filtro-acciones-wrap">
        <button type="submit" class="btn-plei-submit btn-filtro">Buscar</button>
        <a href="lista_docentes.php" class="btn-plei-cancel btn-filtro">Limpiar</a>
      </div>
    </form>

    <div class="resultado-listado-meta">
      Mostrando <?php echo count($docentes); ?> de <?php echo $total; ?> resultados
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>DNI</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($docentes)): ?>
          <tr>
            <td colspan="4" class="text-center py-4">No se encontraron docentes con ese criterio.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($docentes as $doc): ?>
            <tr>
              <td><?php echo htmlspecialchars($doc['dni']); ?></td>
              <td><?php echo htmlspecialchars($doc['nombre']); ?></td>
              <td><?php echo htmlspecialchars($doc['apellido']); ?></td>
              <td>
                <?php if ($es_admin): ?>
                <div class="acciones-tabla">
                  <a href="../modificaciones/editar_docente.php?id=<?php echo urlencode((string)$doc['id_persona']); ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                  <form method="post" action="../modificaciones/eliminar_docente.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas eliminar este docente?');">
                    <?php campo_csrf(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$doc['id_persona']; ?>">
                    <button type="submit" class="btn btn-sm btn-table-del">Eliminar</button>
                  </form>
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
    <nav class="paginador-listado" aria-label="Paginación docentes">
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
