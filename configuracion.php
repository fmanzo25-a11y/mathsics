<?php

include_once 'conexion.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id = $_SESSION['user_id'];




try {
    $conn = Db::conectar();

    // Consulta para los datos del usuario
    $stmtUser = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmtUser->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);


}catch(PDOException){
    
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracion</title>
    <link rel="icon" href="images/logo.png" type="image/png">
</head>
<body>
    <h1>Configuracion</h1>
    <h2>Informacion de perfil</h2>
     <img src="<?php $user['foto_de_perfil']  ?? "images/sinfoto.jpeg"  ?>  "  alt="foto_de_perfil">
     <p>Nombre: <?php $user['nombre']?></p>

</body>
</html>
