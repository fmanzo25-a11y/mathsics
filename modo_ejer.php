<?php
session_start();

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
    'indigo' => ['100'=>'#e0e7ff', '200'=>'#c7d2fe', '500'=>'#6366f1', '700'=>'#4338ca', 'bg'=>'bg-indigo-500', 'text'=>'text-indigo-600', 'border'=>'border-indigo-700', 'hover_bg'=>'hover:bg-indigo-600', 'ring'=>'focus:ring-indigo-500'],
    'green'  => ['100'=>'#dcfce7', '200'=>'#bbf7d0', '500'=>'#22c55e', '700'=>'#15803d', 'bg'=>'bg-green-500', 'text'=>'text-green-600', 'border'=>'border-green-700', 'hover_bg'=>'hover:bg-green-600', 'ring'=>'focus:ring-green-500'],
    'sky'    => ['100'=>'#e0f2fe', '200'=>'#bae6fd', '500'=>'#0ea5e9', '700'=>'#0369a1', 'bg'=>'bg-sky-500', 'text'=>'text-sky-600', 'border'=>'border-sky-700', 'hover_bg'=>'hover:bg-sky-600', 'ring'=>'focus:ring-sky-500'],
    'pink'   => ['100'=>'#fce7f3', '200'=>'#fbcfe8', '500'=>'#ec4899', '700'=>'#be185d', 'bg'=>'bg-pink-500', 'text'=>'text-pink-600', 'border'=>'border-pink-700', 'hover_bg'=>'hover:bg-pink-600', 'ring'=>'focus:ring-pink-500'],
];

$config_actual = $temas_config[$tema_actual] ?? $temas_config['Aritmética'];
$color_actual = $colores[$config_actual['color']] ?? $colores['indigo'];
$icono_actual = $config_actual['icon'] ?? 'fa-calculator';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Modo Ejercicio</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: <?php echo $color_actual['100']; ?>;
            background-image: linear-gradient(to bottom, <?php echo $color_actual['200']; ?>, #ffffff 25%);
        }
        @keyframes fade-in-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fade-out { from { opacity: 1; } to { opacity: 0; } }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); } 20%, 40%, 60%, 80% { transform: translateX(5px); } }
        .animate-fade-in-up { animation: fade-in-up 0.6s ease-out both; }
        .animate-fade-in { animation: fade-in 0.5s ease-out both; }
        .animate-fade-out { animation: fade-out 0.4s ease-in both; }
        .animate-shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }

        #explanation-container { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.5s ease-in-out; margin-top: 0; }
        #explanation-container.show { max-height: 20rem; opacity: 1; margin-top: 1.5rem; }
    </style>
