<?php
// 1. Iniciar la sesión para poder acceder a ella.
session_start();

// 2. Eliminar todas las variables de sesión.
$_SESSION = [];

// 3. Destruir la sesión por completo.
// Esto también elimina la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Redirigir al usuario a la página de inicio de sesión.
// Usamos la ruta absoluta para asegurar que siempre funcione.
header("Location: index.php");
exit();
?>
