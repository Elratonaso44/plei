<?php
include '../conesion.php';
include '../config.php';
include './helpers.php';
session_start();
exigir_rol(['administrador', 'preceptor']);

if (!boletin_modulo_disponible($con)) {
    http_response_code(500);
    exit('El modulo de boletin no esta disponible. Ejecuta la migracion primero.');
}

$id_usuario = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_usuario);
$es_admin = in_array('administrador', $tipos_usuario, true);

$mensaje_tipo = strtolower(trim((string)($_GET['estado'] ?? '')));
if (!in_array($mensaje_tipo, ['ok', 'err'], true)) {
    $mensaje_tipo = '';
}
$mensaje_texto = trim((string)($_GET['msg'] ?? ''));

if ($es_admin) {
    $cursos_disponibles = db_fetch_all(
        $con,
        "SELECT c.id_curso, c.grado, s.seccion, mo.moda
         FROM cursos AS c
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         ORDER BY c.grado ASC, s.seccion ASC"
    );
} else {
    $cursos_disponibles = db_fetch_all(
        $con,
        "SELECT c.id_curso, c.grado, s.seccion, mo.moda
         FROM cursos AS c
         INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         WHERE pc.id_persona = ?
         ORDER BY c.grado ASC, s.seccion ASC",
        'i',
        [$id_usuario]
    );
}

$ids_cursos_disponibles = array_map(static fn($c) => (int)$c['id_curso'], $cursos_disponibles);
$curso_sel = (int)($_REQUEST['curso'] ?? 0);
if ($curso_sel <= 0 && $cursos_disponibles !== []) {
    $curso_sel = (int)$cursos_disponibles[0]['id_curso'];
}

if ($curso_sel > 0 && !in_array($curso_sel, $ids_cursos_disponibles, true)) {
    http_response_code(403);
    exit('No tenes permisos para gestionar ese curso.');
}

