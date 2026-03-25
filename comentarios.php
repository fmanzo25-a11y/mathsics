<?php

include_once 'conexion.php';

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);



if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {

  $contenido = trim($_POST['contenido'] ?? '');
  $id_publicacion = trim($_POST['id_publicacion'] ?? '');
  $userid = $_POST['id_usuario'];



  try {

    $conn = Db::conectar();

    $stmt = $conn->prepare(
      "INSERT INTO comentarios (contenido,id_usuario,id_publicacion) VALUES (:contenido, :userid,:id_publicacion)"
    );
    $stmt->execute([
      ':contenido' => $contenido,
      ':userid' => $userid,
      ':id_publicacion' => $id_publicacion
    ]);

    header('Location: publicacion.php?id=' . $id_publicacion);
    exit();


  } catch (Exception $e) {
    echo $e->getMessage();

  }


}





?>