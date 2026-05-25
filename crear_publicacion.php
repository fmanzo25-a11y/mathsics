<?php
include_once 'conexion.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$mensaje = '';
$tipoMensaje = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_autor = $_SESSION['user_id'];
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'ninguna');
    $rutaImagen = null; 

    if (empty($titulo) || empty($contenido)) {
        $mensaje = "El título y el contenido son obligatorios.";
    } else {
        try {
         
            if (isset($_FILES['imagen_publicacion']) && $_FILES['imagen_publicacion']['error'] === UPLOAD_ERR_OK) {
                
                $archivo = $_FILES['imagen_publicacion'];
                
                if ($archivo['size'] > 5 * 1024 * 1024) { 
                    throw new Exception("El archivo es demasiado grande. El máximo es 5MB.");
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($archivo['tmp_name']);
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF.");
                }

                $directorioUploads = 'uploads/';
                if (!is_dir($directorioUploads)) {
                    mkdir($directorioUploads, 0777, true);
                }
                $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                $nombreUnico = 'post_' . uniqid('', true) . '.' . $extension;
                $rutaDestino = $directorioUploads . $nombreUnico;

                if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    $rutaImagen = $rutaDestino; 
                } else {
                    throw new Exception("Hubo un error al guardar la imagen en el servidor.");
                }
            }

            $conn = Db::conectar();
            $stmt = $conn->prepare(
                "INSERT INTO posts (id_usuario, titulo, contenido, categoria, imagen_url) 
                 VALUES (:id_usuario, :titulo, :contenido, :categoria, :imagen_url)"
            );
            $stmt->execute([
                ':id_usuario' => $id_autor,
                ':titulo' => $titulo,
                ':contenido' => $contenido,
                ':categoria' => $categoria,
                ':imagen_url' => $rutaImagen 
            ]);
            
            header("Location: foro.php");
            exit();

        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Publicación - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
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
            <h1 class="text-2xl font-bold text-gray-800">Crear Publicación</h1>
            <a href="foro.php" class="text-gray-500 hover:text-indigo-600 transition p-2 rounded-md flex items-center gap-2">
                <i class="fas fa-times"></i><span class="hidden sm:inline font-semibold">Cancelar</span>
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 md:p-6 mt-8">
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
            
            <?php if ($mensaje):
            ?>
                <div class="text-center p-3 rounded-lg mb-6 <?php echo $tipoMensaje === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
                    <?php echo htmlspecialchars($mensaje);
                    ?>
                </div>
            <?php endif;
            ?>

            <form action="crear_publicacion.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                    <input type="text" id="titulo" name="titulo" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition duration-200" placeholder="Un título claro y descriptivo" required>
                </div>

                <div>
                    <label for="contenido" class="block text-sm font-medium text-gray-700 mb-1">Contenido del Tutorial</label>
                    <textarea id="contenido" name="contenido" rows="10" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition duration-200" placeholder="Explica tu tema paso a paso..." required></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select id="categoria" name="categoria" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition duration-200">
                            <option value="ninguna">General</option>
                            <option value="aritmetica">Aritmética</option>
                            <option value="algebra">Álgebra</option>
                            <option value="geometria">Geometría</option>
                        </select>
                    </div>
                    <div>
                        <label for="imagen_publicacion" class="block text-sm font-medium text-gray-700 mb-1">Imagen de Portada (Opcional)</label>
                        <input type="file" id="imagen_publicacion" name="imagen_publicacion" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer transition">
                    </div>
                </div>

                <div id="imagePreviewContainer" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vista Previa</label>
                    <img id="imagePreview" src="" alt="Vista previa de la imagen" class="mt-2 rounded-lg max-h-72 w-auto mx-auto shadow-md">
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <button type="submit" class="w-full bg-pink-500 text-white font-bold py-4 text-lg rounded-lg shadow-lg hover:bg-pink-600 transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        <span>Publicar Tutorial</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        const fileInput = document.getElementById('imagen_publicacion');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');

        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = "";
                imagePreviewContainer.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
