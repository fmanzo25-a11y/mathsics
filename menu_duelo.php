<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$nombre_usuario = $_SESSION['user_name'] ?? 'Jugador';
$avatar_usuario = $_SESSION['user_avatar'] ?? 'images/sinfoto.jpeg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duelo Matemático - Selección</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
            background-image: linear-gradient(to top, #e0f2fe 90%, #f0f9ff 100%);
        }
        .tema-btn.active {
            transform: translateY(4px);
            border-bottom-width: 4px;
            filter: brightness(0.9);
        }
        @keyframes pulse-vs { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
        .vs-pulse { animation: pulse-vs 2s infinite cubic-bezier(0.4, 0, 0.6, 1); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div id="seleccion-panel" class="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-8 transition-all duration-300 border-t-4 border-orange-400">
        <div class="text-center mb-8">
            <i class="fas fa-swords text-5xl text-orange-500 mb-2"></i>
            <h1 class="text-4xl font-black text-slate-800">Duelo Matemático</h1>
            <p class="text-gray-500 mt-2 font-semibold">Hola <span class="font-bold text-blue-600"><?php echo htmlspecialchars($nombre_usuario); ?></span>, ¡prepárate para el desafío!</p>
        </div>

        <div class="mb-6 text-center">
            <label class="block text-xl font-bold text-gray-700 mb-4">1. Elige un Tema</label>
            <div id="contenedor-temas" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button data-tema="Aritmética" class="tema-btn bg-indigo-500 text-white font-black py-4 rounded-xl shadow-lg border-b-8 border-indigo-700 hover:-translate-y-1 active:translate-y-0.5 active:border-b-4 transition-transform duration-150 flex items-center justify-center text-lg gap-2">
                    <i class="fas fa-calculator"></i> Aritmética
                </button>
                <button data-tema="Álgebra" class="tema-btn bg-green-500 text-white font-black py-4 rounded-xl shadow-lg border-b-8 border-green-700 hover:-translate-y-1 active:translate-y-0.5 active:border-b-4 transition-transform duration-150 flex items-center justify-center text-lg gap-2">
                    <i class="fas fa-square-root-variable"></i> Álgebra
                </button>
                <button data-tema="Aleatorio" class="tema-btn bg-gray-500 text-white font-black py-4 rounded-xl shadow-lg border-b-8 border-gray-700 hover:-translate-y-1 active:translate-y-0.5 active:border-b-4 transition-transform duration-150 flex items-center justify-center text-lg gap-2">
                    <i class="fas fa-random"></i> Aleatorio
                </button>
            </div>
        </div>

        <div class="text-center mt-8">
            <button id="buscar-duelo-btn" class="w-full bg-orange-500 text-white font-black py-4 text-xl rounded-xl shadow-lg border-b-4 border-orange-700 hover:-translate-y-1 active:translate-y-0.5 active:border-b-0 transition-all duration-150 disabled:bg-gray-300 disabled:border-gray-400 disabled:cursor-not-allowed disabled:hover:transform-none" disabled>
                <i class="fas fa-search mr-2"></i> Buscar Duelo
            </button>
            <a href="menu.php" class="inline-block mt-6 text-gray-500 hover:text-blue-600 transition font-bold">Volver al menú principal</a>
        </div>
    </div>

    <div id="buscando-panel" class="w-full max-w-3xl text-center transition-all duration-300 hidden">
        <h2 class="text-2xl font-black text-slate-800 mb-2">Buscando oponente para un duelo de...</h2>
        <p id="tema-busqueda" class="text-4xl font-black text-blue-600 mb-8"></p>
        <div class="grid grid-cols-3 items-center">
            <div class="flex flex-col items-center">
                <img src="<?php echo htmlspecialchars($avatar_usuario); ?>" class="w-24 h-24 sm:w-32 sm:h-32 rounded-full border-4 border-white shadow-lg">
                <p class="mt-4 font-bold text-lg text-slate-700"><?php echo htmlspecialchars($nombre_usuario); ?></p>
            </div>
            <div class="vs-pulse text-7xl sm:text-8xl font-black text-orange-500" style="filter: drop-shadow(0 0 10px currentColor);">VS</div>
            <div class="flex flex-col items-center">
                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-gray-200 flex items-center justify-center border-4 border-white shadow-lg">
                    <i class="fas fa-question text-5xl text-gray-400"></i>
                </div>
                <p class="mt-4 font-bold text-lg text-slate-700">Oponente...</p>
            </div>
        </div>
        <i class="fas fa-spinner fa-spin text-4xl text-blue-500 my-8"></i>
        <button id="cancelar-busqueda-btn" class="bg-white text-red-500 font-bold py-2 px-6 rounded-full shadow-md hover:bg-red-50 transition border-2 border-gray-200">
            Cancelar Búsqueda
        </button>
    </div>

    <script>
        // La lógica JS no necesita cambios funcionales, solo se adapta al nuevo diseño.
        const temaButtons = document.querySelectorAll('.tema-btn');
        const buscarDueloBtn = document.getElementById('buscar-duelo-btn');
        const seleccionPanel = document.getElementById('seleccion-panel');
        const buscandoPanel = document.getElementById('buscando-panel');
        const cancelarBusquedaBtn = document.getElementById('cancelar-busqueda-btn');
        let temaSeleccionado = null;
        let pollInterval = null;

        temaButtons.forEach(button => {
            button.addEventListener('click', () => {
                temaButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                temaSeleccionado = button.dataset.tema;
                buscarDueloBtn.disabled = false;
            });
        });

        buscarDueloBtn.addEventListener('click', async () => { /* ... (sin cambios) ... */ });
        function iniciarSondeo(id_duelo) { /* ... (sin cambios) ... */ }
        cancelarBusquedaBtn.addEventListener('click', () => { /* ... (sin cambios) ... */ });

        // Script completo del archivo original
        buscarDueloBtn.addEventListener('click', async () => {
            seleccionPanel.classList.add('hidden');
            buscandoPanel.classList.remove('hidden');
            document.getElementById('tema-busqueda').textContent = temaSeleccionado;
            const formData = new FormData();
            formData.append('tema', temaSeleccionado);
            try {
                const response = await fetch('duelo_api.php?action=buscar', { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                if (data.status === 'encontrado') { window.location.href = `duelo.php?id_duelo=${data.id_duelo}`; } 
                else if (data.status === 'buscando') { iniciarSondeo(data.id_duelo); } 
                else { throw new Error(data.message || 'Error desconocido.'); }
            } catch (error) {
                console.error('Error al buscar duelo:', error);
                alert('Hubo un problema al buscar el duelo. Intenta de nuevo.');
                buscandoPanel.classList.add('hidden');
                seleccionPanel.classList.remove('hidden');
            }
        });

        function iniciarSondeo(id_duelo) {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(`duelo_api.php?action=verificar&id_duelo=${id_duelo}`);
                    if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                    const data = await response.json();
                    if (data.status === 'encontrado') {
                        clearInterval(pollInterval);
                        window.location.href = `duelo.php?id_duelo=${id_duelo}`;
                    }
                } catch (error) {
                    console.error('Error durante el sondeo:', error);
                    clearInterval(pollInterval);
                    alert('Se perdió la conexión. Intenta de nuevo.');
                    buscandoPanel.classList.add('hidden');
                    seleccionPanel.classList.remove('hidden');
                }
            }, 3000);
        }

        cancelarBusquedaBtn.addEventListener('click', () => {
            if (pollInterval) { clearInterval(pollInterval); }
            buscandoPanel.classList.add('hidden');
            seleccionPanel.classList.remove('hidden');
        });
    </script>
</body>
</html>