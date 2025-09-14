<?php
include "../conesion.php";
session_start();

if (!isset($_SESSION['id_persona'])) {
		header("Location:https://localhost/Dinamica/practica/index.html");
		exit;
}

$id = $_SESSION['id_persona'];

$resultado = mysqli_query($con, "SELECT * FROM personas 
WHERE id_persona = $id");

$perfil = mysqli_fetch_assoc($resultado);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$mail = $_POST['mail'];

	mysqli_query($con, "UPDATE personas SET mail='$mail' 
    WHERE id_persona='$id'");


	session_unset();
	session_destroy();
	echo '<script>
    alert("Perfil actualizado correctamente. Por favor, vuelva a iniciar sesión para ver los cambios."); 
    window.location.replace("https://localhost/Dinamica/practica/index.html");
        </script>';
	exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Editar Perfil</title>
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
	</head>
	<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
		<div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
			<div class="card-body">
				<h2 class="text-center fw-bold mb-4">Editar Perfil</h2>
				<form method="post">
					<div class="mb-3">
						<label class="form-label">Email</label>
						<input type="text" name="mail" class="form-control" value="<?php echo htmlspecialchars($perfil['mail']); ?>" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>
					</div>
					<button type="submit" class="btn w-100 text-white"
						style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
						onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
						onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">
						Guardar Cambios
					</button>
					<a href="../../home.php" class="boton-volver ms-2 btn" style="background-color: rgba(15, 15, 15, 0.7); color: #fff; border-radius: 0.5rem; border: 2px solid #00004F; transition: all 0.3s; margin-top: 10px;" onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'" onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">Cancelar</a>
				</form>
			</div>
		</div>
		<script src="../../bootstrap-5.0.2-dist/js/bootstrap.js"></script>
	</body>
</head>

</html>
