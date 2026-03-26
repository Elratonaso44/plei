<?php
include '../conesion.php';
include '../config.php';
include './helpers.php';
session_start();
exigir_rol(['administrador', 'docente']);

if (!boletin_modulo_disponible($con)) {
    http_response_code(500);
    exit('El modulo de boletin no esta disponible.');
}

$id_docente = (int)($_SESSION['id_persona'] ?? 0);
$tipos = obtener_tipos_usuario($con, $id_docente);
$es_admin = in_array('administrador', $tipos, true);

$ciclo_activo = boletin_ciclo_activo($con);
$id_ciclo = (int)($ciclo_activo['id_ciclo'] ?? 0);

$opciones = [];
if ($id_ciclo > 0) {
    if ($es_admin && !in_array('docente', $tipos, true)) {
        $opciones = [];
    } else {
        $opciones = db_fetch_all(
            $con,
            "SELECT DISTINCT cp.id_periodo, cp.id_curso, cp.estado,
                    bp.nombre AS periodo_nombre, bp.orden AS periodo_orden,
                    m.id_materia, m.nombre_materia,
                    c.grado, s.seccion, mo.moda
             FROM boletin_curso_periodo AS cp
             INNER JOIN boletin_periodos AS bp ON bp.id_periodo = cp.id_periodo
             INNER JOIN materias AS m ON m.id_curso = cp.id_curso
             INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
             INNER JOIN cursos AS c ON c.id_curso = cp.id_curso
             INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
             INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
             WHERE cp.estado = 'carga_docente'
               AND bp.id_ciclo = ?
               AND bp.activo = 1
               AND dm.id_persona = ?
             ORDER BY bp.orden ASC, c.grado ASC, s.seccion ASC, m.nombre_materia ASC",
            'ii',
            [$id_ciclo, $id_docente]
        );
    }
}

$mensaje_tipo = strtolower(trim((string)($_GET['estado'] ?? '')));
if (!in_array($mensaje_tipo, ['ok', 'err'], true)) {
    $mensaje_tipo = '';
}
$mensaje_texto = trim((string)($_GET['msg'] ?? ''));

$id_periodo_sel = (int)($_REQUEST['id_periodo'] ?? 0);
$id_curso_sel = (int)($_REQUEST['id_curso'] ?? 0);
$id_materia_sel = (int)($_REQUEST['id_materia'] ?? 0);

if ($id_periodo_sel <= 0 && $opciones !== []) {
    $id_periodo_sel = (int)$opciones[0]['id_periodo'];
    $id_curso_sel = (int)$opciones[0]['id_curso'];
    $id_materia_sel = (int)$opciones[0]['id_materia'];
}

