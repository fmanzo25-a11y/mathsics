<?php
include_once 'conexion.php';
$mensaje = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        $conn = Db::conectar();

        // Buscar un usuario con ese token que aún no esté verificado
        $stmt = $conn->prepare("SELECT id, tipo FROM usuarios WHERE token_verificacion = :token AND verificado = 0");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Si el usuario es 'Nino', su cuenta sigue inactiva hasta que el padre la apruebe.
            // Si es 'Normal', se activa.
            $nuevo_estado = ($user['tipo'] === 'Nino') ? 'Inactiva' : 'Activa';

            // Actualizamos el usuario: lo marcamos como verificado y borramos el token.
            $stmtUpdate = $conn->prepare(
                "UPDATE usuarios SET verificado = 1, estado = :estado, token_verificacion = NULL WHERE id = :id"
            );
            $stmtUpdate->execute([':estado' => $nuevo_estado, ':id' => $user['id']]);

            if ($nuevo_estado === 'Activa') {
                $mensaje = "¡Tu cuenta ha sido activada con éxito! Ya puedes iniciar sesión.";
            } else {
                $mensaje = "¡Tu correo ha sido verificado! Tu cuenta ahora está pendiente de activación por parte de tu tutor.";
            }

        } else {
            $mensaje = "Este enlace de verificación no es válido o ya ha sido utilizado.";
        }
    } catch (Exception $e) {
        $mensaje = "Error al procesar la solicitud: " . $e->getMessage();
    }
} else {
    $mensaje = "No se proporcionó un token de verificación.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Cuenta</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
        <h1 class="text-2xl font-bold mb-4">
            <?php echo ($user) ? '¡Felicidades!' : '¡Oops!'; ?>
        </h1>
        <p class="text-gray-700 mb-6"><?php echo htmlspecialchars($mensaje); ?></p>
        <a href="index.php" class="bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-indigo-700 transition">
            Ir a Iniciar Sesión
        </a>
    </div>
</body>
</html>