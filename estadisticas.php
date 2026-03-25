<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inicio_de_sesion.php");
    exit;
}

include_once 'conexion.php'; 

$id_usuario = $_SESSION['user_id'];
$error_message = null;

// Inicializar variables para evitar errores si no hay datos
$rendimiento_data = ['total_aciertos' => 0, 'total_errores' => 0];
$mejora_labels = [];
$mejora_data = [];
$tema_favorito_data = ['tema' => '¡A Jugar!', 'total_jugados' => 0];

try {
    $conn = Db::conectar();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Rendimiento General (Aciertos vs. Errores)
    $stmt_rendimiento = $conn->prepare("SELECT 
        SUM(CASE WHEN respuesta_correcta = 1 THEN 1 ELSE 0 END) as total_aciertos,
        SUM(CASE WHEN respuesta_correcta = 0 THEN 1 ELSE 0 END) as total_errores
        FROM resultados_ejercicios WHERE id_usuario = :id_usuario");
    $stmt_rendimiento->execute(['id_usuario' => $id_usuario]);
    $rendimiento_data_raw = $stmt_rendimiento->fetch(PDO::FETCH_ASSOC);
    $rendimiento_data['total_aciertos'] = $rendimiento_data_raw['total_aciertos'] ?? 0;
    $rendimiento_data['total_errores'] = $rendimiento_data_raw['total_errores'] ?? 0;

    // 2. Tasa de Mejora Semanal
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
        $mejora_labels[] = "Semana " . (int)$week;
        $mejora_data[] = round($row['puntuacion_promedio'], 2);
    }
    
    // ✨ 3. NUEVO: Obtener el Tema Preferido ✨
    $stmt_favorito = $conn->prepare(
        "SELECT tema, COUNT(*) as total_jugados
         FROM resultados_ejercicios
         WHERE id_usuario = :id_usuario
         GROUP BY tema
         ORDER BY total_jugados DESC
         LIMIT 1"
    );
    $stmt_favorito->execute(['id_usuario' => $id_usuario]);
    $favorito_raw = $stmt_favorito->fetch(PDO::FETCH_ASSOC);
    if ($favorito_raw) {
        $tema_favorito_data = $favorito_raw;
    }

} catch (PDOException $e) {
    $error_message = "Error al obtener las estadísticas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tus Estadísticas - Mathsics</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f0f9ff; }
        /* ✨ Contenedor de gráficas responsivo ✨ */
        .chart-container {
            position: relative;
            height: 250px; /* Altura base para móviles */
            width: 100%;
        }
        @media (min-width: 640px) {
            .chart-container {
                height: 300px; /* Un poco más de altura en pantallas grandes */
            }
        }
        @keyframes fade-in-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in-up { animation: fade-in-up 0.6s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-100">

    <header class="bg-white/80 backdrop-blur-sm sticky top-0 z-10 border-b border-gray-200">
        <div class="max-w-5xl mx-auto p-4 flex justify-between items-center">
            <h1 class="text-2xl font-black text-gray-800">Tu Progreso</h1>
            <a href="menu.php" class="text-gray-600 hover:text-blue-600 font-bold flex items-center gap-2 group">
                <i class="fas fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                Volver
            </a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4 md:p-6">
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-1 bg-gradient-to-br from-purple-500 to-indigo-600 text-white p-6 rounded-2xl shadow-lg flex flex-col justify-center items-center text-center animate-fade-in-up">
                <i class="fas fa-star text-4xl text-yellow-300 mb-3" style="filter: drop-shadow(0 0 5px currentColor);"></i>
                <h2 class="text-sm font-bold uppercase tracking-wider opacity-80">Tu Tema Preferido</h2>
                <p class="text-4xl font-black mt-1"><?php echo htmlspecialchars($tema_favorito_data['tema']); ?></p>
                <p class="text-sm font-bold mt-2 opacity-70"><?php echo htmlspecialchars($tema_favorito_data['total_jugados']); ?> ejercicios completados</p>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-lg animate-fade-in-up" style="animation-delay: 100ms;">
                <h2 class="text-xl font-bold text-gray-700 mb-4">Rendimiento General</h2>
                <div class="chart-container">
                    <canvas id="rendimientoChart"></canvas>
                </div>
            </div>

            <div class="lg:col-span-3 bg-white p-6 rounded-2xl shadow-lg animate-fade-in-up" style="animation-delay: 200ms;">
                <h2 class="text-xl font-bold text-gray-700 mb-4">Tasa de Mejora Semanal (%)</h2>
                <div class="chart-container">
                    <canvas id="mejoraChart"></canvas>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Gráfico de Rendimiento (Aciertos vs. Errores)
        const rendimientoCtx = document.getElementById('rendimientoChart').getContext('2d');
        const totalRendimiento = <?php echo $rendimiento_data['total_aciertos'] + $rendimiento_data['total_errores']; ?>;
        new Chart(rendimientoCtx, {
            type: 'doughnut',
            data: {
                labels: ['Aciertos', 'Errores'],
                datasets: [{
                    data: [<?php echo $rendimiento_data['total_aciertos']; ?>, <?php echo $rendimiento_data['total_errores']; ?>],
                    backgroundColor: ['#4ade80', '#f87171'],
                    borderColor: ['#ffffff', '#ffffff'],
                    borderWidth: 4,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '70%',
                plugins: { 
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 14, weight: 'bold' } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed !== null) {
                                    const percentage = (context.parsed / totalRendimiento * 100).toFixed(1) + '%';
                                    label += `${context.raw} (${percentage})`;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Tasa de Mejora
        const mejoraCtx = document.getElementById('mejoraChart').getContext('2d');
        new Chart(mejoraCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($mejora_labels); ?>,
                datasets: [{
                    label: 'Precisión Promedio',
                    data: <?php echo json_encode($mejora_data); ?>,
                    fill: true,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>