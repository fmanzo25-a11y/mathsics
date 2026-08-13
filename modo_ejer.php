<?php
require_once 'seguridad.php';
iniciar_sesion_segura();
inyectar_cabeceras_seguridad();
// OBTENER TEMA Y DEFINIR COLORES
$tema_actual = $_SESSION['tema'] ?? 'Aritmética';
$subtema_actual = $_SESSION['subtema'] ?? '';

$temas_config = [
    'Aritmética' => ['color' => 'indigo', 'icon' => 'fa-calculator'],
    'Álgebra' => ['color' => 'green', 'icon' => 'fa-square-root-variable'],
    'Geometría' => ['color' => 'sky', 'icon' => 'fa-ruler-combined'],
    'Estadística' => ['color' => 'pink', 'icon' => 'fa-chart-pie'],
];

$colores = [
    'indigo' => ['100'=>'#e0e7ff', '200'=>'#c7d2fe', '500'=>'#4338ca', '700'=>'#312e81', 'bg'=>'bg-indigo-700', 'text'=>'text-indigo-700', 'border'=>'border-indigo-900', 'hover_bg'=>'hover:bg-indigo-800', 'ring'=>'focus:ring-indigo-700'],
    'green'  => ['100'=>'#d1fae5', '200'=>'#a7f3d0', '500'=>'#047857', '700'=>'#064e3b', 'bg'=>'bg-emerald-700', 'text'=>'text-emerald-700', 'border'=>'border-emerald-900', 'hover_bg'=>'hover:bg-emerald-800', 'ring'=>'focus:ring-emerald-700'],
    'sky'    => ['100'=>'#e0f2fe', '200'=>'#bae6fd', '500'=>'#0369a1', '700'=>'#0c4a6e', 'bg'=>'bg-sky-700', 'text'=>'text-sky-700', 'border'=>'border-sky-900', 'hover_bg'=>'hover:bg-sky-800', 'ring'=>'focus:ring-sky-700'],
    'pink'   => ['100'=>'#fce7f3', '200'=>'#fbcfe8', '500'=>'#be185d', '700'=>'#831843', 'bg'=>'bg-pink-700', 'text'=>'text-pink-700', 'border'=>'border-pink-900', 'hover_bg'=>'hover:bg-pink-800', 'ring'=>'focus:ring-pink-700'],
];

