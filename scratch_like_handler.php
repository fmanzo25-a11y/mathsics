<?php
include_once 'conexion.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id_juego = $data['id_juego'] ?? null;
$id_usuario = $_SESSION['user_id'];

if (!$id_juego) {
    echo json_encode(['error' => 'ID de juego no proporcionado']);
    http_response_code(400);
    exit();
}

try {
    $conn = Db::conectar();
    $conn->beginTransaction();

    // Check if the user has already liked the game
    $stmt = $conn->prepare("SELECT id FROM scratch_likes WHERE id_juego = :id_juego AND id_usuario = :id_usuario");
    $stmt->execute([':id_juego' => $id_juego, ':id_usuario' => $id_usuario]);
    $like = $stmt->fetch();

    if ($like) {
        // Unlike the game
        $stmt = $conn->prepare("DELETE FROM scratch_likes WHERE id = :id");
        $stmt->execute([':id' => $like['id']]);
        $liked = false;
    } else {
        // Like the game
        $stmt = $conn->prepare("INSERT INTO scratch_likes (id_juego, id_usuario) VALUES (:id_juego, :id_usuario)");
        $stmt->execute([':id_juego' => $id_juego, ':id_usuario' => $id_usuario]);
        $liked = true;
    }

    // Update the total likes count in the scratch_games table
    $stmt = $conn->prepare("SELECT COUNT(*) as total_likes FROM scratch_likes WHERE id_juego = :id_juego");
    $stmt->execute([':id_juego' => $id_juego]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $newLikes = $result['total_likes'];

    $stmt = $conn->prepare("UPDATE scratch_games SET likes = :likes WHERE id = :id");
    $stmt->execute([':likes' => $newLikes, ':id' => $id_juego]);

    $conn->commit();

    echo json_encode(['success' => true, 'liked' => $liked, 'newLikes' => $newLikes]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error en scratch_like_handler.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error en la base de datos']);
    http_response_code(500);
}
?>