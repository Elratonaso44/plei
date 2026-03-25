<?php
include "../conesion.php";
include "../config.php";
include "../material_url.php";
session_start();
exigir_rol(['administrador', 'docente']);

$id_docente = (int)($_SESSION['id_persona'] ?? 0);
$es_admin = usuario_tiene_tipo($con, $id_docente, 'administrador');

$materia_filtro = (int)($_GET['materia'] ?? 0);
$q_filtro = trim((string)($_GET['q'] ?? ''));
$pagina = max(1, (int)($_GET['page'] ?? 1));
$por_pagina = 20;
$id_material_foco = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$material_expandido_id = 0;

if ($es_admin) {
    $materias_docente = db_fetch_all(
        $con,
        "SELECT m.id_materia, m.nombre_materia, c.grado, s.seccion, mo.moda
         FROM materias AS m
         INNER JOIN cursos AS c ON c.id_curso = m.id_curso
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         ORDER BY c.grado ASC, s.seccion ASC, m.nombre_materia ASC"
    );
} else {
    $materias_docente = db_fetch_all(
        $con,
        "SELECT m.id_materia, m.nombre_materia, c.grado, s.seccion, mo.moda
         FROM materias AS m
         INNER JOIN cursos AS c ON c.id_curso = m.id_curso
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
         WHERE dm.id_persona = ?
         ORDER BY c.grado ASC, s.seccion ASC, m.nombre_materia ASC",
        "i",
        [$id_docente]
    );
}

$ids_materias_habilitadas = array_map(static fn($m) => (int)$m['id_materia'], $materias_docente);
$materias_por_id = [];
foreach ($materias_docente as $mat_mapa) {
    $materias_por_id[(int)$mat_mapa['id_materia']] = $mat_mapa;
}

$mensajes = [];
$errores = [];

if ($id_material_foco > 0) {
    if ($es_admin) {
        $material_foco = db_fetch_one(
            $con,
            "SELECT id_material, id_materia
             FROM materiales
             WHERE id_material = ?
             LIMIT 1",
            "i",
            [$id_material_foco]
        );
    } else {
        $material_foco = db_fetch_one(
            $con,
            "SELECT mat.id_material, mat.id_materia
             FROM materiales AS mat
             INNER JOIN docentes_x_materia AS dm ON dm.id_materia = mat.id_materia
             WHERE mat.id_material = ? AND dm.id_persona = ?
             LIMIT 1",
            "ii",
            [$id_material_foco, $id_docente]
        );
    }

    if (!$material_foco) {
        http_response_code($es_admin ? 404 : 403);
        exit($es_admin ? 'Material no encontrado.' : 'Acceso denegado. No tenés permisos para editar ese material.');
    }

    $materia_filtro = (int)$material_foco['id_materia'];
    $material_expandido_id = (int)$material_foco['id_material'];
    $q_filtro = '';
    $pagina = 1;
}

