<?php
include_once 'conexion.php';
session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: recuperar_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$mensaje = '';
$tipoMensaje = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
    
    if (empty($nueva_contrasena) || empty($confirmar_contrasena)) {
        $mensaje = 'Por favor, llena ambos campos.';
    } elseif (strlen($nueva_contrasena) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva_contrasena !== $confirmar_contrasena) {
        $mensaje = 'Las contraseñas no coinciden. Por favor, intenta de nuevo.';
    } else {
        try {
            $conn = Db::conectar();
            $hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);
            
            $stmt = $conn->prepare("UPDATE usuarios SET contrasena = :hash, token_recuperacion = NULL, expiracion_token_recuperacion = NULL WHERE correo = :correo");
            $stmt->execute([':hash' => $hash, ':correo' => $email]);
            
            // Proceso exitoso: limpiamos la sesión
            unset($_SESSION['reset_email']);
            
            // Redirigimos a inicio de sesión con parámetro de éxito
            header("Location: inicio_de_sesion.php?reset_success=1");
            exit();
            
        } catch (Exception $e) {
            $mensaje = 'Error al actualizar la contraseña: ' . $e->getMessage();
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
    <title>Cambiar Contraseña - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f0f9ff; overflow-x: hidden; }
        .glass-panel { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15); }
        @keyframes blob { 0% { transform: translate(0px, 0px) scale(1); } 33% { transform: translate(30px, -50px) scale(1.1); } 66% { transform: translate(-20px, 20px) scale(0.9); } 100% { transform: translate(0px, 0px) scale(1); } }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 relative">
    
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
        <div class="absolute top-[10%] right-[10%] w-96 h-96 bg-green-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob"></div>
        <div class="absolute bottom-[10%] left-[10%] w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000"></div>
    </div>

    <div class="w-full max-w-md z-10">
        <div class="text-center mb-8">
            <a href="index.php" class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 drop-shadow-md">Mathsics</a>
        </div>

        <div class="glass-panel p-8 rounded-3xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-green-400 to-emerald-500"></div>
            
            <h2 class="text-2xl font-black mb-2 text-center text-slate-800">Crea tu Nueva Contraseña</h2>
            <p class="text-center text-slate-600 text-sm mb-6 font-semibold">
                Estás restableciendo la contraseña para: <br><span class="text-indigo-600"><?php echo htmlspecialchars($email); ?></span>
            </p>

            <?php if ($mensaje): ?>
            <div class="flex items-start gap-3 p-4 rounded-lg mb-5 text-sm bg-red-100 text-red-800" role="alert">
                <i class="fa-solid fa-exclamation-triangle mt-1"></i>
                <span><?php echo htmlspecialchars($mensaje); ?></span>
            </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label for="nueva_contrasena" class="font-bold text-slate-700 block mb-1">Nueva Contraseña</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500"></i>
                        <input type="password" id="nueva_contrasena" name="nueva_contrasena" class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/60 border border-white/50 focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition-all shadow-sm" placeholder="••••••••" required minlength="6">
                    </div>
                </div>

                <div>
                    <label for="confirmar_contrasena" class="font-bold text-slate-700 block mb-1 mt-4">Confirmar Nueva Contraseña</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500"></i>
                        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/60 border border-white/50 focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition-all shadow-sm" placeholder="••••••••" required minlength="6">
                    </div>
                </div>

                <button type="submit" class="w-full bg-emerald-500 text-white font-bold py-3 rounded-xl shadow-md hover:bg-emerald-600 hover:-translate-y-1 transition-all duration-300 transform flex items-center justify-center gap-2 mt-8">
                    <i class="fas fa-save"></i> Guardar y Continuar
                </button>
            </form>
        </div>
    </div>
</body>
</html>
