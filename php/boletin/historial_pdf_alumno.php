<?php
include '../conesion.php';
include '../config.php';
include './helpers.php';
session_start();
exigir_rol(['administrador', 'preceptor']);

if (!boletin_modulo_disponible($con)) {
    http_response_code(500);
    exit('Modulo de boletin no disponible.');
}

$id_usuario = (int)($_SESSION['id_persona'] ?? 0);
$tipos = obtener_tipos_usuario($con, $id_usuario);

$id_periodo = (int)($_GET['id_periodo'] ?? 0);
$id_curso = (int)($_GET['id_curso'] ?? 0);
$id_alumno = (int)($_GET['id_alumno'] ?? 0);

if ($id_periodo <= 0 || $id_curso <= 0 || $id_alumno <= 0) {
    http_response_code(400);
    exit('Parametros invalidos.');
}

if (!boletin_usuario_puede_descargar($con, $id_usuario, $tipos, $id_curso, $id_alumno)) {
    http_response_code(403);
    exit('No tenes permisos para ver este historial.');
}

$alumno = db_fetch_one(
    $con,
    "SELECT p.id_persona, p.apellido, p.nombre, p.dni
     FROM personas AS p
     WHERE p.id_persona = ?
     LIMIT 1",
    'i',
    [$id_alumno]
);

$periodo = boletin_periodo_por_id($con, $id_periodo);

$historial = db_fetch_all(
    $con,
    "SELECT version, generado_en, ruta_pdf, hash_sha256
     FROM boletin_pdf_historial
     WHERE id_periodo = ?
       AND id_curso = ?
       AND id_alumno = ?
     ORDER BY version DESC",
    'iii',
    [$id_periodo, $id_curso, $id_alumno]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Historial PDF</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-clock-history"></i> Historial de versiones PDF</h2>

    <div class="resultado-listado-meta mb-3">
        Alumno: <strong><?php echo htmlspecialchars((string)($alumno['apellido'] ?? '') . ', ' . ($alumno['nombre'] ?? '')); ?></strong>
        | DNI: <?php echo htmlspecialchars((string)($alumno['dni'] ?? '-')); ?>
        | Periodo: <?php echo htmlspecialchars((string)($periodo['nombre'] ?? '-')); ?>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle tabla-organizada">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Generado</th>
                    <th>Hash</th>
                    <th>Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($historial === []): ?>
                <tr><td colspan="4" class="text-center py-4">No hay versiones para este alumno.</td></tr>
                <?php else: ?>
                <?php foreach ($historial as $h): ?>
                <tr>
                    <td>v<?php echo (int)$h['version']; ?></td>
                    <td><?php echo htmlspecialchars((string)$h['generado_en']); ?></td>
                    <td style="font-size:.8rem"><?php echo htmlspecialchars((string)$h['hash_sha256']); ?></td>
                    <td>
                        <a class="btn btn-sm btn-table-edit" href="<?php echo url('php/boletin/descargar_boletin.php?id_periodo=' . $id_periodo . '&id_curso=' . $id_curso . '&id_alumno=' . $id_alumno . '&version=' . (int)$h['version']); ?>">
                            Descargar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="text-end mt-3">
        <a href="<?php echo url('php/boletin/preceptor_boletines.php?curso=' . $id_curso . '&periodo_detalle=' . $id_periodo); ?>" class="btn-plei-cancel">Volver a boletines</a>
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">Inicio</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
