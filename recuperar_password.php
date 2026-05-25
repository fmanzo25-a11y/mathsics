<?php
include_once 'conexion.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mensaje = '';
$tipoMensaje = 'error';
$paso = 1; 
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'send_code') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $mensaje = 'Por favor ingresa tu correo electrónico.';
        } else {
            try {
                $conn = Db::conectar();
                $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE correo = :correo");
                $stmt->execute([':correo' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Generar código de 8 dígitos numérico
                    $codigo = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                    
                    // Expiración en 15 minutos
                    $stmtUpdate = $conn->prepare("UPDATE usuarios SET token_recuperacion = :codigo, expiracion_token_recuperacion = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE correo = :correo");
                    $stmtUpdate->execute([':codigo' => $codigo, ':correo' => $email]);
                    
                    // Obtener configuración de correo
                    $env_file = __DIR__ . '/.env';
                    $env = [];
                    if (file_exists($env_file)) {
                        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        foreach ($lines as $line) {
                            if (strpos(trim($line), '#') === 0) continue;
                            $parts = explode('=', $line, 2);
                            if (count($parts) === 2) {
                                $env[trim($parts[0])] = trim(trim($parts[1]), '"\'');
                            }
                        }
                    }
                    
                    $smtp_host = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
                    $smtp_port = $env['SMTP_PORT'] ?? 465;
                    $smtp_user = $env['SMTP_USER'] ?? 'jaremmanzo@gmail.com';
                    $smtp_pass = $env['SMTP_PASS'] ?? 'isyn wygi gtpz kaib';

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $smtp_host;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtp_user;
                    $mail->Password   = $smtp_pass;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = $smtp_port;
                    $mail->CharSet    = 'UTF-8';
                    
                    $mail->setFrom($smtp_user, 'Equipo de Mathsics');
                    $mail->addAddress($email, $user['nombre']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Código de Recuperación de Contraseña';
                    
                    $mail->Body    = "¡Hola " . htmlspecialchars($user['nombre']) . "! <br><br>Has solicitado recuperar tu contraseña en Mathsics. <br><br>Tu código de verificación es: <br><br><span style='font-size: 24px; font-weight: bold; background-color: #f3f4f6; padding: 10px 20px; border-radius: 8px; letter-spacing: 5px; color: #2563eb;'>" . $codigo . "</span><br><br>Este código expirará en 15 minutos. Si no fuiste tú quien solicitó esto, ignora este correo.";
                    $mail->AltBody = "Tu código de verificación es: " . $codigo;
                    
                    $mail->send();
                }
                
                // Siempre mostramos éxito aunque el correo no exista, por seguridad (para no revelar qué correos están registrados)
                $mensaje = 'Si el correo está registrado, te hemos enviado un código de 8 dígitos.';
                $tipoMensaje = 'success';
                $paso = 2; // Avanzar al paso 2
                
            } catch (Exception $e) {
                $mensaje = 'Hubo un error al procesar tu solicitud: ' . $e->getMessage();
            }
        }
    } 
    elseif (isset($_POST['action']) && $_POST['action'] === 'verify_code') {
        $email = trim($_POST['email'] ?? '');
        $codigo_ingresado = trim($_POST['codigo'] ?? '');
        
        if (empty($email) || empty($codigo_ingresado)) {
            $mensaje = 'Por favor ingresa el código de 8 dígitos.';
            $paso = 2;
        } else {
            try {
                $conn = Db::conectar();
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = :correo AND token_recuperacion = :codigo AND expiracion_token_recuperacion > NOW()");
                $stmt->execute([':correo' => $email, ':codigo' => $codigo_ingresado]);
                
                if ($stmt->fetch()) {
                    // Código válido y no expirado
                    $_SESSION['reset_email'] = $email;
                    
                    // Opcional: borrar el token por seguridad para que no se re-use si no cambia la contraseña ahora
                    $stmtClean = $conn->prepare("UPDATE usuarios SET token_recuperacion = NULL, expiracion_token_recuperacion = NULL WHERE correo = :correo");
                    $stmtClean->execute([':correo' => $email]);
                    
                    header("Location: cambiar_password.php");
                    exit();
                } else {
                    $mensaje = 'El código es incorrecto o ha expirado. Por favor, solicita uno nuevo.';
                    $paso = 2;
                }
            } catch (Exception $e) {
                $mensaje = 'Error al verificar el código: ' . $e->getMessage();
                $paso = 2;
            }
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
    <title>Recuperar Contraseña - Mathsics</title>
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
    
    <!-- Background Blobs -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
        <div class="absolute top-[10%] left-[10%] w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob"></div>
        <div class="absolute bottom-[10%] right-[10%] w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000"></div>
    </div>

    <div class="w-full max-w-md z-10">
        <div class="text-center mb-8">
            <a href="index.php" class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 drop-shadow-md">Mathsics</a>
        </div>

        <div class="glass-panel p-8 rounded-3xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
            
            <h2 class="text-2xl font-black mb-2 text-center text-slate-800">Recuperar Contraseña</h2>
            <p class="text-center text-slate-600 text-sm mb-6 font-semibold">
                <?php echo ($paso === 1) ? 'Ingresa tu correo para recibir un código de recuperación.' : 'Revisa tu correo y escribe el código de 8 dígitos.'; ?>
            </p>

            <?php if ($mensaje): ?>
            <div class="flex items-start gap-3 p-4 rounded-lg mb-5 text-sm <?php echo $tipoMensaje === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
                <i class="fa-solid <?php echo $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mt-1"></i>
                <span><?php echo htmlspecialchars($mensaje); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($paso === 1): ?>
                <!-- PASO 1: Pedir Correo -->
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="send_code">
                    <div>
                        <label for="email" class="font-bold text-slate-700 block mb-1">Correo Electrónico</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500"></i>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/60 border border-white/50 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300 transition-all shadow-sm" placeholder="tu@correo.com" required>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl shadow-md hover:bg-blue-700 hover:-translate-y-1 transition-all duration-300 transform flex items-center justify-center gap-2 mt-6">
                        <i class="fas fa-paper-plane"></i> Enviar Código
                    </button>
                </form>
            <?php else: ?>
                <!-- PASO 2: Verificar Código -->
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="verify_code">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Correo Electrónico</label>
                        <div class="px-4 py-3 rounded-xl bg-gray-100/50 border border-gray-200 text-gray-500 font-semibold cursor-not-allowed">
                            <?php echo htmlspecialchars($email); ?>
                        </div>
                    </div>

                    <div>
                        <label for="codigo" class="font-bold text-slate-700 block mb-1 mt-4">Código de Verificación (8 dígitos)</label>
                        <div class="relative">
                            <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500"></i>
                            <input type="text" id="codigo" name="codigo" maxlength="8" class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/80 border border-indigo-200 focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-all shadow-sm text-lg font-bold tracking-widest text-center" placeholder="12345678" required autocomplete="off">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl shadow-md hover:bg-indigo-700 hover:-translate-y-1 transition-all duration-300 transform flex items-center justify-center gap-2 mt-6">
                        <i class="fas fa-unlock"></i> Verificar Código
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <form action="" method="POST" class="inline">
                        <input type="hidden" name="action" value="send_code">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <button type="submit" class="text-sm font-bold text-blue-600 hover:underline cursor-pointer bg-transparent border-none p-0">Reenviar código</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="mt-8 text-center pt-4 border-t border-white/40">
                <a href="inicio_de_sesion.php" class="text-slate-600 font-bold hover:text-blue-600 transition flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> Volver a Iniciar Sesión
                </a>
            </div>
        </div>
    </div>
</body>
</html>
