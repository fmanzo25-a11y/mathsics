<?php
include_once 'conexion.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $scratch_url = trim($_POST['scratch_url'] ?? '');
    $id_usuario = $_SESSION['user_id'];

    if (empty($titulo) || empty($scratch_url)) {
        $error = 'El título y la URL del juego son obligatorios.';
    } elseif (!preg_match('/scratch\.mit\.edu\/projects\/(\d+)/', $scratch_url, $matches)) {
        $error = 'La URL de Scratch no es válida. Debe ser como: https://scratch.mit.edu/projects/123456789/';
    } else {
        $scratch_id = $matches[1];
        try {
            $conn = Db::conectar();
            $stmt = $conn->prepare("INSERT INTO scratch_games (id_usuario, titulo, scratch_id) VALUES (:id_usuario, :titulo, :scratch_id)");
            $stmt->execute([':id_usuario' => $id_usuario, ':titulo' => $titulo, ':scratch_id' => $scratch_id]);
            $success = '¡Tu juego ha sido publicado con éxito! Redirigiendo a la sala de juegos...';
            header("Refresh: 2; url=scratch.php");
        } catch (Exception $e) {
            error_log("Error en crear_juego_scratch.php: " . $e->getMessage());
            $error = 'Hubo un error al publicar tu juego. Por favor, inténtalo de nuevo.';
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
    <title>Publicar Juego de Scratch - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
            background-image: linear-gradient(to top, #e0f2fe, #f0f9ff);
        }
    </style>
</head>
<body class="text-slate-700">

    <header class="sticky top-0 z-40 w-full bg-white/80 backdrop-blur-lg border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-20">
            <h1 class="text-3xl font-black text-slate-800">Publica tu Juego</h1>
            <a href="scratch.php" class="text-gray-500 hover:text-red-600 transition p-2 rounded-full flex items-center gap-2 font-bold">
                <i class="fas fa-times text-xl"></i><span class="hidden sm:inline">Cancelar</span>
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 md:p-6 mt-8">
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 border-t-4 border-blue-500">
            
            <?php if ($error): ?>
                <div class="flex items-start gap-3 text-center p-3 rounded-lg mb-6 text-red-800 bg-red-100 border border-red-300">
                    <i class="fa-solid fa-exclamation-triangle mt-1"></i><span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="flex items-start gap-3 text-center p-3 rounded-lg mb-6 text-green-800 bg-green-100 border border-green-300">
                    <i class="fa-solid fa-check-circle mt-1"></i><span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form action="crear_juego_scratch.php" method="POST" class="space-y-8">
                <div>
                    <label for="titulo" class="block text-base font-bold text-gray-700 mb-2">Título del Juego</label>
                    <input type="text" id="titulo" name="titulo" class="w-full px-4 py-3 bg-gray-50 border-b-2 border-gray-300 focus:border-blue-500 focus:ring-0 focus:bg-white transition duration-200" placeholder="Mi increíble juego de naves" required>
                </div>

                <div>
                    <label for="scratch_url" class="block text-base font-bold text-gray-700 mb-2">URL del Proyecto de Scratch</label>
                    <input type="url" id="scratch_url" name="scratch_url" class="w-full px-4 py-3 bg-gray-50 border-b-2 border-gray-300 focus:border-blue-500 focus:ring-0 focus:bg-white transition duration-200" required placeholder="https://scratch.mit.edu/projects/123456789">
                    <p class="text-gray-500 text-xs mt-2 pl-1 font-semibold">Ve a tu juego en Scratch, copia la URL de la barra de direcciones y pégala aquí.</p>
                </div>

                <div class="pt-6 border-t border-gray-200">
                    <button type="submit" class="w-full bg-blue-500 text-white font-black py-4 text-lg rounded-xl shadow-lg border-b-4 border-blue-700 active:border-b-0 active:translate-y-1 hover:-translate-y-1 transition-all duration-150 transform flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        <span>Publicar Juego</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>