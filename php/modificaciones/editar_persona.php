<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirigir('php/listados/lista_personas.php');
}

$persona = db_fetch_one(
    $con,
    "SELECT p.id_persona, p.dni, p.apellido, p.nombre, p.mail, p.usuario, r.rol
     FROM personas AS p
     LEFT JOIN roles AS r ON r.id_rol = p.id_rol
     WHERE p.id_persona = ?
     LIMIT 1",
    "i",
    [$id]
);
if (!$persona) {
    redirigir('php/listados/lista_personas.php');
}

$tipos_catalogo = db_fetch_all($con, "SELECT id_tipo_persona, tipo FROM tipos_personas ORDER BY tipo");
$tipos_asignados = persona_tipos_ids_validos($con, $id);
$error_form = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $dni = (int)($_POST['dni'] ?? 0);
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $apellido = trim((string)($_POST['apellido'] ?? ''));
    $mail = trim((string)($_POST['mail'] ?? ''));
    $tipos_post = array_values(array_unique(array_filter(
        array_map('intval', (array)($_POST['tipo'] ?? [])),
        static fn($id_tipo) => $id_tipo > 0
    )));

    $persona['dni'] = $dni;
    $persona['nombre'] = $nombre;
    $persona['apellido'] = $apellido;
    $persona['mail'] = $mail;

    if ($dni <= 0 || $nombre === '' || $apellido === '') {
        $error_form = 'DNI, nombre y apellido son obligatorios.';
    } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $error_form = 'El email no tiene un formato válido.';
    } elseif ($tipos_post === []) {
        $error_form = 'Seleccioná al menos un tipo de persona.';
    } else {
        $tipos_existentes = ids_tipo_persona_existentes($con, $tipos_post);
        if (count($tipos_existentes) !== count($tipos_post)) {
            $error_form = 'Uno de los tipos seleccionados no es válido.';
        }
    }

    if ($error_form === '') {
        mysqli_begin_transaction($con);
        try {
            $stmt = mysqli_prepare($con, "UPDATE personas SET dni = ?, nombre = ?, apellido = ?, mail = ? WHERE id_persona = ?");
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la actualización.');
            }
            mysqli_stmt_bind_param($stmt, "isssi", $dni, $nombre, $apellido, $mail, $id);
            if (!mysqli_stmt_execute($stmt)) {
                $errno = mysqli_errno($con);
                mysqli_stmt_close($stmt);
                if ($errno === 1062) {
                    throw new RuntimeException('Ya existe otra persona con ese DNI o email.');
                }
                throw new RuntimeException('No se pudo actualizar la persona.');
            }
            mysqli_stmt_close($stmt);

            $stmt_del = mysqli_prepare($con, "DELETE FROM tipo_persona_x_persona WHERE id_persona = ?");
            if (!$stmt_del) {
                throw new RuntimeException('No se pudieron actualizar los tipos asignados.');
            }
            mysqli_stmt_bind_param($stmt_del, "i", $id);
            if (!mysqli_stmt_execute($stmt_del)) {
                mysqli_stmt_close($stmt_del);
                throw new RuntimeException('No se pudieron actualizar los tipos asignados.');
            }
            mysqli_stmt_close($stmt_del);

            $stmt_ins = mysqli_prepare($con, "INSERT INTO tipo_persona_x_persona (id_persona, id_tipo_persona) VALUES (?, ?)");
            if (!$stmt_ins) {
                throw new RuntimeException('No se pudieron guardar los tipos asignados.');
            }
            foreach ($tipos_post as $id_tipo) {
                mysqli_stmt_bind_param($stmt_ins, "ii", $id, $id_tipo);
                if (!mysqli_stmt_execute($stmt_ins)) {
                    mysqli_stmt_close($stmt_ins);
                    throw new RuntimeException('No se pudieron guardar los tipos asignados.');
                }
            }
            mysqli_stmt_close($stmt_ins);

            if (!sincronizar_rol_persona_desde_tipos($con, $id, $tipos_post)) {
                throw new RuntimeException('No se pudo sincronizar el rol con los tipos seleccionados.');
            }

            mysqli_commit($con);
            redirigir('php/listados/lista_personas.php?estado=ok&msg=' . urlencode('Persona actualizada correctamente.'));
        } catch (Throwable $e) {
            mysqli_rollback($con);
            $error_form = $e->getMessage();
            $tipos_asignados = $tipos_post;
        }
    } else {
        $tipos_asignados = $tipos_post;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Editar Persona</title>
    <script>document.documentElement.classList.add('js-enabled');</script>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
<div class="form-card form-card-wide">
    <h2>Editar Persona</h2>

    <?php if ($error_form !== ''): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?php echo htmlspecialchars($error_form); ?></div>
    <?php endif; ?>

    <form method="post">
        <?php campo_csrf(); ?>
        <div class="mb-3">
            <label class="form-label">DNI</label>
            <input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars((string)$persona['dni']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars((string)$persona['nombre']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars((string)$persona['apellido']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="mail" class="form-control" value="<?php echo htmlspecialchars((string)$persona['mail']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)$persona['usuario']); ?>" disabled>
            <small class="texto-opcional">El usuario no se modifica desde esta pantalla.</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Tipo de persona</label>
            <div class="js-only-multi" data-multi-select>
                <div class="multi-choice-block">
                    <div class="multi-choice-header">
                        <span class="texto-opcional">Podés seleccionar más de una opción.</span>
                        <span class="multi-choice-count" data-multi-count>0 seleccionados</span>
                    </div>
                    <div class="multi-choice-grid">
                        <?php foreach ($tipos_catalogo as $tipo): ?>
                        <?php $id_tipo = (int)$tipo['id_tipo_persona']; ?>
                        <label class="multi-choice-item">
                            <input
                                type="checkbox"
                                class="multi-choice-input"
                                name="tipo[]"
                                value="<?php echo $id_tipo; ?>"
                                <?php echo in_array($id_tipo, $tipos_asignados, true) ? 'checked' : ''; ?>
                            >
                            <span class="multi-choice-card">
                                <span>
                                    <span class="multi-choice-title"><?php echo htmlspecialchars($tipo['tipo']); ?></span>
                                </span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="multi-choice-helper">El rol técnico se sincroniza automáticamente según los tipos elegidos.</p>
            </div>
            <noscript>
                <div class="multi-choice-fallback">
                    <select name="tipo[]" class="form-control" multiple required>
                        <?php foreach ($tipos_catalogo as $tipo): ?>
                        <option value="<?php echo (int)$tipo['id_tipo_persona']; ?>" <?php echo in_array((int)$tipo['id_tipo_persona'], $tipos_asignados, true) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['tipo']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="texto-opcional">Podés seleccionar más de una opción.</small>
                </div>
            </noscript>
        </div>
        <button type="submit" class="btn-plei-submit">Guardar Cambios</button>
        <a href="<?php echo url('php/listados/lista_personas.php'); ?>" class="btn-plei-cancel mt-2 w-100">Cancelar</a>
    </form>
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
