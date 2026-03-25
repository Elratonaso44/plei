<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol(['administrador', 'preceptor']);

redirigir('php/altas/alta_alumno_curso.php');
