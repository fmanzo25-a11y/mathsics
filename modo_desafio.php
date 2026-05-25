<?php
require_once 'seguridad.php';
iniciar_sesion_segura();
inyectar_cabeceras_seguridad();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
if (!isset($_SESSION['desafio_tema'])) { header("Location: desafio_config.php"); exit(); }

$tema = $_SESSION['desafio_tema'];
$mult = $_SESSION['desafio_multiplicador'];

$colores = [
    'indigo' => ['100'=>'#e0e7ff', '200'=>'#c7d2fe', '500'=>'#4338ca', '700'=>'#312e81', 'bg'=>'bg-indigo-700', 'text'=>'text-indigo-700', 'border'=>'border-indigo-900', 'hover_bg'=>'hover:bg-indigo-800'],
    'green'  => ['100'=>'#d1fae5', '200'=>'#a7f3d0', '500'=>'#047857', '700'=>'#064e3b', 'bg'=>'bg-emerald-700', 'text'=>'text-emerald-700', 'border'=>'border-emerald-900', 'hover_bg'=>'hover:bg-emerald-800'],
    'sky'    => ['100'=>'#e0f2fe', '200'=>'#bae6fd', '500'=>'#0369a1', '700'=>'#0c4a6e', 'bg'=>'bg-sky-700', 'text'=>'text-sky-700', 'border'=>'border-sky-900', 'hover_bg'=>'hover:bg-sky-800'],
    'pink'   => ['100'=>'#fce7f3', '200'=>'#fbcfe8', '500'=>'#be185d', '700'=>'#831843', 'bg'=>'bg-pink-700', 'text'=>'text-pink-700', 'border'=>'border-pink-900', 'hover_bg'=>'hover:bg-pink-800'],
    'amber'  => ['100'=>'#fef3c7', '200'=>'#fde68a', '500'=>'#f59e0b', '700'=>'#b45309', 'bg'=>'bg-amber-700', 'text'=>'text-amber-700', 'border'=>'border-amber-900', 'hover_bg'=>'hover:bg-amber-800'],
];

$themeColors = [
    'Aritmética' => $colores['indigo'],
    'Álgebra' => $colores['green'],
    'Geometría' => $colores['sky'],
    'Estadística' => $colores['pink'],
    'Todos' => $colores['amber']
];

