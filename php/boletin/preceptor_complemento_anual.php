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
$ciclo_activo = boletin_ciclo_activo($con);
$id_ciclo = (int)($ciclo_activo['id_ciclo'] ?? 0);

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

$filtro_activo_alumnos = condicion_persona_activa($con, 'p');
$alumnos_curso = $curso_sel > 0 ? db_fetch_all(
    $con,
    "SELECT p.id_persona, p.apellido, p.nombre, p.dni
     FROM alumnos_x_curso AS axc
     INNER JOIN personas AS p ON p.id_persona = axc.id_persona
     INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
     WHERE axc.id_curso = ?
       AND LOWER(tp.tipo) = 'alumno'
       $filtro_activo_alumnos
     ORDER BY p.apellido ASC, p.nombre ASC",
    'i',
    [$curso_sel]
) : [];

$alumno_sel = (int)($_REQUEST['alumno'] ?? 0);
if ($alumno_sel <= 0 && $alumnos_curso !== []) {
    $alumno_sel = (int)$alumnos_curso[0]['id_persona'];
}
$ids_alumnos_curso = array_map(static fn($a) => (int)$a['id_persona'], $alumnos_curso);
if ($alumno_sel > 0 && !in_array($alumno_sel, $ids_alumnos_curso, true)) {
    http_response_code(403);
    exit('El alumno seleccionado no pertenece al curso.');
}

$materias_curso = $curso_sel > 0 ? db_fetch_all(
    $con,
    "SELECT id_materia, nombre_materia
     FROM materias
     WHERE id_curso = ?
     ORDER BY nombre_materia ASC",
    'i',
    [$curso_sel]
) : [];

