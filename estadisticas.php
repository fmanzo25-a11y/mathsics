<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inicio_de_sesion.php");
    exit;
}

include_once 'conexion.php'; 

$id_usuario = $_SESSION['user_id'];
$error_message = null;

// Inicializar variables
$rendimiento_data = ['total_aciertos' => 0, 'total_errores' => 0];
$mejora_labels = [];
$mejora_data = [];
$radar_labels = ['Aritmética', 'Álgebra', 'Geometría', 'Estadística'];
$radar_data = [0, 0, 0, 0];
$win_rate = 0;
$total_ejercicios = 0;
$racha_actual = 0;

try {
    $conn = Db::conectar();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Obtener Racha Actual del Usuario
    $stmt_user = $conn->prepare("SELECT racha FROM usuarios WHERE id = :id");
    $stmt_user->execute(['id' => $id_usuario]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $racha_actual = $user_data['racha'];
    }

    // 2. Rendimiento General (Aciertos vs. Errores)
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
        $mejora_labels[] = "Semana " . (int)$week;
        $mejora_data[] = round($row['puntuacion_promedio'], 2);
    }
    
    // 4. Dominio por Tema (Gráfico Radar)
    $stmt_radar = $conn->prepare("SELECT tema, AVG(respuesta_correcta) * 100 as precision_tema 
        FROM resultados_ejercicios 
        WHERE id_usuario = :id_usuario AND tema IN ('Aritmética', 'Álgebra', 'Geometría', 'Estadística')
        GROUP BY tema");
    $stmt_radar->execute(['id_usuario' => $id_usuario]);
    $radar_raw_data = $stmt_radar->fetchAll(PDO::FETCH_ASSOC);
    
    $radar_dict = [];
    foreach($radar_raw_data as $row) {
        $radar_dict[$row['tema']] = round($row['precision_tema'], 1);
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
    <title>Mathsics - Centro de Comando</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="stylesheet" href="css/global.css">
    
    <style>
        body { 
            color: #334155;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(224, 242, 254, 0.8) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(238, 242, 255, 0.8) 0%, transparent 40%);
        }

        .chart-container {
            position: relative;
            height: 300px; 
            width: 100%;
        }

        .stat-card {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="min-h-screen pb-10 relative">

    <header class="sticky top-0 z-50 glass-panel border-b border-white/60">
        <div class="max-w-7xl mx-auto p-4 flex justify-between items-center">
            <h1 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                <i class="fas fa-chart-network mr-2 text-indigo-500"></i>Centro de Comando
            </h1>
            <a href="menu.php" class="text-slate-600 hover:text-blue-600 font-bold flex items-center gap-2 group transition-all bg-white/50 px-4 py-2 rounded-full shadow-sm hover:shadow-md border border-white/80">
                <i class="fas fa-arrow-left transition-transform group-hover:-translate-x-1"></i> Volver
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 mt-6 relative z-10">
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Tarjetas de Estadísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-panel rounded-2xl p-6 border-b-4 border-emerald-500 stat-card" data-aos="fade-up" data-aos-delay="0">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-500 text-xs font-black tracking-widest uppercase mb-1">Precisión General</p>
                        <h2 class="text-5xl font-black text-emerald-600 flex items-baseline">
                            <span class="counter" data-target="<?php echo $win_rate; ?>">0</span><span class="text-2xl ml-1">%</span>
                        </h2>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 text-xl shadow-inner">
                        <i class="fas fa-bullseye-arrow"></i>
                    </div>
                </div>
            </div>

            <div class="glass-panel rounded-2xl p-6 border-b-4 border-orange-500 stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-500 text-xs font-black tracking-widest uppercase mb-1">Ejercicios Resueltos</p>
                        <h2 class="text-5xl font-black text-orange-600">
                            <span class="counter" data-target="<?php echo $total_ejercicios; ?>">0</span>
                        </h2>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-xl shadow-inner">
                        <i class="fas fa-infinity"></i>
                    </div>
                </div>
            </div>

            <div class="glass-panel rounded-2xl p-6 border-b-4 border-pink-500 stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-500 text-xs font-black tracking-widest uppercase mb-1">Días en Racha</p>
                        <h2 class="text-5xl font-black text-pink-600 flex items-center gap-2">
                            <span class="counter" data-target="<?php echo $racha_actual; ?>">0</span> <i class="fas fa-fire text-3xl"></i>
                        </h2>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center text-pink-600 text-xl shadow-inner">
                        <i class="fas fa-calendar-star"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Gráfico de Radar (Habilidades) -->
            <div class="glass-panel p-6 rounded-3xl" data-aos="zoom-in" data-aos-delay="300">
                <h2 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-3">
                    <div class="bg-sky-100 text-sky-600 w-10 h-10 rounded-full flex items-center justify-center shadow-inner"><i class="fas fa-radar"></i></div>
                    Mapa de Dominio por Tema
                </h2>
                <div class="chart-container flex justify-center">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>

            <!-- Gráfico de Dona (Aciertos vs Errores) -->
            <div class="glass-panel p-6 rounded-3xl" data-aos="zoom-in" data-aos-delay="400">
                <h2 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-3">
                    <div class="bg-purple-100 text-purple-600 w-10 h-10 rounded-full flex items-center justify-center shadow-inner"><i class="fas fa-chart-pie-simple"></i></div>
                    Rendimiento General
                </h2>
                <div class="chart-container">
                    <canvas id="rendimientoChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Línea (Tasa de Mejora) -->
        <div class="glass-panel p-6 md:p-8 rounded-3xl" data-aos="fade-up" data-aos-delay="500">
            <h2 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-3">
                <div class="bg-indigo-100 text-indigo-600 w-10 h-10 rounded-full flex items-center justify-center shadow-inner"><i class="fas fa-chart-line-up"></i></div>
                Evolución de Precisión (Semanal)
            </h2>
            <div class="chart-container" style="height: 350px;">
                <canvas id="mejoraChart"></canvas>
            </div>
        </div>

    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Inicializar animaciones AOS
        AOS.init({ once: true, offset: 50, duration: 800 });

        // Animación de Contadores Numéricos
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            counter.innerText = '0';
            const updateCounter = () => {
                const target = +counter.getAttribute('data-target');
                const c = +counter.innerText;
                const increment = target / 30; // Ajustar velocidad aquí
                if (c < target) {
                    counter.innerText = `${Math.ceil(c + increment)}`;
                    setTimeout(updateCounter, 30);
                } else {
                    counter.innerText = target;
                }
            };
            setTimeout(updateCounter, 500); // Pequeño delay inicial
        });

        // Configuración Global de Chart.js para Tema Luminoso
        Chart.defaults.color = '#64748b'; // slate-500
        Chart.defaults.font.family = "'Nunito', sans-serif";
        Chart.defaults.font.weight = 'bold';

        // 1. Gráfico de Radar
        const radarCtx = document.getElementById('radarChart').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($radar_labels); ?>,
                datasets: [{
                    label: 'Precisión (%)',
                    data: <?php echo json_encode($radar_data); ?>,
                    backgroundColor: 'rgba(14, 165, 233, 0.2)', // sky-500 translúcido
                    borderColor: '#0ea5e9', // sky-500
                    pointBackgroundColor: '#0284c7', // sky-600
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#0ea5e9',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    r: {
                        min: 0,
                        max: 100,
                        angleLines: { color: 'rgba(0, 0, 0, 0.1)' },
                        grid: { color: 'rgba(0, 0, 0, 0.1)' },
                        pointLabels: { color: '#475569', font: { size: 14, weight: 'bold' } },
                        ticks: { display: false } // Ocultar números de la red
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(255, 255, 255, 0.95)', titleColor: '#0ea5e9', bodyColor: '#475569', borderColor: '#e2e8f0', borderWidth: 1, bodyFont: {size: 14} }
                }
            }
        });

        // 2. Gráfico de Doughnut (Aciertos/Errores)
        const rendimientoCtx = document.getElementById('rendimientoChart').getContext('2d');
        const aciertos = <?php echo $rendimiento_data['total_aciertos']; ?>;
        const errores = <?php echo $rendimiento_data['total_errores']; ?>;
        
        new Chart(rendimientoCtx, {
            type: 'doughnut',
            data: {
                labels: ['Aciertos', 'Errores'],
                datasets: [{
                    data: [aciertos, errores],
                    backgroundColor: ['#10b981', '#f43f5e'], // emerald-500, rose-500
                    borderColor: ['#ffffff', '#ffffff'],
                    borderWidth: 4,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '75%',
                plugins: { 
                    legend: { position: 'bottom', labels: { padding: 20, color: '#475569', font: { size: 14, weight: 'bold' } } },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#334155',
                        bodyColor: '#475569',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const total = aciertos + errores;
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) + '%' : '0%';
                                return ` ${context.label}: ${context.raw} (${percentage})`;
                            }
                        }
                    }
                }
            }
        });

        // 3. Gráfico de Líneas con Gradiente
        const mejoraCtx = document.getElementById('mejoraChart').getContext('2d');
        
        // Crear gradiente dinámico para la línea
        let gradient = mejoraCtx.createLinearGradient(0, 0, 0, 350);
        gradient.addColorStop(0, 'rgba(79, 70, 229, 0.3)'); // indigo-600 translúcido
        gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

        new Chart(mejoraCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($mejora_labels); ?>,
                datasets: [{
                    label: 'Precisión',
                    data: <?php echo json_encode($mejora_data); ?>,
                    fill: true,
                    backgroundColor: gradient,
                    borderColor: '#4f46e5', // indigo-600
                    borderWidth: 3,
                    tension: 0.4, // Curvas suaves
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: '#4f46e5',
                    pointHoverBorderColor: '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    y: { 
                        beginAtZero: true, max: 100, 
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false },
                        ticks: { callback: value => value + '%', padding: 10 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#4f46e5',
                        bodyColor: '#475569',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: { label: (ctx) => `Precisión: ${ctx.parsed.y}%` }
                    }
                }
            }
        });
    </script>
</body>
</html>