$color_actual = $themeColors[$tema] ?? $colores['indigo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Modo Desafío Infinito</title>
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: <?php echo $color_actual['100']; ?>; background-image: linear-gradient(to bottom, <?php echo $color_actual['200']; ?>, #ffffff 30%); }
        .glass-panel { background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.8); }
        .heart { transition: all 0.3s ease; }
        .heart.lost { color: #d1d5db; transform: scale(0.8); }
        .heart.pulse { animation: heart-pulse 1s infinite; }
        @keyframes heart-pulse { 0% { transform: scale(1); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
        
        .option-btn { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .option-btn:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .option-btn:active:not(:disabled) { transform: translateY(0); }
        .correct-answer { background-color: #22c55e !important; color: white !important; border-color: #16a34a !important; }
        .wrong-answer { background-color: #ef4444 !important; color: white !important; border-color: #dc2626 !important; animation: shake 0.5s; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-5px); } 40%, 80% { transform: translateX(5px); } }
    </style>
</head>
<body class="min-h-screen flex flex-col pt-4 px-4 sm:px-6">
    <div class="max-w-3xl w-full mx-auto">
        <!-- HEADER HUD -->
        <div class="glass-panel rounded-2xl p-4 sm:p-6 mb-6 flex flex-col sm:flex-row items-center justify-between shadow-lg gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-<?php echo $tema === 'Todos' ? 'amber' : 'blue'; ?>-500 text-white p-3 rounded-xl font-black shadow-inner">
                    Nivel <span id="hud-level">1</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Tema</p>
                    <p class="font-black text-slate-800"><?php echo $tema; ?> <span class="text-orange-500 text-sm">x<?php echo $mult; ?></span></p>
                </div>
            </div>
            
            <div class="text-center">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Puntuación</p>
                <p class="font-black text-4xl text-slate-800" id="hud-score">0</p>
            </div>

            <div class="flex gap-2 text-3xl text-red-500" id="hearts-container">
                <i class="fas fa-heart heart pulse" id="heart-3"></i>
                <i class="fas fa-heart heart pulse" id="heart-2"></i>
                <i class="fas fa-heart heart pulse" id="heart-1"></i>
            </div>
        </div>

        <!-- MAIN GAME AREA -->
        <main class="glass-panel rounded-3xl p-6 sm:p-10 shadow-xl relative min-h-[400px] flex flex-col items-center justify-center">
            
            <div id="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-white/50 backdrop-blur-sm rounded-3xl z-10">
                <i class="fas fa-spinner fa-spin text-5xl text-blue-500 mb-4"></i>
                <p class="font-bold text-slate-600 animate-pulse">Generando desafío...</p>
            </div>

            <div id="game-content" class="w-full text-center hidden">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-800 mb-8" id="question-text">Cargando...</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full" id="options-container">
                    <!-- Opciones inyectadas vía JS -->
                </div>
            </div>

            <button id="next-btn" class="hidden mt-8 bg-slate-800 hover:bg-slate-900 text-white font-black py-4 px-8 rounded-xl shadow-lg transition-all active:scale-95 text-lg w-full sm:w-auto">Siguiente Desafío <i class="fas fa-arrow-right ml-2"></i></button>
        </main>
    </div>

    <!-- Formulario Oculto para enviar resultados -->
    <form id="results-form" action="resultados_desafio.php" method="POST" class="hidden">
        <?= campo_csrf(); ?>
        <input type="hidden" name="score" id="input-score">
        <input type="hidden" name="max_level" id="input-level">
        <input type="hidden" name="correct_count" id="input-correct">
    </form>

    <audio id="sound-correct" src="sonidos/correct_answers.mp3" preload="auto"></audio>
    <audio id="sound-wrong" src="sonidos/incorrect_answers.mp3" preload="auto"></audio>

    <script>
        const tema = "<?php echo $tema; ?>";
        const multiplier = <?php echo $mult; ?>;
        
        let lives = 3;
        let score = 0;
        let currentLevel = 1;
        let streak = 0;
        let correctAnswersTotal = 0;
        
        let currentSolution = null;

        const loadingEl = document.getElementById('loading');
        const gameContent = document.getElementById('game-content');
        const questionText = document.getElementById('question-text');
        const optionsContainer = document.getElementById('options-container');
        const nextBtn = document.getElementById('next-btn');
        const scoreEl = document.getElementById('hud-score');
        const levelEl = document.getElementById('hud-level');
        const form = document.getElementById('results-form');

        async function fetchExercise() {
            loadingEl.classList.remove('hidden');
            gameContent.classList.add('hidden');
            nextBtn.classList.add('hidden');

            try {
                const response = await fetch(`desafio_api.php?tema=${encodeURIComponent(tema)}&nivel=${currentLevel}`);
                const data = await response.json();
                
                if (data.error) {
                    alert("Error generando ejercicio: " + data.error);
                    return;
                }

                currentSolution = data.solucion.toString();
                questionText.innerHTML = data.pregunta;
                optionsContainer.innerHTML = '';

                data.opciones.forEach((opcion, index) => {
                    const btn = document.createElement('button');
                    btn.className = 'option-btn bg-white border-2 border-slate-200 text-slate-700 font-bold text-xl py-4 px-6 rounded-xl shadow-sm hover:border-blue-400 focus:outline-none';
                    btn.innerText = opcion;
                    btn.onclick = () => handleAnswer(btn, opcion.toString());
                    optionsContainer.appendChild(btn);
                });

                loadingEl.classList.add('hidden');
                gameContent.classList.remove('hidden');

            } catch (e) {
                console.error(e);
                alert("Hubo un error de conexión.");
            }
        }

        function handleAnswer(btn, selected) {
            const buttons = optionsContainer.querySelectorAll('button');
            buttons.forEach(b => b.disabled = true);

            if (selected === currentSolution) {
                // Correcto
                btn.classList.add('correct-answer');
                document.getElementById('sound-correct').play().catch(e=>{});
                
                streak++;
                correctAnswersTotal++;
                
                // XP = Base (10 * Nivel) * Multiplicador
                const pointsGained = Math.round((10 * currentLevel) * multiplier);
                score += pointsGained;
                animateScore(scoreEl, score - pointsGained, score);
                confetti({ particleCount: 50, spread: 60, origin: { y: 0.8 } });

                // Escalar nivel
                if (streak % 3 === 0 && currentLevel < 10) {
                    currentLevel++;
                    levelEl.innerText = currentLevel;
                    levelEl.parentElement.classList.add('animate-pulse');
                    setTimeout(() => levelEl.parentElement.classList.remove('animate-pulse'), 1000);
                }

                setTimeout(fetchExercise, 1200);

            } else {
                // Incorrecto
                btn.classList.add('wrong-answer');
                document.getElementById('sound-wrong').play().catch(e=>{});
                streak = 0; // Rompe la racha

                // Mostrar la correcta
                buttons.forEach(b => {
                    if (b.innerText === currentSolution) {
                        b.classList.add('correct-answer');
                        b.style.opacity = '0.7';
                    }
                });

                loseLife();
            }
        }

        function loseLife() {
            const heart = document.getElementById(`heart-${lives}`);
            if (heart) {
                heart.classList.remove('pulse', 'text-red-500');
                heart.classList.add('lost');
            }
            lives--;

            if (lives <= 0) {
                setTimeout(endGame, 1500);
            } else {
                nextBtn.classList.remove('hidden');
                nextBtn.onclick = fetchExercise;
            }
        }

        function endGame() {
            document.getElementById('input-score').value = score;
            document.getElementById('input-level').value = currentLevel;
            document.getElementById('input-correct').value = correctAnswersTotal;
            form.submit();
        }

        function animateScore(el, start, end) {
            let startTimestamp = null;
            const duration = 500;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                el.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        }

        // Iniciar
        fetchExercise();

    </script>
</body>
</html>
