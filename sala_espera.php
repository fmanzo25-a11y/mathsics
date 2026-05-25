<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inicio_de_sesion.php");
    exit();
}

include_once 'conexion.php';

$id_usuario = $_SESSION['user_id'];
$id_duelo = $_GET['id_duelo'] ?? 0;

if (!$id_duelo) {
    header("Location: menu.php");
    exit();
}

try {
    $conn = Db::conectar();
    $stmt = $conn->prepare("
        SELECT d.*, u2.nombre as oponente_nombre, u2.foto_de_perfil as oponente_foto 
        FROM duelos d 
        LEFT JOIN usuarios u2 ON d.jugador2_id = u2.id 
        WHERE d.id = :id_duelo AND d.jugador1_id = :id_usuario
    ");
    $stmt->execute([':id_duelo' => $id_duelo, ':id_usuario' => $id_usuario]);
    $duelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$duelo) {
        header("Location: menu_duelo.php");
        exit();
    }
} catch (Exception $e) {
    die("Error de conexión.");
}

$nombre_usuario = $_SESSION['user_name'];
$avatar_usuario = $_SESSION['user_avatar'] ?? 'images/sinfoto.jpeg';
$oponente_nombre = $duelo['oponente_nombre'] ?? 'Oponente';
$oponente_foto = $duelo['oponente_foto'] ?? 'images/sinfoto.jpeg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Espera - Duelo</title>
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f8fafc; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }
        @keyframes pulse-vs { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
        .vs-pulse { animation: pulse-vs 2s infinite cubic-bezier(0.4, 0, 0.6, 1); }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4 relative overflow-hidden">
    <!-- Background Blobs -->
    <div class="fixed inset-0 z-[-1] pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
        <div class="absolute top-[40%] right-[-10%] w-96 h-96 bg-red-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
    </div>

    <div class="w-full max-w-3xl text-center glass-panel rounded-3xl p-8 sm:p-12 border-t-4 border-orange-500 shadow-2xl relative">
        
        <a href="usuario.php?id=<?php echo $duelo['jugador2_id']; ?>" class="absolute top-6 left-6 text-slate-400 hover:text-red-500 transition font-bold">
            <i class="fas fa-arrow-left"></i> Cancelar
        </a>

        <h2 class="text-3xl sm:text-4xl font-black text-slate-800 mb-2">¡Reto Enviado! ⚔️</h2>
        <p class="text-lg text-slate-500 font-bold mb-10">Esperando a que <span class="text-orange-500"><?php echo htmlspecialchars($oponente_nombre); ?></span> acepte el desafío...</p>
        
        <div class="flex items-center justify-center gap-4 sm:gap-10">
            <div class="flex flex-col items-center">
                <img src="<?php echo htmlspecialchars($avatar_usuario); ?>" class="w-24 h-24 sm:w-32 sm:h-32 object-cover rounded-full border-4 border-orange-200 shadow-lg relative z-10">
                <p class="mt-4 font-black text-lg text-slate-700"><?php echo htmlspecialchars($nombre_usuario); ?></p>
            </div>
            
            <div class="vs-pulse text-6xl sm:text-8xl font-black text-orange-500 z-0">VS</div>
            
            <div class="flex flex-col items-center opacity-70">
                <img src="<?php echo htmlspecialchars($oponente_foto); ?>" class="w-24 h-24 sm:w-32 sm:h-32 object-cover rounded-full border-4 border-dashed border-gray-300 shadow-inner relative z-10 filter grayscale">
                <p class="mt-4 font-bold text-lg text-slate-500"><i class="fas fa-clock mr-1"></i> Esperando...</p>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center">
            <div class="flex space-x-2 text-orange-500 text-3xl mb-4">
                <i class="fas fa-circle transition-all duration-300 animate-bounce" style="animation-delay: 0s;"></i>
                <i class="fas fa-circle transition-all duration-300 animate-bounce" style="animation-delay: 0.2s;"></i>
                <i class="fas fa-circle transition-all duration-300 animate-bounce" style="animation-delay: 0.4s;"></i>
            </div>
            <p class="text-sm font-bold text-gray-400">Si el jugador rechaza, serás devuelto a su perfil.</p>
        </div>
    </div>

    <script>
        const idDuelo = <?php echo $id_duelo; ?>;
        const idOponente = <?php echo $duelo['jugador2_id']; ?>;
        
        setInterval(async () => {
            try {
                const response = await fetch(`duelo_api.php?action=verificar_invitacion&id_duelo=${idDuelo}`);
                if (!response.ok) return;
                const data = await response.json();
                
                if (data.status === 'aceptado') {
                    window.location.href = `duelo.php?id_duelo=${idDuelo}`;
                } else if (data.status === 'rechazado' || data.status === 'error') {
                    alert('El reto fue rechazado o cancelado.');
                    window.location.href = `usuario.php?id=${idOponente}`;
                }
            } catch (error) {
                console.error('Error sondeando:', error);
            }
        }, 3000);
    </script>
</body>
</html>
