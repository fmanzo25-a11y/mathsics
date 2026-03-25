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
                u.nombre as nombre_autor, u.foto_de_perfil,
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
    'algebra' => 'border-t-4 border-green-500',
    'aritmetica' => 'border-t-4 border-indigo-500',
    'geometria' => 'border-t-4 border-sky-500',
    'ninguna' => 'border-t-4 border-gray-300'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidad - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        /* CAMBIO DE ESTILO: Coherencia con el resto de la app */
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
            background-image: linear-gradient(to top, #e0f2fe, #f0f9ff);
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }

        /* NUEVO: Animación de "latido" para el botón de like */
        @keyframes heartbeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        .animate-heartbeat {
            animation: heartbeat 0.3s ease-in-out;
        }
    </style>
</head>
<body class="text-slate-700">

    <header class="sticky top-0 z-40 w-full bg-white/80 backdrop-blur-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-20">
            <h1 class="text-3xl font-black text-slate-800">Comunidad</h1>
            <a href="menu.php" class="bg-white text-blue-600 font-bold py-2 px-4 rounded-full hover:bg-gray-100 transition-transform duration-300 hover:scale-105 border-2 border-blue-100 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i><span class="hidden sm:inline">Volver al Menú</span>
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8">
        <?php if (empty($posts)): ?>
            <div class="text-center py-20 bg-white rounded-lg shadow-sm mt-8">
                <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-semibold text-gray-700">Aún no hay publicaciones.</h2>
                <p class="text-gray-500 mt-2">¡Sé el primero en compartir algo con la comunidad!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($posts as $index => $post): 
                    $color_theme = $category_colors[$post['categoria']] ?? $category_colors['ninguna'];
                ?>
                    <div class="relative">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 h-full w-0.5 bg-gray-300 z-0"></div>

                        <div class="relative z-10 h-11 w-11 flex items-center justify-center">
                            <div class="h-5 w-5 rounded-full border-4 border-white <?php echo $color_theme['dot']; ?>"></div>
                        </div>
                    </div>

                    <article class="mb-10 animate-fade-in" style="animation-delay: <?php echo $index * 100; ?>ms;">
                        <div class="bg-white rounded-2xl shadow-lg transition transform hover:-translate-y-1 hover:shadow-xl border-l-4 <?php echo $color_theme['border']; ?>">
                            <div class="p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <img src="<?php echo htmlspecialchars($post['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); ?>" alt="Foto de perfil" class="w-11 h-11 rounded-full object-cover">
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($post['nombre_autor']); ?></p>
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
                                    <a href="publicacion.php?id=<?php echo $post['id_publicacion']; ?>" class="block h-auto my-4 rounded-lg overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($post['imagen_url']); ?>" alt="Imagen de la publicación" class="w-full h-auto object-cover max-h-96">
                                    </a>
                                <?php endif; ?>

                                <p class="text-gray-600 text-sm leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($post['contenido'])); ?>
                                </p>
                            </div>

                            <footer class="bg-gray-50 px-6 py-4 flex justify-between items-center text-sm border-t rounded-b-xl">
                                <div class="flex items-center gap-6">
                                    <button class="like-btn flex items-center gap-1.5 transition group <?php echo $post['liked_by_user'] ? 'text-pink-500' : 'text-gray-500 hover:text-pink-500'; ?>" data-id="<?php echo $post['id_publicacion']; ?>">
                                        <i class="<?php echo $post['liked_by_user'] ? 'fas' : 'far'; ?> fa-heart text-xl transition-transform"></i>
                                        <span class="font-bold text-base" data-count><?php echo htmlspecialchars($post['likes']); ?></span>
                                    </button>
                                    <a href="publicacion.php?id=<?php echo $post['id_publicacion']; ?>" class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 transition">
                                        <i class="far fa-comment text-xl"></i>
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
    <a href="crear_publicacion.php" class="fixed bottom-8 right-8 bg-blue-500 hover:bg-blue-600 text-white font-bold w-16 h-16 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-110 hover:rotate-90 flex items-center justify-center">
        <i class="fas fa-plus text-2xl"></i>
    </a>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.like-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const postId = button.dataset.id;
                const icon = button.querySelector('i');
                const countSpan = button.querySelector('[data-count]');
                
                // Animación de latido al hacer clic
                icon.classList.add('animate-heartbeat');
                icon.addEventListener('animationend', () => {
                    icon.classList.remove('animate-heartbeat');
                }, { once: true });

                // Lógica de actualización optimista (sin cambios, ya era robusta)
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
                    const response = await fetch('like_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_publicacion: postId })
                    });
                    if (!response.ok) throw new Error('Error en la respuesta del servidor.');
                    const data = await response.json();
                    
                    if(data.success) {
                        // Actualización final con la respuesta del servidor (la fuente de la verdad)
                        countSpan.textContent = data.newLikes;
                        if (data.liked) {
                            icon.classList.replace('far', 'fas');
                            button.classList.add('text-pink-500');
                        } else {
                            icon.classList.replace('fas', 'far');
                            button.classList.remove('text-pink-500');
                        }
                    } else {
                        // Si el servidor reporta un error, revertimos la acción
                        throw new Error(data.error || 'Error desconocido del servidor.');
                    }
                } catch (error) {
                    console.error('Error al dar like:', error);
                    // Revertir la acción si la llamada fetch falla
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