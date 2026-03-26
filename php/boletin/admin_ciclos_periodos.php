<?php
include '../conesion.php';
include '../config.php';
include './helpers.php';
session_start();
exigir_rol('administrador');

if (!boletin_modulo_disponible($con)) {
    http_response_code(500);
    exit('El modulo de boletin no esta disponible. Ejecuta la migracion primero.');
}

$id_usuario = (int)($_SESSION['id_persona'] ?? 0);
$mensaje_tipo = strtolower(trim((string)($_GET['estado'] ?? '')));
if (!in_array($mensaje_tipo, ['ok', 'err'], true)) {
    $mensaje_tipo = '';
}
$mensaje_texto = trim((string)($_GET['msg'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $mensaje_tipo = '';
    $mensaje_texto = '';
    $accion = trim((string)($_POST['accion'] ?? ''));

    if ($accion === 'crear_ciclo') {
        $anio = (int)($_POST['anio'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $activar = isset($_POST['activar']) ? 1 : 0;

        if ($anio < 2000 || $anio > 2100 || $nombre === '') {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Completa año y nombre validos.';
        } else {
            mysqli_begin_transaction($con);
            try {
                if ($activar === 1) {
                    $ok_close = db_query($con, "UPDATE ciclos_lectivos SET estado = 'cerrado' WHERE estado = 'activo'");
                    if (!$ok_close) {
                        throw new RuntimeException('No se pudo desactivar el ciclo activo actual.');
                    }
                }

                $estado = $activar === 1 ? 'activo' : 'cerrado';
                $stmt = mysqli_prepare(
                    $con,
                    "INSERT INTO ciclos_lectivos (anio, nombre, estado, creado_por)
                     VALUES (?, ?, ?, ?)"
                );
                if (!$stmt) {
                    throw new RuntimeException('No se pudo preparar el alta de ciclo.');
                }
                mysqli_stmt_bind_param($stmt, 'issi', $anio, $nombre, $estado, $id_usuario);
                $ok = mysqli_stmt_execute($stmt);
                $errno = mysqli_errno($con);
                mysqli_stmt_close($stmt);
                if (!$ok) {
                    if ($errno === 1062) {
                        throw new RuntimeException('Ya existe un ciclo para ese año.');
                    }
                    throw new RuntimeException('No se pudo crear el ciclo.');
                }
                mysqli_commit($con);
                $mensaje_tipo = 'ok';
                $mensaje_texto = 'Ciclo creado correctamente.';
            } catch (Throwable $e) {
                mysqli_rollback($con);
                $mensaje_tipo = 'err';
                $mensaje_texto = $e->getMessage();
            }
        }
    }

    if ($accion === 'activar_ciclo') {
        $id_ciclo = (int)($_POST['id_ciclo'] ?? 0);
        if ($id_ciclo <= 0) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Ciclo invalido.';
        } else {
            mysqli_begin_transaction($con);
            try {
                $ok1 = db_query($con, "UPDATE ciclos_lectivos SET estado = 'cerrado' WHERE estado = 'activo'");
                $ok2 = db_query(
                    $con,
                    "UPDATE ciclos_lectivos
                     SET estado = 'activo', cerrado_en = NULL, cerrado_por = NULL
                     WHERE id_ciclo = ?",
                    'i',
                    [$id_ciclo]
                );
                if (!$ok1 || !$ok2) {
                    throw new RuntimeException('No se pudo activar el ciclo seleccionado.');
                }
                mysqli_commit($con);
                $mensaje_tipo = 'ok';
                $mensaje_texto = 'Ciclo activado.';
                registrar_auditoria_boletin($con, [
                    'tipo_evento' => 'boletin_ciclo_activado',
                    'entidad' => 'ciclos_lectivos',
                    'id_actor' => $id_usuario,
                    'payload' => ['id_ciclo' => $id_ciclo]
                ]);
            } catch (Throwable $e) {
                mysqli_rollback($con);
                $mensaje_tipo = 'err';
                $mensaje_texto = $e->getMessage();
            }
        }
    }

    if ($accion === 'agregar_periodo') {
        $ciclo = boletin_ciclo_activo($con);
        $id_ciclo = (int)($ciclo['id_ciclo'] ?? 0);
        $nombre = trim((string)($_POST['periodo_nombre'] ?? ''));
        $orden = max(1, (int)($_POST['periodo_orden'] ?? 1));
        $activo = isset($_POST['periodo_activo']) ? 1 : 0;
        $codigo_pdf = strtoupper(trim((string)($_POST['codigo_pdf'] ?? '')));
        if ($codigo_pdf === '') {
            $codigo_pdf = null;
        }

        if ($id_ciclo <= 0) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No hay ciclo activo para agregar periodos.';
        } elseif ($nombre === '') {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'El nombre del periodo es obligatorio.';
        } elseif ($codigo_pdf !== null && !in_array($codigo_pdf, boletin_codigos_pdf_validos(), true)) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Codigo PDF invalido.';
        } else {
            $stmt = mysqli_prepare(
                $con,
                "INSERT INTO boletin_periodos (id_ciclo, nombre, orden, codigo_pdf, activo)
                 VALUES (?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'No se pudo preparar el alta de periodo.';
            } else {
                mysqli_stmt_bind_param($stmt, 'isisi', $id_ciclo, $nombre, $orden, $codigo_pdf, $activo);
                $ok = mysqli_stmt_execute($stmt);
                $errno = mysqli_errno($con);
                mysqli_stmt_close($stmt);
                if ($ok) {
                    $mensaje_tipo = 'ok';
                    $mensaje_texto = 'Periodo agregado al ciclo activo.';
                    registrar_auditoria_boletin($con, [
                        'tipo_evento' => 'boletin_periodo_creado',
                        'entidad' => 'boletin_periodos',
                        'id_actor' => $id_usuario,
                        'payload' => [
                            'id_ciclo' => $id_ciclo,
                            'nombre' => $nombre,
                            'orden' => $orden,
                            'codigo_pdf' => $codigo_pdf,
                            'activo' => $activo,
                        ]
                    ]);
                } elseif ($errno === 1062) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'Ya existe ese nombre, orden o codigo PDF en el ciclo activo.';
                } else {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'No se pudo crear el periodo.';
                }
            }
        }
    }

    if ($accion === 'toggle_periodo') {
        $id_periodo = (int)($_POST['id_periodo'] ?? 0);
        $nuevo_estado = (int)($_POST['activo'] ?? -1);
        if ($id_periodo <= 0) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Periodo invalido.';
        } elseif (!in_array($nuevo_estado, [0, 1], true)) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Estado de periodo invalido.';
        } elseif ($nuevo_estado === 0) {
            $publicado = db_fetch_one(
                $con,
                "SELECT 1
                 FROM boletin_curso_periodo
                 WHERE id_periodo = ?
                   AND estado = 'publicado'
                 LIMIT 1",
                'i',
                [$id_periodo]
            );
            if ($publicado) {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'No se puede desactivar: el periodo tiene cursos ya publicados.';
            }
        }

        if ($mensaje_texto === '') {
            $stmt = mysqli_prepare(
                $con,
                "UPDATE boletin_periodos
                 SET activo = ?
                 WHERE id_periodo = ?"
            );
            if (!$stmt) {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'No se pudo actualizar el periodo.';
            } else {
                mysqli_stmt_bind_param($stmt, 'ii', $nuevo_estado, $id_periodo);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $mensaje_tipo = $ok ? 'ok' : 'err';
                $mensaje_texto = $ok ? 'Periodo actualizado.' : 'No se pudo actualizar el periodo.';
                if ($ok) {
                    registrar_auditoria_boletin($con, [
                        'tipo_evento' => 'boletin_periodo_estado',
                        'entidad' => 'boletin_periodos',
                        'id_actor' => $id_usuario,
                        'id_periodo' => $id_periodo,
                        'payload' => ['activo' => $nuevo_estado]
                    ]);
                }
            }
        }
    }

    if ($accion === 'cerrar_y_nuevo') {
        $ciclo_activo = boletin_ciclo_activo($con);
        $id_ciclo_activo = (int)($ciclo_activo['id_ciclo'] ?? 0);
        $anio_nuevo = (int)($_POST['nuevo_anio'] ?? 0);
        $nombre_nuevo = trim((string)($_POST['nuevo_nombre'] ?? ''));

        if ($id_ciclo_activo <= 0) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No hay ciclo activo para cerrar.';
        } elseif ($anio_nuevo < 2000 || $anio_nuevo > 2100 || $nombre_nuevo === '') {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Completa año y nombre del nuevo ciclo.';
        } else {
            mysqli_begin_transaction($con);
            try {
                $stmt_cierre = mysqli_prepare(
                    $con,
                    "UPDATE ciclos_lectivos
                     SET estado = 'cerrado',
                         cerrado_en = NOW(),
                         cerrado_por = ?
                     WHERE id_ciclo = ?"
                );
                if (!$stmt_cierre) {
                    throw new RuntimeException('No se pudo cerrar el ciclo activo.');
                }
                mysqli_stmt_bind_param($stmt_cierre, 'ii', $id_usuario, $id_ciclo_activo);
                $ok_cierre = mysqli_stmt_execute($stmt_cierre);
                mysqli_stmt_close($stmt_cierre);
                if (!$ok_cierre) {
                    throw new RuntimeException('No se pudo cerrar el ciclo activo.');
                }

                $estado = 'activo';
                $stmt_alta = mysqli_prepare(
                    $con,
                    "INSERT INTO ciclos_lectivos (anio, nombre, estado, creado_por)
                     VALUES (?, ?, ?, ?)"
                );
                if (!$stmt_alta) {
                    throw new RuntimeException('No se pudo crear el nuevo ciclo.');
                }
                mysqli_stmt_bind_param($stmt_alta, 'issi', $anio_nuevo, $nombre_nuevo, $estado, $id_usuario);
                $ok_alta = mysqli_stmt_execute($stmt_alta);
                $errno = mysqli_errno($con);
                mysqli_stmt_close($stmt_alta);
                if (!$ok_alta) {
                    if ($errno === 1062) {
                        throw new RuntimeException('Ya existe un ciclo con ese año.');
                    }
                    throw new RuntimeException('No se pudo crear el nuevo ciclo.');
                }

                mysqli_commit($con);
                $mensaje_tipo = 'ok';
                $mensaje_texto = 'Ciclo cerrado y nuevo ciclo activo creado.';
                registrar_auditoria_boletin($con, [
                    'tipo_evento' => 'boletin_ciclo_cerrado_y_nuevo',
                    'entidad' => 'ciclos_lectivos',
                    'id_actor' => $id_usuario,
                    'payload' => [
                        'id_ciclo_cerrado' => $id_ciclo_activo,
                        'anio_nuevo' => $anio_nuevo,
                        'nombre_nuevo' => $nombre_nuevo,
                    ]
                ]);
            } catch (Throwable $e) {
                mysqli_rollback($con);
                $mensaje_tipo = 'err';
                $mensaje_texto = $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mensaje_texto !== '') {
    $estado_redir = $mensaje_tipo === 'ok' ? 'ok' : 'err';
    redirigir('php/boletin/admin_ciclos_periodos.php?estado=' . urlencode($estado_redir) . '&msg=' . urlencode($mensaje_texto));
}

$ciclo_activo = boletin_ciclo_activo($con);
$id_ciclo_activo = (int)($ciclo_activo['id_ciclo'] ?? 0);
$periodos_activos = $id_ciclo_activo > 0 ? boletin_periodos_por_ciclo($con, $id_ciclo_activo, false) : [];
$ciclos = db_fetch_all(
    $con,
    "SELECT id_ciclo, anio, nombre, estado, creado_en, cerrado_en
     FROM ciclos_lectivos
     ORDER BY anio DESC, id_ciclo DESC"
);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Ciclos y periodos</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>

<body class="fondo-transparente">
    <div class="tarjeta-principal">
        <h2><i class="bi bi-journal-check"></i> Ciclos y periodos de boletin</h2>

        <?php if ($mensaje_texto !== ''): ?>
            <div class="<?php echo $mensaje_tipo === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
                <i class="bi <?php echo $mensaje_tipo === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                <?php echo htmlspecialchars($mensaje_texto); ?>
            </div>
        <?php endif; ?>

        <div class="resultado-listado-meta mb-3">
            <?php if ($ciclo_activo): ?>
                Ciclo activo: <strong><?php echo htmlspecialchars((string)$ciclo_activo['nombre']); ?></strong>
                (<?php echo (int)$ciclo_activo['anio']; ?>)
            <?php else: ?>
                No hay ciclo activo.
            <?php endif; ?>
        </div>
        <div class="mb-3 text-end">
            <a href="<?php echo url('php/boletin/admin_config_boletin_anual.php'); ?>" class="btn btn-sm btn-table-edit">Configuracion boletin anual</a>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                    <tr>
                        <th>Ciclo</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th>Cerrado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ciclos === []): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No hay ciclos cargados todavia.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ciclos as $ciclo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$ciclo['nombre']); ?> (<?php echo (int)$ciclo['anio']; ?>)</td>
                                <td>
                                    <?php if ((string)$ciclo['estado'] === 'activo'): ?>
                                        <span class="role-badge admin" style="font-size:.75rem">Activo</span>
                                    <?php else: ?>
                                        <span class="role-badge" style="font-size:.75rem;background:#e9ecef;color:#333">Cerrado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)$ciclo['creado_en']); ?></td>
                                <td><?php echo htmlspecialchars((string)($ciclo['cerrado_en'] ?? '-')); ?></td>
                                <td>
                                    <?php if ((string)$ciclo['estado'] !== 'activo'): ?>
                                        <form method="post" class="d-inline">
                                            <?php campo_csrf(); ?>
                                            <input type="hidden" name="accion" value="activar_ciclo">
                                            <input type="hidden" name="id_ciclo" value="<?php echo (int)$ciclo['id_ciclo']; ?>">
                                            <button type="submit" class="btn btn-sm btn-table-edit">Activar</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);font-size:.9rem">En uso</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="p-3" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
                    <h5 style="margin-bottom:1rem">Crear ciclo lectivo</h5>
                    <form method="post">
                        <?php campo_csrf(); ?>
                        <input type="hidden" name="accion" value="crear_ciclo">
                        <div class="mb-2">
                            <label class="form-label">Año</label>
                            <input type="number" name="anio" class="form-control" min="2000" max="2100" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Ciclo Lectivo 2026" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="activarCiclo" name="activar" checked>
                            <label class="form-check-label" for="activarCiclo">Activar inmediatamente</label>
                        </div>
                        <button type="submit" class="btn-plei-submit">Crear ciclo</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="p-3" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
                    <h5 style="margin-bottom:1rem">Cerrar ciclo y crear nuevo</h5>
                    <form method="post">
                        <?php campo_csrf(); ?>
                        <input type="hidden" name="accion" value="cerrar_y_nuevo">
                        <div class="mb-2">
                            <label class="form-label">Nuevo año</label>
                            <input type="number" name="nuevo_anio" class="form-control" min="2000" max="2100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre nuevo ciclo</label>
                            <input type="text" name="nuevo_nombre" class="form-control" placeholder="Ej: Ciclo Lectivo 2027" required>
                        </div>
                        <button type="submit" class="btn-plei-submit">Cerrar y abrir nuevo ciclo</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="p-3 mb-3" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
            <h5 style="margin-bottom:1rem">Periodos del ciclo activo</h5>
            <?php if (!$ciclo_activo): ?>
                <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i> Activa o crea un ciclo para administrar periodos.</div>
            <?php else: ?>
                <form method="post" class="row g-2 align-items-end mb-3">
                    <?php campo_csrf(); ?>
                    <input type="hidden" name="accion" value="agregar_periodo">
                    <div class="col-md-5">
                        <label class="form-label">Nombre de periodo</label>
                        <input type="text" name="periodo_nombre" class="form-control" placeholder="Ej: Primer cuatrimestre" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Orden</label>
                        <input type="number" name="periodo_orden" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Codigo PDF</label>
                        <select name="codigo_pdf" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php foreach (boletin_codigos_pdf_validos() as $codigo_pdf_opt): ?>
                            <option value="<?php echo htmlspecialchars($codigo_pdf_opt); ?>"><?php echo htmlspecialchars($codigo_pdf_opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="periodo_activo" id="periodoActivo" checked>
                            <label class="form-check-label" for="periodoActivo">Periodo visible</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn-plei-submit w-100">Agregar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle tabla-organizada">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Periodo</th>
                                <th>Codigo PDF</th>
                                <th>Activo</th>
                                <th>Config cursos</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($periodos_activos === []): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No hay periodos en este ciclo.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($periodos_activos as $p): ?>
                                    <tr>
                                        <td><?php echo (int)$p['orden']; ?></td>
                                        <td><?php echo htmlspecialchars((string)$p['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars((string)($p['codigo_pdf'] ?? '-')); ?></td>
                                        <td><?php echo (int)$p['activo'] === 1 ? 'Si' : 'No'; ?></td>
                                        <td><?php echo (int)$p['total_cursos_configurados']; ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <?php campo_csrf(); ?>
                                                <input type="hidden" name="accion" value="toggle_periodo">
                                                <input type="hidden" name="id_periodo" value="<?php echo (int)$p['id_periodo']; ?>">
                                                <input type="hidden" name="activo" value="<?php echo (int)$p['activo'] === 1 ? 0 : 1; ?>">
                                                <button type="submit" class="btn btn-sm btn-table-edit">
                                                    <?php echo (int)$p['activo'] === 1 ? 'Desactivar' : 'Activar'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-end mt-3">
            <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
        </div>
    </div>
    <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
