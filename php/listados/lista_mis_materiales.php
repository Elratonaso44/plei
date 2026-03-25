<?php
include '../conesion.php';
include '../config.php';
include '../material_url.php';
session_start();
exigir_rol(['administrador', 'docente']);

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$id_materia = (int)($_GET['id'] ?? 0);
$estado = trim((string)($_GET['estado'] ?? ''));
$msg = trim((string)($_GET['msg'] ?? ''));

if ($id_materia <= 0) {
    redirigir('php/listados/lista_materiax_docente.php');
}

$es_admin = usuario_tiene_tipo($con, $id_persona, 'administrador');

if (!$es_admin) {
    $materia_habilitada = db_fetch_one(
        $con,
        "SELECT 1
         FROM docentes_x_materia
         WHERE id_persona = ? AND id_materia = ?
         LIMIT 1",
        'ii',
        [$id_persona, $id_materia]
    );
    if (!$materia_habilitada) {
        http_response_code(403);
        exit('Acceso denegado. No tenés permisos para ver materiales de esa materia.');
    }
}

if ($es_admin) {
    $materiales_materia = db_fetch_all(
        $con,
        "SELECT mat.id_material, mat.tipo_material, mat.unidad, mat.url, mat.id_materia, m.nombre_materia
         FROM materiales AS mat
         INNER JOIN materias AS m ON m.id_materia = mat.id_materia
         WHERE m.id_materia = ?
         ORDER BY mat.id_material DESC",
        'i',
        [$id_materia]
    );
} else {
    $materiales_materia = db_fetch_all(
        $con,
        "SELECT mat.id_material, mat.tipo_material, mat.unidad, mat.url, mat.id_materia, m.nombre_materia
         FROM materiales AS mat
         INNER JOIN materias AS m ON m.id_materia = mat.id_materia
         INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
         WHERE m.id_materia = ? AND dm.id_persona = ?
         ORDER BY mat.id_material DESC",
        'ii',
        [$id_materia, $id_persona]
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Mis materiales</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-files"></i> Materiales de la materia</h2>

    <?php if ($msg !== ''): ?>
    <div class="<?php echo $estado === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
        <i class="bi <?php echo $estado === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle tabla-organizada">
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Unidad</th>
                    <th>Tipo</th>
                    <th>Archivo o enlace</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($materiales_materia)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4" style="color:var(--text-muted)">
                        No hay materiales cargados para esta materia.
                    </td>
                </tr>
            <?php else: ?>
            <?php foreach ($materiales_materia as $material): ?>
            <?php
            $url_material_actual = (string)$material['url'];
            $es_archivo_local = es_ruta_local_material_valida($url_material_actual);
            $url_valida = material_url_es_valida($url_material_actual);
            $ext = extension_material_desde_url($url_material_actual);
            $es_previsualizable = $es_archivo_local && material_local_es_previsualizable($url_material_actual);
            $enlace_ver = htmlspecialchars(url_material($url_material_actual, false, (int)$material['id_material']));
            $enlace_descargar = htmlspecialchars(url_material($url_material_actual, true, (int)$material['id_material']));
            $texto_enlace = $es_archivo_local ? basename($material['url']) : 'Ver enlace';
            $titulo_preview = htmlspecialchars(((string)$material['nombre_materia']) . ' - ' . ((string)$material['tipo_material']));
            ?>
            <tr>
                <td><?php echo htmlspecialchars($material['nombre_materia']); ?></td>
                <td><?php echo htmlspecialchars($material['unidad']); ?></td>
                <td><?php echo htmlspecialchars($material['tipo_material']); ?></td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        <?php if ($url_valida && $es_previsualizable): ?>
                        <a href="<?php echo $enlace_ver; ?>" data-material-preview="1" data-preview-kind="<?php echo htmlspecialchars($ext); ?>" data-preview-title="<?php echo $titulo_preview; ?>" data-download-url="<?php echo $enlace_descargar; ?>" style="color:var(--accent);font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem">
                            <i class="bi <?php echo $es_archivo_local ? 'bi-file-earmark-fill' : 'bi-link-45deg'; ?>"></i>
                            <?php echo htmlspecialchars($texto_enlace); ?>
                        </a>
                        <?php elseif ($url_valida && !$es_archivo_local): ?>
                        <a href="<?php echo $enlace_ver; ?>" target="_blank" rel="noopener" style="color:var(--accent);font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem">
                            <i class="bi bi-link-45deg"></i>
                            Abrir enlace externo
                        </a>
                        <?php elseif ($url_valida): ?>
                        <a href="<?php echo $enlace_descargar; ?>" style="color:var(--accent);font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem">
                            <i class="bi bi-file-earmark-fill"></i>
                            <?php echo htmlspecialchars($texto_enlace); ?>
                        </a>
                        <span style="color:var(--text-muted);font-size:.8rem">Vista previa disponible solo para PDF/JPG/PNG.</span>
                        <?php else: ?>
                        <span style="color:var(--text-muted);display:flex;align-items:center;gap:.4rem">
                            <i class="bi bi-exclamation-triangle-fill"></i> Material no disponible (URL legacy inválida)
                        </span>
                        <?php endif; ?>
                        <?php if ($es_archivo_local && $url_valida): ?>
                        <a href="<?php echo $enlace_descargar; ?>" class="btn btn-sm btn-table-edit" style="width:fit-content">Descargar</a>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="acciones-tabla">
                        <a href="../modificaciones/editar_mi_material.php?id=<?php echo urlencode($material['id_material']); ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                        <form method="post" action="../modificaciones/eliminar_material.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas eliminar este material?');">
                            <?php campo_csrf(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$material['id_material']; ?>">
                            <input type="hidden" name="volver" value="php/listados/lista_mis_materiales.php?id=<?php echo (int)$id_materia; ?>">
                            <button type="submit" class="btn btn-sm btn-table-del">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-end mt-3">
        <a href="<?php echo url('php/listados/lista_materiax_docente.php'); ?>" class="boton-volver">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/material-viewer.js"></script>
</body>
</html>
