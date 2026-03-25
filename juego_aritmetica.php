<?php
session_start();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meteoro Matemático</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes animate-gradient { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .gradient-bg { background: linear-gradient(-45deg, #1e3a8a, #4f46e5, #7c3aed, #1e1b4b); background-size: 400% 400%; animation: animate-gradient 20s ease infinite; }
        
        @keyframes fall {
            from { top: -100px; transform: rotate(0deg); }
            to { top: 100%; transform: rotate(360deg); }
        }
        .meteor {
            position: absolute;
            left: 50%;
            will-change: top;
            animation-name: fall;
            animation-timing-function: linear;
        }
        @keyframes explode {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(2.5); opacity: 0; }
        }
        .explode { animation: explode 0.3s ease-out forwards; }
    </style>
</head>
<body class="gradient-bg text-white font-sans overflow-hidden">

    <div id="game-container" class="relative w-full h-screen max-w-2xl mx-auto flex flex-col items-center justify-between p-4">

        <div id="start-screen" class="absolute inset-0 bg-black/50 backdrop-blur-sm flex flex-col justify-center items-center z-20">
            <h1 class="text-5xl font-bold mb-4 text-yellow-300">Meteoro Matemático</h1>
            <p class="text-xl mb-8">Destruye los meteoros con el poder de la aritmética.</p>
            <button id="start-button" class="bg-pink-500 hover:bg-pink-600 text-white font-bold py-4 px-10 rounded-lg shadow-lg text-2xl transition-transform transform hover:scale-105">¡Jugar!</button>
             <a href="menu.php" class="mt-6 text-indigo-300 hover:text-white transition">&larr; Volver al Menú</a>
        </div>

        <div id="game-over-screen" class="absolute inset-0 bg-black/50 backdrop-blur-sm flex-col justify-center items-center z-20 hidden">
            <h1 class="text-5xl font-bold mb-4 text-red-500">¡Juego Terminado!</h1>
            <p class="text-2xl mb-2">Puntuación Final:</p>
            <p id="final-score" class="text-6xl font-bold mb-8 text-yellow-300">0</p>
             <form action="guardar_partida.php" method="POST" class="flex flex-col items-center">
                 <input type="hidden" id="score-input" name="score">
                 <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg shadow-lg text-xl mb-4">Guardar y Salir</button>
             </form>
            <button id="retry-button" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-3 px-8 rounded-lg shadow-lg text-xl">Volver a Intentar</button>
        </div>

        <header class="w-full flex justify-between items-center bg-white/10 p-4 rounded-xl border border-white/20">
            <div class="text-left">
                <p class="text-sm opacity-80">PUNTUACIÓN</p>
                <p id="score" class="text-3xl font-bold">0</p>
            </div>
            <div class="text-center">
                <p class="text-sm opacity-80">NIVEL</p>
                <p id="level" class="text-3xl font-bold">1</p>
            </div>
            <div id="lives" class="flex gap-3 text-3xl text-red-400">
                <i class="fas fa-heart"></i>
                <i class="fas fa-heart"></i>
                <i class="fas fa-heart"></i>
            </div>
        </header>

        <main id="game-area" class="w-full flex-1 relative"></main>

        <footer class="w-full flex flex-col items-center">
            <div class="w-24 h-12 bg-gray-700 rounded-t-lg border-2 border-gray-500"></div> <input type="number" id="answer-input" class="w-full max-w-xs text-center text-4xl font-bold p-3 bg-white/20 border-2 border-white/30 rounded-lg shadow-lg outline-none focus:border-pink-400 transition" placeholder="?" disabled>
        </footer>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const gameArea = document.getElementById('game-area');
    const scoreEl = document.getElementById('score');
    const levelEl = document.getElementById('level');
    const livesEl = document.getElementById('lives');
    const answerInput = document.getElementById('answer-input');
    const startScreen = document.getElementById('start-screen');
    const gameOverScreen = document.getElementById('game-over-screen');
    const startButton = document.getElementById('start-button');
    const retryButton = document.getElementById('retry-button');
    const finalScoreEl = document.getElementById('final-score');
    const scoreInput = document.getElementById('score-input');

    let score = 0;
    let level = 1;
    let lives = 3;
    let gameActive = false;
    let currentProblem = {};
    let meteorEl;

    function generateProblem() {
        const num1 = Math.floor(Math.random() * (level * 5)) + 1;
        const num2 = Math.floor(Math.random() * 5) + 1;
        let question, answer;

        let operationPool = ['+'];
        if (level >= 2) operationPool.push('-');
        if (level >= 3) operationPool.push('×');
        
        const operation = operationPool[Math.floor(Math.random() * operationPool.length)];

        switch (operation) {
            case '+':
                question = `${num1} + ${num2}`;
                answer = num1 + num2;
                break;
            case '-':
                // Asegurar que la respuesta no sea negativa
                const maxNum = Math.max(num1, num2);
                const minNum = Math.min(num1, num2);
                question = `${maxNum} - ${minNum}`;
                answer = maxNum - minNum;
                break;
            case '×':
                const mult1 = Math.floor(Math.random() * 8) + 2;
                const mult2 = Math.floor(Math.random() * (level * 2)) + 1;
                question = `${mult1} × ${mult2}`;
                answer = mult1 * mult2;
                break;
        }
        return { question, answer };
    }

    function createMeteor() {
        if (!gameActive) return;

        currentProblem = generateProblem();
        meteorEl = document.createElement('div');
        meteorEl.className = 'meteor bg-gradient-to-br from-orange-400 to-red-600 text-white text-3xl font-bold px-8 py-5 rounded-full shadow-lg border-2 border-red-300/50';
        meteorEl.textContent = currentProblem.question;
        
        const speed = Math.max(2.5, 9 - level * 0.5);
        meteorEl.style.animationDuration = `${speed}s`;
        meteorEl.style.left = `${Math.random() * 80 + 10}%`;
        
        meteorEl.addEventListener('animationend', () => {
             if (gameActive) loseLife();
        });
        
        gameArea.appendChild(meteorEl);
    }
    
    function updateHUD() {
        scoreEl.textContent = score;
        levelEl.textContent = level;
        
        let hearts = '';
        for(let i=0; i<lives; i++) hearts += '<i class="fas fa-heart"></i>';
        livesEl.innerHTML = hearts;

        // Subir de nivel cada 100 puntos
        if(score >= level * 100) {
            level++;
            // Podríamos añadir un efecto visual o sonoro de subida de nivel aquí
        }
    }

    function handleCorrectAnswer() {
        score += 10;
        answerInput.value = '';
        
        meteorEl.classList.add('explode');
        meteorEl.addEventListener('animationend', () => meteorEl.remove());
        
        setTimeout(() => {
            updateHUD();
            createMeteor();
        }, 300);
    }
    
    function loseLife() {
        lives--;
        if(meteorEl) meteorEl.remove();
        updateHUD();

        if (lives <= 0) {
            endGame();
        } else {
            // Shake screen effect
            document.body.classList.add('animate-shake'); 
            setTimeout(() => document.body.classList.remove('animate-shake'), 500);
            createMeteor();
        }
    }
    
    function checkAnswer(e) {
        if (e.key === 'Enter' && gameActive) {
            const userAnswer = parseInt(answerInput.value);
            if (userAnswer === currentProblem.answer) {
                handleCorrectAnswer();
            } else {
                answerInput.value = '';
                
            }
        }
    }

    function startGame() {
        score = 0;
        level = 1;
        lives = 3;
        gameActive = true;
        answerInput.disabled = false;
        
        startScreen.classList.add('hidden');
        gameOverScreen.classList.add('hidden');
        
        updateHUD();
        createMeteor();
        answerInput.focus();
    }

    function endGame() {
        gameActive = false;
        answerInput.disabled = true;
        if(meteorEl) meteorEl.remove();
        
        finalScoreEl.textContent = score;
        scoreInput.value = score;
        gameOverScreen.style.display = 'flex';
        gameOverScreen.classList.remove('hidden');

    }
    
    startButton.addEventListener('click', startGame);
    retryButton.addEventListener('click', startGame);
    answerInput.addEventListener('keydown', checkAnswer);
});
</script>
</body>
</html>