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
        $fila_encontrada = mysqli_fetch_assoc($resultado);

        if (!$fila_encontrada) {
          echo 'Usuario no encontrado';
        } else if ($fila_encontrada && $password != $fila_encontrada['password'])
        {
          echo 'Contraseña incorrecta.';
        } else {
          $_SESSION['username'] = $fila_encontrada['usuario'];
          $_SESSION['nombre'] = $fila_encontrada['nombre'];
          $_SESSION['apellido'] = $fila_encontrada['apellido'];
          $_SESSION['id_persona']= $fila_encontrada['id_persona'];
          $_SESSION['rol'] = $fila_encontrada['rol'];
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


