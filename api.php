<?php

require_once 'seguridad.php';
iniciar_sesion_segura();
include_once 'conexion.php';

// Función para redirigir con un mensaje de error
function redirigir_con_error($mensaje) {
    header("Location: inicio_de_sesion.php?error=" . urlencode($mensaje));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar CSRF
    validar_token_csrf($_POST['csrf_token'] ?? '');

    $email = trim($_POST['email'] ?? '');
    $contrasena_plana = trim($_POST['contrasena'] ?? '');

    if (empty($email) || empty($contrasena_plana)) {
        redirigir_con_error("El correo electrónico y la contraseña son obligatorios.");
    }

    try {
        $conn = Db::conectar();

        $stmt = $conn->prepare("SELECT id, nombre, contrasena, tipo, estado FROM usuarios WHERE correo = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['estado'] === 'Activa') {
                if (password_verify($contrasena_plana, $user['contrasena'])) {
                    // Prevenir Session Fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = sanitizar($user['nombre']);
                    $_SESSION['tipo_cuenta'] = $user['tipo'];

                    header("Location: menu.php");
                    exit();
                } else {
                    redirigir_con_error("La contraseña es incorrecta.");
                }
            } else {
                redirigir_con_error("Esta cuenta está inactiva y no puede iniciar sesión.");
            }
        } else {
            redirigir_con_error("No se encontró un usuario con ese correo electrónico.");
        }

    } catch (PDOException $e) {
        error_log("Database Error in login_api.php: " . $e->getMessage());
        redirigir_con_error("Error interno del servidor. Por favor, inténtalo más tarde.");
    }
} else {
    redirigir_con_error("Petición inválida.");
}

?>