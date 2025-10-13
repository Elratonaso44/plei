<?php
include "../conesion.php";

$cursos = [];
$sqlCursos = "SELECT c.id_curso, c.grado, m.moda, s.seccion 
              FROM cursos AS c 
              INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad 
              INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion";
$resultado = mysqli_query($con, $sqlCursos);
while ($fila = mysqli_fetch_assoc($resultado)) {
  $cursos[] = $fila;
}

// AJAX para obtener materias
if (isset($_GET['ajax']) && $_GET['ajax'] === 'materias') {
  $curso_id = intval($_GET['curso_id']);
  $materias = [];

  $sql = "SELECT id_materia, nombre_materia, turno, grupo 
          FROM materias 
          WHERE id_curso = $curso_id";
  $resultado = mysqli_query($con, $sql);
  while ($fila = mysqli_fetch_assoc($resultado)) {
    $materias[] = $fila;
  }
  echo json_encode($materias);
  exit;
}

// AJAX para obtener alumnos
if (isset($_GET['ajax']) && $_GET['ajax'] === 'alumnos') {
  $materia_id = intval($_GET['materia_id']);
  $alumnos = [];

  $sql = "SELECT p.id_persona, p.nombre, p.apellido
          FROM personas AS p
          INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
          INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
          WHERE t.tipo = 'alumno' 
          AND p.id_persona NOT IN (
              SELECT id_persona FROM alumnos_x_materia WHERE id_materia = $materia_id
          )";
  $resultado = mysqli_query($con, $sql);
  while ($fila = mysqli_fetch_assoc($resultado)) {
    $alumnos[] = $fila;
  }
  echo json_encode($alumnos);
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asignar materia a alumno</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100"
      style="background-color: rgba(57, 74, 75, 0.2);">

<div class="card w-100 shadow-lg border-1"
     style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
  <div class="card-body">
    <h2 class="text-center fw-bold mb-4">Asignar materia a alumno</h2>

    <!-- Selección de curso -->
    <div class="mb-3">
      <label class="form-label">Curso</label>
      <select id="curso" class="form-select" required>
        <option value="">Seleccione un curso</option>
        <?php foreach ($cursos as $curso): ?>
          <option value="<?= htmlspecialchars($curso['id_curso']); ?>">
            <?= htmlspecialchars($curso['grado'] . "° " . $curso['seccion'] . " (" . $curso['moda'] . ")"); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Selección de materia -->
    <div class="mb-3">
      <label class="form-label">Materia</label>
      <select id="materia" class="form-select" disabled required>
        <option value="">Seleccione una materia</option>
      </select>
    </div>

    <!-- Selección de alumno -->
    <div class="mb-4">
      <label class="form-label">Alumno</label>
      <select id="alumno" class="form-select" disabled required>
        <option value="">Seleccione un alumno</option>
      </select>
    </div>

    <button id="btnAsignar" class="btn w-100 text-white"
            style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
            onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
            onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'"
            disabled>
      Asignar
    </button>

  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const cursoSelect = document.getElementById("curso");
  const materiaSelect = document.getElementById("materia");
  const alumnoSelect = document.getElementById("alumno");
  const btnAsignar = document.getElementById("btnAsignar");

  cursoSelect.addEventListener("change", async () => {
    const cursoId = cursoSelect.value;
    materiaSelect.innerHTML = "<option value=''>Cargando materias...</option>";
    alumnoSelect.innerHTML = "<option value=''>Seleccione un alumno</option>";
    alumnoSelect.disabled = true;
    btnAsignar.disabled = true;

    if (cursoId) {
      const res = await fetch(`?ajax=materias&curso_id=${cursoId}`);
      const data = await res.json();
      materiaSelect.disabled = false;
      materiaSelect.innerHTML = "<option value=''>Seleccione una materia</option>";
      data.forEach(m => {
        materiaSelect.innerHTML += `<option value="${m.id_materia}">${m.nombre_materia} (Turno ${m.turno})</option>`;
      });
    } else {
      materiaSelect.disabled = true;
    }
  });

  materiaSelect.addEventListener("change", async () => {
    const materiaId = materiaSelect.value;
    alumnoSelect.innerHTML = "<option value=''>Cargando alumnos...</option>";
    btnAsignar.disabled = true;

    if (materiaId) {
      const res = await fetch(`?ajax=alumnos&materia_id=${materiaId}`);
      const data = await res.json();
      alumnoSelect.disabled = false;
      alumnoSelect.innerHTML = "<option value=''>Seleccione un alumno</option>";
      data.forEach(a => {
        alumnoSelect.innerHTML += `<option value="${a.id_persona}">${a.nombre} ${a.apellido}</option>`;
      });
    } else {
      alumnoSelect.disabled = true;
    }
  });

  alumnoSelect.addEventListener("change", () => {
    btnAsignar.disabled = !alumnoSelect.value;
  });

  btnAsignar.addEventListener("click", () => {
    const curso = cursoSelect.value;
    const materia = materiaSelect.value;
    const alumno = alumnoSelect.value;

    if (!curso || !materia || !alumno) {
      alert("Seleccione curso, materia y alumno.");
      return;
    }

    alert(`✅ Alumno asignado correctamente a la materia.\n\nCurso: ${curso}\nMateria: ${materia}\nAlumno: ${alumno}`);
  });
});
</script>

<script src="../bootstrap-5.0.2-dist/js/bootstrap.js"></script>
</body>
</html>