if ($materia_filtro > 0 && !in_array($materia_filtro, $ids_materias_habilitadas, true)) {
    http_response_code(403);
    exit('Acceso denegado. No tenés permisos para ver materiales de esa materia.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $id_mat_edit = (int)($_POST['id_material'] ?? 0);
    $tipo = trim((string)($_POST['tipoM'] ?? ''));
    $unidad = trim((string)($_POST['unidad'] ?? ''));
    $id_materia = (int)($_POST['id_materia'] ?? 0);
    $url_anterior = trim((string)($_POST['url_actual'] ?? ''));
    $url_nueva = $url_anterior;
    $ruta_local_subida_para_limpieza = '';

    $material_expandido_id = $id_mat_edit;

    if ($id_mat_edit <= 0) {
        $errores[] = "El material seleccionado no es válido.";
    }

    if (!in_array($id_materia, $ids_materias_habilitadas, true)) {
        http_response_code(403);
        exit('Acceso denegado. No tenés permisos para asignar ese material a la materia indicada.');
    }

    if (empty($errores)) {
        if ($es_admin) {
            $material_editable = db_fetch_one(
                $con,
                "SELECT id_material
                 FROM materiales
                 WHERE id_material = ?
                 LIMIT 1",
                "i",
                [$id_mat_edit]
            );
        } else {
            $material_editable = db_fetch_one(
                $con,
                "SELECT mat.id_material
                 FROM materiales AS mat
                 INNER JOIN docentes_x_materia AS dm ON dm.id_materia = mat.id_materia
                 WHERE mat.id_material = ? AND dm.id_persona = ?
                 LIMIT 1",
                "ii",
                [$id_mat_edit, $id_docente]
            );
        }

        if (!$material_editable) {
            http_response_code(403);
            exit('Acceso denegado. No tenés permisos para editar ese material.');
        }
    }

    if (empty($errores) && isset($_FILES['archivo_nuevo']) && $_FILES['archivo_nuevo']['error'] === UPLOAD_ERR_OK) {
        $extensiones_ok = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $nombre_original = $_FILES['archivo_nuevo']['name'];
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $tmp_path = $_FILES['archivo_nuevo']['tmp_name'];

        if (!in_array($extension, $extensiones_ok, true)) {
            $errores[] = "Tipo de archivo no permitido.";
        } elseif ($_FILES['archivo_nuevo']['size'] > 10 * 1024 * 1024) {
            $errores[] = "El archivo supera 10 MB.";
        } elseif (!function_exists('finfo_open')) {
            $errores[] = "El servidor no puede validar tipo MIME real de archivos (fileinfo).";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_real = $finfo ? (string)finfo_file($finfo, $tmp_path) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if (!mime_material_permitido($extension, $mime_real)) {
                $errores[] = "El tipo real del archivo no coincide con una extensión permitida.";
            }
        }

        if (empty($errores)) {
            $mat_info = $materias_por_id[$id_materia] ?? null;
            $doc_info = db_fetch_one(
                $con,
                "SELECT apellido, nombre
                 FROM personas
                 WHERE id_persona = ?
                 LIMIT 1",
                "i",
                [$id_docente]
            );

            if (!$mat_info || !$doc_info) {
                $errores[] = "No se pudo validar la materia o la cuenta docente.";
            } else {
                $doc_carp = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $doc_info['apellido'] . '_' . $doc_info['nombre']);
                $mat_carp = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $mat_info['nombre_materia'] . '_' . $mat_info['grado'] . $mat_info['seccion']);
                $carpeta = __DIR__ . '/../../materiales/' . $doc_carp . '/' . $mat_carp . '/';

                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0775, true);
                }

                $nombre_limpio = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($nombre_original, PATHINFO_FILENAME));
                $nombre_final = $nombre_limpio . '_' . time() . '.' . $extension;

                if (move_uploaded_file($_FILES['archivo_nuevo']['tmp_name'], $carpeta . $nombre_final)) {
                    $url_nueva = 'materiales/' . $doc_carp . '/' . $mat_carp . '/' . $nombre_final;
                    $ruta_local_subida_para_limpieza = $url_nueva;
                } else {
                    $errores[] = "No se pudo guardar el archivo. Verificá permisos de la carpeta materiales/.";
                }
            }
        }
    } elseif (empty($errores)) {
        $url_manual = trim((string)($_POST['url_manual'] ?? ''));
        if ($url_manual !== '') {
            if (!es_url_externa_material_valida($url_manual)) {
                $errores[] = "La URL externa debe comenzar con http:// o https://";
            } else {
                $url_nueva = $url_manual;
            }
        }
    }

    if (empty($errores)) {
        $sentencia = mysqli_prepare(
            $con,
            "UPDATE materiales
             SET tipo_material = ?, unidad = ?, url = ?, id_materia = ?
             WHERE id_material = ?"
        );

        if ($sentencia) {
            mysqli_stmt_bind_param($sentencia, "sssii", $tipo, $unidad, $url_nueva, $id_materia, $id_mat_edit);
            $ok_update = mysqli_stmt_execute($sentencia);
            $errno = mysqli_errno($con);
            mysqli_stmt_close($sentencia);

            if ($ok_update) {
                $mensajes[] = "Material actualizado correctamente. Podés seguir editando otros materiales.";

                if ($url_anterior !== '' && $url_anterior !== $url_nueva && ruta_local_material_relativa_valida($url_anterior)) {
                    eliminar_archivo_material_local_si_huerfano($con, $url_anterior, $id_mat_edit);
                }
            } elseif ($errno === 1452) {
                $errores[] = "No se pudo actualizar: la materia seleccionada no existe.";
            } else {
                $errores[] = "No se pudo actualizar el material.";

                if ($ruta_local_subida_para_limpieza !== '' && ruta_local_material_relativa_valida($ruta_local_subida_para_limpieza)) {
                    eliminar_archivo_material_local_si_huerfano($con, $ruta_local_subida_para_limpieza, 0);
                }
            }
        } else {
            $errores[] = "No se pudo actualizar el material.";

            if ($ruta_local_subida_para_limpieza !== '' && ruta_local_material_relativa_valida($ruta_local_subida_para_limpieza)) {
                eliminar_archivo_material_local_si_huerfano($con, $ruta_local_subida_para_limpieza, 0);
            }
        }
    }
}

