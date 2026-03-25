<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: menu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="index.php" class="text-5xl font-black text-blue-600 tracking-wide">Mathsics</a>
            <p class="text-slate-500 mt-2">¡Qué bueno verte de nuevo! Ingresa para continuar.</p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            <h2 class="text-2xl font-bold mb-6 text-center text-slate-800">Iniciar Sesión</h2>

            <?php if (isset($_GET['error'])) : ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">¡Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>
            
            <form action="api.php" method="POST" class="space-y-5">
                <div class="relative">
                    <label for="email" class="font-bold text-slate-700">Correo electrónico</label>
                    <div class="relative mt-1">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" id="email" name="email"
                            class="w-full pl-12 pr-4 py-3 rounded-lg bg-slate-50 border border-slate-200 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                            placeholder="tu@correo.com" required>
                    </div>
                </div>

                <div class="relative">
                    <label for="contrasena" class="font-bold text-slate-700">Contraseña</label>
                    <div class="relative mt-1">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="contrasena" name="contrasena"
                            class="w-full pl-12 pr-4 py-3 rounded-lg bg-slate-50 border border-slate-200 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                            placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded bg-slate-100 border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="remember" class="text-slate-600">Recordarme</label>
                    </div>
                    <a href="#" class="font-bold hover:underline text-blue-600">¿Olvidaste tu contraseña?</a>
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

<script>
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
