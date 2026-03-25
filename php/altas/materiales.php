<?php
include "../conesion.php";
include "../config.php";
include "../material_url.php";
session_start();
exigir_rol(['administrador', 'docente']);

$id_docente = (int)($_SESSION['id_persona'] ?? 0);
$docente_info = db_fetch_one(
    $con,
    "SELECT apellido, nombre
     FROM personas
     WHERE id_persona = ?
     LIMIT 1",
    "i",
    [$id_docente]
);

if (!$docente_info) {
    redirigir('home.php');
}

$docente_carpeta = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $docente_info['apellido'] . '_' . $docente_info['nombre']);
$es_admin = usuario_tiene_tipo($con, $id_docente, 'administrador');
if ($es_admin) {
    $materias = db_fetch_all(
        $con,
        "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, c.grado, s.seccion, mo.moda
         FROM materias AS m
         INNER JOIN cursos AS c ON c.id_curso = m.id_curso
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         ORDER BY m.nombre_materia"
    );
} else {
    $materias = db_fetch_all(
        $con,
        "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, c.grado, s.seccion, mo.moda
         FROM materias AS m
         INNER JOIN cursos AS c ON c.id_curso = m.id_curso
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
         WHERE dm.id_persona = ?
         ORDER BY m.nombre_materia",
        "i",
        [$id_docente]
    );
}