$total_materiales = 0;
$total_paginas = 1;
$offset = 0;
$materiales = [];

if ($materia_filtro > 0) {
    $from_sql = "
        FROM materiales AS mat
        INNER JOIN materias AS m ON m.id_materia = mat.id_materia
        INNER JOIN cursos AS c ON c.id_curso = m.id_curso
        INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
        INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
    ";

    $where = ["mat.id_materia = ?"];
    $types = 'i';
    $params = [$materia_filtro];

    if (!$es_admin) {
        $from_sql .= " INNER JOIN docentes_x_materia AS dm ON dm.id_materia = mat.id_materia ";
        $where[] = "dm.id_persona = ?";
        $types .= 'i';
        $params[] = $id_docente;
    }

    if ($q_filtro !== '') {
        $like = valor_like($q_filtro);
        $where[] = "(
            mat.tipo_material LIKE ? ESCAPE '\\\\'
            OR mat.unidad LIKE ? ESCAPE '\\\\'
            OR mat.url LIKE ? ESCAPE '\\\\'
            OR CAST(mat.id_material AS CHAR) LIKE ? ESCAPE '\\\\'
        )";
        $types .= 'ssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $where_sql = ' WHERE ' . implode(' AND ', $where);

    $fila_total = db_fetch_one(
        $con,
        "SELECT COUNT(*) AS total " . $from_sql . $where_sql,
        $types,
        $params
    );

    $total_materiales = (int)($fila_total['total'] ?? 0);
    $total_paginas = max(1, (int)ceil($total_materiales / $por_pagina));
    if ($pagina > $total_paginas) {
        $pagina = $total_paginas;
    }
    $offset = ($pagina - 1) * $por_pagina;

    $order_sql = " ORDER BY mat.id_material DESC ";
    $types_listado = $types;
    $params_listado = $params;

    if ($id_material_foco > 0 && $material_expandido_id > 0) {
        $order_sql = " ORDER BY (mat.id_material = ?) DESC, mat.id_material DESC ";
        $types_listado .= 'i';
        $params_listado[] = $material_expandido_id;
    }

    $types_listado .= 'ii';
    $params_listado[] = $por_pagina;
    $params_listado[] = $offset;

    $materiales = db_fetch_all(
        $con,
        "SELECT mat.id_material, mat.tipo_material, mat.unidad, mat.url, mat.id_materia,
                m.nombre_materia, c.grado, s.seccion, mo.moda
         " . $from_sql . $where_sql . $order_sql . "
         LIMIT ? OFFSET ?",
        $types_listado,
        $params_listado
    );
}

$parametros_base = [];
if ($materia_filtro > 0) {
    $parametros_base['materia'] = $materia_filtro;
}
if ($q_filtro !== '') {
    $parametros_base['q'] = $q_filtro;
}

$url_pagina = static function (int $n) use ($parametros_base): string {
    $p = $parametros_base;
    $p['page'] = $n;
    return '?' . http_build_query($p);
};

