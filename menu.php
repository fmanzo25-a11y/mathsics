<?php
// BLOQUE PHP PARA OBTENER DATOS DEL USUARIO Y LA SESIÓN
include_once 'conexion.php';
session_start();

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}
$id_usuario = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tema'])) { 
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
    $foto = htmlspecialchars($user['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); 
    $nombre_usuario = htmlspecialchars($user['nombre'] ?? 'Usuario'); 
    $racha = (int)($user['racha'] ?? 0);
    
    $stmtRanking = $conn->prepare("SELECT u.nombre, u.foto_de_perfil, r.puntos FROM usuarios u JOIN ranking r ON u.id = r.id_usuario ORDER BY r.puntos DESC LIMIT 3");
    $stmtRanking->execute(); 
    $topUsuarios = $stmtRanking->fetchAll(PDO::FETCH_ASSOC);
    
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
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Mathsics - Menú Principal</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f0f9ff; background-image: linear-gradient(to top, #e0f2fe, #f0f9ff); }
        @keyframes fade-in-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in-up { animation: fade-in-up 0.6s ease-out forwards; }
        .dropdown-menu { transition: opacity 0.2s ease-out, transform 0.2s ease-out; }
        .perspective-container { perspective: 1000px; }
        .card-3d { transform-style: preserve-3d; transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), box-shadow 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .card-3d:hover { transform: translateY(-10px) translateZ(30px) rotateX(15deg); box-shadow: 0 30px 50px -20px rgba(0, 0, 0, 0.3); }
        .card-3d .card-icon { transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .card-3d:hover .card-icon { transform: rotate(15deg) translateX(10px); }
        #page-transition-overlay { position: fixed; inset: 0; z-index: 9999; opacity: 0; pointer-events: none; display: flex; align-items: center; justify-content: center; transition: opacity 0.4s ease-in-out; }
        #page-transition-overlay.active { opacity: 1; pointer-events: auto; }
        #page-transition-overlay .icon { transform: scale(0.5); opacity: 0; transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease-in-out; }
        #page-transition-overlay.active .icon { transform: scale(1); opacity: 1; }

        @keyframes float-effect { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-15px); } }
        .animate-float { animation: float-effect 4s ease-in-out infinite; }

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
    </style>
</head>
<body class="font-sans text-slate-700">

<div id="welcome-wrapper">
    <div id="liquid-intro-container">
        <div id="welcome-messages" class="animate-float">
            <div id="welcome-step-1" class="welcome-step">
                <h1 class="text-4xl sm:text-5xl font-black">Bienvenido a Mathsics ahora en celular</h1>
                <button id="continue-welcome" class="mt-8 bg-white/20 hover:bg-white/30 font-bold py-3 px-8 rounded-full transition">Continuar</button>
            </div>
            <div id="welcome-step-2" class="welcome-step">
                <h1 class="text-4xl sm:text-5xl font-black">Misma capacidad, distinta plataforma</h1>
            </div>
        </div>
    </div>
    <div id="tutorial-container" class="tutorial-container">
        <div id="highlight-clone" class="highlight-clone"></div>
        <div id="tutorial-step-1" class="tutorial-step w-full px-4" style="top: 25%;">
            <div class="max-w-md mx-auto tutorial-box animate-float">
                <p class="text-xl font-bold">¡Hola! Aquí arriba verás tu nivel y progreso.</p>
                <button class="tutorial-continue mt-4 bg-white/20 hover:bg-white/30 font-bold py-2 px-6 rounded-full transition">Continuar</button>
            </div>
        </div>
        <div id="tutorial-step-2" class="tutorial-step w-full px-4" style="top: 50%;">
            <div class="max-w-md mx-auto tutorial-box animate-float">
                <p class="text-xl font-bold">Estas son tus aventuras. Cada tarjeta es un tema.</p>
                <button class="tutorial-continue mt-4 bg-white/20 hover:bg-white/30 font-bold py-2 px-6 rounded-full transition">Entendido</button>
            </div>
        </div>
        <div id="tutorial-step-3" class="tutorial-step w-full px-4" style="top: 35%;">
            <div class="max-w-md mx-auto tutorial-box animate-float">
                <h2 class="text-4xl font-black">¡Haz tu primer lección!</h2>
            </div>
        </div>
    </div>
    <audio id="intro-audio" preload="auto"><source src="sonidos/transicion.mp3" type="audio/mpeg"></audio>
    <audio id="click-sound" preload="auto"><source src="sonidos/clic_inicio.mp3" type="audio/mpeg"></audio>
</div>

<div id="page-transition-overlay"><div id="page-transition-icon" class="icon"></div></div>
<div class="flex h-screen bg-sky-50/50">
    <aside id="sidebar" class="w-72 bg-white border-r border-gray-200 flex-col fixed top-0 left-0 h-full z-50 -translate-x-full transition-transform duration-300 ease-in-out md:sticky md:translate-x-0 md:flex">
        <div class="flex items-center justify-center h-20 border-b border-gray-200"><a href="menu.php" class="text-3xl font-black text-blue-600 tracking-wide">Mathsics</a></div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="menu.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-100 rounded-xl font-bold"><i class="fas fa-home w-6 text-center"></i><span class="ml-4">Inicio</span></a>
            <a href="foro.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl font-bold"><i class="fas fa-users w-6 text-center text-gray-400"></i><span class="ml-4">Comunidad</span></a>
            <a href="estadisticas.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl font-bold"><i class="fas fa-chart-line w-6 text-center text-gray-400"></i><span class="ml-4">Estadísticas</span></a>
            <a href="notificaciones.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 hover:text-gray-900 rounded-xl font-bold relative"><i class="fas fa-bell w-6 text-center text-gray-400"></i><span class="ml-4">Notificaciones</span><?php if ($numNotificaciones > 0): ?><span class="absolute top-2 right-4 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white shadow-md animate-bounce"><?php echo $numNotificaciones; ?></span><?php endif; ?></a>
            <div class="flex items-center px-4 py-3 text-gray-600 rounded-xl font-bold"><i class="fas fa-fire w-6 text-center text-orange-500 text-xl"></i><span class="ml-4">Racha de <span class="text-orange-500"><?php echo $racha; ?></span> días</span></div>
        </nav>
        <div class="px-4 py-6 mt-auto"><h3 class="px-4 text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">🏆 Ranking Semanal</h3><ul class="space-y-3"><?php foreach ($topUsuarios as $index => $rankedUser): ?><li class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100"><span class="font-black text-lg w-6 text-center text-amber-500">#<?php echo $index + 1; ?></span><img src="<?php echo htmlspecialchars($rankedUser['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200"/><div class="flex-1 truncate"><p class="font-bold text-gray-800 truncate text-sm"><?php echo htmlspecialchars($rankedUser['nombre']); ?></p><p class="text-xs font-bold text-gray-500"><?php echo htmlspecialchars($rankedUser['puntos']); ?> Pts</p></div></li><?php endforeach; ?></ul><div id="countdown" class="mt-4 text-center text-xs text-gray-500 font-bold"></div></div>
        <div class="px-4 py-4 border-t border-gray-200"><a href="logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-red-50 hover:text-red-600 rounded-xl font-bold"><i class="fas fa-sign-out-alt w-6 text-center"></i><span class="ml-4">Cerrar Sesión</span></a></div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header id="main-header" class="flex flex-wrap justify-between items-center p-4 sm:p-6 bg-white/80 backdrop-blur-sm border-b border-gray-200 z-10 gap-4">
            <div class="flex items-center"><button id="menuToggle" class="md:hidden mr-4 text-gray-600 hover:text-blue-600"><i class="fas fa-bars text-2xl" aria-hidden="true"></i><span class="sr-only">Abrir menú</span></button><h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-800 truncate">¡Hola, <span class="text-blue-600"><?php echo $nombre_usuario; ?></span>!</h1></div>
            <div class="flex items-center space-x-3 sm:space-x-6">
                <div class="hidden md:flex items-center space-x-3 bg-white border border-gray-200 px-4 py-2 rounded-full shadow-sm">
                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full font-bold text-xs shadow-md">Nivel <?php echo $nivel; ?></span>
                    <div class="relative w-32 md:w-40 h-4 bg-gray-200 rounded-full"><div id="expBarDesktop" class="h-full bg-yellow-400 rounded-full transition-all" style="width: <?php echo ($limite > 0 ? $exp / $limite * 100 : 0); ?>%"></div></div>
                    <span id="expTextDesktop" class="text-xs font-bold text-gray-500"><?php echo $exp; ?> / <?php echo $limite; ?> XP</span>
                </div>
                <div class="relative">
                    <button id="profileButton" type="button"><img src="<?php echo $foto; ?>" alt="Foto de perfil" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover ring-4 ring-white"/></button>
                    <div id="profileDropdown" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-20 hidden origin-top-right"><div class="py-1"><a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-bold"><i class="fas fa-cog w-5 mr-2 text-gray-400"></i>Configuración</a><a href="logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-bold"><i class="fas fa-sign-out-alt w-5 mr-2"></i>Cerrar Sesión</a></div></div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div id="xp-bar-mobile-container" class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200 animate-fade-in-up md:hidden">
                <div class="flex justify-between items-center mb-2"><span class="bg-blue-500 text-white px-3 py-1 rounded-full font-bold text-xs shadow-md">Nivel <?php echo $nivel; ?></span><span id="expTextMobile" class="text-xs font-bold text-gray-500"><?php echo $exp; ?> / <?php echo $limite; ?> XP</span></div>
                <div class="relative w-full h-4 bg-gray-200 rounded-full"><div id="expBarMobile" class="h-full bg-yellow-400 rounded-full transition-all" style="width: <?php echo ($limite > 0 ? $exp / $limite * 100 : 0); ?>%"></div></div>
            </div>

            <div class="mb-8 animate-fade-in-up" style="animation-delay: 100ms;">
                <h2 class="text-2xl font-black text-slate-800 mb-3">Desafíos Diarios</h2>
                <div class="flex space-x-4 overflow-x-auto pb-4 no-scrollbar">
                    <?php 
                        $gradientColors = [
                            "fa-calculator" => "from-indigo-500 to-purple-600",
                            "fa-square-root-variable" => "from-green-500 to-emerald-600",
                            "fa-bullseye" => "from-blue-500 to-cyan-600",
                            "fa-fire" => "from-red-500 to-orange-600",
                            "fa-swords" => "from-amber-500 to-yellow-600",
                        ];
                    ?>
                    <?php foreach ($desafios_diarios as $desafio): 
                        $progreso = min($desafio['progreso'], $desafio['objetivo_cantidad']);
                        $porcentaje = ($desafio['objetivo_cantidad'] > 0) ? ($progreso / $desafio['objetivo_cantidad']) * 100 : 0;
                        $gradient = $gradientColors[$desafio['icono']] ?? 'from-gray-500 to-gray-600';
                    ?>
                    <div class="flex-shrink-0 w-80 bg-gradient-to-br <?php echo $gradient; ?> text-white rounded-2xl shadow-lg p-5 flex flex-col">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 rounded-lg p-2 w-10 h-10 flex items-center justify-center"><i class="fas <?php echo $desafio['icono']; ?> text-xl"></i></div>
                            <h3 class="font-black text-lg flex-1"><?php echo htmlspecialchars($desafio['titulo']); ?></h3>
                        </div>
                        <p class="text-sm text-white/80 mt-2 flex-grow"><?php echo htmlspecialchars($desafio['descripcion']); ?></p>
                        <div class="mt-4">
                            <div class="progress-bar-container w-full h-3"><div class="progress-bar" style="width: <?php echo $porcentaje; ?>%;"></div></div>
                            <p class="text-right text-xs font-bold text-white/70 mt-1"><?php echo $progreso; ?> / <?php echo $desafio['objetivo_cantidad']; ?></p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/20">
                            <?php if ($desafio['estado'] === 'completado'): ?>
                                <button data-id="<?php echo $desafio['id']; ?>" class="reclamar-btn w-full bg-yellow-400 hover:bg-yellow-500 text-yellow-900 font-bold py-3 px-4 rounded-xl transition text-base flex items-center justify-center gap-2">
                                    <i class="fas fa-gift"></i><span>Reclamar +<?php echo $desafio['recompensa_xp']; ?> XP</span>
                                </button>
                            <?php elseif ($desafio['estado'] === 'reclamado'): ?>
                                <span class="w-full block text-center bg-green-500 text-white font-bold py-3 px-4 rounded-xl text-base"><i class="fas fa-check mr-2"></i>¡Reclamado!</span>
                            <?php else: ?>
                                <span class="w-full block text-center bg-transparent border-2 border-white/30 text-white/80 font-bold py-3 px-4 rounded-xl text-base">En progreso</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h2 class="text-3xl font-black text-slate-800">¡Elige una Aventura!</h2>
            <p class="text-gray-600 mb-6 -mt-1">Selecciona un tema para empezar a practicar.</p>
            <div id="lessons-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-8 perspective-container">
                <?php 
                  $menuItems = [ ["title" => "Aritmética", "icon" => "fa-calculator", "color" => "indigo", "desc" => "Domina sumas, restas, multiplicaciones y divisiones. ¡Los fundamentos!", "tema" => "Aritmética"], ["title" => "Álgebra", "icon" => "fa-square-root-variable", "color" => "green", "desc" => "Explora el mundo de las variables, ecuaciones y expresiones.", "tema" => "Álgebra"], ["title" => "Geometría", "icon" => "fa-ruler-combined", "color" => "sky", "desc" => "Calcula áreas, perímetros, volúmenes y propiedades de figuras.", "tema" => "Geometría"], ["title" => "Estadística", "icon" => "fa-chart-pie", "color" => "pink", "desc" => "Analiza datos, promedios, desviaciones y entiende la probabilidad.", "tema" => "Estadística"], ["title" => "Duelo Matemático", "icon" => "fa-swords", "color" => "orange", "desc" => "Compite contra otros jugadores en tiempo real y demuestra tu destreza.", "link" => "menu_duelo.php"], ["title" => "Juegos de Scratch", "icon" => "fa-gamepad", "color" => "blue", "desc" => "Descubre y comparte divertidos juegos educativos hechos por la comunidad.", "link" => "scratch.php"], ["title" => "Modo Desafío", "icon" => "fa-stopwatch-20", "color" => "purple", "desc" => "Pon a prueba tus habilidades al límite contra el reloj en retos intensos.", "disabled" => true], ];
                  $colors = [ "indigo" => ["card" => "bg-indigo-500", "border" => "border-indigo-700", "btn" => "bg-indigo-100 text-indigo-800", "hex" => "#6366f1"], "green"  => ["card" => "bg-green-500", "border" => "border-green-700", "btn" => "bg-green-100 text-green-800", "hex" => "#22c55e"], "sky"    => ["card" => "bg-sky-500", "border" => "border-sky-700", "btn" => "bg-sky-100 text-sky-800", "hex" => "#0ea5e9"], "pink"   => ["card" => "bg-pink-500", "border" => "border-pink-700", "btn" => "bg-pink-100 text-pink-800", "hex" => "#ec4899"], "orange" => ["card" => "bg-orange-500", "border" => "border-orange-700", "btn" => "bg-orange-100 text-orange-800", "hex" => "#f97316"], "blue"   => ["card" => "bg-blue-500", "border" => "border-blue-700", "btn" => "bg-blue-100 text-blue-800", "hex" => "#3b82f6"], "purple" => ["card" => "bg-purple-500", "border" => "border-purple-700", "btn" => "bg-purple-100 text-purple-800", "hex" => "#a855f7"], ];
                ?>
                <?php foreach ($menuItems as $index => $item):
                    $animationDelay = $index * 60;
                    $colorTheme = $colors[$item['color']] ?? $colors['indigo'];
                    $cardId = ($item['tema'] === 'Aritmética') ? 'id="aritmetica-card"' : '';
                ?>
                <div <?php echo $cardId; ?> class="animate-fade-in-up" style="animation-delay: <?php echo $animationDelay; ?>ms;">
                    <form method="POST" action="<?php echo htmlspecialchars($item['link'] ?? 'menu.php'); ?>" class="theme-form h-full" data-color-hex="<?php echo $colorTheme['hex']; ?>" data-icon-class="fa-solid <?php echo $item['icon']; ?>">
                        <?php if (isset($item['tema'])): ?><input type="hidden" name="tema" value="<?php echo $item['tema']; ?>"><?php endif; ?>
                        <button type="submit" <?php echo (isset($item['disabled']) && $item['disabled']) ? 'disabled' : ''; ?> class="card-3d w-full h-full text-left p-5 rounded-2xl text-white flex flex-col disabled:bg-gray-300 disabled:border-gray-400 disabled:cursor-not-allowed <?php echo $colorTheme['card']; ?> border-b-8 <?php echo $colorTheme['border']; ?>">
                            <i class="card-icon fa-solid <?php echo $item['icon']; ?> text-4xl sm:text-5xl mb-4 opacity-70"></i><h2 class="text-xl font-black flex-1"><?php echo $item['title']; ?></h2><p class="text-sm font-bold mb-4 opacity-90"><?php echo $item['desc'] ?? ''; ?></p><?php if (isset($item['disabled']) && $item['disabled']): ?><span class="w-full text-center font-bold py-2 px-5 rounded-lg bg-gray-100 text-gray-500"><i class="fa-solid fa-lock mr-1"></i> Próximamente</span><?php else: ?><span class="w-full text-center font-black py-2 px-5 rounded-lg <?php echo $colorTheme['btn']; ?>"><?php echo isset($item['link']) ? 'Entrar' : 'Comenzar'; ?></span><?php endif; ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>
<div id="backdrop" class="fixed inset-0 bg-black/70 md:hidden hidden z-40"></div>

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
            const introKey = 'hasSeenFullWelcomeV10';
            
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
                            setTimeout(endFullWelcome, 4000);
                        }
                    }
                });
            });
            
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
            profileButton.addEventListener('click', (event) => { event.stopPropagation(); profileDropdown.classList.toggle('hidden'); });
            window.addEventListener('click', (event) => { if (!profileDropdown.classList.contains('hidden') && !profileButton.contains(event.target)) { profileDropdown.classList.add('hidden'); } });
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
    });
</script>
</body>
</html>
