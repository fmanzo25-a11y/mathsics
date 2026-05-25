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

    // Consulta para obtener las publicaciones y si el usuario actual les ha dado like
    $stmtPosts = $conn->prepare(
        "SELECT p.id_publicacion, p.titulo, p.contenido, p.fecha_publicacion, p.likes, p.num_comentarios, p.imagen_url, p.categoria,
                u.id as id_autor, u.nombre as nombre_autor, u.foto_de_perfil,
                (SELECT COUNT(*) FROM likes l WHERE l.id_publicacion = p.id_publicacion AND l.id_usuario = :id_usuario_actual) as liked_by_user
         FROM posts p
         JOIN usuarios u ON p.id_usuario = u.id
         ORDER BY p.fecha_publicacion DESC"
    );
    $stmtPosts->execute([':id_usuario_actual' => $id_usuario_actual]);
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error en foro.php: " . $e->getMessage());
    die("Error al cargar el foro.");
}

// Array de colores para las categorías
$category_colors = [
    'algebra' => [
        'border' => 'border-t-4 border-green-500',
        'dot' => 'bg-green-500',
        'tag' => 'bg-green-100 text-green-800'
    ],
    'aritmetica' => [
        'border' => 'border-t-4 border-indigo-500',
        'dot' => 'bg-indigo-500',
        'tag' => 'bg-indigo-100 text-indigo-800'
    ],
    'geometria' => [
        'border' => 'border-t-4 border-sky-500',
        'dot' => 'bg-sky-500',
        'tag' => 'bg-sky-100 text-sky-800'
    ],
    'ninguna' => [
        'border' => 'border-t-4 border-gray-300',
        'dot' => 'bg-gray-300',
        'tag' => 'bg-gray-100 text-gray-800'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidad - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/global.css">
    <style>
        /* NUEVO: Animación de "latido" para el botón de like */
        .animate-heartbeat {
            animation: heartbeat 0.3s ease-in-out;
        }
    </style>
</head>
<body class="text-slate-700">
    
    <div id="a11y-announcements" class="sr-only" aria-live="polite"></div>

    <header class="sticky top-0 z-40 w-full glass-panel border-b-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-20">
            <h1 class="text-3xl font-black text-slate-800 drop-shadow-sm">Comunidad</h1>
            <a href="menu.php" class="bg-white/70 text-blue-600 font-bold py-2 px-4 rounded-full hover:bg-white transition-all duration-300 hover:scale-105 border border-white/50 shadow-sm flex items-center gap-2">
                <i class="fas fa-arrow-left" aria-hidden="true"></i><span class="hidden sm:inline">Volver al Menú</span>
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8">
        <?php if (empty($posts)): ?>
            <div class="text-center py-20 bg-white rounded-lg shadow-sm mt-8">
                <i class="fas fa-comments text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h2 class="text-2xl font-semibold text-gray-700">Aún no hay publicaciones.</h2>
                <p class="text-gray-500 mt-2">¡Sé el primero en compartir algo con la comunidad!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($posts as $index => $post): 
                    $color_theme = $category_colors[$post['categoria']] ?? $category_colors['ninguna'];
                ?>
                    <article class="mb-10" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="glass-card rounded-3xl shadow-md hover:-translate-y-2 hover:shadow-2xl border-t-4 <?php echo $color_theme['border']; ?>">
                            <div class="p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <a href="usuario.php?id=<?php echo $post['id_autor']; ?>" class="cursor-pointer">
                                        <img src="<?php echo htmlspecialchars($post['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); ?>" alt="Foto de perfil de <?php echo htmlspecialchars($post['nombre_autor']); ?>" class="w-11 h-11 rounded-full object-cover border-2 border-transparent hover:border-blue-500 transition-colors">
                                    </a>
                                    <div>
                                        <a href="usuario.php?id=<?php echo $post['id_autor']; ?>" class="font-bold text-gray-800 text-sm hover:text-blue-600 hover:underline cursor-pointer"><?php echo htmlspecialchars($post['nombre_autor']); ?></a>
                                        <p class="text-xs text-gray-500">Publicado el <?php echo date('d M Y', strtotime($post['fecha_publicacion'])); ?></p>
                                    </div>
                                </div>

                                <h2 class="text-xl font-black text-gray-900 mb-2 leading-tight">
                                    <a href="publicacion.php?id=<?php echo $post['id_publicacion']; ?>" class="hover:text-blue-600 transition"><?php echo htmlspecialchars($post['titulo']); ?></a>
                                </h2>
                                
                                <?php if (!empty($post['categoria']) && $post['categoria'] !== 'ninguna'): ?>
                                    <span class="inline-block <?php echo $color_theme['tag']; ?> text-xs font-semibold px-2.5 py-1 rounded-full mb-3 capitalize"><?php echo htmlspecialchars($post['categoria']); ?></span>
                                <?php endif; ?>

                                <?php if (!empty($post['imagen_url'])) : ?>
                                    <a href="publicacion.php?id=<?php echo $post['id_publicacion']; ?>" class="block h-auto my-4 rounded-lg overflow-hidden" aria-label="Ver imagen de la publicación: <?php echo htmlspecialchars($post['titulo']); ?>">
                                        <img src="<?php echo htmlspecialchars($post['imagen_url']); ?>" alt="Imagen de la publicación" class="w-full h-auto object-cover max-h-96">
                                    </a>
                                <?php endif; ?>

                                <p class="text-gray-600 text-sm leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($post['contenido'])); ?>
                                </p>
                            </div>

                            <footer class="bg-white/40 px-6 py-4 flex justify-between items-center text-sm border-t border-white/60 rounded-b-3xl backdrop-blur-sm">
                                <div class="flex items-center gap-6">
                                    <button class="like-btn flex items-center gap-1.5 transition group <?php echo $post['liked_by_user'] ? 'text-pink-500' : 'text-gray-500 hover:text-pink-500'; ?>" data-id="<?php echo $post['id_publicacion']; ?>" aria-label="Me gusta, actualmente <?php echo htmlspecialchars($post['likes']); ?>">
                                        <i class="<?php echo $post['liked_by_user'] ? 'fas' : 'far'; ?> fa-heart text-xl transition-transform" aria-hidden="true"></i>
                                        <span class="font-bold text-base" data-count><?php echo htmlspecialchars($post['likes']); ?></span>
                                    </button>
                                    <a href="publicacion.php?id=<?php echo $post['id_publicacion']; ?>" class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 transition" aria-label="<?php echo htmlspecialchars($post['num_comentarios']); ?> comentarios">
                                        <i class="far fa-comment text-xl" aria-hidden="true"></i>
                                        <span class="font-bold text-base"><?php echo htmlspecialchars($post['num_comentarios']); ?></span>
                                    </a>
                                </div>
                                <a href="publicacion.php?id=<?php echo $post['id_publicacion']; ?>" class="font-bold text-blue-500 hover:text-blue-700 text-xs">Leer más &rarr;</a>
                            </footer>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php if (isset($_SESSION['tipo_cuenta']) && $_SESSION['tipo_cuenta'] !== 'niño'): ?>
    <a href="crear_publicacion.php" class="fixed bottom-8 right-8 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold w-16 h-16 rounded-full shadow-[0_4px_15px_rgba(59,130,246,0.5)] hover:shadow-[0_8px_25px_rgba(59,130,246,0.6)] transition-all duration-300 transform hover:scale-110 hover:rotate-90 flex items-center justify-center focus:ring-4 focus:ring-blue-300 outline-none z-50" aria-label="Crear nueva publicación" title="Crear nueva publicación">
        <i class="fas fa-plus text-2xl" aria-hidden="true"></i>
    </a>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const a11yAnnounce = document.getElementById('a11y-announcements');

        function announceA11y(text) {
            if (a11yAnnounce) {
                a11yAnnounce.textContent = '';
                setTimeout(() => {
                    a11yAnnounce.textContent = text;
                }, 50);
            }
        }

        document.querySelectorAll('.like-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const postId = button.dataset.id;
                const icon = button.querySelector('i');
                const countSpan = button.querySelector('[data-count]');
                
                icon.classList.add('animate-heartbeat');
                icon.addEventListener('animationend', () => {
                    icon.classList.remove('animate-heartbeat');
                }, { once: true });

                let currentLikes = parseInt(countSpan.textContent);
                const isLiked = icon.classList.contains('fas');

                if (isLiked) {
                    icon.classList.replace('fas', 'far');
                    button.classList.remove('text-pink-500');
                    const newCount = currentLikes - 1;
                    countSpan.textContent = newCount;
                    button.setAttribute('aria-label', `Me gusta, actualmente ${newCount}`);
                    announceA11y('Has quitado el me gusta de esta publicación.');
                } else {
                    icon.classList.replace('far', 'fas');
                    button.classList.add('text-pink-500');
                    const newCount = currentLikes + 1;
                    countSpan.textContent = newCount;
                    button.setAttribute('aria-label', `Me gusta, actualmente ${newCount}`);
                    announceA11y('Has indicado que te gusta esta publicación.');
                }

                try {
                    const response = await fetch('like_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_publicacion: postId })
                    });
                    if (!response.ok) throw new Error('Error en la respuesta del servidor.');
                    const data = await response.json();
                    
                    if(data.success) {
                        countSpan.textContent = data.newLikes;
                        button.setAttribute('aria-label', `Me gusta, actualmente ${data.newLikes}`);
                        if (data.liked) {
                            icon.classList.replace('far', 'fas');
                            button.classList.add('text-pink-500');
                        } else {
                            icon.classList.replace('fas', 'far');
                            button.classList.remove('text-pink-500');
                        }
                    } else {
                        throw new Error(data.error || 'Error desconocido del servidor.');
                    }
                } catch (error) {
                    console.error('Error al dar like:', error);
                    if (isLiked) {
                        icon.classList.replace('far', 'fas');
                        button.classList.add('text-pink-500');
                        countSpan.textContent = currentLikes;
                        button.setAttribute('aria-label', `Me gusta, actualmente ${currentLikes}`);
                    } else {
                        icon.classList.replace('fas', 'far');
                        button.classList.remove('text-pink-500');
                        countSpan.textContent = currentLikes;
                        button.setAttribute('aria-label', `Me gusta, actualmente ${currentLikes}`);
                    }
                }
            });
        });
    });
    </script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 50 });
    </script>
    <script src="toast_notifications.js"></script>
</body>
</html>