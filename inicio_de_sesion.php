<?php
require_once 'seguridad.php';
iniciar_sesion_segura();
inyectar_cabeceras_seguridad();
if (isset($_SESSION['user_id'])) {
    header("Location: menu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
            overflow-x: hidden;
        }
        /* Glassmorphism & Blobs */
        .glass-panel {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
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
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">

    <!-- Background Blobs -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
        <div class="absolute top-[0%] left-[0%] w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob"></div>
        <div class="absolute top-[30%] right-[0%] w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[0%] left-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-4000"></div>
    </div>

    <div class="w-full max-w-md z-10" data-aos="zoom-in" data-aos-duration="800">
        <div class="text-center mb-8" data-aos="fade-down" data-aos-delay="200">
            <a href="index.php" class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 drop-shadow-md tracking-wide hover:scale-105 transition-transform inline-block">Mathsics</a>
            <p class="text-slate-700 mt-2 font-bold text-lg drop-shadow-sm">¡Qué bueno verte de nuevo! Ingresa para continuar.</p>
        </div>

        <div class="glass-panel p-8 rounded-3xl" data-aos="fade-up" data-aos-delay="400">
            <h2 class="text-2xl font-black mb-6 text-center text-slate-800">Iniciar Sesión</h2>

            <?php if (isset($_GET['reset_success']) && $_GET['reset_success'] == 1): ?>
            <div class="flex items-start gap-3 p-4 rounded-lg mb-5 text-sm bg-green-100 text-green-800 border border-green-300 shadow-sm" role="alert">
                <i class="fa-solid fa-check-circle mt-1" aria-hidden="true"></i>
                <span>¡Tu contraseña ha sido restablecida con éxito! Ya puedes iniciar sesión con tu nueva clave.</span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])) : ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert" aria-live="assertive">
                    <strong class="font-bold">¡Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>
            
            <form action="api.php" method="POST" class="space-y-5">
                <?= campo_csrf(); ?>
                <div class="relative">
                    <label for="email" class="font-bold text-slate-700">Correo electrónico</label>
                    <div class="relative mt-1">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500" aria-hidden="true"></i>
                        <input type="email" id="email" name="email"
                            class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/60 border border-white/50 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300 transition-all shadow-sm"
                            placeholder="tu@correo.com" required>
                    </div>
                </div>

                <div class="relative">
                    <label for="contrasena" class="font-bold text-slate-700">Contraseña</label>
                    <div class="relative mt-1">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500" aria-hidden="true"></i>
                        <input type="password" id="contrasena" name="contrasena"
                            class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/60 border border-white/50 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300 transition-all shadow-sm"
                            placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded bg-slate-100 border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="remember" class="text-slate-600">Recordarme</label>
                    </div>
                    <a href="recuperar_password.php" class="font-bold hover:underline text-blue-600">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-md hover:bg-blue-700 hover:scale-[1.02] transition-all duration-300 transform">
                    Entrar
                </button>
            </form>
            
            <div class="my-6 flex items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="mx-4 text-sm font-bold text-slate-400">O</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
            
            <div id="g_id_onload"
                 data-client_id="137288240716-5brrj54vv6bvncaroql5apraa90ig0v2.apps.googleusercontent.com"
                 data-callback="handleCredentialResponse"
                 data-context="signin">
            </div>
            <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline" data-text="signin_with" data-size="large" data-logo_alignment="left" data-width="100%"></div>

            <p class="text-center text-sm mt-8 text-slate-600">
                ¿No tienes cuenta? <a href="registro.php" class="font-bold hover:underline text-blue-600">Regístrate aquí</a>
            </p>
        </div>
    </div>

<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
    AOS.init();

    async function handleCredentialResponse(response) {
        try {
            const res = await fetch('google_login_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: response.credential })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                // Aquí podrías mostrar el error en un elemento del DOM en lugar de un alert
                alert('Error: ' + data.error);
            }
        } catch (error) {
            console.error("Error al contactar al servidor:", error);
            alert("No se pudo conectar con el servidor. Inténtalo de nuevo.");
        }
    }
</script>

</body>
</html>
