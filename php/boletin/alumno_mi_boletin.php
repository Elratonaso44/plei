<?php
include '../conesion.php';
include '../config.php';
include './helpers.php';
session_start();
exigir_rol('alumno');

if (!boletin_modulo_disponible($con)) {
    http_response_code(500);
    exit('El modulo de boletin no esta disponible.');
}

$id_alumno = (int)($_SESSION['id_persona'] ?? 0);

$boletines = db_fetch_all(
    $con,
    "SELECT h.id_periodo, h.id_curso, h.id_alumno, h.version, h.generado_en,
            bp.nombre AS periodo_nombre,
            cl.nombre AS ciclo_nombre, cl.anio,
            c.grado, s.seccion, mo.moda
     FROM boletin_pdf_historial AS h
     INNER JOIN (
         SELECT bp2.id_ciclo, h2.id_curso, h2.id_alumno, MAX(h2.id_boletin_pdf) AS id_boletin_pdf
         FROM boletin_pdf_historial AS h2
         INNER JOIN boletin_periodos AS bp2 ON bp2.id_periodo = h2.id_periodo
         WHERE h2.id_alumno = ?
         GROUP BY bp2.id_ciclo, h2.id_curso, h2.id_alumno
     ) AS ult
             ON ult.id_boletin_pdf = h.id_boletin_pdf
     INNER JOIN boletin_curso_periodo AS bcp
             ON bcp.id_periodo = h.id_periodo
            AND bcp.id_curso = h.id_curso
            AND bcp.estado = 'publicado'
     INNER JOIN boletin_periodos AS bp ON bp.id_periodo = h.id_periodo
     INNER JOIN ciclos_lectivos AS cl ON cl.id_ciclo = bp.id_ciclo
     INNER JOIN cursos AS c ON c.id_curso = h.id_curso
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     INNER JOIN alumnos_x_curso AS axc ON axc.id_curso = h.id_curso AND axc.id_persona = h.id_alumno
     WHERE h.id_alumno = ?
     ORDER BY cl.anio DESC, h.generado_en DESC",
    'ii',
    [$id_alumno, $id_alumno]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Mi boletin</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-file-earmark-text-fill"></i> Mi boletin digital</h2>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle tabla-organizada">
            <thead>
                <tr>
                    <th>Ciclo</th>
                    <th>Ultimo corte</th>
                    <th>Curso</th>
                    <th>Version</th>
                    <th>Generado</th>
                    <th>Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletines === []): ?>
                <tr>
                    <td colspan="6" class="text-center py-4">
                        No hay boletines publicados para tu usuario todavia.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($boletines as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$b['ciclo_nombre'] . ' (' . $b['anio'] . ')'); ?></td>
                    <td><?php echo htmlspecialchars((string)$b['periodo_nombre']); ?></td>
                    <td><?php echo htmlspecialchars((string)$b['grado'] . '° ' . $b['seccion'] . ' (' . $b['moda'] . ')'); ?></td>
                    <td>v<?php echo (int)$b['version']; ?></td>
                    <td><?php echo htmlspecialchars((string)$b['generado_en']); ?></td>
                    <td>
                        <a class="btn btn-sm btn-table-edit" href="<?php echo url('php/boletin/descargar_boletin.php?id_periodo=' . (int)$b['id_periodo'] . '&id_curso=' . (int)$b['id_curso'] . '&id_alumno=' . (int)$id_alumno); ?>">
                            Descargar PDF
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="text-end mt-3">
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
