<?php
// CABECERAS HTTP DE SEGURIDAD
header("X-Frame-Options: DENY"); // Previene Clickjacking
header("X-Content-Type-Options: nosniff"); // Previene MIME-sniffing
header("Content-Security-Policy: frame-ancestors 'none'"); // Refuerza protección contra IFrames

// BLOQUE PHP PARA OBTENER DATOS DEL USUARIO Y LA SESIÓN
include_once 'conexion.php';

// HARDENING DE SESIÓN
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // HTTPS
    'httponly' => true, // Evita robo de cookie vía JavaScript
    'samesite' => 'Strict' // Evita que se envíen cookies desde sitios externos
]);
session_start();

// GENERACIÓN DE TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}
$id_usuario = (int) $_SESSION['user_id']; // Forzamos validación de tipo int

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tema'])) { 
    // VALIDACIÓN DE TOKEN CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("Posible ataque CSRF o token caducado detectado. Usuario ID: " . $id_usuario);
        die("Error de validación de seguridad. Por favor, recarga la página e inténtalo de nuevo.");
    }
    
    $_SESSION['tema'] = $_POST['tema']; 
    $_SESSION['subtema'] = $_POST['subtema'] ?? null; 
    header("Location: modo_ejer.php"); 
    exit(); 
}
try {
    $conn = Db::conectar();
    $stmtUser = $conn->prepare("SELECT nivel, xp, limite_xp, foto_de_perfil, correo, racha, nombre FROM usuarios WHERE id = :id");
    $stmtUser->bindValue(':id', $id_usuario, PDO::PARAM_INT); 
    $stmtUser->execute(); 
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    $stmtNotificaciones = $conn->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :id AND leida = '0'");
    $stmtNotificaciones->bindValue(':id', $id_usuario, PDO::PARAM_INT); 
    $stmtNotificaciones->execute(); 
    $numNotificaciones = $stmtNotificaciones->fetchColumn();
    
    $nivel = (int)($user['nivel'] ?? 1); 
    $exp = (int)($user['xp'] ?? 0); 
    $limite = (int)($user['limite_xp'] ?? 100);
    $foto = htmlspecialchars($user['foto_de_perfil'] ?? 'images/sinfoto.jpeg', ENT_QUOTES, 'UTF-8'); 
    $nombre_usuario = htmlspecialchars($user['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); 
    $racha = (int)($user['racha'] ?? 0);
    
    include_once 'ligas.php';
    verificarYEjecutarReinicioMensual($conn);
    $infoLigas = obtenerInfoLigas($conn);
    
    // Obtener información de MI liga
    $miLigaObj = isset($infoLigas[$id_usuario]) ? $infoLigas[$id_usuario] : null;
    $miLigaNombre = $miLigaObj ? $miLigaObj['liga'] : 'Aficionado';
    $miLigaColor = $miLigaObj ? $miLigaObj['color'] : 'text-green-500';
    $miLigaIcon = $miLigaObj ? $miLigaObj['icon'] : 'fa-seedling';
    
    // Filtrar top 3 de mi misma liga
    $topUsuarios = [];
    foreach($infoLigas as $j_id => $j) {
        if ($j['liga'] === $miLigaNombre) {
            $topUsuarios[] = [
                'id_usuario' => $j['id'],
                'nombre' => $j['nombre'],
                'foto_de_perfil' => $j['foto'],
                'puntos' => $j['puntos']
            ];
            if (count($topUsuarios) >= 3) break;
        }
    }
    
    // LÓGICA PARA GESTIONAR DESAFÍOS DIARIOS
    $desafios_diarios = [];
    $stmtCheck = $conn->prepare("SELECT ud.id, d.titulo, d.descripcion, d.recompensa_xp, d.icono, ud.progreso, d.objetivo_cantidad, ud.estado FROM usuario_desafios ud JOIN desafios d ON ud.id_desafio = d.id WHERE ud.id_usuario = :id_u AND ud.fecha_asignado = CURDATE()");
    $stmtCheck->execute(['id_u' => $id_usuario]);
    $desafios_diarios = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

    if (empty($desafios_diarios)) {
        $stmtNuevos = $conn->prepare("SELECT id FROM desafios ORDER BY RAND() LIMIT 3");
        $stmtNuevos->execute();
        $nuevos_ids = $stmtNuevos->fetchAll(PDO::FETCH_COLUMN);

        $stmtInsert = $conn->prepare("INSERT INTO usuario_desafios (id_usuario, id_desafio, fecha_asignado) VALUES (:id_u, :id_d, CURDATE())");
        foreach ($nuevos_ids as $id_desafio) {
            $stmtInsert->execute(['id_u' => $id_usuario, 'id_d' => $id_desafio]);
        }
        
        $stmtCheck->execute(['id_u' => $id_usuario]);
        $desafios_diarios = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) { 
    error_log("Error en menu.php: " . $e->getMessage()); 
    die("Error al cargar los datos de la página."); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Mathsics - Menú Principal</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/global.css">
    
    <style>
        .glass-card { border-top: 4px solid; } /* Specific to menu */
        
        #welcome-wrapper {
            position: fixed; inset: 0; z-index: 10000;
            opacity: 0; pointer-events: none;
            transition: opacity 0.5s ease-in-out;
        }
        #welcome-wrapper.active { opacity: 1; pointer-events: auto; }
        #welcome-wrapper.hiding { opacity: 0; pointer-events: none; }

        #liquid-intro-container {
            position: absolute; inset: 0;
            background-color: #a855f7;
            clip-path: circle(0% at 100% 100%);
            transition: clip-path 1.2s cubic-bezier(0.65, 0, 0.35, 1);
            z-index: 2;
        }
        #liquid-intro-container.reveal { clip-path: circle(150% at 100% 100%); }
        
        #welcome-messages {
            position: absolute; inset: 0; display: flex;
            justify-content: center; align-items: center; text-align: center;
            color: white; z-index: 3; padding: 1rem;
        }
        .welcome-step {
            position: absolute; opacity: 0;
            transition: opacity 0.6s ease;
        }
        .welcome-step.visible { opacity: 1; }

        #tutorial-container {
            position: absolute; inset: 0;
            background-color: rgba(0,0,0,0.7);
            opacity: 0; pointer-events: none;
            transition: opacity 0.5s ease-in-out;
            z-index: 1;
        }
        #tutorial-container.visible { opacity: 1; pointer-events: auto; }
        
        .tutorial-step {
            position: fixed; opacity: 0; transition: opacity 0.6s ease;
            pointer-events: none; z-index: 10002;
        }
        .tutorial-step.visible { opacity: 1; pointer-events: auto; }
        .tutorial-box {
            background: rgba(168, 85, 247, 0.85); backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1rem;
            padding: 1.5rem; color: white; text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .highlight-clone {
            position: absolute; z-index: 10001;
            transition: all 0.5s ease-in-out;
            border-radius: 1rem; pointer-events: none;
        }
        .progress-bar-container { background-color: rgba(255,255,255,0.3); border-radius: 999px; overflow: hidden; }
        .progress-bar { background-color: #facc15; height: 100%; transition: width 0.5s ease-in-out; }
        
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Parallax Background */
        #parallax-bg { position: fixed; top: -5%; left: -5%; width: 110%; height: 110%; z-index: -1; pointer-events: none; overflow: hidden; }
        .math-symbol { position: absolute; color: rgba(59, 130, 246, 0.1); font-weight: 900; user-select: none; transition: transform 0.1s ease-out; }

        /* Hover Glow Effect for Cards */
        .card-3d:hover[data-theme="indigo"] { box-shadow: 0 20px 40px -10px rgba(67, 56, 202, 0.5); border-color: rgba(67, 56, 202, 0.6); }
        .card-3d:hover[data-theme="green"] { box-shadow: 0 20px 40px -10px rgba(4, 120, 87, 0.5); border-color: rgba(4, 120, 87, 0.6); }
        .card-3d:hover[data-theme="sky"] { box-shadow: 0 20px 40px -10px rgba(3, 105, 161, 0.5); border-color: rgba(3, 105, 161, 0.6); }
        .card-3d:hover[data-theme="pink"] { box-shadow: 0 20px 40px -10px rgba(190, 24, 93, 0.5); border-color: rgba(190, 24, 93, 0.6); }
        .card-3d:hover[data-theme="orange"] { box-shadow: 0 20px 40px -10px rgba(194, 65, 12, 0.5); border-color: rgba(194, 65, 12, 0.6); }
        .card-3d:hover[data-theme="blue"] { box-shadow: 0 20px 40px -10px rgba(29, 78, 216, 0.5); border-color: rgba(29, 78, 216, 0.6); }
        .card-3d:hover[data-theme="purple"] { box-shadow: 0 20px 40px -10px rgba(126, 34, 206, 0.5); border-color: rgba(126, 34, 206, 0.6); }

        /* Golden Glow for XP bar */
        .xp-glow { animation: pulse-gold 2s infinite; }
        @keyframes pulse-gold { 0% { box-shadow: 0 0 10px 1px rgba(250, 204, 21, 0.5); } 50% { box-shadow: 0 0 20px 4px rgba(250, 204, 21, 0.9); } 100% { box-shadow: 0 0 10px 1px rgba(250, 204, 21, 0.5); } }
    </style>
</head>
<body class="font-sans text-slate-700 overflow-hidden">

<!-- Parallax Background Container -->
<div id="parallax-bg">
    <!-- Generado vía JS -->
</div>

<div id="welcome-wrapper" role="dialog" aria-modal="true" aria-labelledby="welcome-title">
    <div id="liquid-intro-container">
        <div id="welcome-messages" class="animate-float">
            <div id="welcome-step-1" class="welcome-step">
                <h1 id="welcome-title" class="text-4xl sm:text-5xl font-black">Bienvenido a Mathsics</h1>
                <p class="text-lg mt-4 font-bold opacity-90">Plataforma Educativa Interactiva</p>
                <button id="continue-welcome" class="mt-8 bg-white/20 hover:bg-white/30 font-bold py-3 px-8 rounded-full transition shadow-lg">Comenzar</button>
            </div>
            <div id="welcome-step-2" class="welcome-step">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black">Aprende matemáticas a tu propio ritmo</h2>
            </div>
        </div>
    </div>
    <div id="tutorial-container" class="tutorial-container">
        <div id="highlight-clone" class="highlight-clone"></div>
        <div id="tutorial-step-1" class="tutorial-step w-full px-4" style="top: 25%;">
            <div class="max-w-md mx-auto tutorial-box animate-float" role="dialog" aria-label="Paso 1 del Tutorial">
                <p class="text-xl font-bold">Aquí verás tu nivel y progreso.<br>¡Gana experiencia resolviendo problemas!</p>
                <button class="tutorial-continue mt-4 bg-white/20 hover:bg-white/30 font-bold py-2 px-6 rounded-full transition shadow-md">Continuar</button>
            </div>
        </div>
        <div id="tutorial-step-2" class="tutorial-step w-full px-4" style="top: 50%;">
            <div class="max-w-md mx-auto tutorial-box animate-float" role="dialog" aria-label="Paso 2 del Tutorial">
                <p class="text-xl font-bold">Estos son los módulos de aprendizaje.<br>Cada uno te prepara con IA y desafíos.</p>
                <button class="tutorial-continue mt-4 bg-white/20 hover:bg-white/30 font-bold py-2 px-6 rounded-full transition shadow-md">Entendido</button>
            </div>
        </div>
        <div id="tutorial-step-3" class="tutorial-step w-full px-4" style="top: 35%;">
            <div class="max-w-md mx-auto tutorial-box animate-float" role="dialog" aria-label="Paso Final del Tutorial">
                <h2 class="text-4xl font-black mb-4">¡Tu primera misión!</h2>
                <p class="text-lg font-bold mb-4">Toca la tarjeta de Aritmética para jugar.</p>
                <button id="start-adventure-btn" class="mt-4 bg-green-500 hover:bg-green-600 font-bold py-3 px-8 rounded-full transition shadow-lg text-white">¡Empezar Aventura!</button>
            </div>
        </div>
    </div>
    <audio id="intro-audio" preload="auto"><source src="sonidos/transicion.mp3" type="audio/mpeg"></audio>
    <audio id="click-sound" preload="auto"><source src="sonidos/click.mp3" type="audio/mpeg"></audio>
</div>

<div id="page-transition-overlay"><div id="page-transition-icon" class="icon"></div></div>
<div class="flex h-screen bg-sky-50/50">
    <aside id="sidebar" class="w-72 bg-white/80 backdrop-blur-2xl shadow-[5px_0_30px_rgba(0,0,0,0.05)] border-r border-white/60 flex-col fixed top-0 left-0 h-full z-50 -translate-x-full transition-transform duration-300 ease-in-out md:sticky md:translate-x-0 md:flex overflow-y-auto no-scrollbar">
        <div class="flex items-center justify-center h-24 pt-4 md:h-20 md:pt-0 border-b border-white/40"><a href="menu.php" class="text-3xl font-black text-blue-600 tracking-wide">Mathsics</a></div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="menu.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-100 rounded-xl font-bold"><i class="fas fa-home w-6 text-center" aria-hidden="true"></i><span class="ml-4">Inicio</span></a>
            <a href="foro.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl font-bold"><i class="fas fa-users w-6 text-center text-gray-400" aria-hidden="true"></i><span class="ml-4">Comunidad</span></a>
            <a href="estadisticas.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl font-bold"><i class="fas fa-chart-line w-6 text-center text-gray-400" aria-hidden="true"></i><span class="ml-4">Estadísticas</span></a>
            <a href="notificaciones.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl font-bold relative"><i class="fas fa-bell w-6 text-center text-gray-400" aria-hidden="true"></i><span class="ml-4">Notificaciones</span><?php if ($numNotificaciones > 0): ?><span class="absolute top-2 right-4 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white shadow-md animate-bounce"><?php echo $numNotificaciones; ?></span><?php endif; ?></a>
            <div class="flex items-center px-4 py-3 text-gray-600 rounded-xl font-bold"><i class="fas fa-fire w-6 text-center text-orange-500 text-xl" aria-hidden="true"></i><span class="ml-4">Racha de <span class="text-orange-500"><?php echo $racha; ?></span> días</span></div>
        </nav>
        <div class="px-4 py-6 mt-auto"><a href="ranking.php" class="block px-4 text-sm font-bold text-slate-600 uppercase tracking-wider mb-3 hover:text-blue-600 transition" title="Ver Ranking Completo">🏆 LIGA <span class="<?php echo $miLigaColor; ?>"><i class="fas <?php echo $miLigaIcon; ?> mx-1"></i><?php echo $miLigaNombre; ?></span></a><ul class="space-y-3"><?php foreach ($topUsuarios as $index => $rankedUser): ?><li class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 transition-colors"><span class="font-black text-lg w-6 text-center <?php echo $miLigaColor; ?>">#<?php echo $index + 1; ?></span><a href="usuario.php?id=<?php echo $rankedUser['id_usuario']; ?>" class="flex-shrink-0 cursor-pointer"><img src="<?php echo htmlspecialchars($rankedUser['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); ?>" alt="Avatar de <?php echo htmlspecialchars($rankedUser['nombre']); ?>" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 hover:border-blue-500 transition-colors"/></a><div class="flex-1 truncate"><a href="usuario.php?id=<?php echo $rankedUser['id_usuario']; ?>" class="font-bold text-gray-800 hover:text-blue-600 hover:underline cursor-pointer truncate text-sm block"><?php echo htmlspecialchars($rankedUser['nombre']); ?></a><p class="text-xs font-bold text-gray-700"><?php echo htmlspecialchars($rankedUser['puntos']); ?> Pts</p></div></li><?php endforeach; ?></ul><div id="countdown" class="mt-4 text-center text-xs text-slate-600 font-bold"></div><a href="encuesta.php" class="mt-4 w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold py-2 rounded-xl text-center flex items-center justify-center transition border border-indigo-200 shadow-sm"><i class="fas fa-comment-dots mr-2"></i> Dar sugerencias</a></div>
        <div class="px-4 py-4 border-t border-gray-200"><a href="logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-red-50 hover:text-red-600 rounded-xl font-bold"><i class="fas fa-sign-out-alt w-6 text-center" aria-hidden="true"></i><span class="ml-4">Cerrar Sesión</span></a></div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header id="main-header" class="flex justify-between items-center h-16 md:h-20 px-4 sm:px-6 bg-white/70 backdrop-blur-xl border-b border-white/40 z-10 shadow-sm sticky top-0">
            <!-- Izquierda: Menú Móvil -->
            <div class="flex items-center gap-3">
                <button id="menuToggle" class="md:hidden text-gray-600 hover:text-blue-600 focus:outline-none p-2 -ml-2"><i class="fas fa-bars text-2xl" aria-hidden="true"></i><span class="sr-only">Abrir menú</span></button>
            </div>
            <!-- Derecha: Nivel Desktop y Perfil -->
            <div class="flex items-center space-x-3 sm:space-x-6">
                <div class="hidden md:flex items-center gap-3 bg-white border border-gray-200 pl-2 pr-4 py-2 rounded-full shadow-sm">
                    <a href="ranking.php" class="<?php echo $miLigaColor; ?> flex items-center bg-gray-50 hover:bg-gray-100 px-3 py-1.5 rounded-full font-bold text-xs border border-gray-200 transition leading-none"><i class="fas <?php echo $miLigaIcon; ?> mr-1.5"></i> <?php echo $miLigaNombre; ?></a>
                    <span class="bg-yellow-400 text-yellow-900 px-3 py-1.5 rounded-full font-black uppercase tracking-wider shadow-sm leading-none flex items-center" style="font-size: 10px;">Nivel <?php echo $nivel; ?></span>
                    <div class="relative w-32 h-3 bg-gray-200 rounded-full shadow-inner overflow-hidden border border-gray-300/50 flex items-center"><div id="expBarDesktop" class="absolute left-0 top-0 h-full bg-yellow-400 rounded-full transition-all" style="width: <?php echo ($limite > 0 ? $exp / $limite * 100 : 0); ?>%"></div></div>
                    <span id="expTextDesktop" class="font-semibold text-gray-500 whitespace-nowrap leading-none flex items-center" style="font-size: 11px;"><?php echo $exp; ?> / <?php echo $limite; ?> XP</span>
                </div>
                <div class="relative">
                    <button id="profileButton" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Menú de perfil"><img src="<?php echo $foto; ?>" alt="Foto de perfil" class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm"/></button>
                    <div id="profileDropdown" role="menu" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-20 hidden origin-top-right"><div class="py-1"><a href="configuracion.php" role="menuitem" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-bold"><i class="fas fa-cog w-5 mr-2 text-gray-400" aria-hidden="true"></i>Configuración</a><a href="logout.php" role="menuitem" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold"><i class="fas fa-sign-out-alt w-5 mr-2" aria-hidden="true"></i>Cerrar Sesión</a></div></div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">

            <!-- Hero Banner Orgánico (Sin contenedor) -->
            <div class="py-4 sm:py-8 mb-8 flex flex-col md:flex-row items-center justify-between gap-6 relative" data-aos="fade-up">
                <div class="flex-1 z-10">
                    <h2 class="text-3xl sm:text-5xl font-black text-slate-800 mb-3 drop-shadow-sm leading-snug">¡Bienvenido de nuevo, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600"><?php echo $nombre_usuario; ?></span>!</h2>
                    <p class="text-slate-600 text-lg font-bold mb-6">¿Listo para subir de nivel hoy? Tienes una racha de <span class="text-orange-500 font-black"><?php echo $racha; ?> días</span>.</p>
                    
                    <!-- Mobile XP Widget (Movido desde el header) -->
                    <div class="md:hidden flex flex-col gap-3 mb-8 p-5 bg-white/60 border border-white/80 rounded-2xl shadow-sm backdrop-blur-sm">
                        <div class="flex justify-between items-center">
                            <span class="bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full font-black uppercase tracking-wider shadow-sm text-xs">Nivel <?php echo $nivel; ?></span>
                            <span id="expTextMobile" class="font-bold text-slate-500 text-xs"><?php echo $exp; ?> / <?php echo $limite; ?> XP</span>
                        </div>
                        <div class="relative w-full h-3 bg-gray-200 rounded-full shadow-inner overflow-hidden border border-gray-300/50"><div id="expBarMobile" class="absolute left-0 top-0 h-full bg-yellow-400 rounded-full transition-all" style="width: <?php echo ($limite > 0 ? $exp / $limite * 100 : 0); ?>%"></div></div>
                    </div>

                    <a href="#lessons-container" onclick="document.getElementById('lessons-container').scrollIntoView({behavior: 'smooth'}); return false;" class="inline-flex items-center justify-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-lg py-4 px-8 rounded-full shadow-[0_10px_25px_rgba(59,130,246,0.4)] transition-all hover:-translate-y-1 hover:scale-105">
                        <i class="fas fa-play mr-2"></i> Continuar Jugando
                    </a>
                </div>
                <div class="hidden md:flex w-40 h-40 items-center justify-center relative z-10">
                    <div class="absolute inset-0 bg-blue-400/20 rounded-full blur-2xl animate-pulse"></div>
                    <i class="fas fa-rocket text-7xl text-blue-500 animate-float drop-shadow-lg"></i>
                </div>
            </div>

            <div class="mb-8" data-aos="fade-up">
                <h2 class="text-2xl font-black text-slate-800 mb-3">Desafíos Diarios</h2>
                <div class="flex space-x-4 overflow-x-auto pb-4 no-scrollbar">
                    <?php 
                        $textColors = [
                            "fa-calculator" => "text-indigo-600",
                            "fa-square-root-variable" => "text-emerald-600",
                            "fa-bullseye" => "text-cyan-600",
                            "fa-fire" => "text-orange-600",
                            "fa-swords" => "text-yellow-600",
                            "fa-trophy" => "text-yellow-600",
                            "fa-bolt" => "text-yellow-500",
                        ];
                        $bgColors = [
                            "fa-calculator" => "bg-indigo-100",
                            "fa-square-root-variable" => "bg-emerald-100",
                            "fa-bullseye" => "bg-cyan-100",
                            "fa-fire" => "bg-orange-100",
                            "fa-swords" => "bg-yellow-100",
                            "fa-trophy" => "bg-yellow-100",
                            "fa-bolt" => "bg-yellow-50",
                        ];
                    ?>
                    <?php foreach ($desafios_diarios as $desafio): 
                        $progreso = min($desafio['progreso'], $desafio['objetivo_cantidad']);
                        $porcentaje = ($desafio['objetivo_cantidad'] > 0) ? ($progreso / $desafio['objetivo_cantidad']) * 100 : 0;
                        $textColor = $textColors[$desafio['icono']] ?? 'text-blue-600';
                        $bgColor = $bgColors[$desafio['icono']] ?? 'bg-blue-100';
                    ?>
                    <div class="flex-shrink-0 w-80 bg-white/60 backdrop-blur-md text-slate-800 rounded-3xl shadow-sm hover:shadow-lg border border-white/80 p-6 flex flex-col transition-all hover:-translate-y-1">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="<?php echo $bgColor; ?> rounded-2xl p-3 w-12 h-12 flex items-center justify-center"><i class="fas <?php echo $desafio['icono']; ?> text-2xl <?php echo $textColor; ?>"></i></div>
                            <h3 class="font-black text-lg flex-1 text-slate-800 leading-tight"><?php echo htmlspecialchars($desafio['titulo']); ?></h3>
                        </div>
                        <p class="text-sm text-slate-500 font-bold mt-2 flex-grow"><?php echo htmlspecialchars($desafio['descripcion']); ?></p>
                        <div class="mt-4">
                            <div class="progress-bar-container w-full h-3"><div class="progress-bar" style="width: <?php echo $porcentaje; ?>%;"></div></div>
                            <p class="text-right text-xs font-bold text-slate-500 mt-1"><?php echo $progreso; ?> / <?php echo $desafio['objetivo_cantidad']; ?></p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <?php if ($desafio['estado'] === 'completado'): ?>
                                <button data-id="<?php echo $desafio['id']; ?>" class="reclamar-btn w-full bg-yellow-400 hover:bg-yellow-500 text-yellow-900 font-bold py-3 px-4 rounded-xl transition text-base flex items-center justify-center gap-2">
                                    <i class="fas fa-gift"></i><span>Reclamar +<?php echo $desafio['recompensa_xp']; ?> XP</span>
                                </button>
                            <?php elseif ($desafio['estado'] === 'reclamado'): ?>
                                <span class="w-full block text-center bg-green-500 text-white font-bold py-3 px-4 rounded-xl text-base"><i class="fas fa-check mr-2"></i>¡Reclamado!</span>
                            <?php else: ?>
                                <span class="w-full block text-center bg-slate-100 border-2 border-slate-200 text-slate-500 font-bold py-3 px-4 rounded-xl text-base shadow-inner">En progreso</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h2 class="text-3xl font-black text-slate-800">¡Elige una Aventura!</h2>
            <p class="text-slate-600 font-bold mb-6 -mt-1">Selecciona un tema para empezar a practicar.</p>
            <div id="lessons-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-8 perspective-container">
                <?php 
                  $menuItems = [ 
                      ["title" => "Aritmética", "icon" => "fa-calculator", "color" => "indigo", "desc" => "Domina sumas, restas, multiplicaciones y divisiones. ¡Los fundamentos!", "tema" => "Aritmética"], 
                      ["title" => "Álgebra", "icon" => "fa-square-root-variable", "color" => "green", "desc" => "Explora el mundo de las variables, ecuaciones y expresiones.", "tema" => "Álgebra"], 
                      ["title" => "Geometría", "icon" => "fa-ruler-combined", "color" => "sky", "desc" => "Calcula áreas, perímetros, volúmenes y propiedades de figuras.", "tema" => "Geometría"], 
                      ["title" => "Estadística", "icon" => "fa-chart-pie", "color" => "pink", "desc" => "Analiza datos, promedios, desviaciones y entiende la probabilidad.", "tema" => "Estadística"], 
                      ["title" => "Duelo Matemático", "icon" => "fa-trophy", "color" => "orange", "desc" => "Compite contra otros jugadores en tiempo real y demuestra tu destreza.", "link" => "menu_duelo.php"], 
                      ["title" => "Juegos de Scratch", "icon" => "fa-gamepad", "color" => "blue", "desc" => "Descubre y comparte divertidos juegos educativos hechos por la comunidad.", "link" => "scratch.php"], 
                      ["title" => "Modo Desafío", "icon" => "fa-stopwatch-20", "color" => "purple", "desc" => "Modo supervivencia infinito. ¡Multiplica tu XP respondiendo preguntas cada vez más difíciles!", "link" => "desafio_config.php"]
                  ];
                  $colors = [ 
                      "indigo" => ["text" => "text-indigo-800", "bg_icon" => "bg-indigo-100", "text_icon" => "text-indigo-600", "border" => "border-indigo-500", "btn" => "bg-gradient-to-r from-indigo-500 to-indigo-600 text-white", "hex" => "#4338ca"], 
                      "green"  => ["text" => "text-emerald-800", "bg_icon" => "bg-emerald-100", "text_icon" => "text-emerald-600", "border" => "border-emerald-500", "btn" => "bg-gradient-to-r from-emerald-500 to-emerald-600 text-white", "hex" => "#047857"], 
                      "sky"    => ["text" => "text-sky-800", "bg_icon" => "bg-sky-100", "text_icon" => "text-sky-600", "border" => "border-sky-500", "btn" => "bg-gradient-to-r from-sky-500 to-sky-600 text-white", "hex" => "#0369a1"], 
                      "pink"   => ["text" => "text-pink-800", "bg_icon" => "bg-pink-100", "text_icon" => "text-pink-600", "border" => "border-pink-500", "btn" => "bg-gradient-to-r from-pink-500 to-pink-600 text-white", "hex" => "#be185d"], 
                      "orange" => ["text" => "text-orange-800", "bg_icon" => "bg-orange-100", "text_icon" => "text-orange-600", "border" => "border-orange-500", "btn" => "bg-gradient-to-r from-orange-500 to-orange-600 text-white", "hex" => "#c2410c"], 
                      "blue"   => ["text" => "text-blue-800", "bg_icon" => "bg-blue-100", "text_icon" => "text-blue-600", "border" => "border-blue-500", "btn" => "bg-gradient-to-r from-blue-500 to-blue-600 text-white", "hex" => "#1d4ed8"], 
                      "purple" => ["text" => "text-purple-800", "bg_icon" => "bg-purple-100", "text_icon" => "text-purple-600", "border" => "border-purple-500", "btn" => "bg-gradient-to-r from-purple-500 to-purple-600 text-white", "hex" => "#7e22ce"], 
                  ];
                ?>
                <?php foreach ($menuItems as $index => $item):
                    $animationDelay = $index * 100;
                    $colorTheme = $colors[$item['color']] ?? $colors['indigo'];
                    $cardId = (isset($item['tema']) && $item['tema'] === 'Aritmética') ? 'id="aritmetica-card"' : '';
                ?>
                <div <?php echo $cardId; ?>>
                    <form method="POST" action="<?php echo htmlspecialchars($item['link'] ?? 'menu.php', ENT_QUOTES, 'UTF-8'); ?>" class="theme-form h-full" data-color-hex="<?php echo $colorTheme['hex']; ?>" data-icon-class="fa-solid <?php echo $item['icon']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <?php if (isset($item['tema'])): ?><input type="hidden" name="tema" value="<?php echo $item['tema']; ?>"><?php endif; ?>
                        <button type="submit" <?php echo (isset($item['disabled']) && $item['disabled']) ? 'disabled' : ''; ?> data-theme="<?php echo $item['color']; ?>" class="card-3d w-full h-full text-left p-6 rounded-3xl flex flex-col disabled:bg-gray-300 disabled:border-gray-400 disabled:cursor-not-allowed glass-card <?php echo $colorTheme['border']; ?>">
                            <div class="w-16 h-16 rounded-full <?php echo $colorTheme['bg_icon']; ?> flex items-center justify-center mb-6 shadow-inner">
                                <i class="card-icon fa-solid <?php echo $item['icon']; ?> text-3xl <?php echo $colorTheme['text_icon']; ?>" aria-hidden="true"></i>
                            </div>
                            <h3 class="text-xl font-black mb-2 <?php echo $colorTheme['text']; ?>"><?php echo $item['title']; ?></h3>
                            <p class="text-sm font-bold text-slate-600 mb-6 flex-1"><?php echo $item['desc'] ?? ''; ?></p>
                            <?php if (isset($item['disabled']) && $item['disabled']): ?>
                                <span class="w-full text-center font-bold py-3 px-5 rounded-xl bg-gray-200 text-gray-500 shadow-inner mt-auto"><i class="fa-solid fa-lock mr-2" aria-hidden="true"></i> Próximamente</span>
                            <?php else: ?>
                                <span class="w-full text-center font-black py-3 px-5 rounded-xl shadow-md transition-shadow hover:shadow-lg <?php echo $colorTheme['btn']; ?> mt-auto"><?php echo isset($item['link']) ? 'Entrar' : 'Comenzar'; ?></span>
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>
<div id="backdrop" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm md:hidden hidden z-40 transition-opacity"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- LÓGICA DEL TUTORIAL DE BIENVENIDA ---
        const welcomeWrapper = document.getElementById('welcome-wrapper');
        if (welcomeWrapper) {
            const liquidIntro = document.getElementById('liquid-intro-container');
            const welcomeStep1 = document.getElementById('welcome-step-1');
            const welcomeStep2 = document.getElementById('welcome-step-2');
            const continueWelcomeBtn = document.getElementById('continue-welcome');
            const tutorialContainer = document.getElementById('tutorial-container');
            const highlightClone = document.getElementById('highlight-clone');
            const introAudio = document.getElementById('intro-audio');
            const introKey = 'hasSeenFullWelcomeV11';
            
            const tutorialSteps = [
                { step: document.getElementById('tutorial-step-1'), target: window.innerWidth < 768 ? document.getElementById('xp-bar-mobile-container') : document.getElementById('main-header') },
                { step: document.getElementById('tutorial-step-2'), target: document.getElementById('lessons-container') },
                { step: document.getElementById('tutorial-step-3'), target: document.getElementById('aritmetica-card') }
            ];
            let currentTutorialStep = 0;
            
            function vibrateDevice(pattern) {
                if ('vibrate' in navigator) {
                    try { navigator.vibrate(pattern); } 
                    catch (e) { console.warn("Vibration failed:", e); }
                }
            }

            function endFullWelcome() {
                if (introAudio) { introAudio.pause(); introAudio.currentTime = 0; }
                vibrateDevice(0);
                welcomeWrapper.classList.add('hiding');
                welcomeWrapper.addEventListener('transitionend', () => {
                    welcomeWrapper.style.display = 'none';
                }, { once: true });
            }

            function runInteractiveTutorial() {
                tutorialContainer.classList.add('visible');
                showTutorialStep(0);
            }

            function showTutorialStep(index) {
                tutorialSteps.forEach(s => s.step.classList.remove('visible'));
                const current = tutorialSteps[index];
                if (current && current.target) {
                    const rect = current.target.getBoundingClientRect();
                    highlightClone.innerHTML = current.target.innerHTML;
                    highlightClone.className = current.target.className.replace('animate-fade-in-up', '') + ' highlight-clone';
                    highlightClone.style.width = `${rect.width}px`;
                    highlightClone.style.height = `${rect.height}px`;
                    highlightClone.style.top = `${rect.top}px`;
                    highlightClone.style.left = `${rect.left}px`;
                    current.step.classList.add('visible');
                    
                    const stepBtn = current.step.querySelector('.tutorial-continue');
                    if (stepBtn) stepBtn.focus();
                }
            }

            function runWelcomeSequence() {
                welcomeWrapper.classList.add('active');
                liquidIntro.classList.add('reveal');
                const liquidVibrationPattern = [100, 50, 100, 50, 200, 50, 300];
                vibrateDevice(liquidVibrationPattern);
                if (introAudio) { introAudio.play().catch(e => console.warn("Audio bloqueado.")); }
                localStorage.setItem(introKey, 'true');

                liquidIntro.addEventListener('transitionend', () => {
                    if (liquidIntro.classList.contains('reveal')) {
                        welcomeStep1.classList.add('visible');
                        continueWelcomeBtn.focus();
                    }
                }, { once: true });
            }

            continueWelcomeBtn.addEventListener('click', () => {
                vibrateDevice(50);
                welcomeStep1.classList.remove('visible');
                setTimeout(() => {
                    welcomeStep2.classList.add('visible');
                    setTimeout(() => {
                        liquidIntro.classList.remove('reveal');
                        liquidIntro.addEventListener('transitionend', runInteractiveTutorial, { once: true });
                    }, 4000);
                }, 600);
            });
            
            document.querySelectorAll('.tutorial-continue').forEach(button => {
                button.addEventListener('click', () => {
                    vibrateDevice(50);
                    currentTutorialStep++;
                    if (currentTutorialStep < tutorialSteps.length) {
                        showTutorialStep(currentTutorialStep);
                        if (currentTutorialStep === tutorialSteps.length - 1) {
                            const btn = document.getElementById('start-adventure-btn');
                            if (btn) btn.focus();
                        }
                    }
                });
            });

            const startAdventureBtn = document.getElementById('start-adventure-btn');
            if (startAdventureBtn) {
                startAdventureBtn.addEventListener('click', () => {
                    vibrateDevice(50);
                    endFullWelcome();
                    const aritmeticaCard = document.querySelector('#aritmetica-card button[type="submit"]');
                    if (aritmeticaCard) {
                        setTimeout(() => aritmeticaCard.click(), 500); // Dar tiempo a que el tutorial se cierre visualmente
                    }
                });
            }
            
            const isFirstVisitOnMobile = window.innerWidth < 768 && !localStorage.getItem(introKey);
            if (isFirstVisitOnMobile) {
                setTimeout(runWelcomeSequence, 200);
            } else {
                welcomeWrapper.style.display = 'none';
            }
        }

        // --- LÓGICA GENERAL DE LA PÁGINA ---
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('backdrop');
        menuToggle?.addEventListener('click', () => { backdrop.classList.remove('hidden'); sidebar.classList.remove('-translate-x-full'); });
        backdrop?.addEventListener('click', () => { sidebar.classList.add('-translate-x-full'); backdrop.classList.add('hidden'); });

        const countdownElement = document.getElementById('countdown');
        if (countdownElement) {
            const updateCountdown = () => {
                const now = new Date(); const nextMonday = new Date(now); nextMonday.setHours(0, 0, 0, 0);
                const dayOfWeek = now.getDay(); const daysUntilMonday = (dayOfWeek === 0) ? 1 : (8 - dayOfWeek);
                nextMonday.setDate(now.getDate() + daysUntilMonday);
                const timeLeft = nextMonday.getTime() - now.getTime();
                if (timeLeft < 0) { countdownElement.innerHTML = "Ranking reiniciado."; return; }
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24)); const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                countdownElement.innerHTML = `Reinicia en: <b>${days}d ${hours}h ${minutes}m</b>`;
            };
            setInterval(updateCountdown, 1000); updateCountdown();
        }

        const profileButton = document.getElementById('profileButton');
        const profileDropdown = document.getElementById('profileDropdown');
        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (event) => {
                event.stopPropagation();
                const isHidden = profileDropdown.classList.toggle('hidden');
                profileButton.setAttribute('aria-expanded', !isHidden);
            });
            window.addEventListener('click', (event) => {
                if (!profileDropdown.classList.contains('hidden') && !profileButton.contains(event.target)) {
                    profileDropdown.classList.add('hidden');
                    profileButton.setAttribute('aria-expanded', 'false');
                }
            });
        }

        const themeForms = document.querySelectorAll('.theme-form');
        const transitionOverlay = document.getElementById('page-transition-overlay');
        const transitionIcon = document.getElementById('page-transition-icon');
        const clickSound = document.getElementById('click-sound');

        themeForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                clickSound?.play().catch(e => console.warn("Click sound failed"));
                if ('vibrate' in navigator) { navigator.vibrate(50); }
                
                const color = this.dataset.colorHex || '#6366f1';
                const iconClass = this.dataset.iconClass || 'fa-solid fa-atom';
                if (transitionOverlay && transitionIcon) {
                    transitionOverlay.style.backgroundColor = color;
                    transitionIcon.className = `icon ${iconClass} text-white text-8xl sm:text-9xl`;
                    transitionOverlay.classList.add('active');
                    setTimeout(() => { form.submit(); }, 700);
                } else {
                    form.submit();
                }
            });
        });

        // --- LÓGICA PARA RECLAMAR DESAFÍOS ---
        document.querySelectorAll('.reclamar-btn').forEach(button => {
            button.addEventListener('click', function() {
                const desafioId = this.dataset.id;
                const button = this;
                const originalText = button.innerHTML;

                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Reclamando...';

                const formData = new FormData();
                formData.append('id_usuario_desafio', desafioId);

                fetch('reclamar_desafio.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const parent = button.parentElement;
                        button.remove();
                        parent.innerHTML += '<span class="w-full block text-center bg-green-500 text-white font-bold py-3 px-4 rounded-xl text-base"><i class="fas fa-check mr-2"></i>¡Reclamado!</span>';
                        
                        // Opcional: Notificar de forma más elegante que un alert
                        alert(`¡Has ganado ${data.recompensa_xp} XP!`);

                        // Actualizar la UI sin recargar (necesitas que tu PHP devuelva estos datos)
                        if(data.nuevo_xp !== undefined) {
                            const { nuevo_xp, nuevo_nivel, nuevo_limite_xp } = data;
                            const porcentajeXp = (nuevo_limite_xp > 0 ? nuevo_xp / nuevo_limite_xp * 100 : 0);

                            document.querySelectorAll('.bg-blue-500.text-white.px-3.py-1').forEach(el => el.textContent = `Nivel ${nuevo_nivel}`);
                            
                            const expBarDesktop = document.getElementById('expBarDesktop');
                            if(expBarDesktop) expBarDesktop.style.width = porcentajeXp + '%';
                            const expTextDesktop = document.getElementById('expTextDesktop');
                            if(expTextDesktop) expTextDesktop.textContent = `${nuevo_xp} / ${nuevo_limite_xp} XP`;
                            
                            const expBarMobile = document.getElementById('expBarMobile');
                            if(expBarMobile) expBarMobile.style.width = porcentajeXp + '%';
                            const expTextMobile = document.getElementById('expTextMobile');
                            if(expTextMobile) expTextMobile.textContent = `${nuevo_xp} / ${nuevo_limite_xp} XP`;
                        }

                    } else {
                        alert('Error: ' + data.message);
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error de conexión.');
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        });
        // --- LÓGICA DE PARALLAX MATEMÁTICO ---
        const parallaxBg = document.getElementById('parallax-bg');
        if (parallaxBg && window.innerWidth > 768) {
            const symbols = ['π', '∑', '∫', '∆', '√', '∞', 'µ', 'θ', '≈', '≠', '±', 'α', 'β'];
            const numElements = 25;
            
            for(let i=0; i<numElements; i++) {
                const el = document.createElement('div');
                el.className = 'math-symbol';
                el.innerText = symbols[Math.floor(Math.random() * symbols.length)];
                
                const x = Math.random() * 100;
                const y = Math.random() * 100;
                el.style.left = `${x}%`;
                el.style.top = `${y}%`;
                
                const size = Math.random() * 2 + 1; 
                el.style.fontSize = `${size}rem`;
                
                const rot = Math.random() * 360;
                el.dataset.rot = rot;
                el.style.transform = `rotate(${rot}deg)`;
                
                el.dataset.depth = Math.random() * 0.5 + 0.1;
                parallaxBg.appendChild(el);
            }
            
            document.addEventListener('mousemove', (e) => {
                const mouseX = (e.clientX - window.innerWidth / 2) / 100;
                const mouseY = (e.clientY - window.innerHeight / 2) / 100;
                
                const elements = parallaxBg.querySelectorAll('.math-symbol');
                elements.forEach(el => {
                    const depth = el.dataset.depth;
                    const moveX = mouseX * depth * -10; 
                    const moveY = mouseY * depth * -10;
                    el.style.transform = `translate(${moveX}px, ${moveY}px) rotate(${el.dataset.rot}deg)`;
                });
            });
        }
    });
</script>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 50 });
</script>
    <script src="toast_notifications.js"></script>
</body>
</html>
