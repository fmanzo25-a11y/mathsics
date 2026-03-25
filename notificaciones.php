<?php
include_once 'conexion.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_usuario_actual = $_SESSION['user_id'];

try {
    $conn = Db::conectar(); 

   
    $stmt = $conn->prepare(
        "SELECT * FROM notificaciones 
         WHERE id_usuario = :id 
         ORDER BY leida ASC, fecha DESC"
    );
    $stmt->bindValue(':id', $id_usuario_actual, PDO::PARAM_INT);
    $stmt->execute();
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error en notificaciones.php: " . $e->getMessage());
    $notificaciones = []; 
    die("Error al cargar las notificaciones.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Notificaciones - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* Animación para la desaparición de la tarjeta */
        @keyframes fade-out {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.95); }
        }
        .fade-out {
            animation: fade-out 0.5s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-100">

    <header class="sticky top-0 z-50 w-full bg-indigo-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
            <h1 class="text-2xl font-bold">Mis Notificaciones</h1>
            <a href="menu.php" class="text-indigo-200 hover:text-white transition">Volver al Menú</a>
        </div>
    </header>

    <main class="max-w-2xl mx-auto p-4 md:p-6 space-y-4">
        
        <?php if (empty($notificaciones)): ?>
            <div class="text-center py-20">
                <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-600">Todo está tranquilo por aquí</h2>
                <p class="text-gray-500 mt-2">No tienes notificaciones nuevas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notificaciones as $notificacion): ?>
            <!-- Tarjeta de Notificación -->
            <div id="notificacion-<?php echo $notificacion['id_notificacion']; ?>" class="bg-white rounded-xl shadow-md p-5 flex items-start gap-4 transition-all duration-500">
                <!-- Indicador de No Leída -->
                <?php if ($notificacion['leida'] == '0'): ?>
                    <div class="w-3 h-3 bg-indigo-500 rounded-full mt-1.5 flex-shrink-0"></div>
                <?php else: ?>
                    <div class="w-3 h-3"></div>
                <?php endif; ?>

                <div class="flex-1">
                    <p class="text-gray-800"><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                    <small class="text-gray-400"><?php echo date('d M Y, H:i', strtotime($notificacion['fecha'])); ?></small>

                    <!-- Botones de Acción (solo para peticiones) -->
                    <?php if($notificacion['tipo'] == 'peticion' && $notificacion['leida'] == '0'): ?>
                        <form class="notification-form mt-4 flex items-center gap-4">
                            <input type="hidden" name="id_notificacion" value="<?php echo $notificacion['id_notificacion']; ?>">
                            <input type="hidden" name="id_usuario_origen" value="<?php echo $notificacion['id_usuario_origen']; ?>">
                            
                            <button type="submit" name="accion" value="aceptar" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition transform hover:scale-105">
                                <i class="fas fa-check mr-2"></i>Aceptar
                            </button>
                            <button type="submit" name="accion" value="rechazar" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition transform hover:scale-105">
                                <i class="fas fa-times mr-2"></i>Rechazar
                            </button>
                        </form>
                    <?php endif; ?>  
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
        document.querySelectorAll('.notification-form').forEach(form => {
            form.addEventListener('submit', async function(event) {
                event.preventDefault(); // Evita que la página se recargue

                const formData = new FormData(this);
                // Obtenemos el botón que fue presionado para saber la acción
                const action = event.submitter.value; 
                formData.append('accion', action);

                const card = this.closest('[id^="notificacion-"]');

                try {
                    const response = await fetch('notificaciones_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // ¡Animación! La tarjeta se desvanece suavemente
                        card.classList.add('fade-out');
                        // Esperamos que termine la animación para quitarla del todo
                        setTimeout(() => {
                            card.remove();
                        }, 500);
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error al procesar la notificación:', error);
                    alert('Hubo un error de conexión.');
                }
            });
        });
    </script>
</body>
</html>
