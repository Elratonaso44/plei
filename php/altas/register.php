<?php
  include '../conesion.php';

  $resultado = mysqli_query($con, "SELECT * FROM tipos_personas");  
  $tipos_personas = [];

  if($resultado){    
    while($tipo = mysqli_fetch_assoc($resultado)){      
      $tipos_personas[] = $tipo;
    }
  } 

  $resultado = mysqli_query($con, "SELECT * FROM roles");
  $roles = [];

  if($resultado){
    while($rol = mysqli_fetch_assoc($resultado)){
      $roles[] = $rol;
    }
  }


  if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
    $dni = $_POST['dni'];  
    $apellido = $_POST['apellido'];  
    $nombre = $_POST['nombre'];  
    $email = $_POST['email'];  
    $user = $_POST['user'];  
    $pass = $_POST['pass'];  
    $tipo = $_POST['tipo'];
    $rol = $_POST['rol'];

  mysqli_query($con, "INSERT INTO personas 
  (dni, apellido, nombre, mail, usuario, password, id_rol) 
  VALUES ('$dni', '$apellido','$nombre','$email','$user','$pass', '$rol')");

  $ultimo_id=mysqli_insert_id($con); 
  echo "<script> alert($tipo)</script>";
 
  mysqli_query($con, "INSERT INTO tipo_persona_x_persona 
  (id_persona, id_tipo_persona) VALUES ('$ultimo_id','$tipo')");
} 
?>
<!DOCTYPE html><html lang="es">
  <head>  
    <meta charset="UTF-8" />  
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>  
    <title>Registrarse</title>  
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  </head>
  <body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;"> 
       <div class="card-body">      
        <h2 class="text-center fw-bold mb-4">Registrarse</h2>      
        <form autocomplete="off" action="" method="post">        
          <div class="mb-3">          
            <input type="number" name="dni" class="form-control" placeholder="DNI" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>        
          </div>        
          <div class="mb-3">          
            <input type="text" name="apellido" class="form-control" placeholder="Apellido" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>        
          </div>        
          <div class="mb-3">          
            <input type="text" name="nombre" class="form-control" placeholder="Nombre" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>        
          </div>        
          <div class="mb-3">          
            <input type="email" name="email" class="form-control" placeholder="Email" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>        
          </div>
        <div class="mb-3">          
          <select name="tipo" class="form-select" required >       
            <option value="">Seleccione un tipo persona</option>
            <?php foreach ($tipos_personas as $tipo){ ?>
              <option value="<?php echo htmlspecialchars($tipo['id_tipo_persona']); ?>">
                <?php echo htmlspecialchars($tipo['tipo']); ?>
              </option><?php }?>         
            </select>        
          </div>
           <div class="mb-3">          
          <select name="rol" class="form-select" required >       
            <option value="">Seleccione una rol</option>
            <?php foreach ($roles as $rol){ ?>
              <option value="<?php echo htmlspecialchars($rol['id_rol']); ?>">
                <?php echo htmlspecialchars($rol['rol']); ?>
              </option><?php }?>         
            </select>        
          </div>
        <div class="mb-3">          
          <input type="text" name="user" class="form-control" placeholder="Usuario" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>        
        </div>        <div class="mb-3">          <input type="password" name="pass" class="form-control" placeholder="Contraseña" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>        
      </div>        
      <input type="submit" value="Registrarse" class="btn w-100 text-white"          style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"          onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"          onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">                
      <div class="text-center mt-3">          
        <a href="http://localhost/Dinamica/practica/index.html" class="btn btn-sm btn-outline-secondary">¿Ya tenés cuenta? Iniciá sesión</a>        
      </div>      
    </form>    
  </div>  
</div>
  <script src="./bootstrap-5.0.2-dist/js/bootstrap.js"></script>
</body>
</html>