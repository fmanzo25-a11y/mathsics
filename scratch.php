<?php
include_once 'conexion.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario_actual = $_SESSION['user_id'];

try {
    $conn = Db::conectar();
    $stmtGames = $conn->prepare(
        "SELECT sg.id, sg.titulo, sg.scratch_id, sg.fecha_publicacion, sg.likes, sg.num_comentarios,
                u.nombre as nombre_autor, u.foto_de_perfil,
                (SELECT COUNT(*) FROM scratch_likes sl WHERE sl.id_juego = sg.id AND sl.id_usuario = :id_usuario_actual) as liked_by_user
         FROM scratch_games sg
         JOIN usuarios u ON sg.id_usuario = u.id
         ORDER BY sg.fecha_publicacion DESC"
    );
    $stmtGames->execute([':id_usuario_actual' => $id_usuario_actual]);
    $games = $stmtGames->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $games = [];
    $dbError = "Parece que la base de datos para los juegos aún no está configurada. ";
    $dbError .= "Por favor, ejecuta el SQL necesario para crear las tablas `scratch_games` y `scratch_likes`.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Juegos de Scratch - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
            background-image: linear-gradient(to top, #e0f2fe 90%, #f0f9ff 100%);
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes heartbeat { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }
        .animate-heartbeat { animation: heartbeat 0.3s ease-in-out; }

        /* Contenedor para el embed de Scratch */
        .scratch-embed-container {
            position: relative;
            width: 100%;
            padding-top: 82.5%; /* Aspect ratio 4:3.3 (485/402) */
            background-color: #e5e7eb;
        }
        .scratch-embed {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body class="text-slate-700">

    <header class="sticky top-0 z-40 w-full bg-white/80 backdrop-blur-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-20">
            <h1 class="text-3xl font-black text-slate-800">Sala de Juegos</h1>
            <a href="menu.php" class="bg-white text-blue-600 font-bold py-2 px-4 rounded-full hover:bg-gray-100 transition-transform duration-300 hover:scale-105 border-2 border-blue-100 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i><span class="hidden sm:inline">Volver al Menú</span>
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8">
        <?php if (isset($dbError)): ?>
            <div class="text-center py-10 px-6 bg-white rounded-lg shadow-sm mt-8 border-t-4 border-red-500">
                <i class="fas fa-database text-6xl text-red-300 mb-4"></i>
                <h2 class="text-2xl font-black text-gray-700">Error de Base de Datos</h2>
                <div class="text-gray-600 mt-2 font-semibold"><?php echo $dbError; ?></div>
            </div>
        <?php elseif (empty($games)): ?>
            <div class="text-center py-20 bg-white/50 rounded-lg mt-8">
                <i class="fas fa-gamepad text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-black text-gray-700">¡La sala de juegos está vacía!</h2>
                <p class="text-gray-500 mt-2 font-semibold">¡Sé el primero en compartir tu creación de Scratch!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($games as $index => $game): ?>
                    <article class="bg-white rounded-2xl shadow-lg overflow-hidden transition transform hover:-translate-y-2 hover:shadow-2xl animate-fade-in" style="animation-delay: <?php echo $index * 80; ?>ms;">
                        <div class="scratch-embed-container rounded-t-2xl overflow-hidden">
                             <iframe src="https://scratch.mit.edu/projects/<?php echo htmlspecialchars($game['scratch_id']); ?>/embed" class="scratch-embed"></iframe>
                        </div>
                        <div class="p-5">
                            <h2 class="text-xl font-black text-gray-900 leading-tight truncate">
                                <?php echo htmlspecialchars($game['titulo']); ?>
                            </h2>
                             <div class="flex items-center gap-3 mt-3">
                                <img src="<?php echo htmlspecialchars($game['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); ?>" alt="Foto de perfil" class="w-9 h-9 rounded-full object-cover">
                                <div>
                                    <p class="font-bold text-gray-700 text-sm">Por <?php echo htmlspecialchars($game['nombre_autor']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($game['fecha_publicacion'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <footer class="bg-gray-50 px-5 py-3 flex justify-between items-center text-sm border-t">
                            <div class="flex items-center gap-5">
                                <button class="like-btn flex items-center gap-1.5 transition group <?php echo $game['liked_by_user'] ? 'text-pink-500' : 'text-gray-500 hover:text-pink-500'; ?>" data-id="<?php echo $game['id']; ?>">
                                    <i class="<?php echo $game['liked_by_user'] ? 'fas' : 'far'; ?> fa-heart text-xl transition-transform"></i>
                                    <span class="font-bold text-base" data-count><?php echo htmlspecialchars($game['likes']); ?></span>
                                </button>
                                <div class="flex items-center gap-1.5 text-gray-400 cursor-not-allowed">
                                    <i class="far fa-comment text-xl"></i>
                                    <span class="font-bold text-base"><?php echo htmlspecialchars($game['num_comentarios']); ?></span>
                                </div>
                            </div>
                            <a href="https://scratch.mit.edu/projects/<?php echo htmlspecialchars($game['scratch_id']); ?>" target="_blank" class="font-bold text-blue-500 hover:text-blue-700 text-xs">Ver en Scratch &rarr;</a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <a href="crear_juego_scratch.php" class="fixed bottom-8 right-8 bg-blue-500 hover:bg-blue-600 text-white font-bold w-16 h-16 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-110 hover:rotate-90 flex items-center justify-center">
        <i class="fas fa-plus text-2xl"></i>
    </a>

    <script>
    // Este script de likes es correcto y no necesita cambios.
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.like-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const gameId = button.dataset.id;
                const icon = button.querySelector('i');
                const countSpan = button.querySelector('[data-count]');
                
                icon.classList.add('animate-heartbeat');
                icon.addEventListener('animationend', () => icon.classList.remove('animate-heartbeat'), { once: true });

                let currentLikes = parseInt(countSpan.textContent);
                const isLiked = icon.classList.contains('fas');

                if (isLiked) {
                    icon.classList.replace('fas', 'far');
                    button.classList.remove('text-pink-500');
                    countSpan.textContent = currentLikes - 1;
                } else {
                    icon.classList.replace('far', 'fas');
                    button.classList.add('text-pink-500');
                    countSpan.textContent = currentLikes + 1;
                }

                try {
                    const response = await fetch('scratch_like_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_juego: gameId })
                    });
                    if (!response.ok) throw new Error('Error en la respuesta del servidor.');
                    const data = await response.json();

                    if(data.success) {
                        countSpan.textContent = data.newLikes;
                        if (data.liked) {
                            icon.classList.replace('far', 'fas');
                            button.classList.add('text-pink-500');
                        } else {
                            icon.classList.replace('fas', 'far');
                            button.classList.remove('text-pink-500');
                        }
                    } else { throw new Error(data.error); }
                } catch (error) {
                    console.error('Error al dar like:', error);
                    if (isLiked) {
                        icon.classList.replace('far', 'fas');
                        button.classList.add('text-pink-500');
                        countSpan.textContent = currentLikes;
                    } else {
                        icon.classList.replace('fas', 'far');
                        button.classList.remove('text-pink-500');
                        countSpan.textContent = currentLikes;
                    }
                }
            });
        });
    });
    </script>
</body>
</html>