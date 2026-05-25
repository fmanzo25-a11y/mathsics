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
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Notificaciones - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Nunito', sans-serif; 
            background-color: #f0f9ff;
            color: #334155;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(224, 242, 254, 0.8) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(238, 242, 255, 0.8) 0%, transparent 40%);
        }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        /* Animación para la desaparición de la tarjeta */
        @keyframes fade-out {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.95); }
        }
        .fade-out {
            animation: fade-out 0.4s ease-out forwards;
        }
    </style>
</head>
<body class="min-h-screen pb-10">

    <header class="sticky top-0 z-50 glass-panel border-b border-white/60">
        <div class="max-w-7xl mx-auto p-4 flex justify-between items-center">
            <h1 class="text-2xl sm:text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center">
                <i class="fas fa-bell mr-3 text-indigo-500"></i>Notificaciones
            </h1>
            <a href="menu.php" class="text-slate-600 hover:text-blue-600 font-bold flex items-center gap-2 group transition-all bg-white/50 px-4 py-2 rounded-full shadow-sm hover:shadow-md border border-white/80">
                <i class="fas fa-arrow-left transition-transform group-hover:-translate-x-1"></i> <span class="hidden sm:inline">Volver</span>
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto p-4 md:p-6 mt-6 space-y-4">
        
        <?php if (empty($notificaciones)): ?>
            <div class="glass-panel rounded-3xl text-center py-20 px-6 border border-white/80">
                <div class="w-24 h-24 mx-auto bg-slate-100 rounded-full flex items-center justify-center mb-6 shadow-inner">
                    <i class="fas fa-bell-slash text-5xl text-slate-400"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-700">Todo está tranquilo por aquí</h2>
                <p class="text-slate-500 font-bold mt-2">No tienes notificaciones nuevas en este momento.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($notificaciones as $notificacion): ?>
                <!-- Tarjeta de Notificación -->
                <div id="notificacion-<?php echo $notificacion['id_notificacion']; ?>" class="glass-panel rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl border border-white/80 relative overflow-hidden">
                    <!-- Indicador de No Leída -->
                    <?php if ($notificacion['leida'] == '0'): ?>
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.6)]"></div>
                    <?php endif; ?>

                    <!-- Icono según el tipo -->
                    <?php 
                        if ($notificacion['tipo'] == 'peticion') {
                            $bg_color = 'bg-orange-100 text-orange-500';
                            $icon_class = 'fa-user-plus';
                        } elseif ($notificacion['tipo'] == 'duelo') {
                            $bg_color = 'bg-red-100 text-red-500';
                            $icon_class = 'fa-swords';
                        } else {
                            $bg_color = 'bg-blue-100 text-blue-500';
                            $icon_class = 'fa-info-circle';
                        }
                    ?>
                    <div class="flex-shrink-0 w-12 h-12 rounded-full <?php echo $bg_color; ?> flex items-center justify-center shadow-inner ml-2 sm:ml-0">
                        <i class="fas <?php echo $icon_class; ?> text-xl"></i>
                    </div>

                    <div class="flex-1 ml-2 sm:ml-0">
                        <p class="text-slate-800 font-bold text-lg mb-1 leading-tight"><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                        <p class="text-slate-500 font-bold text-xs"><i class="fas fa-clock mr-1"></i><?php echo date('d M Y, H:i', strtotime($notificacion['fecha'])); ?></p>
                    </div>

                    <!-- Botones de Acción (solo para peticiones) -->
                    <?php if($notificacion['tipo'] == 'peticion' && $notificacion['leida'] == '0'): ?>
                        <form class="notification-form mt-4 sm:mt-0 flex w-full sm:w-auto items-center gap-2 justify-end pl-2 sm:pl-0">
                            <input type="hidden" name="id_notificacion" value="<?php echo $notificacion['id_notificacion']; ?>">
                            <input type="hidden" name="id_usuario_origen" value="<?php echo $notificacion['id_usuario_origen']; ?>">
                            
                            <button type="submit" name="accion" value="aceptar" class="flex-1 sm:flex-none bg-gradient-to-r from-emerald-400 to-emerald-500 hover:from-emerald-500 hover:to-emerald-600 text-white font-black py-2 px-4 rounded-xl transition transform hover:scale-105 shadow-md flex items-center justify-center">
                                <i class="fas fa-check sm:mr-2"></i><span class="hidden sm:inline">Aceptar</span>
                            </button>
                            <button type="submit" name="accion" value="rechazar" class="flex-1 sm:flex-none bg-gradient-to-r from-rose-400 to-rose-500 hover:from-rose-500 hover:to-rose-600 text-white font-black py-2 px-4 rounded-xl transition transform hover:scale-105 shadow-md flex items-center justify-center">
                                <i class="fas fa-times sm:mr-2"></i><span class="hidden sm:inline">Rechazar</span>
                            </button>
                        </form>
                    <?php elseif($notificacion['tipo'] == 'duelo'): ?>
                        <form class="notification-form mt-4 sm:mt-0 flex w-full sm:w-auto items-center justify-end pl-2 sm:pl-0">
                            <!-- En este caso, id_usuario_origen tiene guardado el id_duelo gracias a nuestra API de duelos -->
                            <input type="hidden" name="id_duelo" value="<?php echo $notificacion['id_usuario_origen']; ?>">
                            <input type="hidden" name="id_notificacion" value="<?php echo $notificacion['id_notificacion']; ?>">
                            
                            <button type="submit" name="accion" value="aceptar_reto" class="bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white font-black py-2 px-6 rounded-xl transition transform hover:scale-105 shadow-md flex items-center justify-center">
                                <i class="fas fa-swords sm:mr-2"></i><span class="hidden sm:inline">Aceptar Reto</span>
                            </button>
                            <button type="submit" name="accion" value="rechazar_reto" class="ml-2 bg-gradient-to-r from-gray-400 to-gray-500 hover:from-gray-500 hover:to-gray-600 text-white font-black py-2 px-4 rounded-xl transition transform hover:scale-105 shadow-md flex items-center justify-center">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    <?php endif; ?>  
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.querySelectorAll('.notification-form').forEach(form => {
            form.addEventListener('submit', async function(event) {
                event.preventDefault(); 

                const formData = new FormData(this);
                const action = event.submitter.value; 
                formData.append('accion', action);

                const card = this.closest('[id^="notificacion-"]');
                const btns = form.querySelectorAll('button');
                btns.forEach(b => b.disabled = true); // Disable to prevent double click

                try {
                    let endpoint = 'notificaciones_api.php';
                    if (action === 'aceptar_reto' || action === 'rechazar_reto') {
                        endpoint = 'duelo_api.php?action=' + action;
                    }
                    
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        if (action === 'aceptar_reto' && result.id_duelo) {
                            window.location.href = `duelo.php?id_duelo=${result.id_duelo}`;
                            return;
                        }
                        card.classList.add('fade-out');
                        setTimeout(() => card.remove(), 400);
                    } else {
                        alert('Error: ' + result.message);
                        btns.forEach(b => b.disabled = false);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Hubo un error de conexión.');
                    btns.forEach(b => b.disabled = false);
                }
            });
        });
    </script>
</body>
</html>
