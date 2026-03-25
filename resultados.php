<?php
// BLOQUE PHP PARA OBTENER DATOS DEL USUARIO Y LA SESIÓN
include_once 'conexion.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$id_usuario = $_SESSION['user_id'];
$xpGanada = isset($_POST['xp_ganada']) ? (int)$_POST['xp_ganada'] : 0;
$aciertos = isset($_POST['aciertos']) ? (int)$_POST['aciertos'] : 0;
$totalPreguntas = isset($_POST['total_preguntas']) ? (int)$_POST['total_preguntas'] : 0;
$resultados_ejercicios = isset($_POST['resultados_ejercicios']) ? json_decode($_POST['resultados_ejercicios'], true) : [];

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

    // 2. AÑADIR XP Y ACTUALIZAR NIVEL
    if ($xpGanada > 0) {
        $stmtUpdateXp = $conn->prepare("UPDATE usuarios SET xp = xp + :xpGanada WHERE id = :id");
        $stmtUpdateXp->execute([':xpGanada' => $xpGanada, ':id' => $id_usuario]);
        // Asumiendo que tienes una función o trigger que maneja la subida de nivel
        if (method_exists('Db', 'check_and_update_level')) {
             Db::check_and_update_level($id_usuario);
        }
    }

    // 3. ACTUALIZAR RANKING
    if ($xpGanada > 0) {
        $stmtUpdateRanking = $conn->prepare("UPDATE ranking SET puntos = puntos + :xpGanada WHERE id_usuario = :id_usuario");
        $stmtUpdateRanking->execute([':xpGanada' => $xpGanada, ':id_usuario' => $id_usuario]);
    }

    // 4. GUARDAR HISTORIAL DE EJERCICIOS
    if (!empty($resultados_ejercicios)) {
        $sql = "INSERT INTO resultados_ejercicios (id_usuario, id_ejercicio, respuesta_correcta, tema) VALUES (:id_usuario, :id_ejercicio, :respuesta_correcta, :tema)";
        $stmt = $conn->prepare($sql);
        foreach ($resultados_ejercicios as $resultado) {
            $stmt->execute([
                ':id_usuario' => $id_usuario,
                ':id_ejercicio' => $resultado['id_ejercicio'],
                ':respuesta_correcta' => $resultado['respuesta_correcta'] ? 1 : 0,
                ':tema' => $resultado['tema']
            ]);
        }
    }
    $stmtDesafiosActivos = $conn->prepare(
    "SELECT ud.id, d.tipo, d.objetivo_tema, d.objetivo_cantidad, ud.progreso 
     FROM usuario_desafios ud JOIN desafios d ON ud.id_desafio = d.id 
     WHERE ud.id_usuario = :id_u AND ud.estado = 'activo' AND ud.fecha_asignado = CURDATE()"
);
$stmtDesafiosActivos->execute(['id_u' => $id_usuario]);
$desafiosActivos = $stmtDesafiosActivos->fetchAll(PDO::FETCH_ASSOC);

// Asumimos que todos los ejercicios de una tanda son del mismo tema.
$tema = !empty($resultados_ejercicios) ? $resultados_ejercicios[0]['tema'] : null;

