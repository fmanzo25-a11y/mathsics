<?php
// seguridad.php

// 1. Configuración Segura de Sesiones (Antes de session_start)
// Llamar a esta función SIEMPRE antes de session_start() en los archivos principales si es posible,
// o configurar estos valores globalmente en php.ini para el entorno de producción.
function iniciar_sesion_segura() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');
        // ini_set('session.cookie_secure', 1); // Descomentar en producción (requiere HTTPS)
        session_start();
    }
}

// 2. Generación de Token CSRF
function generar_token_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 3. Validación de Token CSRF
function validar_token_csrf($token_recibido) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_recibido)) {
        // En caso de que el token sea inválido o falte, se detiene la ejecución o se lanza un error.
        die("Error 403: Petición inválida o expirada (CSRF Error). Por favor, recarga la página e intenta de nuevo.");
    }
    return true;
}

// 4. Inyección del campo HTML para el Token CSRF
function campo_csrf() {
    $token = generar_token_csrf();
    return '<input type="hidden" name="csrf_token" id="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// 5. Cabeceras HTTP de Seguridad
function inyectar_cabeceras_seguridad() {
    if (!headers_sent()) {
        header("X-Frame-Options: SAMEORIGIN"); // Previene Clickjacking
        header("X-XSS-Protection: 1; mode=block"); // Fuerza protección XSS en navegadores antiguos
        header("X-Content-Type-Options: nosniff"); // Previene MIME-sniffing
        // header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Descomentar en producción para forzar HTTPS
    }
}

// 6. Sanitización básica para mostrar texto por pantalla (XSS)
function sanitizar($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}
?>
