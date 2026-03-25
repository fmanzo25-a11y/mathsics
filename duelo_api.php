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
    $stmt = $conn->prepare("SELECT estado FROM duelos WHERE id = :id_duelo");
    $stmt->execute([':id_duelo' => $id_duelo]);
    $duelo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['status' => $duelo['estado'] === 'en_curso' ? 'encontrado' : 'buscando']);

} elseif ($action === 'iniciar') {
    session_start();
    $id_duelo = $_GET['id_duelo'] ?? 0;
    $stmt = $conn->prepare(
       "SELECT d.*, u1.nombre as nombre1, u2.nombre as nombre2, u1.foto_de_perfil as avatar1, u2.foto_de_perfil as avatar2
        FROM duelos d
        LEFT JOIN usuarios u1 ON d.jugador1_id = u1.id
        LEFT JOIN usuarios u2 ON d.jugador2_id = u2.id
        WHERE d.id = :id_duelo AND (d.jugador1_id = :id_usuario OR d.jugador2_id = :id_usuario)"
    );
    $stmt->execute([':id_duelo' => $id_duelo, ':id_usuario' => $id_usuario_actual]);
    $duelo_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$duelo_info) {
        echo json_encode(['status' => 'error', 'message' => 'Duelo no encontrado.']);
        exit();
    }

    $ejercicios = generarEjercicios($duelo_info['tema'], 10);
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
    
    $puntos = 0;
    $solucion_correcta = $duelo_session['preguntas'][$pregunta_index]['solucion'];
    if ($respuesta_jugador == $solucion_correcta) {
        $puntos = 100; 
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

    if ($resultado === 'victoria') {
        $xp_ganada = 50 + $xp_base;
    } elseif ($resultado === 'derrota') {
        $xp_ganada = 10 + $xp_base;
    } else {
        $xp_ganada = 25 + $xp_base;
    }

    if ($xp_ganada > 0) {
        $stmt = $conn->prepare("UPDATE usuarios SET xp = xp + :xp WHERE id = :id_usuario");
        $stmt->execute([':xp' => $xp_ganada, ':id_usuario' => $id_usuario_actual]);

        // Comprobar y actualizar el nivel del usuario tras ganar XP
        Db::check_and_update_level($id_usuario_actual);
    }

    unset($_SESSION['duelo_actual']);
    session_write_close();

    echo json_encode([
        'status' => 'finalizado',
        'resultado' => $resultado,
        'mi_puntuacion' => $mi_puntuacion,
        'puntuacion_oponente' => $puntuacion_oponente,
        'xp_ganada' => $xp_ganada
    ]);
}
?>