</head>
<body class="min-h-screen text-slate-800">

    <div id="exercise-container" class="w-full max-w-3xl mx-auto flex flex-col h-screen p-4 sm:p-6 animate-fade-in-up">
        
        <header class="flex justify-between items-center w-full">
            <a href="menu.php" class="<?php echo $color_actual['text']; ?> opacity-80 hover:opacity-100 transition group flex items-center gap-2 text-sm font-bold">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                <span>Menú</span>
            </a>
            <div class="text-center font-black <?php echo $color_actual['text']; ?>">
                <i class="fa-solid <?php echo $icono_actual; ?> text-xl"></i>
                <p class="text-lg"><?php echo htmlspecialchars($tema_actual); ?></p>
            </div>
            <div id="timer-container" class="flex items-center justify-center w-14 h-14 rounded-full font-black text-2xl <?php echo $color_actual['bg']; ?> text-white shadow-lg">
                <span id="timer">30</span>
            </div>
        </header>

        <div class="my-4 w-full">
            <div class="w-full bg-gray-200 rounded-full h-4 relative shadow-inner">
                <div id="progress-bar" class="<?php echo $color_actual['bg']; ?> h-full rounded-full transition-all duration-500 ease-out flex items-center justify-end pr-2"></div>
                <p id="progress-text" class="absolute inset-0 flex items-center justify-center text-xs font-bold text-slate-700">Ejercicio 1 de N</p>
            </div>
        </div>
        
        <div id="question-content" class="flex-1 flex flex-col justify-center">
            <h2 id="question-text" class="text-2xl sm:text-3xl md:text-4xl font-black text-center mb-8 text-slate-800">Cargando...</h2>
            <div id="options-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            <div id="explanation-container"></div>
        </div>
        
        <footer class="py-4">
            <button id="next-button" class="w-full <?php echo $color_actual['bg']; ?> <?php echo $color_actual['hover_bg']; ?> text-white font-bold py-4 rounded-xl shadow-lg border-b-4 <?php echo $color_actual['border']; ?> active:border-b-0 active:translate-y-1 transition-all duration-150 hidden">Siguiente</button>
        </footer>
    </div>
    
    <audio id="correct-sound" preload="auto"><source src="sonidos/respuesta_correcta.mp3" type="audio/mpeg"></audio>
    <audio id="incorrect-sound" preload="auto"><source src="sonidos/respuesta_incorrecta.mp3" type="audio/mpeg"></audio>
    <audio id="click-sound" preload="auto"><source src="sonidos/clic_inicio.mp3" type="audio/mpeg"></audio>
    <audio id="victory-sound" preload="auto"><source src="sonidos/victoria.mp3" type="audio/mpeg"></audio>

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

            function vibrateDevice(pattern) {
                if ('vibrate' in navigator) {
                    try { navigator.vibrate(pattern); } 
                    catch (e) { console.warn("Vibration failed:", e); }
                }
            }

            function animateElement(element, animation) { return new Promise(resolve => { element.classList.add(animation); element.addEventListener('animationend', () => { element.classList.remove(animation); resolve(); }, { once: true }); }); }
            
            function startTimer() {
                clearInterval(timerInterval);
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
                    } else if (timeLeft <= DURATION * 0.25) {
                        timerContainer.classList.remove('bg-yellow-400');
                        timerContainer.classList.add('bg-red-500');
                    }
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        vibrateDevice(50); // Vibración corta cuando se acaba el tiempo
                        incorrectSound?.play().catch(e => console.warn("Audio failed"));
                        revealCorrectAnswer();
                        showExplanation();
                        nextButton.classList.remove('hidden');
                    }
                }, 1000);
            }

            function loadExercise(exercise) {
                if (!exercise) return;
                explanationContainer.classList.remove('show');
                explanationContainer.innerHTML = '';
                questionText.innerHTML = exercise.pregunta;
                optionsGrid.innerHTML = '';
                
                exercise.opciones.forEach(opcion => {
                    const button = document.createElement('button');
                    button.innerHTML = `<span>${opcion}</span><span class="feedback-icon"></span>`;
                    button.dataset.answer = opcion;
                    button.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-slate-700 bg-white border-b-4 border-gray-200 shadow-sm transition-all duration-150 hover:-translate-y-1 active:translate-y-0.5 active:border-b-0`;
                    button.addEventListener('click', selectAnswer);
                    optionsGrid.appendChild(button);
                });

                updateProgress();
                startTimer();
                nextButton.classList.add('hidden');
            }

            function updateProgress() { const total = exercises.length; if (total === 0) return; progressText.textContent = `Ejercicio ${currentExerciseIndex + 1} de ${total}`; progressBar.style.width = `${((currentExerciseIndex + 1) / total) * 100}%`; }

            function selectAnswer(e) {
                clearInterval(timerInterval);
                const selectedButton = e.currentTarget;
                const selectedAnswer = selectedButton.dataset.answer;
                const correctAnswer = exercises[currentExerciseIndex].solucion;
                const isCorrect = String(selectedAnswer) === String(correctAnswer);

                resultadosEjercicios.push({
                    id_ejercicio: exercises[currentExerciseIndex].id_ejercicio,
                    respuesta_correcta: isCorrect,
                    tema: exercises[currentExerciseIndex].tema
                });
                
                document.querySelectorAll('.option-btn').forEach(btn => btn.disabled = true);
                const feedbackIcon = selectedButton.querySelector('.feedback-icon');

                if (isCorrect) {
                    correctSound?.play().catch(e => console.warn("Audio failed"));
                    vibrateDevice(100);
                    selectedButton.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-white bg-green-500 border-b-4 border-green-700`;
                    feedbackIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    aciertos++;
                } else {
                    incorrectSound?.play().catch(e => console.warn("Audio failed"));
                    vibrateDevice([100, 50, 100]);
                    selectedButton.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-white bg-red-500 border-b-4 border-red-700`;
                    feedbackIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
                    animateElement(exerciseContainer, 'animate-shake');
                }
                revealCorrectAnswer(selectedAnswer);
                showExplanation();
                nextButton.classList.remove('hidden');
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
                        </div>
                    `;
                    setTimeout(() => { explanationContainer.classList.add('show'); }, 50);
                }
            }

            function revealCorrectAnswer(selectedAnswer = null) {
                document.querySelectorAll('.option-btn').forEach(btn => {
                    btn.disabled = true;
                    if (String(btn.dataset.answer) === String(exercises[currentExerciseIndex].solucion)) {
                        btn.className = `option-btn flex justify-between items-center text-lg font-bold p-4 rounded-xl text-white bg-green-500 border-b-4 border-green-700`;
                    } else if (btn.dataset.answer !== selectedAnswer) {
                        btn.classList.add('opacity-50');
                    }
                });
            }
            
            async function loadNextExercise() { 
                clickSound?.play().catch(e => console.warn("Audio failed"));
                vibrateDevice(50);
                await animateElement(document.getElementById('question-content'), 'animate-fade-out'); 
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

            nextButton.addEventListener('click', loadNextExercise);
            fetchExercises();
        });
    </script>
</body>
</html>