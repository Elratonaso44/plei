<?php
include './php/conesion.php';

session_start();

if(!isset($_SESSION['username']) || !isset($_SESSION['id_persona'])){
  echo " <script>
    alert('Sesion no iniciada');
    window.location.replace('https://localhost/Dinamica/practica/index.html');
    </script>
  ";
  exit;
}

$id = $_SESSION["id_persona"];

$resultado = mysqli_query($con, "SELECT t.tipo
FROM tipo_persona_x_persona as ti
INNER JOIN personas as p on ti.id_persona = p.id_persona
INNER JOIN tipos_personas as t on ti.id_tipo_persona = t.id_tipo_persona
WHERE p.id_persona = '$id' ");

$tipos = [];

if($resultado){
  while($tipo = mysqli_fetch_assoc($resultado)){
    $tipos[] = $tipo;
  }
}


$esAdmin = false;
$esPreceptor = false;
$esDocente = false;
$esAlumno = false;

foreach ($tipos as $tipo){
  $t = strtolower(trim($tipo['tipo']));
  if ($t === 'administrador') {
    $esAdmin = true;
  }
  if ($t === 'preceptor') {
    $esPreceptor = true;
  }
  if ($t === 'docente') {
    $esDocente = true;
  }
  if ($t === 'alumno') {
    $esAlumno = true;
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plei</title>
  <link rel="stylesheet" href="./bootstrap-5.0.2-dist/css/bootstrap.css">
  <style>

    body { background-color: rgba(57, 74, 75, 0.2); }
    .barra { height: 100vh; background: rgba(54, 150, 137, 0.2); border-radius: 1rem; min-width: 260px; max-width: 320px; transition: all 0.3s; overflow: visible !important; }
    .barra .nav-link { color: #222; font-weight: 500; border-radius: 0.5rem; margin-bottom: 0.5rem; background-color: #b2dfd1; transition: background 0.2s, color 0.2s; }
    .barra .nav-link:hover { background: rgb(80,0,100); color: #fff; }
    .contenido { padding: 2rem; width: 100%; }
    .offcanvas { overflow: visible !important; min-width: 260px; max-width: 320px; }
    .tree-menu { position: static; display: block; }
    .tree-menu .nav-link { cursor: pointer; position: relative; z-index: 10001; }
    .tree-branch { display: none; position: fixed; left: 320px; top: auto; min-width: 100px; background: #fff; border-radius: 0.5rem; box-shadow: 0 4px 16px rgba(0,0,0,0.18); z-index: 10000; padding: 0.5rem 0.5rem 0.5rem 1.5rem; margin-left: 10px; margin-top: -10px; border: 1px solid #b2dfd1; }
    .tree-menu:hover > .tree-branch, .tree-menu:focus-within > .tree-branch { display: flex; flex-direction: column; gap: 0.5rem; }
    .tree-branch .btn { width: 100%; text-align: left; background: #b2dfd1; color: #222; border: none; border-radius: 0.5rem; transition: background 0.2s, color 0.2s; }
    .tree-branch .btn:hover { background: rgb(80,0,100); color: #fff; }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: rgba(15, 15, 15, 0.7); border-bottom: 2px solid #00004F;">
    <div class="container-fluid">
      <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#barraMenu" aria-controls="barraMenu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <a class="navbar-brand fw-bold" href="#"><?php echo $_SESSION['nombre']." ".$_SESSION['apellido']; ?></a>
    </div>
  </nav>

  <div class="offcanvas offcanvas-start barra" tabindex="-1" id="barraMenu" aria-labelledby="barraMenuLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title fw-bold" id="barraMenuLabel">Menú</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column justify-content-between" style="height:100%;">
      <nav class="nav flex-column">


        <?php if($esAdmin){ ?>
          <a class="nav-link" href="./php/altas/register.php" target="_blank">Registrar Usuario</a>
          <a class="nav-link" href="./php/listados/lista_personas.php" target="_blank">Lista de personas</a>

          <div class="tree-menu">
            <a class="nav-link" href="#" tabindex="0" style="position: relative;">Preceptores</a>
            <div class="tree-branch">
              <a href="./php/altas/preceptor_x_curso.php" class="btn" target="_blank">Alta preceptor por curso</a>
              <a href="./php/listados/lista_preceptores.php" class="btn" target="_blank">Ver Preceptores</a>
            </div>
          </div>

          <div class="tree-menu">
            <a class="nav-link" href="#" tabindex="0" style="position: relative;">Modalidades</a>
            <div class="tree-branch">
              <a href="./php/altas/modalidad.html" class="btn" target="_blank">Alta Modalidad</a>
              <a href="./php/listados/listar_modalidad.php" class="btn" target="_blank">Ver Modalidades</a>
            </div>
          </div>

          <div class="tree-menu">
            <a class="nav-link" href="#" tabindex="0" style="position: relative;">Secciones</a>
            <div class="tree-branch">
              <a href="./php/altas/seccion.html" class="btn" target="_blank">Alta Sección</a>
              <a href="./php/listados/lista_secciones.php" class="btn" target="_blank">Ver secciones</a>
            </div>
          </div>

          <div class="tree-menu">
            <a class="nav-link" href="#" tabindex="0" style="position: relative;">Tipo Persona</a>
            <div class="tree-branch">
              <a href="./php/altas/tipo_persona.html" class="btn" target="_blank">Alta Tipo</a>
              <a href="./php/listados/ver_tipos.php" class="btn" target="_blank">Ver Tipos</a>
            </div>
          </div>

          <a class="nav-link" href="./php/altas/alta_rol.html" tabindex="0" style="position: relative;" target="_blank">Alta Rol</a>
        <?php } ?>


        <?php if($esAdmin || $esPreceptor || $esDocente){ ?>
          <div class="tree-menu">
            <a class="nav-link" href="#" tabindex="0" style="position: relative;">Docentes</a>
            <div class="tree-branch">
              <a href="./php/listados/lista_docentes.php" class="btn" target="_blank">Ver Docentes</a>
              <a href="./php/altas/AD.php" class="btn" target="_blank">Materias por Docente</a>
              <?php if($esDocente){ ?>
                <a href="./php/modificaciones/editar_mi_material.php" class="btn" target="_blank">Editar mis materiales</a>
              <?php } ?>
            </div>
          </div>

          <div class="tree-menu">
            <a class="nav-link" href="#" tabindex="0" style="position: relative;">Alumnos</a>
            <div class="tree-branch">
              <a href="./php/altas/alta_alumno_curso.php" class="btn" target="_blank">Alta Alumno</a>
              <a href="./php/listados/lista_alumnos.php" class="btn" target="_blank">Ver Alumnos</a>
            </div>
          </div>
          <?php if($esPreceptor){ ?>
            <a class="nav-link" href="./php/modificaciones/asignar_alumno_x_curso.php" class="btn" target="_blank">Asignar alumno a curso</a>
            <a class="nav-link" href="./php/modificaciones/asignar_alumno_x_materia.php" class="btn" target="_blank">Asignar materia a alumno</a>
            <?php } ?>

          <div class="tree-menu">
            <a class="nav-link" href="#" tabindex="0" style="position: relative;">Materias</a>
            <div class="tree-branch">
              <a href="./php/altas/alta_materia.php" class="btn" target="_blank">Alta Materia</a>
              <a href="./php/listados/lista_materias.php" class="btn" target="_blank">Ver Materias</a>
            </div>
          </div>


          <?php if($esAdmin || $esPreceptor){ ?>
            <a class="nav-link" href="./php/altas/cursos.php" target="_blank">Curso</a>
          <?php } ?>
        <?php } ?>

        <?php if($esAdmin || $esDocente){ ?>
          <a class="nav-link" href="./php/altas/materiales.php" tabindex="0" style="position: relative;" target="_blank">Alta Material</a>
        <?php } ?>


        <?php if($esDocente){ ?>
          <a class="nav-link" href="./php/listados/ver_mis_alumnos.php" style="background-color: #b2dfd1;" target="_blank">Ver mis alumnos por materia</a>
          <a class="nav-link" href="./php/modificaciones/editar_perfil.php" target="_blank">Editar mi perfil</a>
        <?php } ?>

        <?php if($esPreceptor){ ?>
          <a class="nav-link" href="./php/listados/lista_cursos_a_cargo.php" target="_blank">Ver mis materias</a>
          <a class="nav-link" href="./php/modificaciones/editar_perfil.php" target="_blank">Editar mi perfil</a>
        <?php } ?>


        <?php if($esAlumno){ ?>
          <a class="nav-link" href="./php/listados/lista_materias_asignadas.php" target="_blank">Listar materias asignadas</a>
          <a class="nav-link" href="./php/listados/lista_material_asignado.php" target="_blank">Listar material asignado</a>
          <a class="nav-link" href="./php/modificaciones/editar_perfilAlumno.php" target="_blank">Editar mi perfil (Alumno)</a>
        <?php } ?>

      </nav>
    </div>
  </div>

  <div class="container-fluid d-flex justify-content-center align-items-center position-relative" style="min-height: 90vh;">
    <div class="contenido text-center">
      <h2 class="fw-bold mb-4">Plei</h2>
      <?php foreach($tipos as $tipo){echo $tipo['tipo'];} ?>
    </div>
  </div>

  <a href="./php/cerrar_sesion.php" class="btn btn-danger position-fixed" style="right: 40px; bottom: 40px; border-radius:0.5rem; z-index:9999;">Cerrar sesión</a>

  <script src="./bootstrap-5.0.2-dist/js/bootstrap.bundle.js"></script>
</body>
</html>
