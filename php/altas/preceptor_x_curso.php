<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

$filtro_activo_preceptor = condicion_persona_activa($con, 'p');
$preceptores = db_fetch_all(
    $con,
    "SELECT p.id_persona, p.nombre, p.apellido
     FROM personas AS p
     INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
     INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
     WHERE LOWER(t.tipo) = 'preceptor' $filtro_activo_preceptor
     ORDER BY p.apellido"
);

$cursos = db_fetch_all(
    $con,
    "SELECT c.id_curso, c.grado, s.seccion, m.moda
     FROM cursos AS c
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
     ORDER BY c.grado"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificar_csrf();
    $id_preceptor = (int)($_POST["preceptor"] ?? 0);
    $ids_cursos = array_map('intval', (array)($_POST["curso"] ?? []));
    if ($id_preceptor && !empty($ids_cursos)) {
        if (!persona_esta_activa($con, $id_preceptor)) {
            redirigir('php/altas/preceptor_x_curso.php?estado=err&msg=' . urlencode('No se puede asignar un preceptor inactivo.'));
        }
        foreach ($ids_cursos as $id_curso) {
            if ($id_curso <= 0) {
                continue;
            }
            $stmt = mysqli_prepare($con, "INSERT INTO preceptor_x_curso (id_persona, id_curso) VALUES (?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $id_preceptor, $id_curso);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        redirigir('php/listados/lista_preceptores.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Alta preceptores por curso</title>
    <script>document.documentElement.classList.add('js-enabled');</script>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
<div class="form-card">
    <h2 class="text-center fw-bold mb-4">Alta de preceptores por curso</h2>
    <form autocomplete="off" method="post">
        <?php campo_csrf(); ?>
        <div class="mb-3">
            <label class="form-label">Preceptor</label>
            <select name="preceptor" class="form-select" required>
                <option value="">Seleccioná un preceptor</option>
                <?php foreach ($preceptores as $p): ?>
                <option value="<?= (int)$p['id_persona'] ?>">
                    <?= htmlspecialchars($p['apellido'].', '.$p['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4">
            <label class="form-label">Cursos a cargo</label>
            <div class="js-only-multi" data-multi-select>
                <div class="multi-choice-block">
                    <div class="multi-choice-header">
                        <span class="texto-opcional">Podés seleccionar más de una opción.</span>
                        <span class="multi-choice-count" data-multi-count>0 seleccionados</span>
                    </div>
                    <div class="multi-choice-grid">
                        <?php foreach ($cursos as $c): ?>
                        <?php $id_curso = (int)$c['id_curso']; ?>
                        <label class="multi-choice-item">
                            <input
                                type="checkbox"
                                class="multi-choice-input"
                                name="curso[]"
                                value="<?= $id_curso ?>"
                            >
                            <span class="multi-choice-card">
                                <span>
                                    <span class="multi-choice-title"><?= htmlspecialchars($c['grado'].'° '.$c['seccion']) ?></span>
                                    <span class="multi-choice-extra"><?= htmlspecialchars($c['moda']) ?></span>
                                </span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <noscript>
                <div class="multi-choice-fallback">
                    <select name="curso[]" class="form-select" multiple required>
                        <?php foreach ($cursos as $c): ?>
                        <option value="<?= (int)$c['id_curso'] ?>">
                            <?= htmlspecialchars($c['grado'].'° '.$c['seccion'].' — '.$c['moda']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="texto-opcional">Podés seleccionar más de una opción.</small>
                </div>
            </noscript>
        </div>
        <button type="submit" class="btn-plei-submit">Dar de alta</button>
    </form>
    <div class="text-center mt-3">
        <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel">Volver</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-multi-select]').forEach((bloque) => {
    const checks = Array.from(bloque.querySelectorAll('.multi-choice-input'));
    const countNode = bloque.querySelector('[data-multi-count]');
    if (!checks.length || !countNode) return;
    const sync = () => {
        const total = checks.filter((check) => check.checked).length;
        countNode.textContent = total === 1 ? '1 seleccionado' : `${total} seleccionados`;
        checks[0].required = total === 0;
    };
    checks.forEach((check) => check.addEventListener('change', sync));
    sync();
});
</script>
</body>
</html>
