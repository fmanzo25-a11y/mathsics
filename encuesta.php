<?php
require_once 'seguridad.php';
iniciar_sesion_segura();
inyectar_cabeceras_seguridad();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: inicio_de_sesion.php");
    exit();
}

$id_usuario = $_SESSION['user_id'];
$mensaje_exito = "";
$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_token_csrf($_POST['csrf_token'] ?? '');
    
    $sugerencia = trim($_POST['sugerencia'] ?? '');
    
    if (empty($sugerencia)) {
        $mensaje_error = "La sugerencia no puede estar vacía.";
    } elseif (strlen($sugerencia) > 1000) {
        $mensaje_error = "La sugerencia es demasiado larga. Máximo 1000 caracteres.";
    } else {
        try {
            $conn = Db::conectar();
            $stmt = $conn->prepare("INSERT INTO sugerencias (id_usuario, sugerencia) VALUES (:id_u, :sug)");
            $stmt->execute([
                ':id_u' => $id_usuario,
                ':sug' => htmlspecialchars($sugerencia)
            ]);
            $mensaje_exito = "¡Gracias por tu aporte! Hemos recibido tu sugerencia.";
        } catch (Exception $e) {
            error_log("Error al guardar sugerencia: " . $e->getMessage());
            $mensaje_error = "Ocurrió un error al enviar tu sugerencia. Inténtalo más tarde.";
        }
    }
}

// Token CSRF
$csrf_token = generar_token_csrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Encuesta / Sugerencias</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f0f9ff; background-image: linear-gradient(to top, #e0f2fe, #f0f9ff); overflow-x: hidden; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
        .animate-fade-in-up { animation: fade-in-up 0.6s ease-out forwards; }
        @keyframes fade-in-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen text-slate-800 relative">

    <!-- Background Blobs -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-2xl opacity-40 animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-2xl opacity-40 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-1/2 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-2xl opacity-40 animate-blob animation-delay-4000"></div>
    </div>

    <!-- Header / Navbar -->
    <header class="glass-panel sticky top-0 z-50 px-6 py-4 flex justify-between items-center shadow-sm">
        <a href="menu.php" class="flex items-center gap-2 text-slate-500 hover:text-blue-600 font-bold group transition-colors">
            <div class="bg-white shadow-sm border border-gray-200 w-10 h-10 rounded-full flex items-center justify-center group-hover:-translate-x-1 group-hover:border-blue-300 transition-all">
                <i class="fas fa-arrow-left transition-transform"></i>
            </div>
            <span class="hidden sm:inline">Volver</span>
        </a>
        <h1 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center gap-2">
            <i class="fas fa-comment-dots text-indigo-500"></i> Sugerencias
        </h1>
        <div class="w-10 sm:w-20"></div> <!-- Spacer -->
    </header>

    <main class="container mx-auto px-4 py-12 max-w-2xl animate-fade-in-up">
        
        <div class="text-center mb-10">
            <h2 class="text-4xl font-black text-slate-800 mb-4 drop-shadow-sm">Ayúdanos a Mejorar</h2>
            <p class="text-slate-600 text-lg font-bold">¿Tienes alguna idea, encontraste un error o quieres un nuevo tema? ¡Cuéntanoslo! Leemos absolutamente todas las sugerencias.</p>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-8 shadow-sm flex items-center animate-fade-in-up">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <p class="font-bold"><?php echo htmlspecialchars($mensaje_exito); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-8 shadow-sm flex items-center animate-fade-in-up">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <p class="font-bold"><?php echo htmlspecialchars($mensaje_error); ?></p>
            </div>
        <?php endif; ?>

        <div class="glass-panel rounded-3xl p-6 sm:p-10 shadow-xl border-t-4 border-indigo-500">
            <form action="encuesta.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="mb-6">
                    <label for="sugerencia" class="block text-slate-700 font-black mb-3 text-lg flex items-center gap-2">
                        <i class="fas fa-lightbulb text-yellow-500"></i> Tu Sugerencia:
                    </label>
                    <textarea 
                        id="sugerencia" 
                        name="sugerencia" 
                        rows="6" 
                        class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-inner resize-none font-semibold text-slate-700" 
                        placeholder="Me gustaría que la app tuviera..."
                        required
                    ></textarea>
                    <p class="text-xs text-slate-500 font-bold mt-2 text-right">Máximo 1000 caracteres.</p>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-xl py-4 rounded-full shadow-[0_10px_20px_rgba(79,70,229,0.3)] hover:shadow-[0_15px_25px_rgba(79,70,229,0.4)] hover:-translate-y-1 active:translate-y-0 transition-all flex justify-center items-center gap-3">
                    <i class="fas fa-paper-plane"></i> Enviar Sugerencia
                </button>
            </form>
        </div>

    </main>

</body>
</html>
