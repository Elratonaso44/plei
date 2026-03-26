<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol(['administrador', 'preceptor']);

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);

$q = trim((string)($_GET['q'] ?? ''));
$ver_inactivos = $es_admin ? solicitud_ver_inactivos() : false;
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

$filtro_activo_sql = condicion_persona_activa($con, 'p', $ver_inactivos);
$expr_activo = expresion_persona_activo($con, 'p');

if ($es_admin) {
    $from_sql = "
        FROM personas AS p
        INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
        INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
        WHERE LOWER(t.tipo) = 'alumno' $filtro_activo_sql $filtro_sql
    ";
} else {
    $from_sql = "
        FROM personas AS p
        INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
        INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
        INNER JOIN alumnos_x_curso AS axc ON axc.id_persona = p.id_persona
        INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = axc.id_curso
        WHERE LOWER(t.tipo) = 'alumno'
          AND pc.id_persona = ? $filtro_activo_sql $filtro_sql
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

$alumnos = db_fetch_all(
    $con,
    "SELECT DISTINCT p.dni, p.apellido, p.nombre, p.id_persona, $expr_activo AS activo,
            (
              SELECT GROUP_CONCAT(DISTINCT CONCAT(c2.grado, '° ', s2.seccion, ' ', mo2.moda) SEPARATOR ' | ')
              FROM alumnos_x_curso AS axc2
              INNER JOIN cursos AS c2 ON c2.id_curso = axc2.id_curso
              INNER JOIN secciones AS s2 ON s2.id_seccion = c2.id_seccion
              INNER JOIN modalidad AS mo2 ON mo2.id_modalidad = c2.id_modalidad
              WHERE axc2.id_persona = p.id_persona
            ) AS curso_detalle
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
if ($ver_inactivos) {
    $parametros_base['inactivos'] = '1';
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
    <h2>Lista de alumnos</h2>

    <?php if ($msg !== ''): ?>
    <div class="<?php echo $estado === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
      <i class="bi <?php echo $estado === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
      <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <?php if (!$es_admin): ?>
    <div class="resultado-listado-meta">
      Vista filtrada por tus cursos a cargo. Solo podés editar alumnos bajo tu preceptoría.
    </div>
    <?php endif; ?>

    <form method="get" class="barra-filtros-listado">
      <div class="filtro-input-wrap">
        <label for="q" class="form-label">Buscar por DNI, nombre o apellido</label>
        <input id="q" type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ej: 44123456, Martina, Gómez">
      </div>
      <div class="filtro-acciones-wrap">
        <button type="submit" class="btn-plei-submit btn-filtro">Buscar</button>
        <a href="lista_alumnos.php" class="btn-plei-cancel btn-filtro">Limpiar</a>
      </div>
      <?php if ($es_admin): ?>
      <label class="texto-opcional d-flex align-items-center gap-2">
        <input type="checkbox" name="inactivos" value="1" <?php echo $ver_inactivos ? 'checked' : ''; ?>>
        Ver inactivos
      </label>
      <?php endif; ?>
    </form>

    <div class="resultado-listado-meta">
      Mostrando <?php echo count($alumnos); ?> de <?php echo $total; ?> resultados
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>DNI</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Curso</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($alumnos)): ?>
          <tr>
            <td colspan="6" class="text-center py-4">No se encontraron alumnos con ese criterio.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($alumnos as $alumno): ?>
          <?php $alumno_activo = (int)($alumno['activo'] ?? 1) === 1; ?>
          <tr>
            <td><?php echo htmlspecialchars($alumno['dni']); ?></td>
            <td><?php echo htmlspecialchars($alumno['nombre']); ?></td>
            <td><?php echo htmlspecialchars($alumno['apellido']); ?></td>
            <td><?php echo htmlspecialchars((string)($alumno['curso_detalle'] ?? 'Sin curso')); ?></td>
            <td>
              <?php if ($alumno_activo): ?>
              <span class="role-badge admin" style="font-size:.72rem">Activo</span>
              <?php else: ?>
              <span class="role-badge" style="font-size:.72rem;background:#6c757d;color:#fff">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="acciones-tabla">
                <a href="../modificaciones/editar_alumno.php?id=<?php echo urlencode((string)$alumno['id_persona']); ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                <?php if ($es_admin): ?>
                <?php if ($alumno_activo): ?>
                <form method="post" action="../modificaciones/eliminar_alumno.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas inactivar este alumno?');">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)$alumno['id_persona']; ?>">
                  <button type="submit" class="btn btn-sm btn-table-del">Inactivar</button>
                </form>
                <?php else: ?>
                <form method="post" action="../modificaciones/reactivar_persona.php" class="form-inline-delete" onsubmit="return confirm('¿Reactivar este alumno?');">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)$alumno['id_persona']; ?>">
                  <input type="hidden" name="volver" value="php/listados/lista_alumnos.php">
                  <button type="submit" class="btn btn-sm btn-table-edit">Reactivar</button>
                </form>
                <?php endif; ?>
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
    <nav class="paginador-listado" aria-label="Paginación alumnos">
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
