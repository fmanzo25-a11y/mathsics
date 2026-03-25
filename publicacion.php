<?php
include_once 'conexion.php';
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario_actual = $_SESSION['user_id'];
$id_publicacion = trim($_GET['id'] ?? 0);

if (empty($id_publicacion)) {
    die("Error: No se ha especificado una publicación.");
}

try {
    $conn = Db::conectar();
    
    $stmtPost = $conn->prepare(
        "SELECT p.*, u.nombre as nombre_autor, u.foto_de_perfil as foto_autor 
         FROM posts p 
         JOIN usuarios u ON u.id = p.id_usuario 
         WHERE p.id_publicacion = :id"
    );
    $stmtPost->execute([':id' => $id_publicacion]);
    $post = $stmtPost->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("La publicación no fue encontrada.");
    }

    $stmtCom = $conn->prepare(
        "SELECT c.*, u.nombre, u.foto_de_perfil 
         FROM comentarios c 
         JOIN usuarios u ON u.id = c.id_usuario 
         WHERE c.id_publicacion = :id 
         ORDER BY c.fecha_comentario ASC"
    );
    $stmtCom->execute([':id' => $id_publicacion]);
    $comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
   error_log("Error en publicacion.php: " . $e->getMessage());
   die("Error al cargar la publicación.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['titulo']); ?> - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
     body {
        background-color: #f3f4f6;
        background-image: radial-gradient(#d1d5db 0.5px, transparent 0.5px), radial-gradient(#d1d5db 0.5px, #f3f4f6 0.5px);
        background-size: 20px 20px;
        background-position: 0 0, 10px 10px;
     }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <header class="sticky top-0 z-40 w-full bg-white/80 backdrop-blur-lg border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
            <div class="flex items-center gap-4">
                <a href="foro.php" class="text-gray-500 hover:text-indigo-600 transition p-2 rounded-md flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i><span class="hidden sm:inline font-semibold">Volver al Foro</span>
                </a>
            </div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-800 truncate hidden sm:block">Publicación</h1>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 md:p-6 space-y-8">
        
        <article class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <?php if (!empty($post['imagen_url'])):
            ?>
                <img src="<?php echo htmlspecialchars($post['imagen_url']); ?>" alt="Imagen de la publicación" class="w-full h-auto max-h-[500px] object-cover">
            <?php endif;
            ?>

            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-4 mb-5">
                    <img src="<?php echo htmlspecialchars($post['foto_autor'] ?? 'images/sinfoto.jpeg'); ?>" alt="Foto de perfil" class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-md">
                    <div>
                        <p class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($post['nombre_autor']); ?></p>
                        <p class="text-sm text-gray-500">Publicado el <?php echo date('d M Y \a \l\a\s H:i', strtotime($post['fecha_publicacion'])); ?></p>
                    </div>
                </div>
                
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-6 leading-tight"><?php echo htmlspecialchars($post['titulo']); ?></h2>
                
                <div class="prose prose-lg max-w-none text-gray-800 leading-relaxed space-y-4">
                    <?php echo nl2br(htmlspecialchars($post['contenido']));
                    ?>
                </div>
            </div>

            <footer class="bg-gray-50 px-6 sm:px-8 py-4 flex items-center gap-8 text-gray-600 border-t">
                <div class="flex items-center gap-2">
                    <i class="fas fa-heart text-xl text-red-400"></i>
                    <span class="font-semibold text-lg"><?php echo htmlspecialchars($post['likes']); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-comment-dots text-xl text-indigo-400"></i>
                    <span class="font-semibold text-lg"><?php echo htmlspecialchars($post['num_comentarios']); ?></span>
                </div>
            </footer>
        </article>

        <section class="bg-white rounded-2xl shadow-lg">
            <header class="p-5 border-b">
                <h3 class="text-xl font-bold text-gray-800">Comentarios (<?php echo count($comentarios);
                ?>)</h3>
            </header>
            
            <div class="p-5 sm:p-6 space-y-6">
                <?php if (empty($comentarios)):
                ?>
                    <p class="text-gray-500 text-center py-4">Aún no hay comentarios. ¡Sé el primero en responder!</p>
                <?php else:
                ?>
                    <?php foreach($comentarios as $com):
                    ?>
                        <div class="flex items-start gap-4">
                            <img src="<?php echo htmlspecialchars($com['foto_de_perfil'] ?? 'images/sinfoto.jpeg'); ?>" alt="Foto de perfil" class="w-10 h-10 rounded-full object-cover mt-1 shadow-sm">
                            <div class="flex-1 bg-gray-100 rounded-xl p-4">
                                <div class="flex justify-between items-center mb-1">
                                    <p class="font-semibold text-indigo-700"><?php echo htmlspecialchars($com['nombre']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($com['fecha_comentario'])); ?></p>
                                </div>
                                <p class="text-gray-800 leading-snug"><?php echo nl2br(htmlspecialchars($com['contenido'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach;
                    ?>
                <?php endif;
                ?>
            </div>

            <footer class="p-5 border-t bg-gray-50/80">
                <form method="POST" action="comentarios.php" class="flex items-start gap-4">
                     <input type="hidden" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>" name="id_usuario">
                    <input type="hidden" value="<?php echo htmlspecialchars($id_publicacion); ?>" name="id_publicacion">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_photo'] ?? 'images/sinfoto.jpeg'); ?>" alt="Tu foto de perfil" class="w-10 h-10 rounded-full object-cover mt-1 shadow-sm">
                    <div class="flex-1">
                        <textarea name="contenido" rows="2" placeholder="Escribe tu comentario..." class="w-full px-4 py-2 rounded-lg border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow duration-200" required></textarea>
                        <div class="flex justify-end mt-2">
                            <button type="submit" class="bg-indigo-600 text-white font-semibold py-2 px-5 rounded-lg shadow-md hover:bg-indigo-700 transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Comentar</span>
                            </button>
                        </div>
                    </div>
                </form>
            </footer>
        </section>

    </main>
</body>
</html>
