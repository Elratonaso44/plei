<?php
include './php/conesion.php'; include './php/config.php'; include './php/material_url.php'; session_start(); exigir_inicio_sesion(); $id = (int)$_SESSION["id_persona"]; $tipos = db_fetch_all( $con, "SELECT t.tipo
   FROM tipo_persona_x_persona AS ti
   INNER JOIN personas AS p ON ti.id_persona = p.id_persona
   INNER JOIN tipos_personas AS t ON ti.id_tipo_persona = t.id_tipo_persona
   WHERE p.id_persona = ?", "i", [$id] ); $esAdmin = false; $esPreceptor = false; $esDocente = false; $esAlumno = false; foreach ($tipos as $tipo){ $t = strtolower(trim($tipo['tipo'])); if ($t === 'administrador') $esAdmin = true; if ($t === 'preceptor') $esPreceptor = true; if ($t === 'docente') $esDocente = true; if ($t === 'alumno') $esAlumno = true; } $materiales_por_materia = []; if ($esAlumno) { $materiales = db_fetch_all( $con, "SELECT DISTINCT mat.id_material, mat.tipo_material, mat.unidad, mat.url, m.id_materia, m.nombre_materia,
            c.grado, s.seccion, mo.moda
     FROM materiales AS mat
     INNER JOIN materias AS m ON m.id_materia = mat.id_materia
     INNER JOIN cursos AS c ON c.id_curso = m.id_curso
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     LEFT JOIN alumnos_x_materia AS axm ON axm.id_materia = m.id_materia AND axm.id_persona = ?
     LEFT JOIN alumnos_x_curso AS axc ON axc.id_curso = m.id_curso AND axc.id_persona = ?
     WHERE axm.id_persona IS NOT NULL OR axc.id_persona IS NOT NULL
     ORDER BY m.nombre_materia ASC, mat.id_material DESC", "ii", [$id, $id] ); foreach ($materiales as $mat){ $clave_materia = (int)$mat['id_materia']; if (!isset($materiales_por_materia[$clave_materia])) { $materiales_por_materia[$clave_materia] = ['titulo' => $mat['nombre_materia'], 'detalle' => $mat['grado'] . '° ' . $mat['seccion'] . ' — ' . $mat['moda'], 'materiales' => []]; } $materiales_por_materia[$clave_materia]['materiales'][] = $mat; } } $hora = (int)date('H'); $saludo = $hora < 12 ? 'Buenos dias' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches'); $nombre = htmlspecialchars($_SESSION['nombre']); $nombre_completo = htmlspecialchars(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PLEI — Inicio</title>
  <link rel="stylesheet" href="./bootstrap-5.0.2-dist/css/bootstrap.css">
  <link rel="stylesheet" href="./plei.css">
</head>
<body>

<nav class="plei-navbar">
  <button class="menu-toggle" type="button"
    data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
    aria-controls="sidebarMenu">
    <i class="bi bi-list"></i>
  </button>
  <a href="#" class="brand">PL<span>EI</span></a>
  <div class="user-badge">
    <i class="bi bi-person-circle"></i>
    <?php echo $nombre_completo; ?>
  </div>
</nav>

<div class="offcanvas offcanvas-start plei-sidebar" tabindex="-1"
     id="sidebarMenu" aria-labelledby="sidebarLabel">
  <div class="offcanvas-header">
    <span class="offcanvas-title" id="sidebarLabel">
      <i class="bi bi-grid-1x2-fill me-2" style="color:var(--accent-light)"></i>Menu Principal
    </span>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="sidebar-body">

      <?php if($esAdmin): ?>
      <span class="sidebar-section-label">Administracion</span>
      <a class="sidebar-link" href="./php/altas/register.php">
        <i class="bi bi-person-plus-fill"></i> Registrar usuario
      </a>
      <a class="sidebar-link" href="./php/listados/lista_personas.php">
        <i class="bi bi-people-fill"></i> Lista de personas
      </a>
      <div class="sidebar-tree-item">
        <button class="sidebar-tree-toggle">
          <i class="bi bi-person-badge-fill icon-main"></i>
          <span class="label">Preceptores</span>
          <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sidebar-submenu">
          <a href="./php/altas/preceptor_x_curso.php">Alta preceptor por curso</a>
          <a href="./php/listados/lista_preceptores.php">Ver preceptores</a>
        </div>
      </div>
      <div class="sidebar-tree-item">
        <button class="sidebar-tree-toggle">
          <i class="bi bi-tag-fill icon-main"></i>
          <span class="label">Modalidades</span>
          <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sidebar-submenu">
          <a href="./php/altas/modalidad.php">Alta modalidad</a>
          <a href="./php/listados/listar_modalidad.php">Ver modalidades</a>
        </div>
      </div>
      <div class="sidebar-tree-item">
        <button class="sidebar-tree-toggle">
          <i class="bi bi-layout-split icon-main"></i>
          <span class="label">Secciones</span>
          <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sidebar-submenu">
          <a href="./php/altas/seccion.php">Alta seccion</a>
          <a href="./php/listados/lista_secciones.php">Ver secciones</a>
        </div>
      </div>
      <div class="sidebar-tree-item">
        <button class="sidebar-tree-toggle">
          <i class="bi bi-person-gear icon-main"></i>
          <span class="label">Tipo persona</span>
          <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sidebar-submenu">
          <a href="./php/altas/tipo_persona.php">Alta tipo</a>
          <a href="./php/listados/ver_tipos.php">Ver tipos</a>
        </div>
      </div>
      <a class="sidebar-link" href="./php/altas/alta_rol.php">
        <i class="bi bi-shield-lock-fill"></i> Alta rol
      </a>
      <?php endif; ?>

      <?php if($esAdmin || $esPreceptor): ?>
      <span class="sidebar-section-label">Gestion Escolar</span>
      <?php if($esAdmin): ?>
      <a class="sidebar-link" href="./php/boletin/admin_ciclos_periodos.php">
        <i class="bi bi-journal-check"></i> Ciclos y periodos boletin
      </a>
      <a class="sidebar-link" href="./php/boletin/admin_config_boletin_anual.php">
        <i class="bi bi-sliders2"></i> Configuracion boletin anual
      </a>
      <?php endif; ?>
      <div class="sidebar-tree-item">
        <button class="sidebar-tree-toggle">
          <i class="bi bi-mortarboard-fill icon-main"></i>
          <span class="label">Docentes</span>
          <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sidebar-submenu">
          <a href="./php/listados/lista_docentes.php">Ver docentes</a>
          <?php if($esAdmin || $esPreceptor): ?>
          <a href="./php/altas/AD.php">Materias por docente</a>
          <?php endif; ?>
        </div>
      </div>
      <?php if($esAdmin || $esPreceptor): ?>
      <div class="sidebar-tree-item">
        <button class="sidebar-tree-toggle">
          <i class="bi bi-people icon-main"></i>
          <span class="label">Alumnos</span>
          <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sidebar-submenu">
          <a href="./php/altas/alta_alumno_curso.php">Alta alumno</a>
          <a href="./php/listados/lista_alumnos.php">Ver alumnos</a>
        </div>
      </div>
      <?php endif; ?>
      <div class="sidebar-tree-item">
        <button class="sidebar-tree-toggle">
          <i class="bi bi-book-fill icon-main"></i>
          <span class="label">Materias</span>
          <i class="bi bi-chevron-right chevron"></i>
        </button>
        <div class="sidebar-submenu">
          <?php if($esAdmin || $esPreceptor): ?>
          <a href="./php/altas/alta_materia.php">Alta materia</a>
          <?php endif; ?>
          <a href="./php/listados/lista_materias.php">Ver materias</a>
        </div>
      </div>
      <?php if($esAdmin): ?>
      <a class="sidebar-link" href="./php/altas/cursos.php">
        <i class="bi bi-calendar3-fill"></i> Alta curso
      </a>
      <?php endif; ?>
      <?php if($esAdmin || $esPreceptor): ?>
      <a class="sidebar-link" href="./php/listados/lista_cursos.php">
        <i class="bi bi-calendar2-week-fill"></i> Ver cursos
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <?php if($esAdmin || $esDocente): ?>
      <span class="sidebar-section-label">Materiales</span>
      <a class="sidebar-link" href="./php/altas/materiales.php">
        <i class="bi bi-file-earmark-plus-fill"></i> Alta material
      </a>
      <?php if($esDocente): ?>
      <a class="sidebar-link" href="./php/modificaciones/editar_mi_material.php">
        <i class="bi bi-pencil-fill"></i> Editar mis materiales
      </a>
      <?php endif; ?>
      <?php endif; ?>

      <?php if($esDocente): ?>
      <span class="sidebar-section-label">Mi espacio</span>
      <a class="sidebar-link" href="./php/listados/ver_mis_alumnos.php">
        <i class="bi bi-person-lines-fill"></i> Ver mis alumnos
      </a>
      <a class="sidebar-link" href="./php/boletin/docente_boletin.php">
        <i class="bi bi-journal-bookmark-fill"></i> Boletin digital
      </a>
      <a class="sidebar-link" href="./php/listados/lista_materiax_docente.php">
        <i class="bi bi-journal-bookmark-fill"></i> Ver mis materias
      </a>
      <a class="sidebar-link" href="./php/modificaciones/editar_perfil.php">
        <i class="bi bi-pencil-square"></i> Editar mi perfil
      </a>
      <?php endif; ?>

      <?php if($esPreceptor): ?>
      <span class="sidebar-section-label">Mi espacio</span>
      <a class="sidebar-link" href="./php/listados/lista_cursos_a_cargo.php">
        <i class="bi bi-house-door-fill"></i> Mis cursos a cargo
      </a>
      <a class="sidebar-link" href="./php/boletin/preceptor_boletines.php">
        <i class="bi bi-clipboard2-check"></i> Boletines por curso
      </a>
      <a class="sidebar-link" href="./php/boletin/preceptor_asignar_grupos.php">
        <i class="bi bi-diagram-3-fill"></i> Asignar grupos de taller
      </a>
      <a class="sidebar-link" href="./php/boletin/preceptor_complemento_anual.php">
        <i class="bi bi-table"></i> Cierre/complemento anual
      </a>
      <a class="sidebar-link" href="./php/modificaciones/editar_perfil.php">
        <i class="bi bi-pencil-square"></i> Editar mi perfil
      </a>
      <?php endif; ?>

      <?php if($esAlumno): ?>
      <span class="sidebar-section-label">Mi espacio</span>
      <a class="sidebar-link" href="./php/listados/lista_materias_asignadas.php">
        <i class="bi bi-book-half"></i> Mis materias
      </a>
      <a class="sidebar-link" href="./php/boletin/alumno_mi_boletin.php">
        <i class="bi bi-file-earmark-text-fill"></i> Mi boletin
      </a>
      <a class="sidebar-link" href="./php/listados/lista_material_asignado.php">
        <i class="bi bi-files"></i> Mis materiales
      </a>
      <a class="sidebar-link" href="./php/modificaciones/editar_perfilAlumno.php">
        <i class="bi bi-pencil-square"></i> Editar mi perfil
      </a>
      <?php endif; ?>

    </div>
  </div>
</div>

<main class="plei-content">
  <div class="welcome-header">
    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
      <?php if($esAdmin): echo '<span class="role-badge admin"><i class="bi bi-shield-fill"></i> Administrador</span>'; endif; ?>
      <?php if($esDocente): echo '<span class="role-badge docente"><i class="bi bi-mortarboard-fill"></i> Docente</span>'; endif; ?>
      <?php if($esPreceptor): echo '<span class="role-badge preceptor"><i class="bi bi-person-badge-fill"></i> Preceptor</span>'; endif; ?>
      <?php if($esAlumno): echo '<span class="role-badge alumno"><i class="bi bi-person-fill"></i> Alumno</span>'; endif; ?>
    </div>
    <div class="greeting"><?php echo $saludo; ?>, <span><?php echo $nombre; ?></span> 👋</div>
    <div class="subtitle">
      <?php if($esAlumno): ?>Aca tenes todos tus materiales organizados por materia.
      <?php elseif($esDocente): ?>Bienvenido al panel docente. Gestiona tus materias y materiales.
      <?php elseif($esPreceptor): ?>Bienvenido al panel de preceptoria. Gestiona tus cursos y alumnos.
      <?php elseif($esAdmin): ?>Panel de administracion. Tenes control total del sistema.
      <?php endif; ?>
    </div>
  </div>

  <?php if($esAlumno): ?>
  <div class="materiales-section">
    <div class="section-title">
      <i class="bi bi-journals"></i> Materiales de tus profesores
    </div>
    <?php if(empty($materiales_por_materia)): ?>
    <div class="mc-empty">
      <i class="bi bi-inbox"></i>
      <strong>Todavia no hay materiales cargados</strong><br>
      <small>Cuando tus docentes suban materiales, van a aparecer aca.</small>
    </div>
    <?php else: ?>
    <?php foreach($materiales_por_materia as $materia_data): $mats = $materia_data['materiales']; ?>
    <div class="subject-group">
      <div class="subject-label">
        <?php echo htmlspecialchars((string)$materia_data['titulo']); ?>
        <span style="font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0"> — <?php echo htmlspecialchars((string)$materia_data['detalle']); ?></span>
        <span style="font-weight:400;color:var(--text-muted);text-transform:none;letter-spacing:0"> &mdash; <?php echo count($mats); ?> recurso<?php echo count($mats)>1?'s':''; ?></span>
      </div>
      <div class="material-cards-row">
        <?php foreach($mats as $mat): ?>
        <?php
          $url_material_actual = (string)($mat['url'] ?? '');
          $tipo = strtolower(trim((string)$mat['tipo_material']));
          $ext = extension_material_desde_url($url_material_actual);
          $es_local = es_ruta_local_material_valida($url_material_actual);
          $url_valida = material_url_es_valida($url_material_actual);
          $es_previsualizable = $es_local && material_local_es_previsualizable($url_material_actual);
          $href_ver = htmlspecialchars(url_material($url_material_actual, false, (int)$mat['id_material']));
          $href_descarga = htmlspecialchars(url_material($url_material_actual, true, (int)$mat['id_material']));
          $titulo_preview = htmlspecialchars(((string)$materia_data['titulo']) . ' - ' . ((string)$mat['tipo_material']));

          if ($ext === 'pdf' || str_contains($tipo, 'pdf')) {
            $ic = 'bi-file-earmark-pdf-fill';
          } elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true) || str_contains($tipo, 'imagen')) {
            $ic = 'bi-file-earmark-image-fill';
          } elseif (str_contains($tipo, 'video')) {
            $ic = 'bi-camera-video-fill';
          } elseif (!$es_local || str_contains($tipo, 'link')) {
            $ic = 'bi-link-45deg';
          } else {
            $ic = 'bi-file-earmark-fill';
          }
        ?>
        <div class="material-card">
          <span class="mc-type"><i class="bi <?php echo $ic; ?>"></i> <?php echo htmlspecialchars($mat['tipo_material']); ?></span>
          <?php if(!empty($mat['unidad'])): ?><div class="mc-unit"><?php echo htmlspecialchars($mat['unidad']); ?></div><?php endif; ?>
          <?php if ($url_valida && $es_previsualizable): ?>
          <a href="<?php echo $href_ver; ?>" class="mc-link" data-material-preview="1" data-preview-kind="<?php echo htmlspecialchars($ext); ?>" data-preview-title="<?php echo $titulo_preview; ?>" data-download-url="<?php echo $href_descarga; ?>">
            Ver material <i class="bi bi-arrow-right"></i>
          </a>
          <?php elseif ($url_valida && !$es_local): ?>
          <a href="<?php echo $href_ver; ?>" class="mc-link" target="_blank" rel="noopener">
            Abrir enlace <i class="bi bi-box-arrow-up-right"></i>
          </a>
          <?php elseif ($url_valida && $es_local): ?>
          <a href="<?php echo $href_descarga; ?>" class="mc-link">
            Descargar material <i class="bi bi-download"></i>
          </a>
          <?php else: ?>
          <span class="mc-link" style="opacity:.6;cursor:not-allowed">Material no disponible</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php elseif($esDocente): ?>
  <div class="action-grid">
    <a href="./php/boletin/docente_boletin.php" class="action-card"><div class="ac-icon"><i class="bi bi-journal-check"></i></div><div class="ac-title">Boletin digital</div><div class="ac-desc">Cargar notas por periodo y materia.</div></a>
    <a href="./php/listados/lista_materiax_docente.php" class="action-card"><div class="ac-icon"><i class="bi bi-journal-bookmark-fill"></i></div><div class="ac-title">Mis materias</div><div class="ac-desc">Ver las materias que tenes asignadas.</div></a>
    <a href="./php/listados/ver_mis_alumnos.php" class="action-card"><div class="ac-icon"><i class="bi bi-person-lines-fill"></i></div><div class="ac-title">Mis alumnos</div><div class="ac-desc">Ver alumnos inscriptos en tus materias.</div></a>
    <a href="./php/altas/materiales.php" class="action-card"><div class="ac-icon"><i class="bi bi-file-earmark-plus-fill"></i></div><div class="ac-title">Subir material</div><div class="ac-desc">Agregar un nuevo recurso para tus alumnos.</div></a>
    <a href="./php/modificaciones/editar_mi_material.php" class="action-card"><div class="ac-icon"><i class="bi bi-pencil-fill"></i></div><div class="ac-title">Editar materiales</div><div class="ac-desc">Modificar o eliminar materiales existentes.</div></a>
    <a href="./php/modificaciones/editar_perfil.php" class="action-card"><div class="ac-icon"><i class="bi bi-person-circle"></i></div><div class="ac-title">Mi perfil</div><div class="ac-desc">Actualizá tus datos personales.</div></a>
  </div>

  <?php elseif($esPreceptor): ?>
  <div class="action-grid">
    <a href="./php/boletin/preceptor_boletines.php" class="action-card"><div class="ac-icon"><i class="bi bi-clipboard2-check"></i></div><div class="ac-title">Boletines</div><div class="ac-desc">Abrir, revisar y publicar boletines.</div></a>
    <a href="./php/boletin/preceptor_asignar_grupos.php" class="action-card"><div class="ac-icon"><i class="bi bi-diagram-3-fill"></i></div><div class="ac-title">Grupos de taller</div><div class="ac-desc">Asignar alumnos por grupo de materia.</div></a>
    <a href="./php/listados/lista_cursos_a_cargo.php" class="action-card"><div class="ac-icon"><i class="bi bi-house-door-fill"></i></div><div class="ac-title">Mis cursos</div><div class="ac-desc">Ver los cursos que tenes a cargo.</div></a>
    <a href="./php/listados/lista_alumnos.php" class="action-card"><div class="ac-icon"><i class="bi bi-people-fill"></i></div><div class="ac-title">Ver alumnos</div><div class="ac-desc">Listado completo de alumnos.</div></a>
    <a href="./php/modificaciones/editar_perfil.php" class="action-card"><div class="ac-icon"><i class="bi bi-person-circle"></i></div><div class="ac-title">Mi perfil</div><div class="ac-desc">Actualizá tus datos personales.</div></a>
  </div>

  <?php elseif($esAdmin): ?>
  <div class="action-grid">
    <a href="./php/boletin/admin_ciclos_periodos.php" class="action-card"><div class="ac-icon"><i class="bi bi-journal-check"></i></div><div class="ac-title">Ciclos boletin</div><div class="ac-desc">Gestionar ciclos y periodos de boletin.</div></a>
    <a href="./php/altas/register.php" class="action-card"><div class="ac-icon"><i class="bi bi-person-plus-fill"></i></div><div class="ac-title">Registrar usuario</div><div class="ac-desc">Crear una nueva cuenta en el sistema.</div></a>
    <a href="./php/listados/lista_personas.php" class="action-card"><div class="ac-icon"><i class="bi bi-people-fill"></i></div><div class="ac-title">Lista de personas</div><div class="ac-desc">Ver y gestionar todos los usuarios.</div></a>
    <a href="./php/listados/lista_docentes.php" class="action-card"><div class="ac-icon"><i class="bi bi-mortarboard-fill"></i></div><div class="ac-title">Docentes</div><div class="ac-desc">Gestionar el cuerpo docente.</div></a>
    <a href="./php/listados/lista_preceptores.php" class="action-card"><div class="ac-icon"><i class="bi bi-person-badge-fill"></i></div><div class="ac-title">Preceptores</div><div class="ac-desc">Asignar y ver preceptores por curso.</div></a>
    <a href="./php/altas/cursos.php" class="action-card"><div class="ac-icon"><i class="bi bi-calendar3-fill"></i></div><div class="ac-title">Cursos</div><div class="ac-desc">Crear y gestionar cursos.</div></a>
    <a href="./php/listados/lista_materias.php" class="action-card"><div class="ac-icon"><i class="bi bi-book-fill"></i></div><div class="ac-title">Materias</div><div class="ac-desc">Ver y gestionar todas las materias.</div></a>
  </div>
  <?php endif; ?>

  <?php if($esAlumno): ?>
  <div class="action-grid" style="margin-top:1rem">
    <a href="./php/boletin/alumno_mi_boletin.php" class="action-card"><div class="ac-icon"><i class="bi bi-file-earmark-text-fill"></i></div><div class="ac-title">Mi boletin</div><div class="ac-desc">Descargar boletines publicados en PDF.</div></a>
  </div>
  <?php endif; ?>

</main>

<a href="./php/cerrar_sesion.php" class="btn-logout">
  <i class="bi bi-box-arrow-right"></i> Cerrar sesion
</a>

<script src="./bootstrap-5.0.2-dist/js/bootstrap.bundle.js"></script>
<script src="./assets/js/material-viewer.js"></script>
<script>
document.querySelectorAll('.sidebar-tree-toggle').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const item = this.closest('.sidebar-tree-item');
    const isOpen = item.classList.contains('open');
    document.querySelectorAll('.sidebar-tree-item').forEach(i => i.classList.remove('open'));
    if (!isOpen) item.classList.add('open');
  });
});
</script>
</body>
</html>
