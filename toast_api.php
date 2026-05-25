<?php
include_once 'conexion.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$id_usuario = $_SESSION['user_id'];

try {
    $conn = Db::conectar();
    
    // Obtener notificaciones importantes no leídas ni mostradas
    $stmt = $conn->prepare("SELECT * FROM notificaciones 
                            WHERE id_usuario = :id_usuario 
                            AND leida = '0' 
                            AND toast_mostrado = 0 
                            AND tipo IN ('duelo', 'peticion')
                            ORDER BY fecha ASC LIMIT 5");
    $stmt->execute([':id_usuario' => $id_usuario]);
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $toasts = [];
    
    if (count($notificaciones) > 0) {
        $ids_to_update = [];
        
        foreach ($notificaciones as $n) {
            $ids_to_update[] = $n['id_notificacion'];
            
            $toasts[] = [
                'id' => $n['id_notificacion'],
                'tipo' => $n['tipo'],
                'mensaje' => $n['mensaje']
            ];
        }
        
        // Marcar como mostrados (solo el toast, la notificación sigue en la bandeja)
        if (!empty($ids_to_update)) {
            $inQuery = implode(',', array_fill(0, count($ids_to_update), '?'));
            $stmtUpdate = $conn->prepare("UPDATE notificaciones SET toast_mostrado = 1 WHERE id_notificacion IN ($inQuery)");
            $stmtUpdate->execute($ids_to_update);
        }
    }
    
    echo json_encode(['success' => true, 'toasts' => $toasts]);

} catch (Exception $e) {
    error_log("Toast API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
