<?php
require_once 'seguridad.php';
iniciar_sesion_segura();
inyectar_cabeceras_seguridad();
include_once 'conexion.php';

if (!isset($_SESSION['user_id'])) { header("Location: inicio_de_sesion.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_token_csrf($_POST['csrf_token'] ?? '');
}

$id_usuario = $_SESSION['user_id'];
$tema = $_SESSION['desafio_tema'] ?? 'Desconocido';
$score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
$max_level = isset($_POST['max_level']) ? (int)$_POST['max_level'] : 1;
$correct_count = isset($_POST['correct_count']) ? (int)$_POST['correct_count'] : 0;

try {
    $conn = Db::conectar();
    
    // 1. OBTENER ESTADO INICIAL
    $stmtInicial = $conn->prepare("SELECT nivel, xp, limite_xp FROM usuarios WHERE id = :id");
    $stmtInicial->execute(['id' => $id_usuario]);
    $user_inicial = $stmtInicial->fetch(PDO::FETCH_ASSOC);
    if (!$user_inicial) throw new Exception("Usuario no encontrado.");

    $nivelInicial = (int)$user_inicial['nivel'];
    $xpInicial = (int)$user_inicial['xp'];
    $limiteXpInicial = (int)$user_inicial['limite_xp'];

    // 2. AÑADIR XP Y ACTUALIZAR NIVEL (La puntuación del desafío es la XP ganada directamente)
    if ($score > 0) {
        $stmtUpdateXp = $conn->prepare("UPDATE usuarios SET xp = xp + :xpGanada WHERE id = :id");
        $stmtUpdateXp->execute([':xpGanada' => $score, ':id' => $id_usuario]);
        
        if (method_exists('Db', 'check_and_update_level')) {
             Db::check_and_update_level($id_usuario);
        }
        // Actualizar ranking (Ligas Dinámicas: Puntos Normalizados y Castigo)
        $isTop3 = false;
        $posicionRanking = 0;
        $puntosRankingReales = 0;
        $puntosPerdidos = 0;
        $miLigaActual = 'Aficionado';
        $rankEnMiLiga = 0;
        
        include_once 'ligas.php';
        verificarYEjecutarReinicioMensual($conn);
        $infoLigas = obtenerInfoLigas($conn);
        $miRangoNum = isset($infoLigas[$id_usuario]['rango_num']) ? $infoLigas[$id_usuario]['rango_num'] : 1;
        
        $puntosPorInactividad = procesarDecaimientoInactividad($conn, $id_usuario, $miRangoNum);
        
        // En desafíos el accuracy es `$accuracy` y se envia por POST (0 a 100)
        $precision = isset($accuracy) ? $accuracy : 0;
        
        if ($score > 0) { // Si sobrevivió el desafío
            $multiplicador_nivel = 1.0 + ($nivelInicial * 0.05);
            $puntosRankingReales = ceil(($correct_count * 15) * $multiplicador_nivel); // Desafios dan +50% mas base
            $stmtUpdateRanking = $conn->prepare("UPDATE ranking SET puntos = puntos + :puntosRanking WHERE id_usuario = :id_usuario");
            $stmtUpdateRanking->execute([':puntosRanking' => $puntosRankingReales, ':id_usuario' => $id_usuario]);
        } else {
            // Perdió vidas muy pronto (precisión baja o 0 correct_count)
            $multiplicador_castigo = max(1, ($nivelInicial / 2)) * $miRangoNum;
            $puntosPerdidos = ceil(10 * $multiplicador_castigo); // Fijo 10 pts base * mult por perder desafio
            if ($puntosPerdidos <= 0) $puntosPerdidos = 10;
            
            $stmtUpdateRanking = $conn->prepare("UPDATE ranking SET puntos = GREATEST(5, CAST(puntos AS SIGNED) - :puntosPerdidos) WHERE id_usuario = :id_usuario");
            $stmtUpdateRanking->execute([':puntosPerdidos' => $puntosPerdidos, ':id_usuario' => $id_usuario]);
        }

        // Obtener nueva posición
        $infoLigasNuevas = obtenerInfoLigas($conn);
        if (isset($infoLigasNuevas[$id_usuario])) {
            $miLigaActual = $infoLigasNuevas[$id_usuario]['liga'];
            $posicionRanking = $infoLigasNuevas[$id_usuario]['posicion'];
            $rankEnMiLiga = 1;
            foreach($infoLigasNuevas as $id => $data) {
                if ($id == $id_usuario) break;
                if ($data['liga'] == $miLigaActual) $rankEnMiLiga++;
            }
            if ($rankEnMiLiga <= 3) $isTop3 = true;
        }        // Actualizar desafíos diarios (XP y aciertos)
        $stmtDesafiosActivos = $conn->prepare("SELECT ud.id, d.tipo, d.objetivo_cantidad, ud.progreso FROM usuario_desafios ud JOIN desafios d ON ud.id_desafio = d.id WHERE ud.id_usuario = :id_u AND ud.estado = 'activo' AND ud.fecha_asignado = CURDATE()");
        $stmtDesafiosActivos->execute(['id_u' => $id_usuario]);
        $desafiosActivos = $stmtDesafiosActivos->fetchAll(PDO::FETCH_ASSOC);

        foreach ($desafiosActivos as $desafio) {
            $progresoActualizado = false;
            $nuevoProgreso = $desafio['progreso'];

            if ($desafio['tipo'] === 'xp') {
                $stmtProgreso = $conn->prepare("UPDATE usuario_desafios SET progreso = progreso + :xpGanada WHERE id = :id");
                $stmtProgreso->execute(['xpGanada' => $score, 'id' => $desafio['id']]);
                $progresoActualizado = true;
                $nuevoProgreso += $score;
            } else if ($desafio['tipo'] === 'aciertos' && $correct_count > 0) {
                $stmtProgreso = $conn->prepare("UPDATE usuario_desafios SET progreso = progreso + :aciertos WHERE id = :id");
                $stmtProgreso->execute(['aciertos' => $correct_count, 'id' => $desafio['id']]);
                $progresoActualizado = true;
                $nuevoProgreso += $correct_count;
            }

            if ($progresoActualizado && $nuevoProgreso >= $desafio['objetivo_cantidad']) {
                $stmtCompletado = $conn->prepare("UPDATE usuario_desafios SET estado = 'completado' WHERE id = :id");
                $stmtCompletado->execute(['id' => $desafio['id']]);
            }
        }
    }

    // 3. OBTENER ESTADO FINAL
    $stmtFinal = $conn->prepare("SELECT nivel, xp, limite_xp FROM usuarios WHERE id = :id");
    $stmtFinal->execute(['id' => $id_usuario]);
    $user_final = $stmtFinal->fetch(PDO::FETCH_ASSOC);

    $nivelFinal = (int)$user_final['nivel'];
    $xpFinal = (int)$user_final['xp'];
    $limiteXpFinal = (int)$user_final['limite_xp'];
    
    $porcentajeInicial = ($limiteXpInicial > 0) ? ($xpInicial / $limiteXpInicial) * 100 : 0;
    $porcentajeFinal = ($limiteXpFinal > 0) ? ($xpFinal / $limiteXpFinal) * 100 : 0;
    $subidaDeNivel = $nivelFinal > $nivelInicial;

    // Limpiar sesión del desafío
    unset($_SESSION['desafio_tema']);
    unset($_SESSION['desafio_multiplicador']);

} catch(Exception $e){
    error_log("Error en resultados_desafio.php: " . $e->getMessage());
    header("Location: menu.php?error=resultados");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Fin del Desafío</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #fef2f2; background-image: linear-gradient(to top, #fee2e2, #fef2f2); }
        .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.8); }
        @keyframes pop-in { 0% { transform: scale(0.8); opacity: 0; } 50% { transform: scale(1.1); opacity: 1; } 100% { transform: scale(1); } }
        .animate-pop-in { animation: pop-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        
        .xp-glow { box-shadow: 0 0 15px 2px rgba(250, 204, 21, 0.6); animation: pulse-gold 2s infinite; }
        @keyframes pulse-gold { 0%, 100% { filter: drop-shadow(0 0 10px #fde047); } 50% { filter: drop-shadow(0 0 20px #facc15); } }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 text-slate-700">

    <main class="glass-panel rounded-3xl shadow-2xl w-full max-w-2xl text-center p-6 sm:p-10 animate-pop-in relative overflow-hidden">
        
        <!-- Elementos decorativos -->
        <div class="absolute -right-10 -top-10 text-red-500/10 text-9xl"><i class="fas fa-skull"></i></div>
        
        <div class="relative z-10">
            <div class="mb-8">
            <?php if ($subidaDeNivel): ?>
                <h1 class="text-4xl md:text-5xl font-black mb-4 text-amber-500 animate-pulse-glow">¡SUBISTE DE NIVEL!</h1>
                <p class="text-lg text-slate-500 font-bold">¡Impresionante desempeño en el desafío!</p>
            <?php else: ?>
                <h1 class="text-4xl md:text-5xl font-black mb-4 text-slate-800">¡Desafío Completado!</h1>
                <p class="text-lg text-slate-500 font-bold">Aquí tienes tus resultados finales:</p>
            <?php endif; ?>

            <?php if (isset($isTop3) && $isTop3): ?>
                <div class="mt-6 mb-4 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-2xl p-4 shadow-lg transform -rotate-1 animate-pulse-glow">
                    <p class="text-white font-black text-xl"><i class="fas fa-crown text-2xl mr-2"></i> ¡Impresionante! ¡Estás en el Top 3 de la Liga <?php echo $miLigaActual; ?>! (#<?php echo $rankEnMiLiga; ?>)</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white/80 p-4 rounded-2xl shadow-sm border border-slate-100">
                <p class="text-slate-400 text-xs font-black tracking-widest uppercase">Aciertos Totales</p>
                <p class="text-3xl font-black text-slate-700"><?php echo $correct_count; ?></p>
            </div>
            <div class="bg-white/80 p-4 rounded-2xl shadow-sm border border-slate-100">
                <p class="text-slate-400 text-xs font-black tracking-widest uppercase">Nivel Máximo</p>
                <p class="text-3xl font-black text-blue-500"><?php echo $max_level; ?> <i class="fas fa-fire text-orange-500 text-xl ml-1"></i></p>
            </div>
            <?php if (isset($puntosPerdidos) && $puntosPerdidos > 0): ?>
                <div class="bg-red-100 p-4 rounded-2xl shadow-sm border border-red-200">
                    <p class="text-red-800 text-xs font-black tracking-widest uppercase">Castigo de Liga</p>
                    <p class="text-3xl font-black text-red-600">-<?php echo htmlspecialchars($puntosPerdidos); ?> pts</p>
                </div>
            <?php else: ?>
                <div class="bg-purple-100 p-4 rounded-2xl shadow-sm border border-purple-200">
                    <p class="text-purple-800 text-xs font-black tracking-widest uppercase">Ranking MMR</p>
                    <p class="text-3xl font-black text-purple-600">+<?php echo htmlspecialchars($puntosRankingReales); ?> pts</p>
                </div>
            <?php endif; ?>
            <div class="bg-gradient-to-br from-yellow-400 to-orange-500 p-4 rounded-2xl shadow-md text-white transform hover:scale-105 transition">
                <p class="text-white/80 text-xs font-black tracking-widest uppercase">XP Masiva Obtenida</p>
                <p class="text-3xl font-black">+<?php echo $score; ?> XP</p>
            </div>
        </div>

            <!-- Progreso del Nivel -->
            <div class="bg-white/60 p-6 rounded-2xl border border-white mb-8">
                <?php if ($subidaDeNivel): ?>
                    <h2 class="text-xl font-black text-amber-500 mb-2 animate-pulse-glow"><i class="fas fa-star mr-2"></i> ¡Has subido al Nivel <?php echo $nivelFinal; ?>!</h2>
                <?php endif; ?>
                
                <div class="flex justify-between font-bold text-sm mb-2">
                    <span class="text-slate-600">Nivel <?php echo $nivelFinal; ?></span>
                    <span class="text-slate-500"><?php echo $xpFinal; ?> / <?php echo $limiteXpFinal; ?> XP</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-4 overflow-hidden shadow-inner">
                    <div class="bg-yellow-400 h-full rounded-full transition-all duration-1000" style="width: <?php echo $porcentajeFinal; ?>%;"></div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="desafio_config.php" class="bg-slate-800 hover:bg-slate-900 text-white font-black py-4 px-8 rounded-xl shadow-lg transition-transform hover:-translate-y-1">
                    <i class="fas fa-rotate-right mr-2"></i>Reintentar
                </a>
                <a href="menu.php" class="bg-white hover:bg-slate-50 text-slate-700 font-black py-4 px-8 rounded-xl shadow-lg transition-transform hover:-translate-y-1 border border-slate-200">
                    <i class="fas fa-home mr-2"></i>Volver al Menú
                </a>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const isLevelUp = <?php echo ($subidaDeNivel) ? 'true' : 'false'; ?>;
            if (isLevelUp) {
                confetti({ particleCount: 150, spread: 100, origin: { y: 0.6 }, gravity: 0.8 });
            }
        });
    </script>
</body>
</html>