$config_actual = $temas_config[$tema_actual] ?? $temas_config['Aritmética'];
$color_actual = $colores[$config_actual['color']] ?? $colores['indigo'];
$icono_actual = $config_actual['icon'] ?? 'fa-calculator';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Modo Ejercicio</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: <?php echo $color_actual['100']; ?>;
            background-image: 
                radial-gradient(circle at 10% 20%, <?php echo $color_actual['200']; ?> 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, #ffffff 0%, transparent 40%);
        }
        
        .glass-btn {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 2px solid transparent;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.9);
            border-color: <?php echo $color_actual['200']; ?>;
        }
        .glass-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05);
        }

        #parallax-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; pointer-events: none; z-index: -1; overflow: hidden; opacity: 0.3; }
        .math-symbol { position: absolute; font-weight: 900; color: <?php echo $color_actual['500']; ?>; user-select: none; transition: transform 0.1s ease-out; }

        @keyframes shake { 0%, 100% { transform: translateX(0); } 10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); } 20%, 40%, 60%, 80% { transform: translateX(5px); } }
        /* Optimizamos box-shadow en lugar de filter: drop-shadow porque drop-shadow causa muchísimo lag en animaciones */
        @keyframes pulse-danger { 0%, 100% { transform: scale(1); box-shadow: 0 0 5px rgba(239,68,68,0.5); } 50% { transform: scale(1.15); box-shadow: 0 0 15px rgba(239,68,68,0.9); } }
        @keyframes float-symbol {
            0% { transform: translateY(0px) rotate(0deg); }
            100% { transform: translateY(-30px) rotate(20deg); }
        }
        
        .animate-fade-in-up { animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .animate-fade-in { animation: fade-in 0.5s ease-out both; }
        .animate-fade-out { animation: fade-out 0.4s ease-in both; }
        .animate-shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
        .animate-pulse-danger { animation: pulse-danger 1s infinite ease-in-out; }

        #explanation-container { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.5s ease-in-out; margin-top: 0; }
        #explanation-container.show { max-height: 20rem; opacity: 1; margin-top: 1.5rem; }
    </style>
</head>
<body class="min-h-screen text-slate-800 relative">
    
    <div id="parallax-bg"></div>

    <div id="a11y-announcements" class="sr-only" aria-live="polite"></div>

    <div id="exercise-container" class="w-full max-w-3xl mx-auto flex flex-col h-screen p-4 sm:p-6 glass-panel sm:my-4 sm:h-[calc(100vh-2rem)] sm:rounded-3xl relative overflow-hidden">
        
        <header class="flex justify-between items-center w-full">
            <a href="menu.php" class="<?php echo $color_actual['text']; ?> opacity-80 hover:opacity-100 transition group flex items-center gap-2 text-sm font-bold">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1" aria-hidden="true"></i>
                <span>Menú</span>
            </a>
            <div class="text-center font-black <?php echo $color_actual['text']; ?>">
                <i class="fa-solid <?php echo $icono_actual; ?> text-xl" aria-hidden="true"></i>
                <p class="text-lg"><?php echo htmlspecialchars($tema_actual); ?></p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center bg-white border border-gray-200 rounded-full h-10 shadow-md group relative pr-1 pl-1 transition-all duration-300" id="music-container">
                    <button id="toggle-music" class="flex-shrink-0 w-8 h-8 rounded-full font-bold text-lg <?php echo $color_actual['text']; ?> flex items-center justify-center hover:scale-105 transition-transform z-10 bg-white" aria-label="Música de fondo" title="Música de fondo">
                        <i class="fa-solid fa-music"></i>
                    </button>
                    <div class="overflow-hidden transition-all duration-300 max-w-0 group-hover:max-w-xs flex items-center">
                        <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.2" class="w-20 ml-2 mr-1 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    </div>
                </div>
                <button id="toggle-untimed" class="flex items-center justify-center w-10 h-10 rounded-full font-bold text-lg bg-white border border-gray-200 shadow-md <?php echo $color_actual['text']; ?> hover:scale-105 active:scale-95 transition-transform" aria-label="Desactivar temporizador" title="Desactivar temporizador">
                    <i class="fa-solid fa-hourglass-half"></i>
                </button>
                <div id="timer-container" class="flex items-center justify-center w-14 h-14 rounded-full font-black text-2xl <?php echo $color_actual['bg']; ?> text-white shadow-lg">
                    <span id="timer" aria-label="Segundos restantes">30</span>
                </div>
            </div>
        </header>

        <div class="my-4 w-full">
            <div class="w-full bg-white/50 rounded-full h-4 relative shadow-inner border border-white/60">
                <div id="progress-bar" class="<?php echo $color_actual['bg']; ?> h-full rounded-full transition-all duration-500 ease-out flex items-center justify-end pr-2 shadow-md"></div>
                <p id="progress-text" class="absolute inset-0 flex items-center justify-center text-xs font-bold text-slate-700 drop-shadow-sm">Ejercicio 1 de N</p>
            </div>
        </div>
        
        <div id="question-content" class="flex-1 overflow-y-auto px-2 py-4 flex flex-col animate-fade-in-up">
            <div class="my-auto w-full">
                <h2 id="question-text" class="text-2xl sm:text-3xl md:text-4xl font-black text-center mb-8 text-slate-800">Cargando...</h2>
                <div id="options-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                <div id="explanation-container"></div>
                <!-- CONTENEDOR NUEVO PARA LA IA -->
                <div id="ia-container" class="mt-4"></div>
            </div>
        </div>
        
        <footer class="py-4">
            <button id="next-button" class="w-full <?php echo $color_actual['bg']; ?> opacity-90 hover:opacity-100 text-white font-bold py-4 rounded-xl shadow-lg border-b-4 <?php echo $color_actual['border']; ?> active:border-b-0 active:translate-y-1 transition-all duration-150 hidden backdrop-blur-md">Siguiente</button>
        </footer>
    </div>
    
    <audio id="bg-music" loop preload="auto"><source src="sonidos/musica.mp3" type="audio/mpeg"></audio>
    <audio id="correct-sound" preload="auto"><source src="sonidos/correct_answers.mp3" type="audio/mpeg"></audio>
    <audio id="incorrect-sound" preload="auto"><source src="sonidos/incorrect_answers.mp3" type="audio/mpeg"></audio>
    <audio id="click-sound" preload="auto"><source src="sonidos/click.mp3" type="audio/mpeg"></audio>
    <audio id="victory-sound" preload="auto"><source src="sonidos/victory.mp3" type="audio/mpeg"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const exerciseContainer = document.getElementById('exercise-container');
            const questionText = document.getElementById('question-text');
            const optionsGrid = document.getElementById('options-grid');
            const nextButton = document.getElementById('next-button');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const timerDisplay = document.getElementById('timer');
            const timerContainer = document.getElementById('timer-container');
            const explanationContainer = document.getElementById('explanation-container');
            const toggleUntimedBtn = document.getElementById('toggle-untimed');
            const toggleMusicBtn = document.getElementById('toggle-music');
            const volumeSlider = document.getElementById('volume-slider');
            const musicContainer = document.getElementById('music-container');
            const a11yAnnounce = document.getElementById('a11y-announcements');

            const bgMusic = document.getElementById('bg-music');
            const correctSound = document.getElementById('correct-sound');
            const incorrectSound = document.getElementById('incorrect-sound');
            const clickSound = document.getElementById('click-sound');
            const victorySound = document.getElementById('victory-sound');

            let currentExerciseIndex = 0;
            let exercises = [];
            let aciertos = 0;
            let timerInterval;
            const DURATION = 30;
            let resultadosEjercicios = [];
            let ultimaRespuestaUsuario = '';
            let untimedMode = false;

            function announceA11y(text) {
                if (a11yAnnounce) {
                    a11yAnnounce.textContent = '';
                    setTimeout(() => {
                        a11yAnnounce.textContent = text;
                    }, 50);
                }
            }
            
            // --- Lógica de Música de Fondo (Ducking) ---
            let isMusicPlaying = false;
            let baseVolume = 0.2; // Volumen normal bajo para no desconcentrar
            let duckedVolume = 0.05; // Volumen casi imperceptible cuando la IA habla

            const initMusic = () => {
                if (!isMusicPlaying && bgMusic) {
                    bgMusic.volume = baseVolume;
                    bgMusic.play().then(() => {
                        isMusicPlaying = true;
                        toggleMusicBtn.innerHTML = '<i class="fa-solid fa-music"></i>';
                    }).catch(e => console.log("Música bloqueada por el navegador hasta interactuar."));
                    document.removeEventListener('click', initMusic);
                }
            };
            document.addEventListener('click', initMusic, { once: true });

            if (musicContainer && volumeSlider) {
                musicContainer.addEventListener('mouseenter', () => {
                    volumeSlider.classList.remove('opacity-0', 'pointer-events-none');
                });
                musicContainer.addEventListener('mouseleave', () => {
                    volumeSlider.classList.add('opacity-0', 'pointer-events-none');
                });
                
                volumeSlider.addEventListener('input', (e) => {
                    baseVolume = parseFloat(e.target.value);
                    if (isMusicPlaying) bgMusic.volume = baseVolume;
                    
                    if (baseVolume === 0) {
                        toggleMusicBtn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
                        toggleMusicBtn.classList.add('opacity-50');
                    } else {
                        toggleMusicBtn.innerHTML = '<i class="fa-solid fa-music"></i>';
                        toggleMusicBtn.classList.remove('opacity-50');
                        if (!isMusicPlaying && bgMusic.paused) {
                            bgMusic.play();
                            isMusicPlaying = true;
                        }
                    }
                });
            }

            if (toggleMusicBtn) {
                toggleMusicBtn.addEventListener('click', (e) => {
                    e.stopPropagation(); 
                    if (isMusicPlaying) {
                        bgMusic.pause();
                        isMusicPlaying = false;
                        toggleMusicBtn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
                        toggleMusicBtn.classList.add('opacity-50');
                    } else {
                        if (baseVolume === 0) { baseVolume = 0.2; volumeSlider.value = 0.2; }
                        bgMusic.volume = baseVolume;
                        bgMusic.play();
                        isMusicPlaying = true;
                        toggleMusicBtn.innerHTML = '<i class="fa-solid fa-music"></i>';
                        toggleMusicBtn.classList.remove('opacity-50');
                    }
                });
            }

            function stopSpeech() {
                window.speechSynthesis.cancel();
                if (bgMusic && isMusicPlaying) bgMusic.volume = baseVolume;
            }
            
            function duckMusicTemporarily(durationMs = 1500) {
                if (bgMusic && isMusicPlaying) {
                    bgMusic.volume = duckedVolume;
                    setTimeout(() => {
                        // Solo restaurar si no está hablando la IA en este momento
                        if (isMusicPlaying && !window.speechSynthesis.speaking) {
                            bgMusic.volume = baseVolume;
                        }
                    }, durationMs);
                }
            }

            function vibrateDevice(pattern) {
                if ('vibrate' in navigator) {
                    try { navigator.vibrate(pattern); } 
                    catch (e) { console.warn("Vibration failed:", e); }
                }
            }

            if (toggleUntimedBtn) {
                toggleUntimedBtn.addEventListener('click', () => {
                    untimedMode = !untimedMode;
                    vibrateDevice(50);
                    if (untimedMode) {
                        clearInterval(timerInterval);
                        timerContainer.style.display = 'none';
                        toggleUntimedBtn.innerHTML = '<i class="fa-solid fa-clock"></i>';
                        toggleUntimedBtn.setAttribute('aria-label', 'Activar temporizador');
                        toggleUntimedBtn.setAttribute('title', 'Activar temporizador');
                        announceA11y('Temporizador desactivado. Ahora puedes resolver el ejercicio sin límite de tiempo.');
                    } else {
                        timerContainer.style.display = 'flex';
                        toggleUntimedBtn.innerHTML = '<i class="fa-solid fa-hourglass-half"></i>';
                        toggleUntimedBtn.setAttribute('aria-label', 'Desactivar temporizador');
                        toggleUntimedBtn.setAttribute('title', 'Desactivar temporizador');
                        announceA11y('Temporizador activado. Tienes 30 segundos.');
                        startTimer();
                    }
                });
            }

            function vibrateDevice(pattern) {
                if ('vibrate' in navigator) {
                    try { navigator.vibrate(pattern); } 
                    catch (e) { console.warn("Vibration failed:", e); }
                }
            }

            function animateElement(element, animation) { return new Promise(resolve => { element.classList.add(animation); element.addEventListener('animationend', () => { element.classList.remove(animation); resolve(); }, { once: true }); }); }
            
            function startTimer() {
                clearInterval(timerInterval);
                if (untimedMode) return;
                let timeLeft = DURATION;
                timerDisplay.textContent = timeLeft;
                timerContainer.classList.remove('bg-yellow-400', 'bg-red-500');
                timerContainer.classList.add('<?php echo $color_actual['bg']; ?>');

                timerInterval = setInterval(() => {
                    timeLeft--;
                    timerDisplay.textContent = timeLeft;
                    if (timeLeft < DURATION * 0.5 && timeLeft > DURATION * 0.25) {
                        timerContainer.classList.remove('<?php echo $color_actual['bg']; ?>');
                        timerContainer.classList.add('bg-yellow-400');
                        timerContainer.classList.remove('animate-pulse-danger');
                    } else if (timeLeft <= DURATION * 0.25) {
                        timerContainer.classList.remove('bg-yellow-400');
                        timerContainer.classList.add('bg-red-500', 'animate-pulse-danger');
                    }
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        vibrateDevice(50); // Vibración corta cuando se acaba el tiempo
                        incorrectSound?.play().catch(e => console.warn("Audio failed"));
                        revealCorrectAnswer();
                        showExplanation();
                        nextButton.classList.remove('hidden');
                        nextButton.focus();
                        announceA11y('Se ha agotado el tiempo. La respuesta correcta ha sido revelada.');
                    }
                }, 1000);
            }

            function loadExercise(exercise) {
                if (!exercise) return;
                const qContent = document.getElementById('question-content');
                qContent.classList.remove('animate-fade-out');
                // Forzar reflujo para reiniciar la animación
                void qContent.offsetWidth;
                qContent.classList.add('animate-fade-in-up');
                
                explanationContainer.classList.remove('show');
                explanationContainer.innerHTML = '';
                document.getElementById('ia-container').innerHTML = '';
                ultimaRespuestaUsuario = '';
                questionText.innerHTML = exercise.pregunta;
                optionsGrid.innerHTML = '';
                
                exercise.opciones.forEach(opcion => {
                    const button = document.createElement('button');
                    button.innerHTML = `<span>${opcion}</span><span class="feedback-icon"></span>`;
                    button.dataset.answer = opcion;
                    button.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-slate-700 glass-btn border-gray-300 hover:-translate-y-1`;
                    button.addEventListener('click', selectAnswer);
                    optionsGrid.appendChild(button);
                });

                updateProgress();
                startTimer();
                nextButton.classList.add('hidden');

                const textoPregunta = questionText.textContent || questionText.innerText;
                announceA11y(`Pregunta: ${textoPregunta}. Elige una opción.`);

                setTimeout(() => {
                    const firstOption = optionsGrid.querySelector('.option-btn');
                    if (firstOption) firstOption.focus();
                }, 150);
            }

            function updateProgress() { const total = exercises.length; if (total === 0) return; progressText.textContent = `Ejercicio ${currentExerciseIndex + 1} de ${total}`; progressBar.style.width = `${((currentExerciseIndex + 1) / total) * 100}%`; }

                        function selectAnswer(e) {
                clearInterval(timerInterval);
                timerContainer.classList.remove('animate-pulse-danger');
                const selectedButton = e.currentTarget;
                const selectedAnswer = selectedButton.dataset.answer;
                const correctAnswer = exercises[currentExerciseIndex].solucion;
                const isCorrect = String(selectedAnswer) === String(correctAnswer);
                ultimaRespuestaUsuario = String(selectedAnswer);

                resultadosEjercicios.push({
                    id_ejercicio: exercises[currentExerciseIndex].id_ejercicio,
                    respuesta_correcta: isCorrect,
                    tema: exercises[currentExerciseIndex].tema
                });
                
                document.querySelectorAll('.option-btn').forEach(btn => btn.disabled = true);
                const feedbackIcon = selectedButton.querySelector('.feedback-icon');

                document.getElementById('ia-container').innerHTML = '';
                stopSpeech();
                duckMusicTemporarily(1500); // Bajar música momentáneamente al responder

                if (isCorrect) {
                    correctSound?.play().catch(e => console.warn("Audio failed"));
                    vibrateDevice(100);
                    selectedButton.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-white bg-green-500 border-b-4 border-green-700`;
                    feedbackIcon.innerHTML = '<span class="sr-only">Correcto. </span><i class="fas fa-check-circle" aria-hidden="true"></i>';
                    aciertos++;
                    announceA11y('¡Respuesta Correcta!');
                } else {
                    incorrectSound?.play().catch(e => console.warn("Audio failed"));
                    vibrateDevice([100, 50, 100]);
                    selectedButton.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-white bg-red-500 border-b-4 border-red-700`;
                    feedbackIcon.innerHTML = '<span class="sr-only">Incorrecto. </span><i class="fas fa-times-circle" aria-hidden="true"></i>';
                    animateElement(exerciseContainer, 'animate-shake');
                    announceA11y(`Respuesta Incorrecta. La opción correcta era: ${correctAnswer}`);
                }
                revealCorrectAnswer(selectedAnswer);
                showExplanation();
                nextButton.classList.remove('hidden');
                nextButton.focus();
            }

            // === PEGA ESTAS NUEVAS FUNCIONES ANTES DE: nextButton.addEventListener('click', loadNextExercise); ===

            async function solicitarAyudaIA(ejercicio, respuestaUsuario, nivelDetalle = 'basico') {
                const iaContainer = document.getElementById('ia-container');
                if (!iaContainer) return;

                const ejercicioPayload = {
                    pregunta: String(ejercicio?.pregunta ?? ''),
                    opciones: Array.isArray(ejercicio?.opciones) ? ejercicio.opciones : [],
                    solucion: String(ejercicio?.solucion ?? ''),
                    tema: String(ejercicio?.tema ?? ''),
                    explicacion: String(ejercicio?.explicacion ?? '')
                };
                const respuestaPayload = String(respuestaUsuario ?? '');

                // Mostrar mensaje de "Pensando..."
                iaContainer.innerHTML = `
                    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-r-lg text-left mt-2 animate-fade-in-up">
                        <h3 class="font-black text-lg mb-2 flex items-center gap-2 text-indigo-700">
                            <i class="fa-solid fa-robot fa-bounce"></i> Tutor IA Pensando...
                        </h3>
                        <p class="text-slate-600 font-semibold text-sm">Analizando tu respuesta y buscando la mejor manera de explicarlo...</p>
                    </div>
                `;

                try {
                    const respuesta = await fetch('gemini_tutor.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            ejercicio: ejercicioPayload,
                            respuesta_usuario: respuestaPayload,
                            nivel_detalle: nivelDetalle
                        })
                    });

                    const data = await respuesta.json();
                    
                    // Renderizar respuesta
                    iaContainer.innerHTML = `
                        <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-r-lg text-left mt-2 animate-fade-in-up">
                            <h3 class="font-black text-lg mb-2 flex items-center gap-2 text-indigo-700">
                                <i class="fa-solid fa-robot"></i> Tutor IA dice:
                            </h3>
                            <p class="text-slate-600 font-semibold text-sm mb-3">${data.explicacion}</p>
                            ${nivelDetalle === 'basico' ? `<button id="btn-saber-mas" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition-all duration-150 active:scale-95"><i class="fa-solid fa-plus-circle mr-1"></i> ¡Explícame más a fondo!</button>` : ''}
                        </div>
                    `;
                    
                    // Leer texto en voz alta
                    leerTextoConVoz(data.explicacion);

                    // Si estamos en nivel básico, asignar acción al botón de "Saber más"
                    if (nivelDetalle === 'basico') {
                        document.getElementById('btn-saber-mas').addEventListener('click', (e) => {
                            e.target.disabled = true;
                            e.target.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Cargando...';
                            solicitarAyudaIA(ejercicioPayload, respuestaPayload, 'profundo');
                        });
                    }

                } catch (error) {
                    console.error(error);
                    iaContainer.innerHTML = `
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg text-left mt-2 animate-fade-in-up">
                            <p class="text-red-700 font-semibold text-sm"><i class="fa-solid fa-triangle-exclamation mr-1"></i> El tutor IA está tomando un descanso. Intenta de nuevo más tarde.</p>
                        </div>`;
                }
            }

            function leerTextoConVoz(texto) {
                // Detener si hay un audio reproduciéndose
                stopSpeech(); 
                
                // Limpiar asteriscos que a veces envía la IA
                const textoLimpio = texto.replace(/\*/g, ''); 
                const mensaje = new SpeechSynthesisUtterance(textoLimpio);
                
                mensaje.lang = 'es-ES'; 
                mensaje.rate = 1.0; 
                mensaje.pitch = 1.0;
                
                // Ducking de Audio (bajar volumen de música)
                mensaje.onstart = () => { if (bgMusic && isMusicPlaying) bgMusic.volume = duckedVolume; };
                mensaje.onend = () => { if (bgMusic && isMusicPlaying) bgMusic.volume = baseVolume; };
                mensaje.onerror = () => { if (bgMusic && isMusicPlaying) bgMusic.volume = baseVolume; };
                
                // Intentar usar la mejor voz en español disponible
                const voces = window.speechSynthesis.getVoices();
                const vozEspanol = voces.find(v => v.lang.includes('es'));
                if (vozEspanol) mensaje.voice = vozEspanol;

                window.speechSynthesis.speak(mensaje);
            }
            
            function showExplanation() {
                const explanationText = exercises[currentExerciseIndex].explicacion;
                if (explanationText) {
                    explanationContainer.innerHTML = `
                        <div class="bg-sky-50 border-l-4 <?php echo $color_actual['border']; ?> p-4 rounded-r-lg text-left">
                            <h3 class="font-black text-lg mb-2 flex items-center gap-2 <?php echo $color_actual['text']; ?>">
                                <i class="fa-solid fa-circle-info"></i>¡Aprende el truco!
                            </h3>
                            <p class="text-slate-600 font-semibold">${explanationText}</p>
                            <button id="btn-ia-explicacion" class="bg-sky-500 hover:bg-sky-600 text-white font-bold py-2 px-4 rounded-lg text-sm mt-3 transition-all duration-150 active:scale-95"><i class="fa-solid fa-robot mr-1"></i> Preguntarle al tutor IA</button>
                        </div>
                    `;
                    setTimeout(() => { explanationContainer.classList.add('show'); }, 50);
                }
            }

            explanationContainer.addEventListener('click', (event) => {
                const trigger = event.target.closest('#btn-ia-explicacion');
                if (!trigger) return;

                stopSpeech();

                const ejercicioActual = exercises[currentExerciseIndex];
                if (!ejercicioActual) return;

                solicitarAyudaIA(ejercicioActual, ultimaRespuestaUsuario, 'basico');
            });

            function revealCorrectAnswer(selectedAnswer = null) {
                document.querySelectorAll('.option-btn').forEach(btn => {
                    btn.disabled = true;
                    if (String(btn.dataset.answer) === String(exercises[currentExerciseIndex].solucion)) {
                        btn.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-white bg-green-500 border-b-4 border-green-700`;
                        const fbIcon = btn.querySelector('.feedback-icon');
                        if (fbIcon) fbIcon.innerHTML = '<span class="sr-only">Correcto. </span><i class="fas fa-check-circle" aria-hidden="true"></i>';
                    } else if (btn.dataset.answer !== selectedAnswer) {
                        btn.classList.add('opacity-50');
                    }
                });
            }
            
            async function loadNextExercise(event) { 
                clickSound?.play().catch(e => console.warn("Audio failed"));
                vibrateDevice(50);
                const qContent = document.getElementById('question-content');
                qContent.classList.remove('animate-fade-in-up');
                await animateElement(qContent, 'animate-fade-out'); 
                currentExerciseIndex++; 
                if (currentExerciseIndex < exercises.length) { 
                    loadExercise(exercises[currentExerciseIndex]); 
                } else { 
                    showFinalScreen(); 
                } 
            }
            
            function showFinalScreen() {
                victorySound?.play().catch(e => console.warn("Audio failed"));
                vibrateDevice([200, 100, 200]);

                const total = exercises.length;
                const percentage = total > 0 ? ((aciertos / total) * 100).toFixed(0) : 0;
                const xpPorAcierto = 15;
                const xpGanada = aciertos * xpPorAcierto;
                
                exerciseContainer.innerHTML = `
                    <div class="flex flex-col h-full text-center animate-fade-in-up p-6">
                        <div class="flex-1 flex flex-col justify-center">
                            <i class="fas fa-trophy text-8xl text-yellow-400" style="filter: drop-shadow(0 0 15px currentColor);"></i>
                            <h2 class="text-5xl font-black text-slate-800 my-3">¡Lo Lograste!</h2>
                            <p class="<?php echo $color_actual['text']; ?> text-lg mb-8 font-bold">Este es tu resumen de ${exercises[0].tema}:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left mb-8 max-w-lg mx-auto w-full">
                                <div class="bg-blue-100 p-4 rounded-lg border-b-4 border-blue-300"><p class="text-blue-800 text-sm font-bold">ACIERTOS</p><p class="text-3xl font-black text-slate-700">${aciertos}/${total}</p></div>
                                <div class="bg-green-100 p-4 rounded-lg border-b-4 border-green-300"><p class="text-green-800 text-sm font-bold">PRECISIÓN</p><p class="text-3xl font-black text-slate-700">${percentage}%</p></div>
                                <div class="bg-amber-100 p-4 rounded-lg border-b-4 border-amber-300"><p class="text-amber-800 text-sm font-bold">EXPERIENCIA</p><p class="text-3xl font-black text-yellow-500">+<span id="xp-counter">0</span> XP</p></div>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <form action="resultados.php" method="POST">
                                <?= campo_csrf(); ?>
                                <input type="hidden" name="xp_ganada" value="${xpGanada}">
                                <input type="hidden" name="aciertos" value="${aciertos}">
                                <input type="hidden" name="total_preguntas" value="${total}">
                                <input type="hidden" name="resultados_ejercicios" value='${JSON.stringify(resultadosEjercicios)}'>
                                <input type="hidden" name="tema" value="${exercises[0].tema}"> 
                                <button type="submit" class="w-full <?php echo $color_actual['bg']; ?> <?php echo $color_actual['hover_bg']; ?> text-white font-bold py-4 rounded-xl shadow-lg border-b-4 <?php echo $color_actual['border']; ?> active:border-b-0 active:translate-y-1 transition-all duration-150">
                                    Finalizar y Guardar Progreso
                                </button>
                            </form>
                        </div>
                    </div>`;

                const xpCounter = document.getElementById('xp-counter'); let currentXp = 0; if (xpGanada > 0) { const increment = Math.max(1, Math.ceil(xpGanada / 50)); const counterInterval = setInterval(() => { currentXp += increment; if (currentXp >= xpGanada) { currentXp = xpGanada; clearInterval(counterInterval); } xpCounter.textContent = currentXp; }, 20); }
            }

            async function fetchExercises() { try { const tema = "<?php echo addslashes($tema_actual); ?>"; const subtema = "<?php echo addslashes($subtema_actual); ?>"; if (!tema) throw new Error('No se ha seleccionado un tema.'); const url = `ejercicios.php?cantidad=5&tema=${encodeURIComponent(tema)}&subtema=${encodeURIComponent(subtema)}`; const response = await fetch(url); const data = await response.json(); if (!response.ok || data.error) throw new Error(data.error || 'Error al cargar los ejercicios.'); exercises = data; if (!exercises || exercises.length === 0) throw new Error('No se encontraron ejercicios para este tema.'); loadExercise(exercises[currentExerciseIndex]); } catch (error) { console.error('Error:', error); questionText.innerHTML = `No se pudieron cargar los ejercicios. <br><small class="text-red-500 mt-2 block">${error.message}</small>`; optionsGrid.innerHTML = ''; timerContainer.style.display = 'none'; } }

           let nextClicked = false;
           nextButton.addEventListener('click', (event) => {
  nextClicked = true;

  if (nextClicked) {
    // Esto se cumple cuando ya hubo al menos un click
     window.speechSynthesis.cancel(); 
  }

  loadNextExercise(event);
});
            fetchExercises();
        });

        // --- Animación Ligera de Símbolos ---
        document.addEventListener('DOMContentLoaded', () => {
            const parallaxBg = document.getElementById('parallax-bg');
            if (parallaxBg && window.innerWidth > 768) {
                const symbols = ['+', '-', '×', '÷', '=', '%', 'π', '∞', '√', '∫', '∑'];
                const numSymbols = 15;
                for (let i = 0; i < numSymbols; i++) {
                    const el = document.createElement('div');
                    el.className = 'math-symbol';
                    el.textContent = symbols[Math.floor(Math.random() * symbols.length)];
                    el.style.left = `${Math.random() * 100}vw`;
                    el.style.top = `${Math.random() * 100}vh`;
                    el.style.fontSize = `${Math.random() * 3 + 1}rem`;
                    
                    const duration = 15 + Math.random() * 15;
                    const delay = Math.random() * 5;
                    el.style.animation = `float-symbol ${duration}s ease-in-out ${delay}s infinite alternate`;
                    
                    parallaxBg.appendChild(el);
                }
            }
        });
    </script>
    <script src="toast_notifications.js"></script>
</body>
</html>