$mensajes = [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $id_materia = (int)($_POST['materia'] ?? 0);
    $unidad = trim((string)($_POST['unidad'] ?? ''));
    $tipo_texto = trim((string)($_POST['tipoM'] ?? ''));

    $materia_info = null;
    foreach ($materias as $m) {
        if ((int)$m['id_materia'] === $id_materia) {
            $materia_info = $m;
            break;
        }
    }

    if (!$materia_info) {
        $errores[] = "Seleccioná una materia válida.";
    } else {
        $materia_carpeta = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $materia_info['nombre_materia'] . '_' . $materia_info['grado'] . $materia_info['seccion']);
        $carpeta_destino = __DIR__ . '/../../materiales/' . $docente_carpeta . '/' . $materia_carpeta . '/';
        $carpeta_destino = rtrim($carpeta_destino, '/') . '/';

        if (!is_dir($carpeta_destino)) {
            if (!mkdir($carpeta_destino, 0775, true)) {
                $errores[] = "No se pudo subir el archivo.";
            }
        }

        $extensiones_ok = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $archivos_subidos = 0;

        if (empty($errores) && isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
            if ($finfo === false) {
                $errores[] = 'El servidor no puede validar tipo MIME real de archivos (fileinfo).';
            }

            $total = count($_FILES['archivos']['name']);
            for ($i = 0; $i < $total; $i++) {
                if (!empty($errores) && $finfo === false) {
                    break;
                }

                if ($_FILES['archivos']['error'][$i] !== UPLOAD_ERR_OK) {
                    $codigos = [
                        UPLOAD_ERR_INI_SIZE => 'El archivo supera upload_max_filesize en php.ini.',
                        UPLOAD_ERR_FORM_SIZE => 'El archivo supera MAX_FILE_SIZE del formulario.',
                        UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
                        UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal de PHP.',
                        UPLOAD_ERR_CANT_WRITE => 'PHP no puede escribir en disco.',
                    ];
                    $cod = $_FILES['archivos']['error'][$i];
                    $msg = $codigos[$cod] ?? "Error desconocido (código $cod).";
                    $errores[] = "Archivo \"{$_FILES['archivos']['name'][$i]}\": $msg";
                    continue;
                }

                $nombre_original = $_FILES['archivos']['name'][$i];
                $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                $tmp_path = $_FILES['archivos']['tmp_name'][$i];

                if (!in_array($extension, $extensiones_ok, true)) {
                    $errores[] = "\"$nombre_original\": tipo no permitido. Solo PDF, Word, JPG, PNG.";
                    continue;
                }

                $mime_real = is_string($tmp_path) && $tmp_path !== '' && $finfo ? (string)finfo_file($finfo, $tmp_path) : '';
                if (!mime_material_permitido($extension, $mime_real)) {
                    $errores[] = "\"$nombre_original\": el tipo real del archivo no coincide con una extensión permitida.";
                    continue;
                }

                if ($_FILES['archivos']['size'][$i] > 10 * 1024 * 1024) {
                    $errores[] = "\"$nombre_original\": supera el límite de 10 MB.";
                    continue;
                }

                $nombre_limpio = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($nombre_original, PATHINFO_FILENAME));
                $nombre_final = $nombre_limpio . '_' . time() . '_' . $i . '.' . $extension;
                $destino = $carpeta_destino . $nombre_final;

                if (move_uploaded_file($_FILES['archivos']['tmp_name'][$i], $destino)) {
                    $ruta_db = 'materiales/' . $docente_carpeta . '/' . $materia_carpeta . '/' . $nombre_final;
                    $tipo_final = !empty($tipo_texto) ? $tipo_texto : match ($extension) {
                        'pdf' => 'PDF',
                        'doc', 'docx' => 'Word',
                        'jpg', 'jpeg', 'png', 'gif' => 'Imagen',
                        default => strtoupper($extension)
                    };

                    $sentencia = mysqli_prepare($con, "INSERT INTO materiales (tipo_material, unidad, url, id_materia) VALUES (?, ?, ?, ?)");
                    if ($sentencia) {
                        mysqli_stmt_bind_param($sentencia, "sssi", $tipo_final, $unidad, $ruta_db, $id_materia);
                        mysqli_stmt_execute($sentencia);
                        mysqli_stmt_close($sentencia);
                    }
                    $archivos_subidos++;
                } else {
                    $errores[] = "No se pudo guardar \"$nombre_original\". Verificá permisos de la carpeta materiales/.";
                }
            }

            if ($finfo) {
                finfo_close($finfo);
            }
        }

        $url_manual = trim((string)($_POST['url_manual'] ?? ''));
        if ($url_manual !== '') {
            if (!es_url_externa_material_valida($url_manual)) {
                $errores[] = "La URL externa debe comenzar con http:// o https://";
            } else {
                $tipo_url = !empty($tipo_texto) ? $tipo_texto : 'Enlace';
                $sentencia = mysqli_prepare($con, "INSERT INTO materiales (tipo_material, unidad, url, id_materia) VALUES (?, ?, ?, ?)");
                if ($sentencia) {
                    mysqli_stmt_bind_param($sentencia, "sssi", $tipo_url, $unidad, $url_manual, $id_materia);
                    mysqli_stmt_execute($sentencia);
                    mysqli_stmt_close($sentencia);
                }
                $archivos_subidos++;
            }
        }

        if ($archivos_subidos > 0 && empty($errores)) {
            $mensajes[] = "Se subieron $archivos_subidos material(es) correctamente.";
        } elseif ($archivos_subidos > 0) {
            $mensajes[] = "Se subieron $archivos_subidos material(es), pero hubo algunos errores.";
        } elseif (empty($errores)) {
            $errores[] = "No seleccionaste ningún archivo ni ingresaste una URL.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Subir Material</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
    <style>
        .upload-zone {
            border: 2.5px dashed var(--accent-light);
            border-radius: var(--radius-lg);
            background: rgba(99,102,241,0.06);
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s var(--ease);
            position: relative;
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: var(--accent-light);
            background: #d8f2eb;
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-zone .upload-icon {
            font-size: 2.5rem;
            color: var(--accent-light);
            margin-bottom: 0.5rem;
        }

        .upload-zone .upload-label {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: var(--accent);
        }

        .upload-zone .upload-sub {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .preview-list {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .preview-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--white);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--radius-sm);
            padding: 0.4rem 0.8rem;
            font-size: 0.83rem;
            font-weight: 500;
            color: #1a1b2e;
            animation: fadeInUp 0.2s ease both;
        }

        .preview-item i {
            color: var(--accent);
            font-size: 1rem;
        }

        .alert-ok {
            background: rgba(52,211,153,0.09);
            border: 1.5px solid var(--accent-light);
            color: #6ee7b7;
            border-radius: var(--radius-md);
            padding: 0.85rem 1.1rem;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .alert-err {
            background: #fde8e8;
            border: 1.5px solid #f5a0a0;
            color: #8b1a1a;
            border-radius: var(--radius-md);
            padding: 0.85rem 1.1rem;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .divider-or {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.25rem 0;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .divider-or::before,
        .divider-or::after {
            content: '';
            flex: 1;
            height: 1.5px;
            background: var(--glass-border);
        }
    </style>
</head>

<body class="form-page-body">
    <div class="form-card form-card-wide">

        <h2>Subir Material</h2>
        <p class="form-subtitle">
            Como: <strong><?php echo htmlspecialchars($docente_info['nombre'] . ' ' . $docente_info['apellido']); ?></strong>
        </p>

        <?php foreach ($mensajes as $msg): ?>
            <div class="alert-ok"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        <?php foreach ($errores as $err): ?>
            <div class="alert-err"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; ?>


        <form method="POST" enctype="multipart/form-data" id="formMaterial" autocomplete="off">
        <?php campo_csrf(); ?>

            <div class="mb-3">
                <label class="form-label">Materia</label>
                <select name="materia" class="form-select" required>
                    <option value="">Seleccioná una materia</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?php echo $m['id_materia']; ?>">
                            <?php echo htmlspecialchars($m['nombre_materia'] . ' — ' . $m['grado'] . '° ' . $m['seccion'] . ' (' . $m['moda'] . ') Turno ' . $m['turno']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Unidad / Tema <span class="texto-opcional">(opcional)</span></label>
                <input type="text" name="unidad" class="form-control" placeholder="Ej: Unidad 3 — Fracciones">
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción del tipo <span class="texto-opcional">(opcional)</span></label>
                <input type="text" name="tipoM" class="form-control" placeholder="Ej: Tarea, Guía, Evaluación...">
            </div>

            <div class="mb-3">
                <label class="form-label">Archivos <span class="texto-opcional">(PDF, Word, JPG, PNG — máx. 10 MB c/u)</span></label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="archivos[]" id="inputArchivos" multiple
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                    <div class="upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                    <div class="upload-label">Arrastrá archivos acá o hacé click para elegir</div>
                    <div class="upload-sub">PDF · Word · JPG · PNG — Podés seleccionar varios a la vez</div>
                </div>
                <div class="preview-list" id="previewList"></div>
            </div>

            <div class="divider-or">O también podés agregar un enlace</div>

            <div class="mb-4">
                <label class="form-label">URL externa <span class="texto-opcional">(Google Drive, YouTube...)</span></label>
                <input type="url" name="url_manual" class="form-control" placeholder="https://...">
            </div>

            <button type="submit" class="btn-plei-submit">
                <i class="bi bi-cloud-upload-fill me-2"></i>Subir materiales
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel">Volver al inicio</a>
        </div>
    </div>

    <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const input = document.getElementById('inputArchivos');
        const zone = document.getElementById('uploadZone');
        const preview = document.getElementById('previewList');
        const iconos = {
            pdf: 'bi-file-earmark-pdf-fill',
            doc: 'bi-file-earmark-word-fill',
            docx: 'bi-file-earmark-word-fill',
            jpg: 'bi-file-earmark-image-fill',
            jpeg: 'bi-file-earmark-image-fill',
            png: 'bi-file-earmark-image-fill',
            gif: 'bi-file-earmark-image-fill'
        };

        function renderPreview(files) {
            preview.innerHTML = '';
            Array.from(files).forEach(f => {
                const ext = f.name.split('.').pop().toLowerCase();
                const ic = iconos[ext] || 'bi-file-earmark-fill';
                const kb = f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.round(f.size / 1024) + ' KB';
                const el = document.createElement('div');
                el.className = 'preview-item';
                const icon = document.createElement('i');
                icon.className = 'bi ' + ic;
                const name = document.createElement('span');
                name.textContent = f.name;
                const size = document.createElement('small');
                size.style.color = 'var(--text-muted)';
                size.textContent = kb;
                el.appendChild(icon);
                el.appendChild(name);
                el.appendChild(size);
                preview.appendChild(el);
            });
        }
        input.addEventListener('change', () => renderPreview(input.files));
        ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => {
            e.preventDefault();
            zone.classList.add('drag-over');
        }));
        ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => {
            e.preventDefault();
            zone.classList.remove('drag-over');
        }));
        zone.addEventListener('drop', e => {
            input.files = e.dataTransfer.files;
            renderPreview(input.files);
        });
    </script>
</body>

</html>
