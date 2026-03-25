


<?php 

// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;



include_once 'conexion.php';



require 'vendor/autoload.php';




// Crear una instancia de PHPMailer
$mail = new PHPMailer(true);


if( $_SERVER['REQUEST_METHOD'] == 'POST' ){

   $contenido = trim($_POST['contenido'] ?? '');

 

try{
$conn = Db::conectar();
  $user = [];
  $stmtUser = $conn->prepare("SELECT  correo,nombre FROM usuarios");
    $stmtUser->execute();
    $users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);





    



    foreach($users as $index => $correo):

     
$patron_de_busqueda = '/\bnombre_usuarios\b/'; // Busca la palabra "gato" completa
$variable_de_reemplazo = $correo['nombre'] ;
$nuevo_texto = preg_replace($patron_de_busqueda, $variable_de_reemplazo, $contenido);


  $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';  // Servidor SMTP de tu proveedor
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jaremmanzo@gmail.com'; // Tu dirección de correo
    $mail->Password   = 'isyn wygi gtpz kaib'; // Contraseña de aplicación (NO la de tu cuenta)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    //Remitente y destinatarios
    $mail->setFrom('jaremmanzo@gmail.com', 'Francisco Jarem Manzo Suastegui');
    $mail->addAddress($correo['correo'], $correo['nombre']); // Añadir un destinatario
    // $mail->addReplyTo('info@example.com', 'Información');
    // $mail->addCC('cc@example.com');
    // $mail->addBCC('bcc@example.com');

    // Contenido del correo
    $mail->isHTML(true); // Establecer el formato del correo a HTML
    $mail->Subject = '¡¡¡¡Bienvenido a mathsics!!!';
    $mail->Body    = $nuevo_texto ;
    $mail->AltBody = '¡Hola! ' + $correo['nombre'];

    // Para adjuntar un archivo
    // $mail->addAttachment('/path/to/file.pdf');

    $mail->send();
    echo 'El mensaje ha sido enviado';
     

    endforeach;

}catch(PDOException){
    echo "no se pudo enviar";
}
}
?>