<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desafio_tema'])) { 
    $_SESSION['desafio_tema'] = $_POST['desafio_tema'];
    $_SESSION['desafio_multiplicador'] = $_POST['desafio_multiplicador'];
    header("Location: modo_desafio.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Configurar Desafío</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f3e8ff; background-image: linear-gradient(to bottom right, #f3e8ff, #e0e7ff); min-height: 100vh; }
        .glass-panel { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.6); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        
        .glow-indigo:hover { box-shadow: 0 0 20px rgba(79, 70, 229, 0.5); border-color: rgba(79, 70, 229, 0.5); }
        .glow-green:hover { box-shadow: 0 0 20px rgba(16, 185, 129, 0.5); border-color: rgba(16, 185, 129, 0.5); }
        .glow-sky:hover { box-shadow: 0 0 20px rgba(14, 165, 233, 0.5); border-color: rgba(14, 165, 233, 0.5); }
        .glow-pink:hover { box-shadow: 0 0 20px rgba(236, 72, 153, 0.5); border-color: rgba(236, 72, 153, 0.5); }
        .glow-amber:hover { box-shadow: 0 0 30px rgba(245, 158, 11, 0.8); border-color: rgba(245, 158, 11, 0.8); transform: scale(1.02); }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="glass-panel p-8 rounded-3xl w-full max-w-4xl shadow-xl relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl z-0"></div>
        <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl z-0"></div>
        
        <div class="relative z-10">
            <div class="flex justify-between items-center mb-6">
                <a href="menu.php" class="text-slate-500 hover:text-slate-800 transition bg-white/50 w-10 h-10 rounded-full flex items-center justify-center font-bold shadow-sm"><i class="fas fa-arrow-left"></i></a>
                <h1 class="text-3xl sm:text-4xl font-black text-slate-800 text-center flex-1">Configurar Desafío</h1>
                <div class="w-10"></div>
            </div>
            
            <p class="text-center text-slate-600 font-bold mb-8 max-w-xl mx-auto">Selecciona tu nivel de riesgo. Los temas más complejos otorgan un mayor multiplicador de XP. ¡Elige "Todos" para el desafío definitivo!</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <?php 
                $opciones = [
                    ['tema'=>'Aritmética', 'mult'=>1.0, 'color'=>'indigo', 'glow'=>'glow-indigo', 'icon'=>'fa-calculator', 'desc'=>'Ideal para calentar.'],
                    ['tema'=>'Geometría', 'mult'=>1.5, 'color'=>'sky', 'glow'=>'glow-sky', 'icon'=>'fa-ruler-combined', 'desc'=>'Áreas y volúmenes moderados.'],
                    ['tema'=>'Álgebra', 'mult'=>1.5, 'color'=>'green', 'glow'=>'glow-green', 'icon'=>'fa-square-root-variable', 'desc'=>'Ecuaciones para pensar.'],
                    ['tema'=>'Estadística', 'mult'=>2.0, 'color'=>'pink', 'glow'=>'glow-pink', 'icon'=>'fa-chart-pie', 'desc'=>'Probabilidad de fallar: Alta.'],
                ];
                
                foreach($opciones as $op): ?>
                <form method="POST">
                    <input type="hidden" name="desafio_tema" value="<?php echo $op['tema']; ?>">
                    <input type="hidden" name="desafio_multiplicador" value="<?php echo $op['mult']; ?>">
                    <button type="submit" class="w-full text-left bg-white/60 p-5 rounded-2xl border-2 border-transparent card-hover <?php echo $op['glow']; ?> flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-<?php echo $op['color']; ?>-100 flex items-center justify-center text-<?php echo $op['color']; ?>-600 text-2xl shadow-inner">
                            <i class="fas <?php echo $op['icon']; ?>"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-black text-lg text-slate-800"><?php echo $op['tema']; ?></h3>
                            <p class="text-sm font-bold text-slate-500"><?php echo $op['desc']; ?></p>
                        </div>
                        <div class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full font-black shadow-sm">
                            x<?php echo number_format($op['mult'], 1); ?> XP
                        </div>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>

            <!-- Botón de TODOS -->
            <form method="POST" class="mt-4">
                <input type="hidden" name="desafio_tema" value="Todos">
                <input type="hidden" name="desafio_multiplicador" value="3.0">
                <button type="submit" class="w-full text-left bg-gradient-to-r from-amber-400 to-orange-500 p-1 rounded-2xl card-hover glow-amber shadow-xl">
                    <div class="bg-white/90 rounded-xl p-5 flex flex-col sm:flex-row items-center gap-4 h-full w-full">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-3xl shadow-lg">
                            <i class="fas fa-infinity"></i>
                        </div>
                        <div class="flex-1 text-center sm:text-left mt-2 sm:mt-0">
                            <h3 class="font-black text-2xl text-slate-800">Desafío Caos (Todos)</h3>
                            <p class="text-sm font-bold text-slate-600">Temas aleatorios. La dificultad escala más rápido. Sólo para genios.</p>
                        </div>
                        <div class="bg-gradient-to-r from-amber-400 to-orange-500 text-white px-4 py-2 rounded-full font-black shadow-md mt-4 sm:mt-0 animate-pulse text-lg">
                            x3.0 XP MAX
                        </div>
                    </div>
                </button>
            </form>

        </div>
    </div>
</body>
</html>
