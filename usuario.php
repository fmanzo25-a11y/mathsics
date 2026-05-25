<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inicio_de_sesion.php");
    exit;
}

include_once 'conexion.php'; 

$id_visitante = $_SESSION['user_id'];
$nombre_visitante = $_SESSION['user_name'];
$id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_usuario === 0) {
    header("Location: menu.php");
    exit;
}

$error_message = null;
$success_message = null;

// Inicializar variables
$rendimiento_data = ['total_aciertos' => 0, 'total_errores' => 0];
$mejora_labels = [];
$mejora_data = [];
$radar_labels = ['Aritmética', 'Álgebra', 'Geometría', 'Estadística'];
$radar_data = [0, 0, 0, 0];
$win_rate = 0;
$total_ejercicios = 0;
$racha_actual = 0;
$nombre_usuario = "Usuario";
$foto_perfil = "images/sinfoto.jpeg";
$puntos_ranking = 0;
$materia_dominante = "Ninguna";
$posicion_ranking = "-";

try {
    $conn = Db::conectar();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Si hubo petición de reto
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retar'])) {
        $mensaje = "⚔️ ¡El usuario " . htmlspecialchars($nombre_visitante) . " te ha retado a un duelo! Búscalo en la arena de duelos.";
        $stmt_notif = $conn->prepare("INSERT INTO notificaciones (id_usuario, tipo, mensaje) VALUES (:id_usuario, 'duelo', :mensaje)");
        $stmt_notif->execute([
            ':id_usuario' => $id_usuario,
            ':mensaje' => $mensaje
        ]);
        $success_message = "¡Reto enviado exitosamente!";
    }

    // 1. Obtener Datos Básicos del Usuario
    $stmt_user = $conn->prepare("SELECT u.nombre, u.foto_de_perfil, u.racha, r.puntos 
                                 FROM usuarios u 
                                 LEFT JOIN ranking r ON u.id = r.id_usuario 
                                 WHERE u.id = :id");
    $stmt_user->execute(['id' => $id_usuario]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data) {
        die("Usuario no encontrado.");
    }
    
    $nombre_usuario = $user_data['nombre'];
    $foto_perfil = $user_data['foto_de_perfil'] ?? 'images/sinfoto.jpeg';
    $racha_actual = $user_data['racha'] ?? 0;
    $puntos_ranking = $user_data['puntos'] ?? 0;

    // Calcular posición en el ranking
    $stmt_pos = $conn->prepare("SELECT COUNT(*) + 1 as posicion FROM ranking WHERE puntos > :puntos");
    $stmt_pos->execute(['puntos' => $puntos_ranking]);
    $pos_data = $stmt_pos->fetch(PDO::FETCH_ASSOC);
    $posicion_ranking = $pos_data['posicion'] ?? "-";

    // 2. Rendimiento General
    $stmt_rendimiento = $conn->prepare("SELECT 
        SUM(CASE WHEN respuesta_correcta = 1 THEN 1 ELSE 0 END) as total_aciertos,
        SUM(CASE WHEN respuesta_correcta = 0 THEN 1 ELSE 0 END) as total_errores
        FROM resultados_ejercicios WHERE id_usuario = :id_usuario");
    $stmt_rendimiento->execute(['id_usuario' => $id_usuario]);
    $rendimiento_data_raw = $stmt_rendimiento->fetch(PDO::FETCH_ASSOC);
    
    $rendimiento_data['total_aciertos'] = $rendimiento_data_raw['total_aciertos'] ?? 0;
    $rendimiento_data['total_errores'] = $rendimiento_data_raw['total_errores'] ?? 0;
    
    $total_ejercicios = $rendimiento_data['total_aciertos'] + $rendimiento_data['total_errores'];
    if ($total_ejercicios > 0) {
        $win_rate = round(($rendimiento_data['total_aciertos'] / $total_ejercicios) * 100, 1);
    }

    // 3. Tasa de Mejora Semanal
    $stmt_mejora = $conn->prepare("SELECT 
        YEARWEEK(fecha, 1) as semana,
        AVG(respuesta_correcta) * 100 as puntuacion_promedio
        FROM resultados_ejercicios 
        WHERE id_usuario = :id_usuario
        GROUP BY semana ORDER BY semana ASC LIMIT 10");
    $stmt_mejora->execute(['id_usuario' => $id_usuario]);
    $mejora_raw_data = $stmt_mejora->fetchAll(PDO::FETCH_ASSOC);

    foreach ($mejora_raw_data as $row) {
        $week = substr($row['semana'], 4, 2);
        $mejora_labels[] = "Sem. " . (int)$week;
        $mejora_data[] = round($row['puntuacion_promedio'], 2);
    }
    
    // 4. Dominio por Tema
    $stmt_radar = $conn->prepare("SELECT tema, AVG(respuesta_correcta) * 100 as precision_tema 
        FROM resultados_ejercicios 
        WHERE id_usuario = :id_usuario AND tema IN ('Aritmética', 'Álgebra', 'Geometría', 'Estadística')
        GROUP BY tema");
    $stmt_radar->execute(['id_usuario' => $id_usuario]);
    $radar_raw_data = $stmt_radar->fetchAll(PDO::FETCH_ASSOC);
    
    $radar_dict = [];
    $max_precision = -1;
    foreach($radar_raw_data as $row) {
        $val = round($row['precision_tema'], 1);
        $radar_dict[$row['tema']] = $val;
        if ($val > $max_precision) {
            $max_precision = $val;
            $materia_dominante = $row['tema'];
        }
    }
    
    for ($i=0; $i < count($radar_labels); $i++) {
        $radar_data[$i] = $radar_dict[$radar_labels[$i]] ?? 0;
    }

} catch (PDOException $e) {
    $error_message = "Error al obtener las estadísticas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($nombre_usuario); ?></title>
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="min-h-screen text-slate-800 pb-12">
    
    <!-- Navbar -->
    <nav class="glass-panel sticky top-0 z-50 px-6 py-4 flex justify-between items-center shadow-sm">
        <a href="javascript:history.back()" class="text-blue-600 hover:text-blue-800 font-bold flex items-center transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
        <h1 class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">
            Perfil Público
        </h1>
        <div class="w-20"></div> <!-- Spacer -->
    </nav>

    <main class="container mx-auto px-4 mt-8 max-w-5xl">
        
        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
                <p class="font-bold">¡Éxito!</p>
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Perfil Header -->
        <div class="glass-panel rounded-3xl p-8 mb-8 flex flex-col md:flex-row items-center gap-8 border-t-4 border-indigo-400" data-aos="fade-up">
            <div class="relative">
                <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Avatar" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-xl">
                <div class="absolute -bottom-2 -right-2 bg-yellow-400 text-yellow-900 w-10 h-10 rounded-full flex items-center justify-center font-black shadow-lg border-2 border-white">
                    #<?php echo $posicion_ranking; ?>
                </div>
            </div>
            
            <div class="flex-1 text-center md:text-left">
                <h2 class="text-4xl font-black text-slate-800 mb-2"><?php echo htmlspecialchars($nombre_usuario); ?></h2>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm font-bold text-slate-600">
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full"><i class="fas fa-star mr-1"></i> <?php echo number_format($puntos_ranking); ?> Pts</span>
                    <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full"><i class="fas fa-fire mr-1"></i> Racha: <?php echo $racha_actual; ?> días</span>
                    <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full"><i class="fas fa-crown mr-1"></i> Experto en: <?php echo $materia_dominante; ?></span>
                </div>
            </div>

            <?php if ($id_usuario != $id_visitante): ?>
            <div class="mt-4 md:mt-0">
                <button id="btn-retar" data-id="<?php echo $id_usuario; ?>" class="bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white font-black py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all flex items-center gap-2">
                    <i class="fas fa-swords text-xl"></i> Retar a Duelo
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Win Rate -->
            <div class="glass-panel rounded-2xl p-6 flex flex-col justify-center items-center stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-500 text-3xl mb-3 shadow-inner">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-gray-500 font-bold uppercase text-xs tracking-wider">Aciertos (Win Rate)</h3>
                <p class="text-4xl font-black text-slate-700"><?php echo $win_rate; ?>%</p>
                <p class="text-xs text-gray-400 mt-1 font-semibold"><?php echo $rendimiento_data['total_aciertos']; ?> / <?php echo $total_ejercicios; ?> ejercicios</p>
            </div>

            <!-- Radar Chart -->
            <div class="glass-panel rounded-2xl p-6 col-span-1 md:col-span-2 flex flex-col stat-card" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-gray-500 font-bold uppercase text-xs tracking-wider mb-4 flex items-center"><i class="fas fa-radar mr-2"></i> Dominio por Materia</h3>
                <div class="relative h-64 w-full flex-1">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Mejora en el tiempo -->
        <div class="glass-panel rounded-2xl p-6 stat-card" data-aos="fade-up" data-aos-delay="300">
            <h3 class="text-gray-500 font-bold uppercase text-xs tracking-wider mb-4 flex items-center"><i class="fas fa-chart-line mr-2"></i> Evolución de Precisión (Últimas 10 semanas)</h3>
            <div class="relative h-64 w-full">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, duration: 800 });

        const btnRetar = document.getElementById('btn-retar');
        if (btnRetar) {
            btnRetar.addEventListener('click', async function() {
                this.disabled = true;
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin text-xl"></i> Enviando...';
                
                const formData = new FormData();
                formData.append('id_oponente', this.dataset.id);
                formData.append('tema', 'Aleatorio'); // Por defecto
                
                try {
                    const response = await fetch('duelo_api.php?action=retar', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        window.location.href = `sala_espera.php?id_duelo=${data.id_duelo}`;
                    } else {
                        alert('Error al retar: ' + (data.message || 'Desconocido'));
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexión.');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            });
        }

        // Gráfico Radar
        const ctxRadar = document.getElementById('radarChart').getContext('2d');
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($radar_labels); ?>,
                datasets: [{
                    label: 'Precisión (%)',
                    data: <?php echo json_encode($radar_data); ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color: 'rgba(0,0,0,0.1)' },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        pointLabels: { font: { family: 'Nunito', weight: 'bold', size: 12 }, color: '#475569' },
                        ticks: { min: 0, max: 100, stepSize: 20, display: false }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Gráfico de Línea
        const ctxLine = document.getElementById('lineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($mejora_labels); ?>,
                datasets: [{
                    label: 'Precisión Promedio (%)',
                    data: <?php echo json_encode($mejora_data); ?>,
                    borderColor: 'rgba(249, 115, 22, 1)',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgba(249, 115, 22, 1)',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { borderDash: [5, 5], color: 'rgba(0,0,0,0.05)' }, ticks: { font: { family: 'Nunito', weight: 'bold' }, color: '#64748b' } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Nunito', weight: 'bold' }, color: '#64748b' } }
                },
                plugins: { legend: { display: false }, tooltip: { titleFont: { family: 'Nunito', size: 14 }, bodyFont: { family: 'Nunito', size: 14 } } }
            }
        });
    </script>
    <script src="toast_notifications.js"></script>
</body>
</html>
