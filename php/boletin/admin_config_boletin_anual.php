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

$ciclo_activo = boletin_ciclo_activo($con);
$id_ciclo_activo = (int)($ciclo_activo['id_ciclo'] ?? 0);
$config_inst = boletin_config_institucion($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = trim((string)($_POST['accion'] ?? ''));
    $mensaje_tipo = '';
    $mensaje_texto = '';

    if ($accion === 'guardar_institucion') {
        $nombre_escuela = trim((string)($_POST['nombre_escuela'] ?? ''));
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        $ciudad = trim((string)($_POST['ciudad'] ?? ''));
        $codigo_postal = trim((string)($_POST['codigo_postal'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));

        if ($nombre_escuela === '' || $direccion === '' || $ciudad === '' || $codigo_postal === '' || $telefono === '') {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Completa todos los datos institucionales.';
        } else {
            $cfg_actual = db_fetch_one(
                $con,
                "SELECT id_config
                 FROM boletin_institucion_config
                 ORDER BY id_config ASC
                 LIMIT 1"
            );
            if ($cfg_actual) {
                $id_config = (int)$cfg_actual['id_config'];
                $stmt = mysqli_prepare(
                    $con,
                    "UPDATE boletin_institucion_config
                     SET nombre_escuela = ?, direccion = ?, ciudad = ?, codigo_postal = ?, telefono = ?, actualizado_por = ?
                     WHERE id_config = ?"
                );
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssii', $nombre_escuela, $direccion, $ciudad, $codigo_postal, $telefono, $id_usuario, $id_config);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    $ok = false;
                }
            } else {
                $stmt = mysqli_prepare(
                    $con,
                    "INSERT INTO boletin_institucion_config
                        (nombre_escuela, direccion, ciudad, codigo_postal, telefono, actualizado_por)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssi', $nombre_escuela, $direccion, $ciudad, $codigo_postal, $telefono, $id_usuario);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    $ok = false;
                }
            }

            if ($ok) {
                registrar_auditoria_boletin($con, [
                    'tipo_evento' => 'boletin_config_institucion',
                    'entidad' => 'boletin_institucion_config',
                    'id_actor' => $id_usuario,
                    'payload' => [
                        'nombre_escuela' => $nombre_escuela,
                        'direccion' => $direccion,
                        'ciudad' => $ciudad,
                        'codigo_postal' => $codigo_postal,
                        'telefono' => $telefono,
                    ],
                ]);
                $mensaje_tipo = 'ok';
                $mensaje_texto = 'Datos institucionales guardados.';
            } else {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'No se pudieron guardar los datos institucionales.';
            }
        }
    }

    if ($accion === 'mapear_periodo') {
        $id_periodo = (int)($_POST['id_periodo'] ?? 0);
        $codigo_pdf = strtoupper(trim((string)($_POST['codigo_pdf'] ?? '')));
        if ($id_ciclo_activo <= 0) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No hay ciclo activo.';
        } elseif ($id_periodo <= 0) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Periodo invalido.';
        } elseif ($codigo_pdf !== '' && !in_array($codigo_pdf, boletin_codigos_pdf_validos(), true)) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Codigo PDF invalido.';
        } else {
            $periodo = db_fetch_one(
                $con,
                "SELECT id_periodo
                 FROM boletin_periodos
                 WHERE id_periodo = ? AND id_ciclo = ?
                 LIMIT 1",
                'ii',
                [$id_periodo, $id_ciclo_activo]
            );
            if (!$periodo) {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'El periodo no pertenece al ciclo activo.';
            } else {
                mysqli_begin_transaction($con);
                try {
                    if ($codigo_pdf !== '') {
                        $stmt_clear = mysqli_prepare(
                            $con,
                            "UPDATE boletin_periodos
                             SET codigo_pdf = NULL
                             WHERE id_ciclo = ? AND codigo_pdf = ?"
                        );
                        if (!$stmt_clear) {
                            throw new RuntimeException('No se pudo limpiar asignaciones previas.');
                        }
                        mysqli_stmt_bind_param($stmt_clear, 'is', $id_ciclo_activo, $codigo_pdf);
                        mysqli_stmt_execute($stmt_clear);
                        mysqli_stmt_close($stmt_clear);
                    }

                    if ($codigo_pdf === '') {
                        $stmt_upd = mysqli_prepare(
                            $con,
                            "UPDATE boletin_periodos
                             SET codigo_pdf = NULL
                             WHERE id_periodo = ? AND id_ciclo = ?"
                        );
                        if (!$stmt_upd) {
                            throw new RuntimeException('No se pudo actualizar el mapeo.');
                        }
                        mysqli_stmt_bind_param($stmt_upd, 'ii', $id_periodo, $id_ciclo_activo);
                        $ok_upd = mysqli_stmt_execute($stmt_upd);
                        mysqli_stmt_close($stmt_upd);
                    } else {
                        $codigo_sql = $codigo_pdf;
                        $stmt_upd = mysqli_prepare(
                            $con,
                            "UPDATE boletin_periodos
                             SET codigo_pdf = ?
                             WHERE id_periodo = ? AND id_ciclo = ?"
                        );
                        if (!$stmt_upd) {
                            throw new RuntimeException('No se pudo actualizar el mapeo.');
                        }
                        mysqli_stmt_bind_param($stmt_upd, 'sii', $codigo_sql, $id_periodo, $id_ciclo_activo);
                        $ok_upd = mysqli_stmt_execute($stmt_upd);
                        mysqli_stmt_close($stmt_upd);
                    }
                    if (!$ok_upd) {
                        throw new RuntimeException('No se pudo guardar el mapeo del periodo.');
                    }

                    registrar_auditoria_boletin($con, [
                        'tipo_evento' => 'boletin_periodo_codigo_pdf',
                        'entidad' => 'boletin_periodos',
                        'id_actor' => $id_usuario,
                        'id_periodo' => $id_periodo,
                        'payload' => ['codigo_pdf' => ($codigo_pdf === '' ? null : $codigo_pdf)],
                    ]);

                    mysqli_commit($con);
                    $mensaje_tipo = 'ok';
                    $mensaje_texto = 'Mapeo de periodo actualizado.';
                } catch (Throwable $e) {
                    mysqli_rollback($con);
                    $mensaje_tipo = 'err';
                    $mensaje_texto = $e->getMessage();
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mensaje_texto !== '') {
    $estado = $mensaje_tipo === 'ok' ? 'ok' : 'err';
    redirigir('php/boletin/admin_config_boletin_anual.php?estado=' . urlencode($estado) . '&msg=' . urlencode($mensaje_texto));
}

$config_inst = boletin_config_institucion($con);
$periodos = $id_ciclo_activo > 0 ? boletin_periodos_por_ciclo($con, $id_ciclo_activo, false) : [];
$codigos = boletin_codigos_pdf_validos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Configuracion Boletin Anual</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-sliders2"></i> Configuracion Boletin Anual</h2>

    <?php if ($mensaje_texto !== ''): ?>
    <div class="<?php echo $mensaje_tipo === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
        <i class="bi <?php echo $mensaje_tipo === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
        <?php echo htmlspecialchars($mensaje_texto); ?>
    </div>
    <?php endif; ?>

    <?php if (!$ciclo_activo): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No hay ciclo activo. Activá un ciclo para mapear periodos.
    </div>
    <?php else: ?>
    <div class="resultado-listado-meta mb-3">
        Ciclo activo: <strong><?php echo htmlspecialchars((string)$ciclo_activo['nombre']); ?></strong> (<?php echo (int)$ciclo_activo['anio']; ?>)
    </div>
    <?php endif; ?>

    <div class="p-3 mb-3" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
        <h5>Datos institucionales del encabezado PDF</h5>
        <form method="post" class="row g-2">
            <?php campo_csrf(); ?>
            <input type="hidden" name="accion" value="guardar_institucion">
            <div class="col-md-12">
                <label class="form-label">Nombre escuela</label>
                <input type="text" name="nombre_escuela" class="form-control" required value="<?php echo htmlspecialchars((string)$config_inst['nombre_escuela']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Direccion</label>
                <input type="text" name="direccion" class="form-control" required value="<?php echo htmlspecialchars((string)$config_inst['direccion']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ciudad</label>
                <input type="text" name="ciudad" class="form-control" required value="<?php echo htmlspecialchars((string)$config_inst['ciudad']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Codigo postal</label>
                <input type="text" name="codigo_postal" class="form-control" required value="<?php echo htmlspecialchars((string)$config_inst['codigo_postal']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Telefono</label>
                <input type="text" name="telefono" class="form-control" required value="<?php echo htmlspecialchars((string)$config_inst['telefono']); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn-plei-submit w-100">Guardar datos</button>
            </div>
        </form>
    </div>

    <div class="p-3 mb-3" style="border:1px solid var(--glass-border);border-radius:var(--radius-md)">
        <h5>Mapeo de periodos a columnas del PDF</h5>
        <div class="texto-opcional mb-2">
            Cada codigo puede estar asignado a un unico periodo por ciclo: <strong>INF1, CUAT1, INF2, CUAT2</strong>.
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Periodo</th>
                        <th>Activo</th>
                        <th>Codigo PDF</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($periodos === []): ?>
                    <tr><td colspan="5" class="text-center py-3">No hay periodos cargados.</td></tr>
                    <?php else: ?>
                    <?php foreach ($periodos as $p): ?>
                    <?php $codigo_actual = strtoupper(trim((string)($p['codigo_pdf'] ?? ''))); ?>
                    <tr>
                        <td><?php echo (int)$p['orden']; ?></td>
                        <td><?php echo htmlspecialchars((string)$p['nombre']); ?></td>
                        <td><?php echo (int)$p['activo'] === 1 ? 'Si' : 'No'; ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2 align-items-center">
                                <?php campo_csrf(); ?>
                                <input type="hidden" name="accion" value="mapear_periodo">
                                <input type="hidden" name="id_periodo" value="<?php echo (int)$p['id_periodo']; ?>">
                                <select name="codigo_pdf" class="form-select form-select-sm">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($codigos as $cod): ?>
                                    <option value="<?php echo htmlspecialchars($cod); ?>" <?php echo $codigo_actual === $cod ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cod); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                        </td>
                        <td>
                                <button type="submit" class="btn btn-sm btn-table-edit">Guardar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-end mt-3">
        <a href="<?php echo url('php/boletin/admin_ciclos_periodos.php'); ?>" class="btn-plei-cancel">Ciclos y periodos</a>
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
