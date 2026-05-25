<?php
error_reporting(0);
ini_set('display_errors', 0);

include_once 'conexion.php';
include_once 'generador_ejercicios.php';
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit();
}

$id_usuario_actual = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

session_write_close();

$conn = Db::conectar();

if ($action === 'buscar') {
    $tema = $_POST['tema'] ?? 'Aleatorio';
    $stmt = $conn->prepare("SELECT id FROM duelos WHERE tema = :tema AND estado = 'buscando' AND jugador1_id != :id_usuario AND jugador2_id IS NULL LIMIT 1");
    $stmt->execute([':tema' => $tema, ':id_usuario' => $id_usuario_actual]);
    $duelo_encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($duelo_encontrado) {
        $id_duelo = $duelo_encontrado['id'];
        $stmt = $conn->prepare("UPDATE duelos SET jugador2_id = :jugador2_id, estado = 'en_curso' WHERE id = :id_duelo");
        $stmt->execute([':jugador2_id' => $id_usuario_actual, ':id_duelo' => $id_duelo]);
        echo json_encode(['status' => 'encontrado', 'id_duelo' => $id_duelo]);
    } else {
        $stmt = $conn->prepare("INSERT INTO duelos (jugador1_id, tema, estado) VALUES (:jugador1_id, :tema, 'buscando')");
        $stmt->execute([':jugador1_id' => $id_usuario_actual, ':tema' => $tema]);
        $id_duelo = $conn->lastInsertId();
        echo json_encode(['status' => 'buscando', 'id_duelo' => $id_duelo]);
    }

} elseif ($action === 'verificar') {
    $id_duelo = $_GET['id_duelo'] ?? 0;
    $stmt = $conn->prepare("SELECT estado, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(fecha_creacion) as segundos_esperando FROM duelos WHERE id = :id_duelo");
    $stmt->execute([':id_duelo' => $id_duelo]);
    $duelo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($duelo && $duelo['estado'] === 'buscando' && $duelo['segundos_esperando'] > 10) {
        $stmtBot = $conn->prepare("SELECT id FROM usuarios WHERE correo = 'bot@mathsics.com' LIMIT 1");
        $stmtBot->execute();
        $bot = $stmtBot->fetch(PDO::FETCH_ASSOC);
        if ($bot) {
            $stmtUpdate = $conn->prepare("UPDATE duelos SET jugador2_id = :bot_id, estado = 'en_curso' WHERE id = :id_duelo");
            $stmtUpdate->execute([':bot_id' => $bot['id'], ':id_duelo' => $id_duelo]);
            $duelo['estado'] = 'en_curso';
        }
    }
    
    echo json_encode(['status' => ($duelo['estado'] === 'en_curso' ? 'encontrado' : 'buscando')]);

} elseif ($action === 'retar') {
    $id_oponente = $_POST['id_oponente'] ?? 0;
    $tema = $_POST['tema'] ?? 'Aleatorio';
    
    $stmt = $conn->prepare("INSERT INTO duelos (jugador1_id, jugador2_id, tema, estado) VALUES (:j1, :j2, :tema, 'invitacion')");
    $stmt->execute([':j1' => $id_usuario_actual, ':j2' => $id_oponente, ':tema' => $tema]);
    $id_duelo = $conn->lastInsertId();
    
    $nombre_visitante = $_SESSION['user_name'] ?? 'Un usuario';
    $mensaje = "⚔️ ¡" . htmlspecialchars($nombre_visitante) . " te ha retado a un duelo de " . htmlspecialchars($tema) . "!";
    
    $stmt_notif = $conn->prepare("INSERT INTO notificaciones (id_usuario, id_usuario_origen, tipo, mensaje) VALUES (:id_usuario, :id_duelo, 'duelo', :mensaje)");
    $stmt_notif->execute([
        ':id_usuario' => $id_oponente,
        ':id_duelo' => $id_duelo,
        ':mensaje' => $mensaje
    ]);
    
    echo json_encode(['success' => true, 'id_duelo' => $id_duelo]);

} elseif ($action === 'aceptar_reto') {
    $id_duelo = $_POST['id_duelo'] ?? 0;
    $id_notificacion = $_POST['id_notificacion'] ?? 0;
    
    $stmt = $conn->prepare("UPDATE duelos SET estado = 'en_curso' WHERE id = :id_duelo AND jugador2_id = :id_usuario AND estado = 'invitacion'");
    $stmt->execute([':id_duelo' => $id_duelo, ':id_usuario' => $id_usuario_actual]);
    
    if ($stmt->rowCount() > 0) {
        $conn->prepare("UPDATE notificaciones SET leida = '1' WHERE id_notificacion = :id_not")->execute([':id_not' => $id_notificacion]);
        echo json_encode(['success' => true, 'id_duelo' => $id_duelo]);
    } else {
        echo json_encode(['success' => false, 'message' => 'El reto ya no es válido o ha expirado.']);
    }

} elseif ($action === 'rechazar_reto') {
    $id_duelo = $_POST['id_duelo'] ?? 0;
    $id_notificacion = $_POST['id_notificacion'] ?? 0;
    
    $conn->prepare("UPDATE duelos SET estado = 'finalizado' WHERE id = :id_duelo AND jugador2_id = :id_usuario")->execute([':id_duelo' => $id_duelo, ':id_usuario' => $id_usuario_actual]);
    $conn->prepare("UPDATE notificaciones SET leida = '1' WHERE id_notificacion = :id_not")->execute([':id_not' => $id_notificacion]);
    
    echo json_encode(['success' => true]);

} elseif ($action === 'verificar_invitacion') {
    $id_duelo = $_GET['id_duelo'] ?? 0;
    $stmt = $conn->prepare("SELECT estado FROM duelos WHERE id = :id_duelo AND jugador1_id = :id_usuario");
    $stmt->execute([':id_duelo' => $id_duelo, ':id_usuario' => $id_usuario_actual]);
    $duelo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($duelo) {
        if ($duelo['estado'] === 'en_curso') echo json_encode(['status' => 'aceptado']);
        elseif ($duelo['estado'] === 'finalizado') echo json_encode(['status' => 'rechazado']);
        else echo json_encode(['status' => 'esperando']);
    } else {
        echo json_encode(['status' => 'error']);
    }

} elseif ($action === 'iniciar') {
    session_start();
    $id_duelo = $_GET['id_duelo'] ?? 0;
    $stmt = $conn->prepare(
       "SELECT d.*, u1.nombre as nombre1, u2.nombre as nombre2, u1.foto_de_perfil as avatar1, u2.foto_de_perfil as avatar2
        FROM duelos d
        LEFT JOIN usuarios u1 ON d.jugador1_id = u1.id
        LEFT JOIN usuarios u2 ON d.jugador2_id = u2.id
        WHERE d.id = :id_duelo AND (d.jugador1_id = :id_usuario1 OR d.jugador2_id = :id_usuario2)"
    );
    $stmt->execute([':id_duelo' => $id_duelo, ':id_usuario1' => $id_usuario_actual, ':id_usuario2' => $id_usuario_actual]);
    $duelo_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$duelo_info) {
        echo json_encode(['status' => 'error', 'message' => 'Duelo no encontrado.']);
        exit();
    }

    $ejercicios = generarEjercicios($duelo_info['tema'], 10, [$duelo_info['jugador1_id'], $duelo_info['jugador2_id']]);
    $preguntas_duelo = [];
    foreach ($ejercicios as $ej) {
        $preguntas_duelo[] = [
            'pregunta' => $ej['pregunta'],
            'opciones' => $ej['opciones'],
            'solucion' => $ej['solucion']
        ];
    }
    
    $_SESSION['duelo_actual'] = ['id' => $id_duelo, 'preguntas' => $preguntas_duelo];
    session_write_close();

    $oponente = ($duelo_info['jugador1_id'] == $id_usuario_actual)
        ? ['nombre' => $duelo_info['nombre2'], 'avatar' => $duelo_info['avatar2']]
        : ['nombre' => $duelo_info['nombre1'], 'avatar' => $duelo_info['avatar1']];

    echo json_encode([
        'status' => 'listo',
        'tema' => $duelo_info['tema'],
        'oponente' => $oponente,
        'preguntas' => array_map(function($p) { unset($p['solucion']); return $p; }, $preguntas_duelo)
    ]);

} elseif ($action === 'responder') {
    session_start();
    $id_duelo = $_POST['id_duelo'] ?? 0;
    $pregunta_index = (int)($_POST['pregunta_index'] ?? -1);
    $respuesta_jugador = $_POST['respuesta'] ?? '';
    $duelo_session = $_SESSION['duelo_actual'] ?? null;

    if (!$duelo_session || $duelo_session['id'] != $id_duelo || !isset($duelo_session['preguntas'][$pregunta_index])) {
        echo json_encode(['status' => 'error', 'message' => 'Error de sincronización.']);
        exit();
    }
    
    $combo_bonus = (int)($_POST['combo_bonus'] ?? 0);
    $puntos = 0;
    $solucion_correcta = $duelo_session['preguntas'][$pregunta_index]['solucion'];
    if ($respuesta_jugador == $solucion_correcta) {
        $puntos = 100 + $combo_bonus; 
    }

    $stmt = $conn->prepare("SELECT jugador1_id FROM duelos WHERE id = :id_duelo");
    $stmt->execute([':id_duelo' => $id_duelo]);
    $es_jugador1 = ($stmt->fetchColumn() == $id_usuario_actual);
    $campo_puntuacion = $es_jugador1 ? 'puntuacion_j1' : 'puntuacion_j2';

    $sql = "UPDATE duelos SET $campo_puntuacion = $campo_puntuacion + :puntos WHERE id = :id_duelo";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':puntos' => $puntos, ':id_duelo' => $id_duelo]);

    // --- LÓGICA DE FINALIZACIÓN ANTICIPADA ---
    $stmt = $conn->prepare("SELECT jugador1_id, puntuacion_j1, jugador2_id, puntuacion_j2 FROM duelos WHERE id = :id_duelo");
    $stmt->execute([':id_duelo' => $id_duelo]);
    $estado_duelo = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_preguntas = count($duelo_session['preguntas']);
    $preguntas_restantes = $total_preguntas - ($pregunta_index + 1);
    $puntos_max_restantes = $preguntas_restantes * 100;

    $ganador_id = null;
    if ($estado_duelo['puntuacion_j1'] > $estado_duelo['puntuacion_j2'] + $puntos_max_restantes) {
        $ganador_id = $estado_duelo['jugador1_id'];
    } elseif ($estado_duelo['puntuacion_j2'] > $estado_duelo['puntuacion_j1'] + $puntos_max_restantes) {
        $ganador_id = $estado_duelo['jugador2_id'];
    }

    $duelo_terminado = !is_null($ganador_id) || ($preguntas_restantes <= 0);

    if ($duelo_terminado) {
        // Si no se determinó un ganador por puntaje, se decide al final
        if (is_null($ganador_id) && $preguntas_restantes <= 0) {
            if ($estado_duelo['puntuacion_j1'] > $estado_duelo['puntuacion_j2']) {
                $ganador_id = $estado_duelo['jugador1_id'];
            } elseif ($estado_duelo['puntuacion_j2'] > $estado_duelo['puntuacion_j1']) {
                $ganador_id = $estado_duelo['jugador2_id'];
            }
        }
        
        $stmt = $conn->prepare("UPDATE duelos SET estado = 'finalizado', ganador_id = :ganador_id, fecha_finalizacion = NOW() WHERE id = :id_duelo AND estado != 'finalizado'");
        $stmt->execute([':ganador_id' => $ganador_id, ':id_duelo' => $id_duelo]);
    }
    
    session_write_close();
    echo json_encode(['status' => 'ok', 'puntos_ganados' => $puntos, 'duelo_terminado' => $duelo_terminado, 'respuesta_correcta' => $solucion_correcta]);

} elseif ($action === 'finalizar') {
    session_start();
    $id_duelo = $_POST['id_duelo'] ?? 0;
    
    $stmt = $conn->prepare("SELECT * FROM duelos WHERE id = :id_duelo");
    $stmt->execute([':id_duelo' => $id_duelo]);
    $duelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$duelo) { exit(); }

    $es_jugador1 = $duelo['jugador1_id'] == $id_usuario_actual;
    $mi_puntuacion = $es_jugador1 ? $duelo['puntuacion_j1'] : $duelo['puntuacion_j2'];
    $puntuacion_oponente = $es_jugador1 ? $duelo['puntuacion_j2'] : $duelo['puntuacion_j1'];

    $resultado = 'empate';
    if ($duelo['ganador_id'] === $id_usuario_actual) {
        $resultado = 'victoria';
    } elseif (!is_null($duelo['ganador_id'])) {
        $resultado = 'derrota';
    }

    $xp_base = floor($mi_puntuacion / 20);
    $xp_ganada = 0;

    $puntos_ranking = 0;
    if ($resultado === 'victoria') {
        $xp_ganada = 50 + $xp_base;
        $puntos_ranking = 20; // Puntos estándar por victoria
    } elseif ($resultado === 'derrota') {
        $xp_ganada = 10 + $xp_base;
        // Castigo ELO dinámico
        $stmt_rank = $conn->prepare("SELECT puntos FROM ranking WHERE id_usuario = :id_usuario");
        $stmt_rank->execute([':id_usuario' => $id_usuario_actual]);
        $rank_actual = $stmt_rank->fetchColumn() ?: 0;
        
        if ($rank_actual > 500) $puntos_ranking = -15; // Veteranos pierden más
        elseif ($rank_actual > 100) $puntos_ranking = -10;
        elseif ($rank_actual > 50) $puntos_ranking = -5;
        else $puntos_ranking = 0; // Novatos no pierden
    } else {
        $xp_ganada = 25 + $xp_base;
        $puntos_ranking = 5; // Empate
    }

    if ($xp_ganada > 0) {
        $stmt = $conn->prepare("UPDATE usuarios SET xp = xp + :xp WHERE id = :id_usuario");
        $stmt->execute([':xp' => $xp_ganada, ':id_usuario' => $id_usuario_actual]);

        // Comprobar y actualizar el nivel del usuario tras ganar XP
        Db::check_and_update_level($id_usuario_actual);
    }
    
    if ($puntos_ranking != 0) {
        $conn->prepare("INSERT IGNORE INTO ranking (id_usuario, puntos) VALUES (:id, 0)")->execute([':id' => $id_usuario_actual]);
        $conn->prepare("UPDATE ranking SET puntos = GREATEST(0, puntos + :pts) WHERE id_usuario = :id")->execute([':pts' => $puntos_ranking, ':id' => $id_usuario_actual]);
    }

    unset($_SESSION['duelo_actual']);
    session_write_close();

    echo json_encode([
        'status' => 'finalizado',
        'resultado' => $resultado,
        'mi_puntuacion' => $mi_puntuacion,
        'puntuacion_oponente' => $puntuacion_oponente,
        'xp_ganada' => $xp_ganada,
        'puntos_ranking' => $puntos_ranking
    ]);
} elseif ($action === 'estado_duelo') {
    $id_duelo = $_GET['id_duelo'] ?? 0;
    $stmt = $conn->prepare("SELECT estado, puntuacion_j1, puntuacion_j2, poder_usado_j1, poder_usado_j2, UNIX_TIMESTAMP(congelado_hasta_j1) as congelado_hasta_j1, UNIX_TIMESTAMP(congelado_hasta_j2) as congelado_hasta_j2, jugador1_id, jugador2_id FROM duelos WHERE id = :id_duelo");
    $stmt->execute([':id_duelo' => $id_duelo]);
    $duelo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$duelo) { echo json_encode(['status' => 'error']); exit; }
    
    $es_jugador1 = $duelo['jugador1_id'] == $id_usuario_actual;
    $mi_puntuacion = $es_jugador1 ? $duelo['puntuacion_j1'] : $duelo['puntuacion_j2'];
    $su_puntuacion = $es_jugador1 ? $duelo['puntuacion_j2'] : $duelo['puntuacion_j1'];
    $mi_congelacion = $es_jugador1 ? $duelo['congelado_hasta_j1'] : $duelo['congelado_hasta_j2'];
    
    $estoy_congelado = ($mi_congelacion && $mi_congelacion > time());
    
    echo json_encode([
        'status' => 'ok',
        'estado_duelo' => $duelo['estado'],
        'mi_puntuacion' => $mi_puntuacion,
        'su_puntuacion' => $su_puntuacion,
        'estoy_congelado' => $estoy_congelado
    ]);
} elseif ($action === 'usar_poder') {
    session_start();
    $id_duelo = $_POST['id_duelo'] ?? 0;
    $poder = $_POST['poder'] ?? '';
    
    $stmt = $conn->prepare("SELECT jugador1_id FROM duelos WHERE id = :id_duelo");
    $stmt->execute([':id_duelo' => $id_duelo]);
    $es_jugador1 = ($stmt->fetchColumn() == $id_usuario_actual);
    
    $col_poder = $es_jugador1 ? 'poder_usado_j1' : 'poder_usado_j2';
    
    $incorrectas_a_eliminar = [];
    if ($poder === 'congelar') {
        $col_congelar = $es_jugador1 ? 'congelado_hasta_j2' : 'congelado_hasta_j1';
        $stmt = $conn->prepare("UPDATE duelos SET $col_poder = 'congelar', $col_congelar = DATE_ADD(NOW(), INTERVAL 3 SECOND) WHERE id = :id_duelo AND ($col_poder IS NULL OR $col_poder = '')");
        $stmt->execute([':id_duelo' => $id_duelo]);
    } elseif ($poder === '50_50') {
        $stmt = $conn->prepare("UPDATE duelos SET $col_poder = '50_50' WHERE id = :id_duelo AND ($col_poder IS NULL OR $col_poder = '')");
        $stmt->execute([':id_duelo' => $id_duelo]);
        
        if ($stmt->rowCount() > 0 && isset($_SESSION['duelo_actual'])) {
            $pregunta_index = (int)($_POST['pregunta_index'] ?? 0);
            $pregunta = $_SESSION['duelo_actual']['preguntas'][$pregunta_index] ?? null;
            if ($pregunta) {
                $correcta = $pregunta['solucion'];
                $opciones = $pregunta['opciones'];
                $incorrectas = array_values(array_filter($opciones, function($op) use ($correcta) { return $op != $correcta; }));
                shuffle($incorrectas);
                $incorrectas_a_eliminar = array_slice($incorrectas, 0, 2);
            }
        }
    }
    echo json_encode(['success' => $stmt->rowCount() > 0, 'incorrectas_a_eliminar' => $incorrectas_a_eliminar]);
}
?>