$complementos_existentes = [];
if ($id_ciclo > 0 && $curso_sel > 0 && $alumno_sel > 0) {
    $filas_comp = db_fetch_all(
        $con,
        "SELECT id_materia, inas_1, inas_2, int_dic, int_feb_mar, nota_final
         FROM boletin_complementos_anuales
         WHERE id_ciclo = ?
           AND id_curso = ?
           AND id_alumno = ?",
        'iii',
        [$id_ciclo, $curso_sel, $alumno_sel]
    );
    foreach ($filas_comp as $fc) {
        $complementos_existentes[(int)$fc['id_materia']] = $fc;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = trim((string)($_POST['accion'] ?? ''));
    if ($accion === 'guardar_complementos') {
        $curso_sel = (int)($_POST['curso'] ?? 0);
        $alumno_sel = (int)($_POST['alumno'] ?? 0);

        if ($id_ciclo <= 0) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No hay ciclo activo para cargar complementos.';
        } elseif (!in_array($curso_sel, $ids_cursos_disponibles, true)) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No tenes permisos para ese curso.';
        } else {
            $alumno_valido = db_fetch_one(
                $con,
                "SELECT 1
                 FROM alumnos_x_curso
                 WHERE id_persona = ? AND id_curso = ?
                 LIMIT 1",
                'ii',
                [$alumno_sel, $curso_sel]
            );
            if (!$alumno_valido) {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'Alumno invalido para ese curso.';
            } else {
                $materias_curso = db_fetch_all(
                    $con,
                    "SELECT id_materia, nombre_materia
                     FROM materias
                     WHERE id_curso = ?
                     ORDER BY nombre_materia ASC",
                    'i',
                    [$curso_sel]
                );
                $map_nombres = [];
                foreach ($materias_curso as $m) {
                    $map_nombres[(int)$m['id_materia']] = (string)$m['nombre_materia'];
                }

                $filas_prev = db_fetch_all(
                    $con,
                    "SELECT id_materia, inas_1, inas_2, int_dic, int_feb_mar, nota_final
                     FROM boletin_complementos_anuales
                     WHERE id_ciclo = ? AND id_curso = ? AND id_alumno = ?",
                    'iii',
                    [$id_ciclo, $curso_sel, $alumno_sel]
                );
                $prev_map = [];
                foreach ($filas_prev as $fp) {
                    $prev_map[(int)$fp['id_materia']] = $fp;
                }

                $inas1_post = (array)($_POST['inas_1'] ?? []);
                $inas2_post = (array)($_POST['inas_2'] ?? []);
                $int_dic_post = (array)($_POST['int_dic'] ?? []);
                $int_feb_post = (array)($_POST['int_feb_mar'] ?? []);
                $nota_final_post = (array)($_POST['nota_final'] ?? []);

                $errores = [];
                $payload = [];
                foreach ($materias_curso as $m) {
                    $id_materia = (int)$m['id_materia'];
                    $nombre_materia = (string)$m['nombre_materia'];

                    $parse_entero = static function ($raw, string $campo, array &$errores_ref, string $nombre) {
                        $raw = trim((string)$raw);
                        if ($raw === '') {
                            return null;
                        }
                        if (!preg_match('/^\d+$/', $raw)) {
                            $errores_ref[] = $nombre . ': ' . $campo . ' debe ser entero.';
                            return null;
                        }
                        return (int)$raw;
                    };

                    $inas1 = $parse_entero($inas1_post[$id_materia] ?? '', 'INAS 1', $errores, $nombre_materia);
                    $inas2 = $parse_entero($inas2_post[$id_materia] ?? '', 'INAS 2', $errores, $nombre_materia);
                    $int_dic = $parse_entero($int_dic_post[$id_materia] ?? '', 'INT DIC', $errores, $nombre_materia);
                    $int_feb = $parse_entero($int_feb_post[$id_materia] ?? '', 'INT FEB/MAR', $errores, $nombre_materia);

                    $nota_final_raw = trim((string)($nota_final_post[$id_materia] ?? ''));
                    $nota_final = null;
                    if ($nota_final_raw !== '') {
                        $normalizada = str_replace(',', '.', $nota_final_raw);
                        if (!is_numeric($normalizada)) {
                            $errores[] = $nombre_materia . ': Nota final invalida.';
                        } else {
                            $nota_final = round((float)$normalizada, 1);
                        }
                    }

                    if ($inas1 !== null && $inas1 < 0) {
                        $errores[] = $nombre_materia . ': INAS 1 no puede ser negativo.';
                    }
                    if ($inas2 !== null && $inas2 < 0) {
                        $errores[] = $nombre_materia . ': INAS 2 no puede ser negativo.';
                    }
                    if ($int_dic !== null && ($int_dic < 1 || $int_dic > 10)) {
                        $errores[] = $nombre_materia . ': INT DIC debe estar entre 1 y 10.';
                    }
                    if ($int_feb !== null && ($int_feb < 1 || $int_feb > 10)) {
                        $errores[] = $nombre_materia . ': INT FEB/MAR debe estar entre 1 y 10.';
                    }
                    if ($nota_final !== null && ($nota_final < 1.0 || $nota_final > 10.0)) {
                        $errores[] = $nombre_materia . ': Nota final debe estar entre 1 y 10.';
                    }

                    $payload[$id_materia] = [
                        'inas_1' => $inas1,
                        'inas_2' => $inas2,
                        'int_dic' => $int_dic,
                        'int_feb_mar' => $int_feb,
                        'nota_final' => $nota_final,
                    ];
                }

                if ($errores !== []) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = implode(' | ', $errores);
                } else {
                    mysqli_begin_transaction($con);
                    try {
                        $cambios = 0;
                        foreach ($payload as $id_materia => $vals) {
                            $prev = $prev_map[$id_materia] ?? null;
                            $todo_vacio = $vals['inas_1'] === null
                                && $vals['inas_2'] === null
                                && $vals['int_dic'] === null
                                && $vals['int_feb_mar'] === null
                                && $vals['nota_final'] === null;

                            if ($todo_vacio) {
                                if ($prev) {
                                    $stmt_del = mysqli_prepare(
                                        $con,
                                        "DELETE FROM boletin_complementos_anuales
                                         WHERE id_ciclo = ? AND id_curso = ? AND id_alumno = ? AND id_materia = ?"
                                    );
                                    if (!$stmt_del) {
                                        throw new RuntimeException('No se pudo limpiar complemento.');
                                    }
                                    mysqli_stmt_bind_param($stmt_del, 'iiii', $id_ciclo, $curso_sel, $alumno_sel, $id_materia);
                                    mysqli_stmt_execute($stmt_del);
                                    mysqli_stmt_close($stmt_del);
                                    $cambios++;
                                }
                                continue;
                            }

                            $cambio_real = !$prev
                                || ((string)($prev['inas_1'] ?? '') !== (string)($vals['inas_1'] ?? ''))
                                || ((string)($prev['inas_2'] ?? '') !== (string)($vals['inas_2'] ?? ''))
                                || ((string)($prev['int_dic'] ?? '') !== (string)($vals['int_dic'] ?? ''))
                                || ((string)($prev['int_feb_mar'] ?? '') !== (string)($vals['int_feb_mar'] ?? ''))
                                || ((string)($prev['nota_final'] ?? '') !== (string)($vals['nota_final'] ?? ''));

                            $stmt_up = mysqli_prepare(
                                $con,
                                "INSERT INTO boletin_complementos_anuales
                                    (id_ciclo, id_curso, id_alumno, id_materia, inas_1, inas_2, int_dic, int_feb_mar, nota_final, actualizado_por)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE
                                    inas_1 = VALUES(inas_1),
                                    inas_2 = VALUES(inas_2),
                                    int_dic = VALUES(int_dic),
                                    int_feb_mar = VALUES(int_feb_mar),
                                    nota_final = VALUES(nota_final),
                                    actualizado_por = VALUES(actualizado_por),
                                    actualizado_en = CURRENT_TIMESTAMP"
                            );
                            if (!$stmt_up) {
                                throw new RuntimeException('No se pudo guardar un complemento.');
                            }
                            mysqli_stmt_bind_param(
                                $stmt_up,
                                'iiiiiiiidi',
                                $id_ciclo,
                                $curso_sel,
                                $alumno_sel,
                                $id_materia,
                                $vals['inas_1'],
                                $vals['inas_2'],
                                $vals['int_dic'],
                                $vals['int_feb_mar'],
                                $vals['nota_final'],
                                $id_usuario
                            );
                            $ok_up = mysqli_stmt_execute($stmt_up);
                            mysqli_stmt_close($stmt_up);
                            if (!$ok_up) {
                                throw new RuntimeException('No se pudo guardar un complemento.');
                            }
                            if ($cambio_real) {
                                $cambios++;
                            }
                        }

                        registrar_auditoria_boletin($con, [
                            'tipo_evento' => 'boletin_complemento_anual_guardado',
                            'entidad' => 'boletin_complementos_anuales',
                            'id_actor' => $id_usuario,
                            'id_curso' => $curso_sel,
                            'id_alumno' => $alumno_sel,
                            'payload' => ['cambios' => $cambios],
                        ]);
                        mysqli_commit($con);
                        $mensaje_tipo = 'ok';
                        $mensaje_texto = 'Complementos anuales guardados. Cambios: ' . $cambios . '.';
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
    $estado = $mensaje_tipo === 'ok' ? 'ok' : 'err';
    $params = [
        'curso' => $curso_sel,
        'alumno' => $alumno_sel,
        'estado' => $estado,
        'msg' => $mensaje_texto,
    ];
    redirigir('php/boletin/preceptor_complemento_anual.php?' . http_build_query($params));
}

$alumnos_curso = $curso_sel > 0 ? db_fetch_all(
    $con,
    "SELECT p.id_persona, p.apellido, p.nombre, p.dni
     FROM alumnos_x_curso AS axc
     INNER JOIN personas AS p ON p.id_persona = axc.id_persona
     INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
     WHERE axc.id_curso = ?
       AND LOWER(tp.tipo) = 'alumno'
       $filtro_activo_alumnos
     ORDER BY p.apellido ASC, p.nombre ASC",
    'i',
    [$curso_sel]
) : [];

$materias_curso = $curso_sel > 0 ? db_fetch_all(
    $con,
    "SELECT id_materia, nombre_materia
     FROM materias
     WHERE id_curso = ?
     ORDER BY nombre_materia ASC",
    'i',
    [$curso_sel]
) : [];

$complementos_existentes = [];
if ($id_ciclo > 0 && $curso_sel > 0 && $alumno_sel > 0) {
    $filas_comp = db_fetch_all(
        $con,
        "SELECT id_materia, inas_1, inas_2, int_dic, int_feb_mar, nota_final
         FROM boletin_complementos_anuales
         WHERE id_ciclo = ?
           AND id_curso = ?
           AND id_alumno = ?",
        'iii',
        [$id_ciclo, $curso_sel, $alumno_sel]
    );
    foreach ($filas_comp as $fc) {
        $complementos_existentes[(int)$fc['id_materia']] = $fc;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Cierre Anual</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-table"></i> Cierre / Complemento Anual</h2>

    <?php if ($mensaje_texto !== ''): ?>
    <div class="<?php echo $mensaje_tipo === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
        <i class="bi <?php echo $mensaje_tipo === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
        <?php echo htmlspecialchars($mensaje_texto); ?>
    </div>
    <?php endif; ?>

    <?php if (!$ciclo_activo): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No hay ciclo activo para cargar complementos.
    </div>
    <?php endif; ?>

    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <label class="form-label">Curso</label>
            <select name="curso" class="form-select" required>
                <?php foreach ($cursos_disponibles as $c): ?>
                <option value="<?php echo (int)$c['id_curso']; ?>" <?php echo $curso_sel === (int)$c['id_curso'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string)$c['grado'] . '° ' . $c['seccion'] . ' (' . $c['moda'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Alumno</label>
            <select name="alumno" class="form-select" required>
                <?php foreach ($alumnos_curso as $al): ?>
                <option value="<?php echo (int)$al['id_persona']; ?>" <?php echo $alumno_sel === (int)$al['id_persona'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string)$al['apellido'] . ', ' . $al['nombre'] . ' (DNI ' . $al['dni'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn-plei-submit w-100">Cargar grilla</button>
        </div>
    </form>

    <?php if ($curso_sel > 0 && $alumno_sel > 0 && $materias_curso !== []): ?>
    <form method="post">
        <?php campo_csrf(); ?>
        <input type="hidden" name="accion" value="guardar_complementos">
        <input type="hidden" name="curso" value="<?php echo $curso_sel; ?>">
        <input type="hidden" name="alumno" value="<?php echo $alumno_sel; ?>">

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>INAS 1</th>
                        <th>INAS 2</th>
                        <th>INT DIC</th>
                        <th>INT FEB/MAR</th>
                        <th>NOTA FINAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materias_curso as $m): ?>
                    <?php
                    $id_materia = (int)$m['id_materia'];
                    $comp = $complementos_existentes[$id_materia] ?? null;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$m['nombre_materia']); ?></td>
                        <td><input type="number" class="form-control" min="0" name="inas_1[<?php echo $id_materia; ?>]" value="<?php echo htmlspecialchars((string)($comp['inas_1'] ?? '')); ?>"></td>
                        <td><input type="number" class="form-control" min="0" name="inas_2[<?php echo $id_materia; ?>]" value="<?php echo htmlspecialchars((string)($comp['inas_2'] ?? '')); ?>"></td>
                        <td><input type="number" class="form-control" min="1" max="10" name="int_dic[<?php echo $id_materia; ?>]" value="<?php echo htmlspecialchars((string)($comp['int_dic'] ?? '')); ?>"></td>
                        <td><input type="number" class="form-control" min="1" max="10" name="int_feb_mar[<?php echo $id_materia; ?>]" value="<?php echo htmlspecialchars((string)($comp['int_feb_mar'] ?? '')); ?>"></td>
                        <td><input type="number" class="form-control" min="1" max="10" step="0.1" name="nota_final[<?php echo $id_materia; ?>]" value="<?php echo htmlspecialchars((string)($comp['nota_final'] ?? '')); ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-end">
            <button type="submit" class="btn-plei-submit">Guardar complementos</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="text-end mt-3">
        <a href="<?php echo url('php/boletin/preceptor_boletines.php?curso=' . $curso_sel); ?>" class="btn-plei-cancel">Boletines por curso</a>
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
