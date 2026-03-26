<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol('administrador');

$q = trim((string)($_GET['q'] ?? ''));
$ver_inactivos = solicitud_ver_inactivos();
$pagina = max(1, (int)($_GET['page'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$filtro_sql = '';
$filtro_activo_sql = condicion_persona_activa($con, 'p', $ver_inactivos);
$expr_activo = expresion_persona_activo($con, 'p');
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

$total_row = db_fetch_one(
    $con,
    "SELECT COUNT(DISTINCT p.id_persona) AS total
     FROM personas AS p
     INNER JOIN tipo_persona_x_persona AS tp ON tp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS t ON tp.id_tipo_persona = t.id_tipo_persona
     INNER JOIN roles AS r ON r.id_rol = p.id_rol
     WHERE 1=1 $filtro_activo_sql $filtro_sql",
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

$personas = db_fetch_all(
    $con,
    "SELECT p.id_persona, p.dni, p.apellido, p.nombre, p.mail, p.usuario, r.rol, $expr_activo AS activo,
            GROUP_CONCAT(t.tipo SEPARATOR ' | ') AS tipo
     FROM personas AS p
     INNER JOIN tipo_persona_x_persona AS tp ON tp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS t ON tp.id_tipo_persona = t.id_tipo_persona
     INNER JOIN roles AS r ON r.id_rol = p.id_rol
     WHERE 1=1 $filtro_activo_sql $filtro_sql
     GROUP BY p.id_persona, p.dni, p.apellido, p.nombre, p.mail, p.usuario, r.rol
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
    <h2>Lista de personas</h2>

    <?php if ($msg !== ''): ?>
    <div class="<?php echo $estado === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
      <i class="bi <?php echo $estado === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
      <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <form method="get" class="barra-filtros-listado">
      <div class="filtro-input-wrap">
        <label for="q" class="form-label">Buscar por DNI, nombre o apellido</label>
        <input id="q" type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ej: 44123456, Juan, Pérez">
      </div>
      <div class="filtro-acciones-wrap">
        <button type="submit" class="btn-plei-submit btn-filtro">Buscar</button>
        <a href="lista_personas.php" class="btn-plei-cancel btn-filtro">Limpiar</a>
      </div>
      <label class="texto-opcional d-flex align-items-center gap-2">
        <input type="checkbox" name="inactivos" value="1" <?php echo $ver_inactivos ? 'checked' : ''; ?>>
        Ver inactivos
      </label>
    </form>

    <div class="resultado-listado-meta">
      Mostrando <?php echo count($personas); ?> de <?php echo $total; ?> resultados
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>DNI</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Tipo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($personas)): ?>
          <tr>
            <td colspan="8" class="text-center py-4">No se encontraron personas con ese criterio.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($personas as $persona): ?>
          <?php $persona_activa = (int)($persona['activo'] ?? 1) === 1; ?>
          <tr>
            <td><?php echo htmlspecialchars($persona['dni']); ?></td>
            <td><?php echo htmlspecialchars($persona['nombre']); ?></td>
            <td><?php echo htmlspecialchars($persona['apellido']); ?></td>
            <td><?php echo htmlspecialchars($persona['mail']); ?></td>
            <td><?php echo htmlspecialchars($persona['tipo']); ?></td>
            <td><?php echo htmlspecialchars($persona['rol']); ?></td>
            <td>
              <?php if ($persona_activa): ?>
              <span class="role-badge admin" style="font-size:.72rem">Activo</span>
              <?php else: ?>
              <span class="role-badge" style="font-size:.72rem;background:#6c757d;color:#fff">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="acciones-tabla">
                <a href="../modificaciones/editar_persona.php?id=<?php echo urlencode($persona['id_persona']); ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                <?php if ($persona_activa): ?>
                <form method="post" action="../modificaciones/eliminar_persona.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas inactivar esta persona?');">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)$persona['id_persona']; ?>">
                  <button type="submit" class="btn btn-sm btn-table-del">Inactivar</button>
                </form>
                <?php else: ?>
                <form method="post" action="../modificaciones/reactivar_persona.php" class="form-inline-delete" onsubmit="return confirm('¿Reactivar esta persona?');">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)$persona['id_persona']; ?>">
                  <input type="hidden" name="volver" value="php/listados/lista_personas.php">
                  <button type="submit" class="btn btn-sm btn-table-edit">Reactivar</button>
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
    <nav class="paginador-listado" aria-label="Paginación personas">
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
