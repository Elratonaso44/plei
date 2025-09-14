<?php
session_start();

session_destroy();
header("Location: https://localhost/Dinamica/practica/index.html");
exit;
