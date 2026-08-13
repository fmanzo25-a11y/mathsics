<?php
// ligas.php - Motor de Ligas Dinámicas, Decaimiento y Ranking
include_once 'conexion.php';

function obtenerInfoLigas($conn) {
    // 1. Obtenemos todos los jugadores ordenados por puntos (MMR Relativo)
    $stmt = $conn->prepare("
        SELECT u.id, u.nombre, u.foto_de_perfil, r.puntos, u.nivel 
        FROM ranking r 
        JOIN usuarios u ON r.id_usuario = u.id 
        ORDER BY r.puntos DESC, u.nivel DESC
    ");
    $stmt->execute();
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($jugadores);
    if ($total == 0) return ['jugadores' => [], 'mi_info' => null];
    
    // Proporciones estipuladas por el jugador:
    // Peritus: 10%, Pro: 15%, Semi: 25%, Provectus: 25%, Aficionado: 25%
    $limite_peritus = max(1, ceil($total * 0.10));
    $limite_pro = max(1, ceil($total * 0.25));
    $limite_semi = max(1, ceil($total * 0.50));
    $limite_provectus = max(1, ceil($total * 0.75));
    
    $ligas = [];
    foreach ($jugadores as $index => $j) {
        $pos = $index + 1;
        
        // Si el jugador tiene 0 (o menos de 10 puntos), automáticamente va a la liga base (Aficionado), sin importar su percentil.
        // Esto evita que alguien sea "Peritus" teniendo 0 puntos solo porque hay pocos jugadores o es inicio de mes.
        if ($j['puntos'] <= 0) {
            $liga = 'Aficionado'; $color = 'text-green-500'; $bg = 'bg-green-100'; $icon = 'fa-seedling'; $rango_num = 1;
        } else {
            if ($pos <= $limite_peritus) {
                $liga = 'Peritus'; $color = 'text-purple-600'; $bg = 'bg-purple-100'; $icon = 'fa-crown'; $rango_num = 5;
            } elseif ($pos <= $limite_pro) {
                $liga = 'Pro'; $color = 'text-red-500'; $bg = 'bg-red-100'; $icon = 'fa-fire'; $rango_num = 4;
            } elseif ($pos <= $limite_semi) {
                $liga = 'Semi'; $color = 'text-yellow-600'; $bg = 'bg-yellow-100'; $icon = 'fa-star'; $rango_num = 3;
            } elseif ($pos <= $limite_provectus) {
                $liga = 'Provectus'; $color = 'text-blue-500'; $bg = 'bg-blue-100'; $icon = 'fa-shield-alt'; $rango_num = 2;
            } else {
                $liga = 'Aficionado'; $color = 'text-green-500'; $bg = 'bg-green-100'; $icon = 'fa-seedling'; $rango_num = 1;
            }
        }
        
        $ligas[$j['id']] = [
            'id' => $j['id'],
            'nombre' => $j['nombre'],
            'foto' => $j['foto_de_perfil'],
            'posicion' => $pos,
            'liga' => $liga,
            'color' => $color,
            'bg' => $bg,
            'icon' => $icon,
            'puntos' => $j['puntos'],
            'rango_num' => $rango_num,
            'nivel' => $j['nivel']
        ];
    }
    
    return $ligas;
}

function procesarDecaimientoInactividad($conn, $id_usuario, $rango_num) {
    $stmt = $conn->prepare("SELECT ultima_actividad, nivel FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $id_usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['ultima_actividad']) return 0;
    
    $ultima = new DateTime($user['ultima_actividad']);
    $hoy = new DateTime();
    $diferencia = $hoy->diff($ultima)->days;
    
    if ($diferencia >= 2) { // Castigo empieza al segundo día de inactividad
        // Escala de castigo basada en la liga (Peritus sufre muchísimo más que Aficionado)
        $multiplicadores = [1 => 0.5, 2 => 1.5, 3 => 3.0, 4 => 6.0, 5 => 12.0];
        $nivel = max(1, $user['nivel']);
        
        // Puntos Perdidos = (Días Inactivos - 1) * (Nivel) * (Severidad de Liga)
        $dias_castigables = $diferencia - 1;
        $puntosPerdidos = ceil($dias_castigables * $nivel * $multiplicadores[$rango_num]);
        
        if ($puntosPerdidos > 0) {
            // GREATEST(5, ...) evita que el jugador se quede en 0 absoluto por castigo, para no desmotivar.
            $stmtUpdate = $conn->prepare("UPDATE ranking SET puntos = GREATEST(5, CAST(puntos AS SIGNED) - :perdidos) WHERE id_usuario = :id");
            $stmtUpdate->execute(['perdidos' => $puntosPerdidos, 'id' => $id_usuario]);
            return $puntosPerdidos;
        }
    }
    return 0;
}

function verificarYEjecutarReinicioMensual($conn) {
    // Usaremos la tabla de log_reinicios si existe, o nos basamos en una consulta rápida.
    // Por simplicidad para no requerir cambios en BD, guardamos un archivo de texto de bandera.
    $bandera_file = __DIR__ . '/.last_reset';
    $mes_actual = date('Y-m');
    
    if (file_exists($bandera_file)) {
        $ultimo_reinicio = file_get_contents($bandera_file);
        if ($ultimo_reinicio === $mes_actual) {
            return false; // Ya se reinició este mes
        }
    }
    
    // Si llegamos aquí, es un nuevo mes. ¡Reiniciamos todo el ranking a 0!
    $stmtReset = $conn->prepare("UPDATE ranking SET puntos = 0");
    $stmtReset->execute();
    
    // Actualizar bandera
    file_put_contents($bandera_file, $mes_actual);
    return true;
}

// Helper para calcular la fecha del fin de mes para el cronómetro
function obtenerTiempoFinDeMes() {
    $ultimoDia = new DateTime('last day of this month 23:59:59');
    return $ultimoDia->format('Y-m-d\TH:i:s');
}
?>