foreach ($desafiosActivos as $desafio) {
    $progresoActualizado = false;
    // Desafío tipo "leccion"
    if ($desafio['tipo'] === 'leccion' && $desafio['objetivo_tema'] === $tema) {
        $stmtProgreso = $conn->prepare("UPDATE usuario_desafios SET progreso = progreso + 1 WHERE id = :id");
        $stmtProgreso->execute(['id' => $desafio['id']]);
        $progresoActualizado = true;
    }
    // Desafío tipo "aciertos"
    if ($desafio['tipo'] === 'aciertos') {
        $stmtProgreso = $conn->prepare("UPDATE usuario_desafios SET progreso = progreso + :aciertos WHERE id = :id");
        $stmtProgreso->execute(['aciertos' => $aciertos, 'id' => $desafio['id']]);
        $progresoActualizado = true;
    }

    if ($progresoActualizado) {
        // Verificar si se completó el desafío
        $nuevoProgreso = $desafio['progreso'] + ($desafio['tipo'] === 'aciertos' ? $aciertos : 1);
        if ($nuevoProgreso >= $desafio['objetivo_cantidad']) {
            $stmtCompletado = $conn->prepare("UPDATE usuario_desafios SET estado = 'completado' WHERE id = :id");
            $stmtCompletado->execute(['id' => $desafio['id']]);
        }
    }
}

    // 5. OBTENER ESTADO FINAL
    $stmtFinal = $conn->prepare("SELECT nivel, xp, limite_xp FROM usuarios WHERE id = :id");
    $stmtFinal->execute(['id' => $id_usuario]);
    $user_final = $stmtFinal->fetch(PDO::FETCH_ASSOC);

    $nivelFinal = (int)$user_final['nivel'];
    $xpFinal = (int)$user_final['xp'];
    $limiteXpFinal = (int)$user_final['limite_xp'];
    
    $porcentajeInicial = ($limiteXpInicial > 0) ? ($xpInicial / $limiteXpInicial) * 100 : 0;
    $porcentajeFinal = ($limiteXpFinal > 0) ? ($xpFinal / $limiteXpFinal) * 100 : 0;
    $subidaDeNivel = $nivelFinal > $nivelInicial;

} catch(Exception $e){
    error_log("Error en resultados.php: " . $e->getMessage());
    header("Location: menu.php?error=resultados");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - ¡Resultados!</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f0f9ff; background-image: linear-gradient(to top, #e0f2fe, #f0f9ff); }
        @keyframes slide-in-fade { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slide-in { animation: slide-in-fade 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) both; }
        @keyframes pulse-glow { 0%, 100% { transform: scale(1); filter: drop-shadow(0 0 10px #fde047); } 50% { transform: scale(1.05); filter: drop-shadow(0 0 20px #facc15); } }
        .animate-pulse-glow { animation: pulse-glow 2s infinite ease-in-out; }

        /* ✨ Animación de Brillo para la Barra de XP ✨ */
        @keyframes shine-effect {
            0% { transform: translateX(-100%) skewX(-20deg); }
            100% { transform: translateX(200%) skewX(-20deg); }
        }
        #xp-bar {
            position: relative;
            overflow: hidden;
        }
        #xp-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.5) 50%, rgba(255,255,255,0) 100%);
            transform: translateX(-100%) skewX(-20deg);
        }
        #xp-bar.shining::after {
            animation: shine-effect 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ✨ NUEVO: Animaciones para Level Up ✨ */
        @keyframes pop-in {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.25); opacity: 1; }
            100% { transform: scale(1); }
        }
        .animate-pop-in {
            display: inline-block; /* Necesario para que transform funcione correctamente */
            animation: pop-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes screen-shake {
            0%, 100% { transform: translate(0, 0) rotate(0); }
            10%, 30%, 50%, 70% { transform: translate(-2px, -3px) rotate(-0.5deg); }
            20%, 40%, 60%, 80% { transform: translate(2px, 3px) rotate(0.5deg); }
        }
        .animate-screen-shake {
            animation: screen-shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 text-slate-700">

    <main class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl text-center p-6 sm:p-8 md:p-10 animate-slide-in 
                 <?php if ($subidaDeNivel): echo 'border-t-8 border-yellow-400'; endif; ?>" id="main-container">
        
        <div>
            <?php if ($subidaDeNivel): ?>
                <h1 class="text-4xl md:text-5xl font-black mb-4 text-amber-500 animate-pulse-glow">¡SUBISTE DE NIVEL!</h1>
                <p class="text-lg text-slate-500 font-bold">¡Todo tu esfuerzo ha dado sus frutos!</p>
            <?php else: ?>
                <h1 class="text-4xl md:text-5xl font-black mb-4 text-slate-800">¡Tanda Completada!</h1>
                <p class="text-lg text-slate-500 font-bold">¡Buen trabajo! Revisa tu progreso:</p>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-8 text-left">
                <div class="bg-blue-100 p-4 rounded-lg border-b-4 border-blue-300"><p class="text-blue-800 text-sm font-bold">ACIERTOS</p><p class="text-3xl font-black text-slate-700"><?php echo htmlspecialchars($aciertos); ?>/<?php echo htmlspecialchars($totalPreguntas); ?></p></div>
                <div class="bg-amber-100 p-4 rounded-lg border-b-4 border-amber-300"><p class="text-amber-800 text-sm font-bold">EXPERIENCIA</p><p class="text-3xl font-black text-yellow-500">+<?php echo htmlspecialchars($xpGanada); ?> XP</p></div>
                <div class="bg-green-100 p-4 rounded-lg border-b-4 border-green-300"><p class="text-green-800 text-sm font-bold">NIVEL ACTUAL</p><p class="text-3xl font-black text-slate-700" id="level-display"><?php echo $nivelInicial; ?></p></div>
            </div>

            <div class="mb-2">
                <div class="flex justify-between font-bold text-lg mb-1">
                    <span id="level-text" class="text-slate-600">Nivel <?php echo $nivelInicial; ?></span>
                    <span id="xp-counter" class="text-slate-500"><?php echo $xpInicial; ?> / <?php echo $limiteXpInicial; ?> XP</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden shadow-inner">
                    <div id="xp-bar" class="bg-yellow-400 h-6 rounded-full" style="width: <?php echo $porcentajeInicial; ?>%; transition: width 1.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);"></div>
                </div>
            </div>
        </div>

        <a href="menu.php" class="mt-10 bg-blue-500 hover:bg-blue-600 text-white font-bold text-lg py-4 px-8 rounded-xl shadow-lg border-b-4 border-blue-700 active:border-b-0 active:translate-y-1 hover:-translate-y-1 transition-all duration-150 transform inline-block">
            <i class="fas fa-arrow-right mr-2"></i>Continuar
        </a>
    </main>

    <audio id="xp-bar-sound" src="sonidos/xp_subiendo.mp3" preload="auto" loop></audio>
    <audio id="level-up-sound" src="sonidos/level_up.mp3" preload="auto"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const data = {
                nivelInicial: <?php echo $nivelInicial; ?>, nivelFinal: <?php echo $nivelFinal; ?>,
                xpInicial: <?php echo $xpInicial; ?>, xpFinal: <?php echo $xpFinal; ?>,
                limiteXpInicial: <?php echo $limiteXpInicial; ?>, limiteXpFinal: <?php echo $limiteXpFinal; ?>,
                porcentajeFinal: <?php echo $porcentajeFinal; ?>,
                subidaDeNivel: <?php echo ($subidaDeNivel) ? 'true' : 'false'; ?>
            };

            const mainContainer = document.getElementById('main-container');
            const xpBar = document.getElementById('xp-bar');
            const xpCounter = document.getElementById('xp-counter');
            const levelDisplay = document.getElementById('level-display');
            const levelText = document.getElementById('level-text');
            const xpBarSound = document.getElementById('xp-bar-sound');
            const levelUpSound = document.getElementById('level-up-sound');
            
            const xpBarVibrationPattern = [50, 80, 50, 80, 80, 60, 80, 60, 100, 50, 150];
            // ✨ NUEVO: Patrón de vibración más intenso para el level up ✨
            const levelUpVibrationPattern = [200, 80, 200, 80, 300, 100, 100];

            function vibrateDevice(pattern) {
                if ('vibrate' in navigator) {
                    try { navigator.vibrate(pattern); } 
                    catch (e) { console.warn("Vibration failed:", e); }
                }
            }
            
            function animateCounter(element, start, end, limit, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const currentValue = Math.floor(progress * (end - start) + start);
                    element.textContent = `${currentValue} / ${limit} XP`;
                    if (progress < 1) { window.requestAnimationFrame(step); }
                };
                window.requestAnimationFrame(step);
            }

            async function animateResults() {
                vibrateDevice(xpBarVibrationPattern);
                xpBarSound.play().catch(e => console.warn("Audio de barra de XP bloqueado."));
                xpBar.classList.add('shining');

                if (data.subidaDeNivel) {
                    xpBar.style.width = '100%';
                    animateCounter(xpCounter, data.xpInicial, data.limiteXpInicial, data.limiteXpInicial, 1500);
                    
                    await new Promise(resolve => setTimeout(resolve, 1600));

                    xpBarSound.pause(); xpBarSound.currentTime = 0;
                    vibrateDevice(0);

                    // ✨ ¡MOMENTO DEL LEVEL UP! ✨
                    vibrateDevice(levelUpVibrationPattern); 
                    mainContainer.classList.add('animate-screen-shake');
                    confetti({ particleCount: 250, spread: 150, origin: { y: 0.6 }, gravity: 0.8 });
                    levelUpSound.play().catch(e => console.warn("Audio de level up bloqueado."));

                    xpBar.classList.remove('shining');
                    xpBar.style.transition = 'none';
                    xpBar.style.width = '0%';
                    
                    // ✨ Animación de "Pop" para el nivel ✨
                    levelDisplay.classList.add('animate-pop-in');
                    levelText.classList.add('animate-pop-in');
                    levelDisplay.textContent = data.nivelFinal;
                    levelText.textContent = `Nivel ${data.nivelFinal}`;
                    xpCounter.textContent = `0 / ${data.limiteXpFinal} XP`;
                    animateCounter(xpCounter, 0, data.xpFinal, data.limiteXpFinal, 1500);

                    // Limpiar clases de animación
                    mainContainer.addEventListener('animationend', () => mainContainer.classList.remove('animate-screen-shake'), { once: true });
                    levelDisplay.addEventListener('animationend', () => levelDisplay.classList.remove('animate-pop-in'), { once: true });
                    levelText.addEventListener('animationend', () => levelText.classList.remove('animate-pop-in'), { once: true });

                    await new Promise(resolve => setTimeout(resolve, 100));
                    
                    void xpBar.offsetWidth; // Forzar reflow

                    vibrateDevice(xpBarVibrationPattern);
                    xpBarSound.play().catch(e => console.warn("Audio de barra de XP (2) bloqueado."));
                    xpBar.classList.add('shining');

                    xpBar.style.transition = 'width 1.5s cubic-bezier(0.68, -0.55, 0.27, 1.55)';
                    xpBar.style.width = `${data.porcentajeFinal}%`;
                    animateCounter(xpCounter, 0, data.xpFinal, data.limiteXpFinal, 1500);

                } else {
                    xpBar.style.width = `${data.porcentajeFinal}%`;
                    animateCounter(xpCounter, data.xpInicial, data.xpFinal, data.limiteXpFinal, 1500);
                }

                setTimeout(() => {
                    xpBarSound.pause();
                    xpBarSound.currentTime = 0;
                    xpBar.classList.remove('shining');
                }, 1500);
            }
            
            setTimeout(animateResults, 500);
        });
    </script>
</body>
</html>