$mostrando = count($materiales);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Editar Materiales</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
    <style>
        body { background: transparent; padding: 2rem; }
        .content-wrap { max-width: 980px; margin: 0 auto; }

        .page-header {
            margin: 0 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-header h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: #1a1b2e;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
        }

        .filtros-wrap {
            background: var(--white);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.4fr auto;
            gap: .75rem;
            align-items: end;
        }

        .filtros-actions {
            display: flex;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .btn-filtrar {
            width: auto;
            margin-top: 0;
            padding: .68rem 1.1rem;
        }

        .resultado-meta {
            font-size: .84rem;
            color: var(--text-muted);
            margin: .2rem 0 .8rem;
        }

        .empty-state {
            text-align: center;
            padding: 2.2rem 1.2rem;
            color: var(--text-muted);
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 2px dashed var(--glass-border);
        }

        .empty-state i {
            font-size: 2.1rem;
            display: block;
            margin-bottom: .6rem;
            opacity: .45;
        }

        details.material-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1.5px solid var(--glass-border);
            margin-bottom: .9rem;
            overflow: hidden;
        }

        details.material-card[open] {
            border-color: rgba(129, 140, 248, .45);
            box-shadow: 0 8px 26px rgba(0,0,0,.18);
        }

        .material-summary {
            list-style: none;
            cursor: pointer;
            padding: .9rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .8rem;
        }

        .material-summary::-webkit-details-marker { display: none; }

        .sum-left {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .sum-icon {
            color: var(--accent-light);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .sum-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: .98rem;
            color: #1a1b2e;
            line-height: 1.2;
        }

        .sum-sub {
            color: var(--text-muted);
            font-size: .82rem;
            line-height: 1.2;
            margin-top: .1rem;
        }

        .sum-right {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-shrink: 0;
        }

        .sum-badge {
            font-size: .71rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            background: var(--accent-soft);
            color: var(--accent);
            padding: .2rem .56rem;
            border-radius: 999px;
        }

        .sum-id {
            font-size: .72rem;
            color: var(--text-muted);
            background: rgba(255,255,255,.25);
            border: 1px solid var(--glass-border);
            border-radius: 999px;
            padding: .18rem .52rem;
        }

        .sum-chevron {
            color: var(--text-muted);
            transition: transform .2s ease;
        }

        details[open] .sum-chevron {
            transform: rotate(180deg);
        }

        .material-body {
            padding: .9rem 1rem 1rem;
            border-top: 1px solid var(--glass-border);
        }

        .url-actual {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(99,102,241,0.06);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--radius-sm);
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            margin-bottom: 0.85rem;
            word-break: break-all;
        }

        .url-actual i { color: var(--accent); flex-shrink: 0; }
        .url-actual a { color: var(--accent); font-weight: 600; text-decoration: none; }

        .btn-save {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            padding: .52rem 1.2rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: .88rem;
            cursor: pointer;
            transition: all 0.2s var(--ease);
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        .btn-save:hover { background: var(--purple-700); transform: translateY(-2px); }

        .alert-ok  { background:rgba(52,211,153,0.09); border:1.5px solid var(--accent-light); color:#6ee7b7; border-radius:var(--radius-md); padding:.75rem 1rem; font-weight:600; font-size:.88rem; margin-bottom:1rem; }
        .alert-err { background:#fde8e8; border:1.5px solid #f5a0a0; color:#8b1a1a; border-radius:var(--radius-md); padding:.75rem 1rem; font-weight:600; font-size:.88rem; margin-bottom:.5rem; }

        @media (max-width: 880px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            .filtros-actions {
                justify-content: flex-start;
            }
            .sum-right {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="content-wrap">
    <div class="page-header">
        <h2><i class="bi bi-pencil-square"></i> Editar mis materiales</h2>
    </div>

    <?php foreach ($mensajes as $msg): ?>
        <div class="alert-ok"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($msg); ?></div>
    <?php endforeach; ?>
    <?php foreach ($errores as $err): ?>
        <div class="alert-err"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($err); ?></div>
    <?php endforeach; ?>

    <div class="filtros-wrap">
        <form method="get" class="filtros-grid" autocomplete="off">
            <div>
                <label for="materia" class="form-label">Materia</label>
                <select id="materia" name="materia" class="form-select">
                    <option value="">Seleccioná una materia</option>
                    <?php foreach ($materias_docente as $md): ?>
                    <option value="<?php echo (int)$md['id_materia']; ?>" <?php echo $materia_filtro === (int)$md['id_materia'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($md['nombre_materia'] . ' — ' . $md['grado'] . '° ' . $md['seccion'] . ' (' . $md['moda'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="q" class="form-label">Buscar en materiales (tipo, unidad, URL o ID)</label>
                <input id="q" type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q_filtro); ?>" placeholder="Ej: PDF, Unidad 2, drive, 153">
            </div>
            <div class="filtros-actions">
                <button type="submit" class="btn-plei-submit btn-filtrar">Filtrar</button>
                <a href="editar_mi_material.php" class="btn-plei-cancel btn-filtrar">Limpiar</a>
            </div>
        </form>
    </div>

    <?php if (empty($ids_materias_habilitadas)): ?>
    <div class="empty-state">
        <i class="bi bi-lock-fill"></i>
        <strong>No tenés materias habilitadas para editar materiales.</strong><br>
        <small>Cuando tengas materias asignadas, vas a poder gestionarlas desde acá.</small>
    </div>

    <?php elseif ($materia_filtro <= 0): ?>
    <div class="empty-state">
        <i class="bi bi-funnel-fill"></i>
        <strong>Seleccioná una materia para empezar.</strong><br>
        <small>Así evitamos mostrar todo junto y podés editar más rápido.</small>
    </div>

    <?php else: ?>
    <div class="resultado-meta">
        Mostrando <?php echo $mostrando; ?> de <?php echo $total_materiales; ?> resultado(s)
        <?php if ($q_filtro !== ''): ?> para la búsqueda <strong><?php echo htmlspecialchars($q_filtro); ?></strong><?php endif; ?>.
    </div>

    <?php if (empty($materiales)): ?>
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <strong>No se encontraron materiales con ese filtro.</strong><br>
        <small>Probá con otra búsqueda o limpiá filtros.</small>
    </div>
    <?php else: ?>

    <?php foreach ($materiales as $mat):
        $url_raw = (string)$mat['url'];
        $href = htmlspecialchars(url_material($url_raw, false, (int)$mat['id_material']));
        $url_valida = material_url_es_valida($url_raw);
        $es_archivo = str_starts_with($url_raw, 'materiales/');
        $ext = strtolower(pathinfo($url_raw, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $ic = 'bi-file-earmark-pdf-fill';
        } elseif (in_array($ext, ['doc', 'docx'], true)) {
            $ic = 'bi-file-earmark-word-fill';
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            $ic = 'bi-file-earmark-image-fill';
        } elseif (!$es_archivo) {
            $ic = 'bi-link-45deg';
        } else {
            $ic = 'bi-file-earmark-fill';
        }

        $is_open = $material_expandido_id > 0 && (int)$mat['id_material'] === $material_expandido_id;
    ?>
    <details class="material-card" <?php echo $is_open ? 'open' : ''; ?>>
        <summary class="material-summary">
            <div class="sum-left">
                <i class="bi <?php echo $ic; ?> sum-icon"></i>
                <div>
                    <div class="sum-title"><?php echo htmlspecialchars($mat['nombre_materia']); ?></div>
                    <div class="sum-sub"><?php echo htmlspecialchars($mat['grado'] . '° ' . $mat['seccion'] . ' (' . $mat['moda'] . ')'); ?><?php echo $mat['unidad'] !== '' ? ' • ' . htmlspecialchars($mat['unidad']) : ''; ?></div>
                </div>
            </div>
            <div class="sum-right">
                <span class="sum-badge"><?php echo htmlspecialchars($mat['tipo_material']); ?></span>
                <span class="sum-id">#<?php echo (int)$mat['id_material']; ?></span>
                <i class="bi bi-chevron-down sum-chevron"></i>
            </div>
        </summary>

        <div class="material-body">
            <div class="url-actual">
                <i class="bi <?php echo $ic; ?>"></i>
                <span>Actual:</span>
                <?php if ($url_valida): ?>
                <a href="<?php echo $href; ?>">
                    <?php echo $es_archivo ? htmlspecialchars(basename($url_raw)) : htmlspecialchars($url_raw); ?>
                </a>
                <?php else: ?>
                <span style="color:var(--text-muted)">Material no disponible (URL legacy inválida)</span>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <?php campo_csrf(); ?>
                <input type="hidden" name="id_material" value="<?php echo (int)$mat['id_material']; ?>">
                <input type="hidden" name="url_actual" value="<?php echo htmlspecialchars($url_raw); ?>">

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <input type="text" name="tipoM" class="form-control" value="<?php echo htmlspecialchars($mat['tipo_material']); ?>" placeholder="PDF, Guía...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unidad / Tema</label>
                        <input type="text" name="unidad" class="form-control" value="<?php echo htmlspecialchars($mat['unidad']); ?>" placeholder="Ej: Unidad 2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Materia</label>
                        <select name="id_materia" class="form-select">
                            <?php foreach ($materias_docente as $md): ?>
                            <option value="<?php echo (int)$md['id_materia']; ?>" <?php echo (int)$md['id_materia'] === (int)$mat['id_materia'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($md['nombre_materia'] . ' ' . $md['grado'] . '° ' . $md['seccion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Reemplazar con nuevo archivo <span class="texto-opcional">(opcional)</span></label>
                        <input type="file" name="archivo_nuevo" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">O reemplazar con URL externa <span class="texto-opcional">(opcional)</span></label>
                        <input type="url" name="url_manual" class="form-control" placeholder="https://...">
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="bi bi-floppy-fill"></i> Guardar cambios
                </button>
            </form>
        </div>
    </details>
    <?php endforeach; ?>

    <?php if ($total_paginas > 1): ?>
    <nav class="paginador-listado" aria-label="Paginación materiales">
        <a class="btn-plei-cancel btn-pagina <?php echo $pagina <= 1 ? 'disabled' : ''; ?>" href="<?php echo $pagina <= 1 ? '#' : htmlspecialchars($url_pagina($pagina - 1)); ?>">Anterior</a>
        <span class="pagina-actual">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
        <a class="btn-plei-cancel btn-pagina <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>" href="<?php echo $pagina >= $total_paginas ? '#' : htmlspecialchars($url_pagina($pagina + 1)); ?>">Siguiente</a>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
    <?php endif; ?>

    <div class="text-end mt-2">
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">
            <i class="bi bi-arrow-left"></i> Volver al inicio
        </a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
