<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$id_duelo = $_GET['id_duelo'] ?? 0;
if (!$id_duelo) {
    header("Location: menu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duelo Épico</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f8fafc; overflow-x: hidden; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }
        #temporizador-barra { transition: width 0.1s linear, background-color 0.3s; }
        .shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        .combo-text {
            animation: popIn 0.3s ease-out forwards;
        }
        @keyframes popIn {
            0% { transform: scale(0.5); opacity: 0; }
            80% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .frozen { filter: grayscale(100%) sepia(100%) hue-rotate(180deg) saturate(300%) brightness(80%); pointer-events: none; }
        .frozen-overlay {
            position: absolute; inset: 0; background: rgba(0, 200, 255, 0.3); backdrop-filter: blur(2px);
            display: flex; align-items: center; justify-content: center; z-index: 50; border-radius: inherit;
        }
    </style>
</head>
<body class="text-slate-700 relative">

    <div class="fixed inset-0 z-[-1] pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
        <div class="absolute top-[40%] right-[-10%] w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
    </div>

    <div class="container mx-auto p-4 flex flex-col min-h-screen z-10 relative" id="contenedor-principal">

        <header class="w-full max-w-4xl mx-auto" id="header-duelo" style="display: none;">
            <div class="grid grid-cols-3 items-center gap-4">
                <div class="flex items-center gap-4">
                    <img id="j1-avatar" src="" class="w-16 h-16 rounded-full border-4 border-blue-500 shadow-lg">
                    <div>
                        <h2 id="j1-nombre" class="font-black text-lg text-slate-800">Tú</h2>
                        <p id="puntuacion-j1" class="text-2xl font-black text-blue-600">0</p>
                    </div>
                </div>
                <div class="text-center relative">
                    <h1 id="tema-titulo" class="text-3xl font-black text-orange-500">Cargando...</h1>
                    <p id="numero-pregunta" class="font-bold text-gray-500">Preparando...</p>
                    <div id="combo-display" class="absolute top-10 left-1/2 transform -translate-x-1/2 text-2xl font-black text-yellow-500 combo-text hidden drop-shadow-md">
                        ¡Combo x2! 🔥
                    </div>
                </div>
                <div class="flex items-center flex-row-reverse gap-4 relative">
                    <img id="j2-avatar" src="" class="w-16 h-16 rounded-full border-4 border-pink-500 shadow-lg">
                    <div class="text-right">
                        <h2 id="j2-nombre" class="font-black text-lg text-slate-800">Oponente</h2>
                        <p id="puntuacion-j2" class="text-2xl font-black text-pink-500">0</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-between mt-4">
                <div class="flex gap-2">
                    <button id="btn-congelar" class="bg-cyan-100 text-cyan-600 border border-cyan-300 font-bold px-3 py-1 rounded-lg text-sm shadow-sm hover:bg-cyan-200 transition disabled:opacity-50" title="Congelar al oponente por 3s"><i class="fas fa-snowflake"></i> Congelar</button>
                    <button id="btn-5050" class="bg-purple-100 text-purple-600 border border-purple-300 font-bold px-3 py-1 rounded-lg text-sm shadow-sm hover:bg-purple-200 transition disabled:opacity-50" title="Eliminar 2 respuestas incorrectas"><i class="fas fa-magic"></i> 50/50</button>
                </div>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-5 mt-2 shadow-inner relative overflow-hidden">
                <div id="temporizador-barra" class="bg-gradient-to-r from-green-400 to-green-500 h-5 rounded-full" style="width: 100%;"></div>
            </div>
        </header>

        <main id="panel-pregunta" class="flex-1 w-full max-w-2xl mx-auto text-center my-8 flex flex-col justify-center relative z-10" style="display: none;">
            <div id="pantalla-congelada" class="hidden frozen-overlay text-cyan-800 font-black text-3xl flex-col">
                <i class="fas fa-snowflake text-6xl mb-4 animate-spin" style="animation-duration: 3s;"></i>
                ¡ESTÁS CONGELADO!
            </div>
            <div class="glass-panel p-8 rounded-3xl" id="caja-pregunta">
                <h3 id="pregunta-texto" class="text-3xl font-black leading-tight text-slate-800"></h3>
                <div id="opciones-respuesta" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
                    <!-- Botones inyectados por JS -->
                </div>
            </div>
        </main>

        <div id="panel-carga" class="flex-1 flex flex-col items-center justify-center">
            <i class="fas fa-swords text-6xl text-orange-500 mb-4 animate-bounce"></i>
            <h2 class="text-3xl font-black text-slate-700">Preparando la Arena...</h2>
        </div>

        <div id="panel-resultados" class="w-full max-w-2xl mx-auto text-center my-8 hidden glass-panel p-10 rounded-3xl border-t-8 border-yellow-400">
            <h2 id="resultado-titulo" class="text-5xl font-black mb-4"></h2>
            <div class="flex justify-center items-center gap-8 my-8">
                <div class="text-center">
                    <p class="text-gray-500 font-bold uppercase tracking-wider mb-2">Tus Puntos</p>
                    <p id="res-mi-puntuacion" class="text-4xl font-black text-blue-600">0</p>
                </div>
                <div class="text-3xl font-black text-gray-300">VS</div>
                <div class="text-center">
                    <p class="text-gray-500 font-bold uppercase tracking-wider mb-2">Oponente</p>
                    <p id="res-su-puntuacion" class="text-4xl font-black text-pink-500">0</p>
                </div>
            </div>
            <div class="bg-gray-100 p-4 rounded-xl inline-block mb-8">
                <p class="font-bold text-slate-700">Recompensas Obtenidas:</p>
                <p class="text-green-600 font-black text-xl mt-1"><span id="res-xp">+0 XP</span> | <span id="res-ranking">+0 Ranking</span></p>
            </div>
            <div>
                <a href="menu.php" class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-black py-3 px-8 rounded-xl shadow-lg hover:-translate-y-1 transition">Volver al Menú</a>
            </div>
        </div>

    </div>

    <script>
        const ID_DUELO = <?php echo $id_duelo; ?>;
        let preguntas = [];
        let indexActual = 0;
        let miPuntuacion = 0;
        let suPuntuacion = 0;
        
        let timerInterval;
        let tiempoRestante = 15;
        const TIEMPO_TOTAL = 15;
        
        let comboCount = 0;
        let respondidoEnEstaPregunta = false;

        let pollingInterval;
        
        // --- MOTOR DE AUDIO ---
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const audioCtx = new AudioContext();

        function playTone(freq, type, duration, vol=0.1) {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
            gain.gain.setValueAtTime(vol, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + duration);
        }

        const sounds = {
            tick: () => playTone(800, 'sine', 0.1, 0.05),
            ding: () => { playTone(523.25, 'sine', 0.1, 0.1); setTimeout(() => playTone(659.25, 'sine', 0.2, 0.1), 100); },
            buzz: () => { playTone(150, 'sawtooth', 0.3, 0.1); setTimeout(() => playTone(100, 'sawtooth', 0.4, 0.1), 150); },
            powerup: () => { playTone(880, 'sine', 0.2, 0.05); setTimeout(() => playTone(1760, 'sine', 0.4, 0.05), 100); }
        };

        // --- INICIALIZACIÓN ---
        async function iniciarDuelo() {
            try {
                const res = await fetch(`duelo_api.php?action=iniciar&id_duelo=${ID_DUELO}`);
                const data = await res.json();
                if (data.status !== 'listo') {
                    alert('Error: ' + data.message);
                    window.location.href = 'menu.php';
                    return;
                }
                
                preguntas = data.preguntas;
                document.getElementById('tema-titulo').textContent = data.tema;
                document.getElementById('j2-nombre').textContent = data.oponente.nombre;
                document.getElementById('j2-avatar').src = data.oponente.avatar;
                
                // Fetch own avatar from somewhere? We will set it to default, or just leave empty if it doesn't matter.
                // We'll leave the PHP set values. Wait, PHP set it but we rewrote the HTML.
                document.getElementById('j1-avatar').src = '<?php echo $_SESSION['user_avatar'] ?? 'images/sinfoto.jpeg'; ?>';
                
                document.getElementById('panel-carga').style.display = 'none';
                document.getElementById('header-duelo').style.display = 'block';
                document.getElementById('panel-pregunta').style.display = 'flex';
                
                iniciarPolling();
                mostrarPregunta();
            } catch (e) {
                console.error(e);
            }
        }

        // --- FLUJO DE PREGUNTAS ---
        function mostrarPregunta() {
            if (indexActual >= preguntas.length) {
                finalizarDuelo();
                return;
            }
            
            respondidoEnEstaPregunta = false;
            document.getElementById('numero-pregunta').textContent = `Pregunta ${indexActual + 1}/10`;
            
            const p = preguntas[indexActual];
            document.getElementById('pregunta-texto').innerHTML = p.pregunta.replace(/(\d+)/g, '<span class="text-blue-600">$1</span>');
            
            const contenedorOpciones = document.getElementById('opciones-respuesta');
            contenedorOpciones.innerHTML = '';
            
            p.opciones.forEach(op => {
                const btn = document.createElement('button');
                btn.className = 'btn-opcion bg-gray-100 text-slate-700 border-b-4 border-gray-300 font-bold p-4 rounded-lg text-2xl hover:-translate-y-1 active:translate-y-0.5 active:border-b-0 transition-all duration-150';
                btn.textContent = op;
                btn.onclick = () => enviarRespuesta(btn, op);
                contenedorOpciones.appendChild(btn);
            });

            tiempoRestante = TIEMPO_TOTAL;
            actualizarBarra();
            clearInterval(timerInterval);
            timerInterval = setInterval(tick, 1000);
        }

        function tick() {
            tiempoRestante--;
            actualizarBarra();
            if (tiempoRestante <= 3 && tiempoRestante > 0) {
                sounds.tick();
            }
            if (tiempoRestante <= 0) {
                clearInterval(timerInterval);
                if (!respondidoEnEstaPregunta) enviarRespuesta(null, null); // Tiempo agotado
            }
        }

        function actualizarBarra() {
            const barra = document.getElementById('temporizador-barra');
            const pct = (tiempoRestante / TIEMPO_TOTAL) * 100;
            barra.style.width = `${pct}%`;
            if (pct > 50) { barra.className = 'bg-gradient-to-r from-green-400 to-green-500 h-5 rounded-full'; }
            else if (pct > 20) { barra.className = 'bg-gradient-to-r from-yellow-400 to-yellow-500 h-5 rounded-full'; }
            else { barra.className = 'bg-gradient-to-r from-red-500 to-orange-500 h-5 rounded-full animate-pulse'; }
        }

        async function enviarRespuesta(btnHTML, respuestaStr) {
            if (respondidoEnEstaPregunta) return;
            respondidoEnEstaPregunta = true;
            clearInterval(timerInterval);

            // Deshabilitar botones
            document.querySelectorAll('.btn-opcion').forEach(b => {
                b.disabled = true;
                b.classList.remove('hover:-translate-y-1', 'active:border-b-0');
            });

            // Si fue por tiempo
            if (!btnHTML) {
                comboCount = 0;
                sounds.buzz();
                document.getElementById('caja-pregunta').classList.add('shake');
            }

            let combo_bonus = 0;
            if (tiempoRestante > 10 && comboCount >= 2) combo_bonus = 50; // Bonus por rapidez y racha

            const formData = new FormData();
            formData.append('id_duelo', ID_DUELO);
            formData.append('pregunta_index', indexActual);
            formData.append('respuesta', respuestaStr || '');
            formData.append('combo_bonus', combo_bonus);

            try {
                const res = await fetch('duelo_api.php?action=responder', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.puntos_ganados > 0) {
                    comboCount++;
                    sounds.ding();
                    if (btnHTML) {
                        btnHTML.classList.replace('bg-gray-100', 'bg-green-500');
                        btnHTML.classList.replace('text-slate-700', 'text-white');
                        btnHTML.classList.replace('border-gray-300', 'border-green-700');
                    }
                    if (combo_bonus > 0) mostrarCombo();
                } else {
                    comboCount = 0;
                    if (btnHTML) {
                        sounds.buzz();
                        document.getElementById('caja-pregunta').classList.add('shake');
                        btnHTML.classList.replace('bg-gray-100', 'bg-red-500');
                        btnHTML.classList.replace('text-slate-700', 'text-white');
                        btnHTML.classList.replace('border-gray-300', 'border-red-700');
                    }
                    // Pintar la correcta de verde
                    document.querySelectorAll('.btn-opcion').forEach(b => {
                        if (b.textContent == data.respuesta_correcta) {
                            b.classList.replace('bg-gray-100', 'bg-green-400');
                        }
                    });
                }
                
                setTimeout(() => {
                    document.getElementById('caja-pregunta').classList.remove('shake');
                    if (data.duelo_terminado) finalizarDuelo();
                    else {
                        indexActual++;
                        mostrarPregunta();
                    }
                }, 2000);

            } catch (e) {
                console.error(e);
            }
        }

        function mostrarCombo() {
            const display = document.getElementById('combo-display');
            display.textContent = `¡Combo x${comboCount}! 🔥`;
            display.classList.remove('hidden');
            display.style.animation = 'none';
            display.offsetHeight; /* trigger reflow */
            display.style.animation = null;
            setTimeout(() => display.classList.add('hidden'), 2000);
        }

        // --- PODERES ---
        document.getElementById('btn-congelar').onclick = () => usarPoder('congelar', document.getElementById('btn-congelar'));
        document.getElementById('btn-5050').onclick = () => usarPoder('50_50', document.getElementById('btn-5050'));

        async function usarPoder(tipo, btnEl) {
            btnEl.disabled = true;
            btnEl.classList.add('opacity-50');
            sounds.powerup();
            
            const formData = new FormData();
            formData.append('id_duelo', ID_DUELO);
            formData.append('poder', tipo);
            formData.append('pregunta_index', indexActual);
            
            try {
                const res = await fetch('duelo_api.php?action=usar_poder', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success && tipo === '50_50' && !respondidoEnEstaPregunta) {
                    if (data.incorrectas_a_eliminar && data.incorrectas_a_eliminar.length > 0) {
                        document.querySelectorAll('.btn-opcion').forEach(btn => {
                            if (data.incorrectas_a_eliminar.includes(btn.textContent)) {
                                btn.style.visibility = 'hidden';
                            }
                        });
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

        // --- SONDEO Y TIEMPO REAL ---
        function iniciarPolling() {
            pollingInterval = setInterval(async () => {
                try {
                    const res = await fetch(`duelo_api.php?action=estado_duelo&id_duelo=${ID_DUELO}`);
                    const data = await res.json();
                    if (data.status === 'ok') {
                        document.getElementById('puntuacion-j1').textContent = data.mi_puntuacion;
                        document.getElementById('puntuacion-j2').textContent = data.su_puntuacion;
                        
                        // Congelación
                        const panel = document.getElementById('panel-pregunta');
                        const capaHielo = document.getElementById('pantalla-congelada');
                        if (data.estoy_congelado) {
                            panel.classList.add('frozen');
                            capaHielo.classList.remove('hidden');
                        } else {
                            panel.classList.remove('frozen');
                            capaHielo.classList.add('hidden');
                        }
                    }
                } catch (e) { console.error(e); }
            }, 2000);
        }

        // --- FINALIZACIÓN ---
        async function finalizarDuelo() {
            clearInterval(timerInterval);
            clearInterval(pollingInterval);
            
            document.getElementById('header-duelo').style.display = 'none';
            document.getElementById('panel-pregunta').style.display = 'none';
            
            const formData = new FormData();
            formData.append('id_duelo', ID_DUELO);
            const res = await fetch('duelo_api.php?action=finalizar', { method: 'POST', body: formData });
            const data = await res.json();
            
            const pRes = document.getElementById('panel-resultados');
            const tit = document.getElementById('resultado-titulo');
            
            document.getElementById('res-mi-puntuacion').textContent = data.mi_puntuacion;
            document.getElementById('res-su-puntuacion').textContent = data.puntuacion_oponente;
            
            let colorRanking = 'text-green-600';
            let signoRanking = '+';
            if (data.puntos_ranking < 0) { colorRanking = 'text-red-500'; signoRanking = ''; }
            
            document.getElementById('res-xp').textContent = `+${data.xp_ganada} XP`;
            document.getElementById('res-ranking').textContent = `${signoRanking}${data.puntos_ranking} Ranking`;
            document.getElementById('res-ranking').className = `font-black text-xl mt-1 ${colorRanking}`;

            pRes.classList.remove('hidden');
            
            if (data.resultado === 'victoria') {
                tit.textContent = '¡VICTORIA!';
                tit.className = 'text-5xl font-black mb-4 text-green-500';
                lanzarConfeti();
            } else if (data.resultado === 'derrota') {
                tit.textContent = 'DERROTA';
                tit.className = 'text-5xl font-black mb-4 text-red-500';
            } else {
                tit.textContent = 'EMPATE';
                tit.className = 'text-5xl font-black mb-4 text-gray-500';
            }
        }

        function lanzarConfeti() {
            var duration = 3000;
            var end = Date.now() + duration;
            (function frame() {
                confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#26ccff', '#a25afd', '#ff5e7e'] });
                confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#26ccff', '#a25afd', '#ff5e7e'] });
                if (Date.now() < end) requestAnimationFrame(frame);
            }());
        }

        // --- ARRANQUE ---
        window.onload = iniciarDuelo;
    </script>
</body>
</html>