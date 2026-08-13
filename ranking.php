<?php
require_once 'seguridad.php';
iniciar_sesion_segura();
inyectar_cabeceras_seguridad();
require_once 'conexion.php';
require_once 'ligas.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: inicio_de_sesion.php");
    exit();
}

$conn = Db::conectar();
$id_usuario = $_SESSION['user_id'];
$stmtUser = $conn->prepare("SELECT nombre, foto_de_perfil, racha FROM usuarios WHERE id = :id");
$stmtUser->execute(['id' => $id_usuario]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

verificarYEjecutarReinicioMensual($conn);
$infoLigas = obtenerInfoLigas($conn);

$ligasClasificadas = [
    'Peritus' => [],
    'Pro' => [],
    'Semi' => [],
    'Provectus' => [],
    'Aficionado' => []
];

$miLiga = 'Aficionado';
$miRankEnLiga = 0;

foreach ($infoLigas as $j) {
    $ligasClasificadas[$j['liga']][] = $j;
    if ($j['id'] == $id_usuario) {
        $miLiga = $j['liga'];
        $miRankEnLiga = count($ligasClasificadas[$j['liga']]);
    }
}

// Tiempo para fin de mes
$finDeMes = obtenerTiempoFinDeMes();
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Ranking Global</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="index.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .tab-btn.active { border-bottom-width: 4px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .bg-Peritus { background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%); color: white; }
        .bg-Pro { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: white; }
        .bg-Semi { background: linear-gradient(135deg, #eab308 0%, #a16207 100%); color: white; }
        .bg-Provectus { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; }
        .bg-Aficionado { background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: white; }
    </style>
</head>
<body class="bg-sky-50 min-h-screen pb-12">

    <!-- Navegación -->
    <nav class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="menu.php" class="flex items-center gap-2 text-slate-500 hover:text-blue-600 font-bold group transition-colors">
                        <div class="bg-white shadow-sm border border-gray-200 w-10 h-10 rounded-full flex items-center justify-center group-hover:-translate-x-1 group-hover:border-blue-300 transition-all">
                            <i class="fas fa-arrow-left transition-transform"></i>
                        </div>
                        <span class="font-bold hidden sm:inline">Volver al Menú</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($user['nombre']); ?></p>
                        <p class="text-xs font-bold text-gray-500">Liga <?php echo $miLiga; ?> (#<?php echo $miRankEnLiga; ?>)</p>
                    </div>
                    <img class="h-10 w-10 rounded-full border-2 border-gray-200 object-cover" src="<?php echo htmlspecialchars($user['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); ?>" alt="Perfil">
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <!-- Header Section -->
        <div class="text-center mb-10 animate__animated animate__fadeInDown">
            <h1 class="text-4xl md:text-5xl font-black text-slate-800 mb-4 tracking-tight">Clasificación <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Mundial</span></h1>
            <p class="text-slate-600 font-medium text-lg max-w-2xl mx-auto">Las ligas son relativas. Si superas en puntos a los de arriba, ellos bajarán a tu categoría. ¡Pelea por tu puesto!</p>
            
            <div class="mt-6 inline-flex items-center bg-white border border-gray-200 rounded-full px-6 py-3 shadow-sm">
                <i class="fas fa-clock text-blue-500 mr-3 text-xl"></i>
                <div class="text-left">
                    <p class="text-xs text-slate-500 uppercase font-black tracking-wider">Reinicio Mensual En</p>
                    <p id="countdown-timer" class="font-mono font-bold text-slate-800 text-sm" data-time="<?php echo $finDeMes; ?>">Calculando...</p>
                </div>
            </div>
        </div>

        <!-- Pestañas de Ligas -->
        <div class="glass-panel rounded-3xl p-2 mb-8 shadow-xl border border-gray-100 overflow-hidden">
            <div class="flex flex-wrap md:flex-nowrap justify-between gap-2">
                <?php 
                $tabs = [
                    'Peritus' => ['icon' => 'fa-crown', 'color' => 'border-purple-500', 'text' => 'text-purple-600', 'desc' => 'Top 10%'],
                    'Pro' => ['icon' => 'fa-fire', 'color' => 'border-red-500', 'text' => 'text-red-500', 'desc' => 'Siguiente 15%'],
                    'Semi' => ['icon' => 'fa-star', 'color' => 'border-yellow-500', 'text' => 'text-yellow-600', 'desc' => 'Siguiente 25%'],
                    'Provectus' => ['icon' => 'fa-shield-alt', 'color' => 'border-blue-500', 'text' => 'text-blue-600', 'desc' => 'Siguiente 25%'],
                    'Aficionado' => ['icon' => 'fa-seedling', 'color' => 'border-green-500', 'text' => 'text-green-600', 'desc' => 'Base 25%']
                ];
                foreach ($tabs as $liga_name => $tab): ?>
                    <button class="tab-btn flex-1 min-w-[100px] py-4 px-2 rounded-2xl transition-all duration-200 font-bold text-center border-b-0 hover:bg-slate-50 focus:outline-none flex flex-col items-center justify-center <?php echo ($miLiga == $liga_name) ? 'active bg-slate-50 shadow-inner '.$tab['color'] : 'text-slate-400 border-transparent'; ?>" data-target="liga-<?php echo strtolower($liga_name); ?>">
                        <i class="fas <?php echo $tab['icon']; ?> text-2xl mb-1 <?php echo ($miLiga == $liga_name) ? $tab['text'] : ''; ?>"></i>
                        <span class="<?php echo ($miLiga == $liga_name) ? 'text-slate-800' : ''; ?>"><?php echo $liga_name; ?></span>
                        <span class="text-[10px] uppercase font-black tracking-wider opacity-60"><?php echo count($ligasClasificadas[$liga_name]); ?> Jugadores</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contenido de las Ligas -->
        <div class="glass-panel rounded-3xl overflow-hidden shadow-2xl border border-gray-200">
            <?php foreach ($tabs as $liga_name => $tab): ?>
            <div id="liga-<?php echo strtolower($liga_name); ?>" class="tab-content <?php echo ($miLiga == $liga_name) ? 'active' : ''; ?>">
                <!-- Header Liga -->
                <div class="bg-<?php echo $liga_name; ?> p-8 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <i class="fas <?php echo $tab['icon']; ?> text-6xl opacity-20 absolute -right-4 -bottom-4 transform rotate-12"></i>
                    <h2 class="text-3xl font-black relative z-10 tracking-tight uppercase">Liga <?php echo $liga_name; ?></h2>
                    <p class="font-bold opacity-90 relative z-10 text-sm mt-1">Los mejores del <?php echo $tab['desc']; ?> mundial.</p>
                </div>

                <!-- Lista de Jugadores -->
                <div class="p-0">
                    <?php if (empty($ligasClasificadas[$liga_name])): ?>
                        <div class="p-12 text-center text-slate-500 font-bold">
                            <i class="fas fa-ghost text-4xl mb-3 opacity-30"></i>
                            <p>No hay jugadores en esta liga por ahora.</p>
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-100">
                            <?php 
                            $rangoInterno = 1;
                            foreach ($ligasClasificadas[$liga_name] as $jugador): 
                                $esYo = ($jugador['id'] == $id_usuario);
                                $bgClass = $esYo ? 'bg-blue-50/50 hover:bg-blue-50' : 'hover:bg-slate-50';
                            ?>
                                <li class="flex items-center p-4 sm:p-6 transition-colors <?php echo $bgClass; ?>">
                                    <div class="w-12 sm:w-16 text-center font-black text-xl sm:text-2xl <?php echo $esYo ? 'text-blue-600' : 'text-slate-400'; ?>">
                                        #<?php echo $rangoInterno; ?>
                                    </div>
                                    <div class="flex-shrink-0 relative">
                                        <img class="h-12 w-12 sm:h-14 sm:w-14 rounded-full object-cover border-2 <?php echo $esYo ? 'border-blue-500 ring-4 ring-blue-100' : 'border-gray-200'; ?>" src="<?php echo htmlspecialchars($jugador['foto'] ?? 'images/sinfoto.jpeg'); ?>" alt="">
                                        <?php if($rangoInterno == 1 && $liga_name == 'Peritus'): ?>
                                            <div class="absolute -top-2 -right-2 bg-yellow-400 text-white rounded-full h-6 w-6 flex items-center justify-center text-xs border-2 border-white shadow-sm"><i class="fas fa-star"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <a href="usuario.php?id=<?php echo $jugador['id']; ?>" class="text-base sm:text-lg font-bold text-slate-800 hover:text-blue-600 transition truncate block">
                                            <?php echo htmlspecialchars($jugador['nombre']); ?> <?php echo $esYo ? '<span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full uppercase tracking-wider">Tú</span>' : ''; ?>
                                        </a>
                                        <p class="text-xs sm:text-sm font-bold text-slate-500">Nivel <?php echo $jugador['nivel']; ?> • Global #<?php echo $jugador['posicion']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight"><?php echo number_format($jugador['puntos']); ?></p>
                                        <p class="text-xs font-bold uppercase tracking-wider <?php echo $tab['text']; ?>">Puntos</p>
                                    </div>
                                </li>
                            <?php $rangoInterno++; endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Lógica de Pestañas
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                
                // Remover actives
                tabBtns.forEach(b => {
                    b.classList.remove('active', 'bg-slate-50', 'shadow-inner');
                    b.classList.add('text-slate-400', 'border-transparent');
                    // Resetear colores del icono y texto
                    b.querySelector('i').className = b.querySelector('i').className.replace(/text-(purple|red|yellow|blue|green)-[456]00/g, '');
                    b.querySelector('span').classList.remove('text-slate-800');
                });
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Activar seleccionado
                btn.classList.add('active', 'bg-slate-50', 'shadow-inner');
                btn.classList.remove('text-slate-400', 'border-transparent');
                btn.querySelector('span').classList.add('text-slate-800');
                
                // Restaurar color del icono según la liga
                if(targetId.includes('peritus')) btn.querySelector('i').classList.add('text-purple-600');
                else if(targetId.includes('pro')) btn.querySelector('i').classList.add('text-red-500');
                else if(targetId.includes('semi')) btn.querySelector('i').classList.add('text-yellow-600');
                else if(targetId.includes('provectus')) btn.querySelector('i').classList.add('text-blue-600');
                else btn.querySelector('i').classList.add('text-green-600');

                document.getElementById(targetId).classList.add('active');
            });
        });

        // Lógica del Cronómetro Mensual
        const timerEl = document.getElementById('countdown-timer');
        const finDeMes = new Date(timerEl.getAttribute('data-time')).getTime();

        function updateTimer() {
            const now = new Date().getTime();
            const dist = finDeMes - now;

            if (dist < 0) {
                timerEl.innerHTML = "REINICIANDO...";
                setTimeout(() => window.location.reload(), 5000);
                return;
            }

            const days = Math.floor(dist / (1000 * 60 * 60 * 24));
            const hours = Math.floor((dist % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const mins = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));

            timerEl.innerHTML = `${days}D ${hours}H ${mins}M`;
        }

        setInterval(updateTimer, 60000); // Actualizar cada minuto
        updateTimer();
    </script>
</body>
</html>
