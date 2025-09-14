 <?php
  include("../conesion.php");

  session_start();


 if($_SERVER["REQUEST_METHOD"] == "POST"){ 
    $usuario = $_POST['user'];
    $password = $_POST['pass'];
    if(!empty($usuario) || !empty($password)){
    
      $resultado = mysqli_query($con, "SELECT personas.id_persona,nombre,apellido,usuario,password,t.tipo,r.rol from personas 
      INNER JOIN tipo_persona_x_persona as ti on ti.id_persona=personas.id_persona
      INNER JOIN tipos_personas as t on t.id_tipo_persona=ti.id_tipo_persona
      INNER JOIN roles as r on personas.id_rol=r.id_rol WHERE usuario='$usuario'");

      
      if ($resultado) { 
        $informacion = mysqli_fetch_assoc($resultado);

        if (!$informacion) {
          echo 'Usuario no encontrado';
        } else if ($informacion && $password != $informacion['password'])
        {
          echo 'Contraseña incorrecta.';
        } else {
          $_SESSION['username'] = $informacion['usuario'];
          $_SESSION['nombre'] = $informacion['nombre'];
          $_SESSION['apellido'] = $informacion['apellido'];
          $_SESSION['id_persona']= $informacion['id_persona'];
          $_SESSION['rol'] = $informacion['rol'];
          $_SESSION['tipo'] = $informacion['tipo'];
          echo 'Usuario logueado.';
          header("Location:http://localhost/Dinamica/practica/home.php");
        }
      }
    mysqli_close($con);
    } else {
      echo "<script>
          alert('Campos incompletos!');
        window.history.back();
    </script>
    ";
  }
 }



?> 


