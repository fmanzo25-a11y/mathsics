<?php
// Archivo: reclamar_desafio.php
session_start();
include_once 'conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

$id_usuario = $_SESSION['user_id'];
$id_usuario_desafio = $_POST['id_usuario_desafio'] ?? 0;

if ($id_usuario_desafio == 0) {
    echo json_encode(['success' => false, 'message' => 'ID de desafío inválido.']);
    exit;
}

try {
    $conn = Db::conectar();
    
    // 1. Verificar que el desafío pertenece al usuario y está 'completado'
    $stmt = $conn->prepare("SELECT ud.id, d.recompensa_xp FROM usuario_desafios ud JOIN desafios d ON ud.id_desafio = d.id WHERE ud.id = :id_ud AND ud.id_usuario = :id_u AND ud.estado = 'completado'");
    $stmt->execute(['id_ud' => $id_usuario_desafio, 'id_u' => $id_usuario]);
    $desafio = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($desafio) {
        $recompensa = (int)$desafio['recompensa_xp'];

        // 2. Aplicar la recompensa de XP
        $stmtUser = $conn->prepare("UPDATE usuarios SET xp = xp + :recompensa WHERE id = :id_u");
        $stmtUser->execute(['recompensa' => $recompensa, 'id_u' => $id_usuario]);

        // Verificar si subió de nivel
        if (method_exists('Db', 'check_and_update_level')) {
             Db::check_and_update_level($id_usuario);
        }

        // Obtener los datos actualizados
        $stmtUpdated = $conn->prepare("SELECT xp, nivel, limite_xp FROM usuarios WHERE id = :id_u");
        $stmtUpdated->execute(['id_u' => $id_usuario]);
        $userData = $stmtUpdated->fetch(PDO::FETCH_ASSOC);

        // 3. Marcar el desafío como 'reclamado'
        $stmtUpdate = $conn->prepare("UPDATE usuario_desafios SET estado = 'reclamado' WHERE id = :id_ud");
        $stmtUpdate->execute(['id_ud' => $id_usuario_desafio]);

        echo json_encode([
            'success' => true, 
            'recompensa_xp' => $recompensa, // El front espera esto en el alert (o recompensa)
            'recompensa' => $recompensa, 
            'nuevo_xp' => (int)$userData['xp'],
            'nuevo_nivel' => (int)$userData['nivel'],
            'nuevo_limite_xp' => (int)$userData['limite_xp']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se puede reclamar este desafío.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>