$ciclo_activo = boletin_ciclo_activo($con);
$id_ciclo_activo = (int)($ciclo_activo['id_ciclo'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $mensaje_tipo = '';
    $mensaje_texto = '';

    $accion = trim((string)($_POST['accion'] ?? ''));
    $id_periodo = (int)($_POST['id_periodo'] ?? 0);
    $id_curso_post = (int)($_POST['id_curso'] ?? 0);
    $curso_sel = $id_curso_post > 0 ? $id_curso_post : $curso_sel;

    if ($id_ciclo_activo <= 0) {
        $mensaje_tipo = 'err';
        $mensaje_texto = 'No hay ciclo activo.';
    } elseif (!in_array($curso_sel, $ids_cursos_disponibles, true)) {
        $mensaje_tipo = 'err';
        $mensaje_texto = 'No tenes permisos para ese curso.';
    } elseif ($id_periodo <= 0) {
        $mensaje_tipo = 'err';
        $mensaje_texto = 'Periodo invalido.';
    } else {
        $periodo = boletin_periodo_por_id($con, $id_periodo);
        if (!$periodo || (int)$periodo['id_ciclo'] !== $id_ciclo_activo) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'El periodo no pertenece al ciclo activo.';
        } elseif ((int)($periodo['activo'] ?? 0) !== 1) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No se puede operar: el periodo esta desactivado.';
        } else {
            $problemas_grupo = boletin_validar_materias_con_grupos($con, $curso_sel);

            if ($accion === 'abrir') {
                if ($problemas_grupo !== []) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'No se puede abrir: faltan asignaciones de alumnos por grupo en materias de taller.';
                } else {
                    mysqli_begin_transaction($con);
                    try {
                        $estado_bloqueado = boletin_obtener_curso_periodo_for_update($con, $curso_sel, $id_periodo);
                        if (!$estado_bloqueado) {
                            throw new RuntimeException('No se pudo bloquear el estado del curso/periodo.');
                        }
                        if ((string)($estado_bloqueado['estado'] ?? 'cerrado') === 'publicado') {
                            throw new RuntimeException('Ese periodo ya fue publicado. Usa Reabrir para corregir.');
                        }

                        $periodo_tx = boletin_periodo_por_id($con, $id_periodo);
                        if (!$periodo_tx || (int)$periodo_tx['id_ciclo'] !== $id_ciclo_activo || (int)($periodo_tx['activo'] ?? 0) !== 1) {
                            throw new RuntimeException('No se puede abrir: el periodo no está activo en el ciclo actual.');
                        }

                        $ok = boletin_cambiar_estado_curso_periodo($con, $curso_sel, $id_periodo, 'carga_docente', $id_usuario);
                        if (!$ok) {
                            throw new RuntimeException('No se pudo habilitar el periodo.');
                        }
                        registrar_auditoria_boletin($con, [
                            'tipo_evento' => 'boletin_apertura_periodo',
                            'entidad' => 'boletin_curso_periodo',
                            'id_actor' => $id_usuario,
                            'id_curso' => $curso_sel,
                            'id_periodo' => $id_periodo,
                            'payload' => ['estado_nuevo' => 'carga_docente']
                        ]);
                        mysqli_commit($con);
                        $mensaje_tipo = 'ok';
                        $mensaje_texto = 'Periodo habilitado para carga docente.';
                    } catch (Throwable $e) {
                        mysqli_rollback($con);
                        $mensaje_tipo = 'err';
                        $mensaje_texto = $e->getMessage();
                    }
                }
            }

            if ($accion === 'reabrir') {
                mysqli_begin_transaction($con);
                try {
                    $estado_bloqueado = boletin_obtener_curso_periodo_for_update($con, $curso_sel, $id_periodo);
                    if (!$estado_bloqueado) {
                        throw new RuntimeException('No se pudo bloquear el estado del curso/periodo.');
                    }
                    if ((string)($estado_bloqueado['estado'] ?? '') !== 'publicado') {
                        throw new RuntimeException('Solo se puede reabrir un periodo publicado.');
                    }

                    $periodo_tx = boletin_periodo_por_id($con, $id_periodo);
                    if (!$periodo_tx || (int)$periodo_tx['id_ciclo'] !== $id_ciclo_activo || (int)($periodo_tx['activo'] ?? 0) !== 1) {
                        throw new RuntimeException('No se puede reabrir: el periodo no está activo en el ciclo actual.');
                    }

                    $ok = boletin_cambiar_estado_curso_periodo($con, $curso_sel, $id_periodo, 'carga_docente', $id_usuario);
                    if (!$ok) {
                        throw new RuntimeException('No se pudo reabrir el periodo.');
                    }
                    registrar_auditoria_boletin($con, [
                        'tipo_evento' => 'boletin_reapertura_periodo',
                        'entidad' => 'boletin_curso_periodo',
                        'id_actor' => $id_usuario,
                        'id_curso' => $curso_sel,
                        'id_periodo' => $id_periodo,
                        'payload' => ['estado_nuevo' => 'carga_docente']
                    ]);
                    mysqli_commit($con);
                    $mensaje_tipo = 'ok';
                    $mensaje_texto = 'Periodo reabierto para correcciones.';
                } catch (Throwable $e) {
                    mysqli_rollback($con);
                    $mensaje_tipo = 'err';
                    $mensaje_texto = $e->getMessage();
                }
            }

            if ($accion === 'publicar') {
                if ($problemas_grupo !== []) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'No se puede publicar: hay materias con grupos sin asignacion completa.';
                } else {
                    mysqli_begin_transaction($con);
                    try {
                        $estado_bloqueado = boletin_obtener_curso_periodo_for_update($con, $curso_sel, $id_periodo);
                        if (!$estado_bloqueado) {
                            throw new RuntimeException('No se pudo bloquear el estado del curso/periodo.');
                        }
                        if ((string)($estado_bloqueado['estado'] ?? '') !== 'carga_docente') {
                            throw new RuntimeException('El periodo debe estar en carga docente para publicarse.');
                        }

                        $periodo_tx = boletin_periodo_por_id($con, $id_periodo);
                        if (!$periodo_tx || (int)$periodo_tx['id_ciclo'] !== $id_ciclo_activo || (int)($periodo_tx['activo'] ?? 0) !== 1) {
                            throw new RuntimeException('No se puede publicar: el periodo no está activo en el ciclo actual.');
                        }

                        $resumen = boletin_resumen_completitud_curso_periodo($con, $curso_sel, $id_periodo);
                        if (!(bool)$resumen['completo']) {
                            throw new RuntimeException('No se puede publicar: faltan notas por cargar.');
                        }

                        $ok_estado = boletin_cambiar_estado_curso_periodo($con, $curso_sel, $id_periodo, 'publicado', $id_usuario);
                        if (!$ok_estado) {
                            throw new RuntimeException('No se pudo marcar como publicado.');
                        }
                        $estado_post = boletin_obtener_curso_periodo($con, $curso_sel, $id_periodo);
                        $version = (int)($estado_post['version_publicada'] ?? 0);
                        if ($version <= 0) {
                            throw new RuntimeException('No se pudo obtener la version de publicacion.');
                        }

                        $pdfs = boletin_generar_pdfs_publicacion($con, $id_periodo, $curso_sel, $version, $id_usuario);
                        if ($pdfs['errores'] !== []) {
                            throw new RuntimeException('Se publico, pero fallaron algunos PDFs: alumnos ' . implode(', ', $pdfs['errores']));
                        }

                        registrar_auditoria_boletin($con, [
                            'tipo_evento' => 'boletin_publicacion_periodo',
                            'entidad' => 'boletin_curso_periodo',
                            'id_actor' => $id_usuario,
                            'id_curso' => $curso_sel,
                            'id_periodo' => $id_periodo,
                            'payload' => [
                                'version_publicada' => $version,
                                'pdf_generados' => (int)$pdfs['generados'],
                            ]
                        ]);

                        mysqli_commit($con);
                        $mensaje_tipo = 'ok';
                        $mensaje_texto = 'Periodo publicado. PDFs generados: ' . (int)$pdfs['generados'] . '.';
                    } catch (Throwable $e) {
                        mysqli_rollback($con);
                        $mensaje_tipo = 'err';
                        $mensaje_texto = $e->getMessage();
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mensaje_texto !== '') {
    $estado_redir = $mensaje_tipo === 'ok' ? 'ok' : 'err';
    $params = [
        'curso' => $curso_sel,
        'estado' => $estado_redir,
        'msg' => $mensaje_texto,
    ];
    redirigir('php/boletin/preceptor_boletines.php?' . http_build_query($params));
}

$periodos_ciclo = $id_ciclo_activo > 0 ? boletin_periodos_por_ciclo($con, $id_ciclo_activo, true) : [];
$curso_info = $curso_sel > 0 ? boletin_descripcion_curso($con, $curso_sel) : null;
$problemas_grupo_curso = $curso_sel > 0 ? boletin_validar_materias_con_grupos($con, $curso_sel) : [];
$periodo_detalle = (int)($_GET['periodo_detalle'] ?? 0);
$resumen_detalle = ($curso_sel > 0 && $periodo_detalle > 0) ? boletin_resumen_completitud_curso_periodo($con, $curso_sel, $periodo_detalle) : null;
$estado_detalle = ($curso_sel > 0 && $periodo_detalle > 0) ? boletin_obtener_curso_periodo($con, $curso_sel, $periodo_detalle) : null;
$alumnos_descarga = [];
if ($estado_detalle && (string)$estado_detalle['estado'] === 'publicado') {
    $filtro_activo_alumnos = condicion_persona_activa($con, 'p');
    $alumnos_descarga = db_fetch_all(
        $con,
        "SELECT p.id_persona, p.apellido, p.nombre, p.dni
         FROM alumnos_x_curso AS axc
         INNER JOIN personas AS p ON p.id_persona = axc.id_persona
         INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
         INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
         WHERE axc.id_curso = ? AND LOWER(tp.tipo) = 'alumno'
           $filtro_activo_alumnos
         ORDER BY p.apellido ASC, p.nombre ASC",
        'i',
        [$curso_sel]
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Boletines por curso</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-clipboard2-check"></i> Boletines por curso</h2>

    <?php if ($mensaje_texto !== ''): ?>
    <div class="<?php echo $mensaje_tipo === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
        <i class="bi <?php echo $mensaje_tipo === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
        <?php echo htmlspecialchars($mensaje_texto); ?>
    </div>
    <?php endif; ?>

    <?php if (!$ciclo_activo): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No hay ciclo activo. Pedi a administracion que cree/active uno.
    </div>
    <?php endif; ?>

    <form method="get" class="barra-filtros-listado mb-3">
        <div class="filtro-input-wrap">
            <label for="curso" class="form-label">Selecciona curso</label>
            <select id="curso" name="curso" class="form-select" required>
                <?php foreach ($cursos_disponibles as $curso): ?>
                <option value="<?php echo (int)$curso['id_curso']; ?>" <?php echo (int)$curso_sel === (int)$curso['id_curso'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string)$curso['grado'] . '° ' . $curso['seccion'] . ' (' . $curso['moda'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtro-acciones-wrap">
            <button type="submit" class="btn-plei-submit btn-filtro">Ver estado</button>
        </div>
    </form>

    <?php if ($curso_info): ?>
    <div class="resultado-listado-meta mb-3">
        Curso seleccionado: <strong><?php echo htmlspecialchars(boletin_nombre_curso_corto($curso_info)); ?></strong>
    </div>
    <div class="mb-3 text-end">
        <a class="btn btn-sm btn-table-edit" href="<?php echo url('php/boletin/preceptor_complemento_anual.php?curso=' . $curso_sel); ?>">
            Cargar cierre/complemento anual
        </a>
    </div>
    <?php endif; ?>

    <?php if ($problemas_grupo_curso !== []): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Hay materias con grupos incompletos. No se podra abrir ni publicar hasta corregir asignaciones.
        <a href="<?php echo url('php/boletin/preceptor_asignar_grupos.php?curso=' . $curso_sel); ?>" class="btn btn-sm btn-table-edit ms-2">Corregir grupos</a>
    </div>
    <?php endif; ?>

    <div class="table-responsive mb-3">
        <table class="table table-bordered table-hover align-middle tabla-organizada">
            <thead>
                <tr>
                    <th>Periodo</th>
                    <th>Estado</th>
                    <th>Completitud</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($periodos_ciclo === []): ?>
                <tr><td colspan="4" class="text-center py-4">No hay periodos activos en el ciclo.</td></tr>
            <?php else: ?>
                <?php foreach ($periodos_ciclo as $periodo): ?>
                    <?php
                    $id_periodo = (int)$periodo['id_periodo'];
                    $estado = $curso_sel > 0 ? boletin_asegurar_curso_periodo($con, $curso_sel, $id_periodo) : null;
                    $estado_txt = (string)($estado['estado'] ?? 'cerrado');
                    $resumen = $curso_sel > 0 ? boletin_resumen_completitud_curso_periodo($con, $curso_sel, $id_periodo) : ['materias_completas' => 0, 'total_materias' => 0, 'faltantes_total' => 0];
                    ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars((string)$periodo['nombre']); ?>
                        <div style="color:var(--text-muted);font-size:.85rem">Orden <?php echo (int)$periodo['orden']; ?></div>
                    </td>
                    <td>
                        <?php if ($estado_txt === 'publicado'): ?>
                            <span class="role-badge admin" style="font-size:.75rem">Publicado</span>
                        <?php elseif ($estado_txt === 'carga_docente'): ?>
                            <span class="role-badge docente" style="font-size:.75rem">Carga docente</span>
                        <?php else: ?>
                            <span class="role-badge" style="font-size:.75rem;background:#e9ecef;color:#333">Cerrado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo (int)$resumen['materias_completas']; ?>/<?php echo (int)$resumen['total_materias']; ?> materias
                        <div style="color:var(--text-muted);font-size:.85rem">Faltantes: <?php echo (int)$resumen['faltantes_total']; ?></div>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-table-edit" href="?curso=<?php echo $curso_sel; ?>&periodo_detalle=<?php echo $id_periodo; ?>">Detalle</a>
                        <?php if ($estado_txt === 'cerrado'): ?>
                        <form method="post" class="d-inline">
                            <?php campo_csrf(); ?>
                            <input type="hidden" name="accion" value="abrir">
                            <input type="hidden" name="id_periodo" value="<?php echo $id_periodo; ?>">
                            <input type="hidden" name="id_curso" value="<?php echo $curso_sel; ?>">
                            <button type="submit" class="btn btn-sm btn-table-edit">Abrir</button>
                        </form>
                        <?php elseif ($estado_txt === 'carga_docente'): ?>
                        <form method="post" class="d-inline">
                            <?php campo_csrf(); ?>
                            <input type="hidden" name="accion" value="publicar">
                            <input type="hidden" name="id_periodo" value="<?php echo $id_periodo; ?>">
                            <input type="hidden" name="id_curso" value="<?php echo $curso_sel; ?>">
                            <button type="submit" class="btn btn-sm btn-table-edit">Publicar</button>
                        </form>
                        <?php else: ?>
                        <form method="post" class="d-inline">
                            <?php campo_csrf(); ?>
                            <input type="hidden" name="accion" value="reabrir">
                            <input type="hidden" name="id_periodo" value="<?php echo $id_periodo; ?>">
                            <input type="hidden" name="id_curso" value="<?php echo $curso_sel; ?>">
                            <button type="submit" class="btn btn-sm btn-table-edit">Reabrir</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($resumen_detalle): ?>
    <div class="p-3 mb-3" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
        <h5>Detalle de completitud del periodo</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Grupos</th>
                        <th>Docentes</th>
                        <th>Esperadas</th>
                        <th>Cargadas</th>
                        <th>Faltantes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($resumen_detalle['materias'] as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$m['nombre_materia']); ?></td>
                        <td><?php echo htmlspecialchars((string)$m['grupos_txt']); ?></td>
                        <td><?php echo (int)$m['total_docentes']; ?></td>
                        <td><?php echo (int)$m['esperado']; ?></td>
                        <td><?php echo (int)$m['cargadas']; ?></td>
                        <td><?php echo (int)$m['faltantes']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($estado_detalle && (string)$estado_detalle['estado'] === 'publicado'): ?>
    <div class="p-3 mb-3" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
        <h5>Descarga de boletines (publicados)</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Alumno</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alumnos_descarga as $al): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$al['dni']); ?></td>
                        <td><?php echo htmlspecialchars((string)$al['apellido'] . ', ' . $al['nombre']); ?></td>
                        <td>
                            <a class="btn btn-sm btn-table-edit" href="<?php echo url('php/boletin/descargar_boletin.php?id_periodo=' . (int)$periodo_detalle . '&id_curso=' . (int)$curso_sel . '&id_alumno=' . (int)$al['id_persona']); ?>">
                                Descargar PDF
                            </a>
                            <a class="btn btn-sm btn-table-edit" href="<?php echo url('php/boletin/historial_pdf_alumno.php?id_periodo=' . (int)$periodo_detalle . '&id_curso=' . (int)$curso_sel . '&id_alumno=' . (int)$al['id_persona']); ?>">
                                Historial
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-end mt-3">
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
