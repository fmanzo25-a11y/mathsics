<?php
include_once 'conexion.php';
session_start();

// Establecemos que la respuesta será en formato JSON
header('Content-Type: application/json');

// Verificamos que el usuario esté logueado y que la petición sea POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si no, enviamos un error y terminamos
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

// Leemos los datos enviados por el formulario de JavaScript
$id_notificacion = $_POST['id_notificacion'] ?? null;
$id_usuario_origen = $_POST['id_usuario_origen'] ?? null; // El ID del niño que solicita activación
$accion = $_POST['accion'] ?? null;
$id_tutor = $_SESSION['user_id']; // El usuario actual es el tutor

// Validamos que tenemos toda la información necesaria
if (!$id_notificacion || !$id_usuario_origen || !$accion) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

try {
    $conn = Db::conectar();
    // Iniciamos una transacción para asegurar que todas las operaciones se completen con éxito
    $conn->beginTransaction();

    // Primero, verificamos que la notificación realmente le pertenece al tutor actual para seguridad
    $stmtCheck = $conn->prepare("SELECT * FROM notificaciones WHERE id_notificacion = :id_notificacion AND id_usuario = :id_tutor");
    $stmtCheck->execute([':id_notificacion' => $id_notificacion, ':id_tutor' => $id_tutor]);
    if (!$stmtCheck->fetch()) {
        throw new Exception("Notificación no válida o no te pertenece.");
    }
    
    // Realizamos la acción correspondiente
    if ($accion === 'aceptar') {
        // Si se acepta, se activa la cuenta del niño
        $stmtUpdateUser = $conn->prepare("UPDATE usuarios SET estado = 'Activa' WHERE id = :id_usuario_origen");
        $stmtUpdateUser->execute([':id_usuario_origen' => $id_usuario_origen]);
    }

    // Tanto si se acepta como si se rechaza, la notificación se marca como leída
    $stmtUpdateNotif = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id_notificacion = :id_notificacion");
    $stmtUpdateNotif->execute([':id_notificacion' => $id_notificacion]);

    // Si todo salió bien, guardamos los cambios en la base de datos
    $conn->commit();

    // Enviamos una respuesta de éxito al JavaScript
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Si algo falla, revertimos todos los cambios
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Y enviamos un mensaje de error al JavaScript
    error_log("Error en notificaciones_api.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error al procesar la solicitud.']);
}
?>