$permiso_seleccion = false;
foreach ($opciones as $op) {
    if ((int)$op['id_periodo'] === $id_periodo_sel
        && (int)$op['id_curso'] === $id_curso_sel
        && (int)$op['id_materia'] === $id_materia_sel) {
        $permiso_seleccion = true;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = trim((string)($_POST['accion'] ?? ''));
    if ($accion === 'guardar_notas') {
        $id_periodo_sel = (int)($_POST['id_periodo'] ?? 0);
        $id_curso_sel = (int)($_POST['id_curso'] ?? 0);
        $id_materia_sel = (int)($_POST['id_materia'] ?? 0);

        $permiso_seleccion = false;
        foreach ($opciones as $op) {
            if ((int)$op['id_periodo'] === $id_periodo_sel
                && (int)$op['id_curso'] === $id_curso_sel
                && (int)$op['id_materia'] === $id_materia_sel) {
                $permiso_seleccion = true;
                break;
            }
        }

        if (!$permiso_seleccion) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No tenes permisos para cargar notas en esa seleccion.';
        } else {
            $alumnos = boletin_alumnos_esperados_materia_docente($con, $id_curso_sel, $id_materia_sel, $id_docente);
            $ids_alumnos = array_map(static fn($a) => (int)$a['id_persona'], $alumnos);
            $notas_post = (array)($_POST['nota'] ?? []);

            $guardadas = 0;
            $errores = [];

            mysqli_begin_transaction($con);
            try {
                $estado_bloqueado = boletin_obtener_curso_periodo_for_update($con, $id_curso_sel, $id_periodo_sel);
                if (!$estado_bloqueado || (string)($estado_bloqueado['estado'] ?? '') !== 'carga_docente') {
                    throw new RuntimeException('La carga docente está cerrada para ese curso/período.');
                }
                $periodo_tx = boletin_periodo_por_id($con, $id_periodo_sel);
                if (!$periodo_tx || (int)($periodo_tx['activo'] ?? 0) !== 1 || (int)($periodo_tx['id_ciclo'] ?? 0) !== $id_ciclo) {
                    throw new RuntimeException('El período seleccionado no está habilitado para carga.');
                }

                $notas_previas = [];
                if ($ids_alumnos !== []) {
                    $placeholders = implode(',', array_fill(0, count($ids_alumnos), '?'));
                    $tipos_prev = 'iii' . str_repeat('i', count($ids_alumnos));
                    $params_prev = array_merge([$id_periodo_sel, $id_curso_sel, $id_materia_sel], $ids_alumnos);
                    $filas_prev = db_fetch_all(
                        $con,
                        "SELECT id_alumno, nota_num, sigla
                         FROM boletin_notas
                         WHERE id_periodo = ? AND id_curso = ? AND id_materia = ?
                           AND id_alumno IN ($placeholders)",
                        $tipos_prev,
                        $params_prev
                    );
                    foreach ($filas_prev as $fp) {
                        $notas_previas[(int)$fp['id_alumno']] = [
                            'nota' => (int)$fp['nota_num'],
                            'sigla' => (string)$fp['sigla'],
                        ];
                    }
                }
                $cambios_audit = [];

                $stmt = mysqli_prepare(
                    $con,
                    "INSERT INTO boletin_notas
                        (id_periodo, id_curso, id_materia, id_alumno, id_docente, nota_num, sigla)
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        id_docente = VALUES(id_docente),
                        nota_num = VALUES(nota_num),
                        sigla = VALUES(sigla),
                        actualizado_en = CURRENT_TIMESTAMP"
                );
                if (!$stmt) {
                    throw new RuntimeException('No se pudo preparar el guardado de notas.');
                }

                foreach ($ids_alumnos as $id_alumno) {
                    $raw = trim((string)($notas_post[$id_alumno] ?? ''));
                    if ($raw === '') {
                        continue;
                    }
                    if (!preg_match('/^\d+$/', $raw)) {
                        $errores[] = 'Alumno ' . $id_alumno . ': nota no numerica.';
                        continue;
                    }
                    $nota = (int)$raw;
                    if ($nota < 1 || $nota > 10) {
                        $errores[] = 'Alumno ' . $id_alumno . ': nota fuera de rango.';
                        continue;
                    }
                    $sigla = boletin_calcular_sigla($nota);
                    mysqli_stmt_bind_param($stmt, 'iiiiiis', $id_periodo_sel, $id_curso_sel, $id_materia_sel, $id_alumno, $id_docente, $nota, $sigla);
                    if (!mysqli_stmt_execute($stmt)) {
                        $errores[] = 'Alumno ' . $id_alumno . ': no se pudo guardar.';
                        continue;
                    }
                    $prev = $notas_previas[$id_alumno] ?? null;
                    $nota_prev = $prev['nota'] ?? null;
                    $sigla_prev = $prev['sigla'] ?? null;
                    if ($nota_prev !== $nota || $sigla_prev !== $sigla) {
                        $cambios_audit[] = [
                            'id_alumno' => $id_alumno,
                            'nota_anterior' => $nota_prev,
                            'sigla_anterior' => $sigla_prev,
                            'nota_nueva' => $nota,
                            'sigla_nueva' => $sigla,
                        ];
                    }
                    $guardadas++;
                }

                mysqli_stmt_close($stmt);

                if ($errores !== []) {
                    throw new RuntimeException('Se guardaron ' . $guardadas . ' notas, pero hubo errores: ' . implode(' | ', $errores));
                }

                foreach ($cambios_audit as $cambio) {
                    registrar_auditoria_boletin($con, [
                        'tipo_evento' => 'boletin_nota_actualizada',
                        'entidad' => 'boletin_notas',
                        'id_actor' => $id_docente,
                        'id_curso' => $id_curso_sel,
                        'id_periodo' => $id_periodo_sel,
                        'id_materia' => $id_materia_sel,
                        'id_alumno' => (int)$cambio['id_alumno'],
                        'id_docente' => $id_docente,
                        'payload' => $cambio,
                    ]);
                }

                mysqli_commit($con);
                $mensaje_tipo = 'ok';
                $mensaje_texto = 'Notas guardadas correctamente. Total actualizadas: ' . $guardadas . '.';
            } catch (Throwable $e) {
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    mysqli_stmt_close($stmt);
                }
                mysqli_rollback($con);
                $mensaje_tipo = 'err';
                $mensaje_texto = $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mensaje_texto !== '') {
    $estado_redir = $mensaje_tipo === 'ok' ? 'ok' : 'err';
    $params_redir = [
        'id_periodo' => $id_periodo_sel,
        'id_curso' => $id_curso_sel,
        'id_materia' => $id_materia_sel,
        'estado' => $estado_redir,
        'msg' => $mensaje_texto,
    ];
    redirigir('php/boletin/docente_boletin.php?' . http_build_query($params_redir));
}

$alumnos_materia = [];
$notas_actuales = [];
$seleccion_texto = '';

if ($permiso_seleccion) {
    $alumnos_materia = boletin_alumnos_esperados_materia_docente($con, $id_curso_sel, $id_materia_sel, $id_docente);
    $filas_notas = db_fetch_all(
        $con,
        "SELECT id_alumno, nota_num, sigla
         FROM boletin_notas
         WHERE id_periodo = ? AND id_curso = ? AND id_materia = ?",
        'iii',
        [$id_periodo_sel, $id_curso_sel, $id_materia_sel]
    );
    foreach ($filas_notas as $n) {
        $notas_actuales[(int)$n['id_alumno']] = [
            'nota' => (int)$n['nota_num'],
            'sigla' => (string)$n['sigla'],
        ];
    }

    foreach ($opciones as $op) {
        if ((int)$op['id_periodo'] === $id_periodo_sel
            && (int)$op['id_curso'] === $id_curso_sel
            && (int)$op['id_materia'] === $id_materia_sel) {
            $seleccion_texto = (string)$op['periodo_nombre']
                . ' - ' . $op['nombre_materia']
                . ' - ' . $op['grado'] . '° ' . $op['seccion'] . ' (' . $op['moda'] . ')';
            break;
        }
    }
}

$publicados_docente = [];
if ($id_ciclo > 0) {
    $publicados_docente = db_fetch_all(
        $con,
        "SELECT DISTINCT cp.id_periodo, cp.id_curso, cp.version_publicada,
                bp.nombre AS periodo_nombre, bp.orden AS periodo_orden,
                c.grado, s.seccion, mo.moda
         FROM boletin_curso_periodo AS cp
         INNER JOIN boletin_periodos AS bp ON bp.id_periodo = cp.id_periodo
         INNER JOIN materias AS m ON m.id_curso = cp.id_curso
         INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
         INNER JOIN cursos AS c ON c.id_curso = cp.id_curso
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         WHERE cp.estado = 'publicado'
           AND bp.id_ciclo = ?
           AND dm.id_persona = ?
         ORDER BY bp.orden DESC, c.grado ASC, s.seccion ASC",
        'ii',
        [$id_ciclo, $id_docente]
    );
}

$pub_periodo_sel = (int)($_GET['pub_periodo'] ?? 0);
$pub_curso_sel = (int)($_GET['pub_curso'] ?? 0);
if ($pub_periodo_sel <= 0 && $publicados_docente !== []) {
    $pub_periodo_sel = (int)$publicados_docente[0]['id_periodo'];
    $pub_curso_sel = (int)$publicados_docente[0]['id_curso'];
}
$permiso_pub_sel = false;
foreach ($publicados_docente as $pub) {
    if ((int)$pub['id_periodo'] === $pub_periodo_sel && (int)$pub['id_curso'] === $pub_curso_sel) {
        $permiso_pub_sel = true;
        break;
    }
}
$alumnos_pub = [];
if ($permiso_pub_sel) {
    $filtro_activo_alumnos_pub = condicion_persona_activa($con, 'p');
    $alumnos_pub = db_fetch_all(
        $con,
        "SELECT p.id_persona, p.apellido, p.nombre, p.dni
         FROM alumnos_x_curso AS axc
         INNER JOIN personas AS p ON p.id_persona = axc.id_persona
         INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
         INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
         WHERE axc.id_curso = ? AND LOWER(tp.tipo) = 'alumno'
           $filtro_activo_alumnos_pub
         ORDER BY p.apellido ASC, p.nombre ASC",
        'i',
        [$pub_curso_sel]
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Boletin digital docente</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-journal-bookmark-fill"></i> Boletin digital</h2>

    <?php if ($mensaje_texto !== ''): ?>
    <div class="<?php echo $mensaje_tipo === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
        <i class="bi <?php echo $mensaje_tipo === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
        <?php echo htmlspecialchars($mensaje_texto); ?>
    </div>
    <?php endif; ?>

    <?php if ($id_ciclo <= 0): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i> No hay ciclo activo.</div>
    <?php endif; ?>

    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-12">
            <label class="form-label">Selecciona periodo + materia habilitada</label>
            <select class="form-select" onchange="if(this.value){location.href=this.value}">
                <?php if ($opciones === []): ?>
                    <option value="">No hay materias habilitadas para carga</option>
                <?php else: ?>
                    <?php foreach ($opciones as $op): ?>
                        <?php
                        $url = '?id_periodo=' . (int)$op['id_periodo']
                            . '&id_curso=' . (int)$op['id_curso']
                            . '&id_materia=' . (int)$op['id_materia'];
                        $sel = ((int)$op['id_periodo'] === $id_periodo_sel
                            && (int)$op['id_curso'] === $id_curso_sel
                            && (int)$op['id_materia'] === $id_materia_sel);
                        ?>
                        <option value="<?php echo htmlspecialchars($url); ?>" <?php echo $sel ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)$op['periodo_nombre'] . ' | ' . $op['nombre_materia'] . ' | ' . $op['grado'] . '° ' . $op['seccion'] . ' (' . $op['moda'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </form>

    <?php if ($permiso_seleccion): ?>
    <div class="resultado-listado-meta mb-3">
        Carga actual: <strong><?php echo htmlspecialchars($seleccion_texto); ?></strong>
    </div>

    <form method="post">
        <?php campo_csrf(); ?>
        <input type="hidden" name="accion" value="guardar_notas">
        <input type="hidden" name="id_periodo" value="<?php echo (int)$id_periodo_sel; ?>">
        <input type="hidden" name="id_curso" value="<?php echo (int)$id_curso_sel; ?>">
        <input type="hidden" name="id_materia" value="<?php echo (int)$id_materia_sel; ?>">

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Alumno</th>
                        <th>Nota (1-10)</th>
                        <th>Sigla actual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($alumnos_materia === []): ?>
                    <tr><td colspan="4" class="text-center py-4">No hay alumnos asignados para esta materia.</td></tr>
                    <?php else: ?>
                    <?php foreach ($alumnos_materia as $a): ?>
                        <?php
                        $id_al = (int)$a['id_persona'];
                        $nota_val = isset($notas_actuales[$id_al]) ? (string)$notas_actuales[$id_al]['nota'] : '';
                        $sigla = isset($notas_actuales[$id_al]) ? (string)$notas_actuales[$id_al]['sigla'] : '-';
                        ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$a['dni']); ?></td>
                        <td><?php echo htmlspecialchars((string)$a['apellido'] . ', ' . $a['nombre']); ?></td>
                        <td>
                            <input type="number" class="form-control" min="1" max="10" name="nota[<?php echo $id_al; ?>]" value="<?php echo htmlspecialchars($nota_val); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($sigla); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn-plei-submit">Guardar notas</button>
    </form>
    <?php endif; ?>

    <?php if ($publicados_docente !== []): ?>
    <div class="p-3 mt-4" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
        <h5>Boletines publicados para descargar</h5>
        <form method="get" class="row g-2 align-items-end mb-3">
            <div class="col-md-9">
                <label class="form-label">Selecciona curso + periodo publicado</label>
                <select class="form-select" onchange="if(this.value){location.href=this.value}">
                    <?php foreach ($publicados_docente as $pub): ?>
                        <?php
                        $url_pub = '?id_periodo=' . (int)$id_periodo_sel
                            . '&id_curso=' . (int)$id_curso_sel
                            . '&id_materia=' . (int)$id_materia_sel
                            . '&pub_periodo=' . (int)$pub['id_periodo']
                            . '&pub_curso=' . (int)$pub['id_curso'];
                        $sel_pub = ((int)$pub['id_periodo'] === $pub_periodo_sel && (int)$pub['id_curso'] === $pub_curso_sel);
                        ?>
                        <option value="<?php echo htmlspecialchars($url_pub); ?>" <?php echo $sel_pub ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)$pub['periodo_nombre'] . ' | ' . $pub['grado'] . '° ' . $pub['seccion'] . ' (' . $pub['moda'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($permiso_pub_sel): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Alumno</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alumnos_pub as $al): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$al['dni']); ?></td>
                        <td><?php echo htmlspecialchars((string)$al['apellido'] . ', ' . $al['nombre']); ?></td>
                        <td>
                            <a class="btn btn-sm btn-table-edit" href="<?php echo url('php/boletin/descargar_boletin.php?id_periodo=' . (int)$pub_periodo_sel . '&id_curso=' . (int)$pub_curso_sel . '&id_alumno=' . (int)$al['id_persona']); ?>">
                                Descargar PDF
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="text-end mt-3">
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
