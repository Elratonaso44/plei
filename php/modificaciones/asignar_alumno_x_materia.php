<?php
include "../config.php";
session_start();
exigir_rol(['administrador', 'preceptor']);

redirigir('home.php');
