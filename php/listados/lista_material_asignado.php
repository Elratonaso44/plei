<?php
include '../conesion.php';
include '../config.php';
include '../material_url.php';
session_start();
exigir_rol('alumno');

$id_alumno = (int)($_SESSION['id_persona'] ?? 0);
$materiales = db_fetch_all(
    $con,
    "SELECT DISTINCT mat.id_material, mat.tipo_material, mat.unidad, mat.url,
            m.id_materia, m.nombre_materia, c.grado, s.seccion, mo.moda
     FROM materiales AS mat
     INNER JOIN materias AS m ON m.id_materia = mat.id_materia
     INNER JOIN cursos AS c ON c.id_curso = m.id_curso
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     LEFT JOIN alumnos_x_materia AS axm ON axm.id_materia = m.id_materia AND axm.id_persona = ?
     LEFT JOIN alumnos_x_curso AS axc ON axc.id_curso = m.id_curso AND axc.id_persona = ?
     WHERE axm.id_persona IS NOT NULL OR axc.id_persona IS NOT NULL
     ORDER BY c.grado, s.seccion, m.nombre_materia, mat.id_material DESC",
    "ii",
    [$id_alumno, $id_alumno]
);

$por_materia = [];
foreach ($materiales as $material) {
    $clave = (int)$material['id_materia'];
    if (!isset($por_materia[$clave])) {
        $por_materia[$clave] = [
            'titulo' => (string)$material['nombre_materia'],
            'detalle' => $material['grado'] . '° ' . $material['seccion'] . ' — ' . $material['moda'],
            'materiales' => [],
        ];
    }
    $por_materia[$clave]['materiales'][] = $material;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Mis Materiales</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
    <style>
        body { background-color: transparent; padding: 2rem; }
        .page-header { max-width: 1150px; margin: 0 auto 1.5rem; }
        .page-header h2 { font-family:'Outfit',sans-serif; font-weight:800; font-size:1.6rem; color:#1a1b2e; display:flex; align-items:center; gap:0.6rem; }
        .subject-group { max-width:1150px; margin:0 auto 2rem; }
        .subject-label {
            font-family:'Outfit',sans-serif; font-size:0.82rem; font-weight:700;
            letter-spacing:0.1em; text-transform:uppercase; color:var(--accent);
            padding:0.5rem 0; margin-bottom:1rem;
            border-bottom:2px solid var(--accent-soft);
            display:flex; align-items:center; gap:0.5rem;
            flex-wrap: wrap;
        }
        .material-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:1rem; }
        .mat-card {
            background:var(--white); border-radius:var(--radius-lg);
            padding:1.25rem; box-shadow:var(--shadow-xs);
            border:1.5px solid var(--glass-border);
            transition:all 0.25s var(--ease-bounce);
            display:flex; flex-direction:column; gap:0.6rem;
        }
        .mat-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-sm); border-color:var(--accent-light); }
        .mat-tipo {
            display:inline-flex; align-items:center; gap:0.4rem;
            font-size:0.72rem; font-weight:700; letter-spacing:0.06em;
            text-transform:uppercase; color:var(--accent);
            background:var(--accent-soft); padding:0.2rem 0.6rem; border-radius:999px; width:fit-content;
        }
        .mat-unidad { font-size:0.85rem; color:var(--text-muted); flex:1; }
        .mat-btn-ver {
            display:inline-flex; align-items:center; gap:0.4rem;
            padding:0.5rem 1rem; background:#1a1b2e; color:#fff;
            border-radius:var(--radius-sm); font-family:'Outfit',sans-serif;
            font-weight:600; font-size:0.85rem; text-decoration:none;
            transition:all 0.2s var(--ease); border:none; margin-top:auto;
        }
        .mat-btn-ver:hover { background:var(--purple-700); color:#fff; transform:translateY(-1px); }
        .empty-state {
            max-width:500px; margin:4rem auto; text-align:center;
            color:var(--text-muted);
        }
        .empty-state i { font-size:3.5rem; display:block; margin-bottom:1rem; color:rgba(255,255,255,0.15); }
    </style>
</head>
<body>
    <div class="page-header">
        <h2><i class="bi bi-journals"></i> Materiales de tus profesores</h2>
    </div>

    <?php if (empty($por_materia)): ?>
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h5 style="font-family:Outfit,sans-serif;font-weight:700;color:#1a1b2e">Todavía no hay materiales</h5>
        <p>Cuando tus docentes suban materiales, van a aparecer acá organizados por materia.</p>
    </div>
    <?php else: ?>
    <?php foreach ($por_materia as $materia_data): $mats = $materia_data['materiales']; ?>
    <div class="subject-group">
        <div class="subject-label">
            <i class="bi bi-bookmark-fill"></i>
            <?php echo htmlspecialchars($materia_data['titulo']); ?>
            <span style="font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0">
                — <?php echo htmlspecialchars($materia_data['detalle']); ?>
            </span>
            <span style="font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0">
                — <?php echo count($mats); ?> recurso<?php echo count($mats) > 1 ? 's' : ''; ?>
            </span>
        </div>
        <div class="material-grid">
        <?php foreach ($mats as $mat): ?>
        <?php
        $url_material_actual = (string)$mat['url'];
        $ext = extension_material_desde_url($url_material_actual);
        $tipo = strtolower((string)$mat['tipo_material']);
        if ($ext === 'pdf' || str_contains($tipo, 'pdf')) {
            $ic = 'bi-file-earmark-pdf-fill';
        } elseif (in_array($ext, ['doc', 'docx'], true) || str_contains($tipo, 'word')) {
            $ic = 'bi-file-earmark-word-fill';
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true) || str_contains($tipo, 'imagen')) {
            $ic = 'bi-file-earmark-image-fill';
        } elseif (str_contains($tipo, 'video')) {
            $ic = 'bi-camera-video-fill';
        } elseif (str_contains($tipo, 'link') || str_contains($tipo, 'enlace')) {
            $ic = 'bi-link-45deg';
        } else {
            $ic = 'bi-file-earmark-fill';
        }
        $es_local = es_ruta_local_material_valida($url_material_actual);
        $url_valida = material_url_es_valida($url_material_actual);
        $es_previsualizable = $es_local && material_local_es_previsualizable($url_material_actual);
        $href = htmlspecialchars(url_material($url_material_actual, false, (int)$mat['id_material']));
        $href_descarga = htmlspecialchars(url_material($url_material_actual, true, (int)$mat['id_material']));
        $titulo_preview = htmlspecialchars(((string)$materia_data['titulo']) . ' - ' . ((string)$mat['tipo_material']));
        ?>
        <div class="mat-card">
            <span class="mat-tipo"><i class="bi <?php echo $ic; ?>"></i><?php echo htmlspecialchars($mat['tipo_material']); ?></span>
            <?php if (!empty($mat['unidad'])): ?>
            <div class="mat-unidad"><i class="bi bi-layers" style="margin-right:.3rem"></i><?php echo htmlspecialchars($mat['unidad']); ?></div>
            <?php endif; ?>
            <?php if ($url_valida): ?>
            <?php if ($es_previsualizable): ?>
            <a href="<?php echo $href; ?>" class="mat-btn-ver" data-material-preview="1" data-preview-kind="<?php echo htmlspecialchars($ext); ?>" data-preview-title="<?php echo $titulo_preview; ?>" data-download-url="<?php echo $href_descarga; ?>">
                <i class="bi bi-eye-fill"></i> Ver material
            </a>
            <a href="<?php echo $href_descarga; ?>" class="mat-btn-ver">
                <i class="bi bi-download"></i> Descargar
            </a>
            <?php elseif (!$es_local): ?>
            <a href="<?php echo $href; ?>" class="mat-btn-ver" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> Abrir enlace
            </a>
            <?php else: ?>
            <a href="<?php echo $href_descarga; ?>" class="mat-btn-ver">
                <i class="bi bi-download"></i> Descargar archivo
            </a>
            <?php endif; ?>
            <?php else: ?>
            <span class="mat-btn-ver" style="opacity:.6;cursor:not-allowed">
                <i class="bi bi-exclamation-triangle-fill"></i> Material no disponible
            </span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div style="max-width:1150px;margin:1rem auto;text-align:right">
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
    <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/material-viewer.js"></script>
</body>
</html>
