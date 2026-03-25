<?php
include_once 'conexion.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id_publicacion'])) {
    echo json_encode(['success' => false, 'error' => 'Post ID not provided']);
    exit();
}

$id_usuario = $_SESSION['user_id'];
$id_publicacion = $data['id_publicacion'];

try {
    $conn = Db::conectar();
    $conn->beginTransaction();

    // 1. Revisar si el like ya existe
    $stmtCheck = $conn->prepare("SELECT id_like FROM likes WHERE id_usuario = :id_usuario AND id_publicacion = :id_publicacion");
    $stmtCheck->execute([':id_usuario' => $id_usuario, ':id_publicacion' => $id_publicacion]);
    $existingLike = $stmtCheck->fetch();

    if ($existingLike) {
        // Si ya existe, lo borramos (unlike)
        $stmtDelete = $conn->prepare("DELETE FROM likes WHERE id_like = :id_like");
        $stmtDelete->execute([':id_like' => $existingLike['id_like']]);

        // Decrementamos el contador en la tabla de posts
        $stmtUpdate = $conn->prepare("UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE id_publicacion = :id_publicacion");
        $stmtUpdate->execute([':id_publicacion' => $id_publicacion]);
        $liked = false;
    } else {
        // Si no existe, lo insertamos (like)
        $stmtInsert = $conn->prepare("INSERT INTO likes (id_usuario, id_publicacion) VALUES (:id_usuario, :id_publicacion)");
        $stmtInsert->execute([':id_usuario' => $id_usuario, ':id_publicacion' => $id_publicacion]);

        // Incrementamos el contador en la tabla de posts
        $stmtUpdate = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id_publicacion = :id_publicacion");
        $stmtUpdate->execute([':id_publicacion' => $id_publicacion]);
        $liked = true;
    }

    // Obtenemos el nuevo conteo de likes
    $stmtCount = $conn->prepare("SELECT likes FROM posts WHERE id_publicacion = :id_publicacion");
    $stmtCount->execute([':id_publicacion' => $id_publicacion]);
    $newLikes = $stmtCount->fetchColumn();

    $conn->commit();

    echo json_encode(['success' => true, 'liked' => $liked, 'newLikes' => (int)$newLikes]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Like handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>