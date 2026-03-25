<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
// En un futuro, estos datos vendrán de la base de datos a través de id_duelo
$tema = $_GET['tema'] ?? 'Aleatorio';
$jugador1_nombre = $_SESSION['user_name'] ?? 'Tú';
$jugador1_avatar = $_SESSION['user_avatar'] ?? 'images/sinfoto.jpeg';
$jugador2_nombre = 'Oponente';
$jugador2_avatar = 'images/sinfoto.jpeg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duelo: <?php echo htmlspecialchars($tema); ?></title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
            background-image: linear-gradient(to top, #e0f2fe 90%, #f0f9ff 100%);
        }
        #temporizador-barra { transition: width 0.2s linear; }
    </style>
</head>
<body class="text-slate-700">
    <div class="container mx-auto p-4 flex flex-col min-h-screen">

        <header class="w-full max-w-4xl mx-auto">
            <div class="grid grid-cols-3 items-center gap-4">
                <div class="flex items-center gap-4">
                    <img src="<?php echo htmlspecialchars($jugador1_avatar); ?>" class="w-16 h-16 rounded-full border-4 border-white shadow-lg">
                    <div>
                        <h2 class="font-black text-lg text-slate-800"><?php echo htmlspecialchars($jugador1_nombre); ?></h2>
                        <p id="puntuacion-j1" class="text-2xl font-black text-blue-600">0</p>
                    </div>
                </div>
                <div class="text-center">
                    <h1 class="text-3xl font-black text-orange-500"><?php echo htmlspecialchars($tema); ?></h1>
                    <p id="numero-pregunta" class="font-bold text-gray-500">Pregunta 1/10</p>
                </div>
                <div class="flex items-center flex-row-reverse gap-4">
                    <img src="<?php echo htmlspecialchars($jugador2_avatar); ?>" class="w-16 h-16 rounded-full border-4 border-white shadow-lg">
                    <div class="text-right">
                        <h2 class="font-black text-lg text-slate-800"><?php echo htmlspecialchars($jugador2_nombre); ?></h2>
                        <p id="puntuacion-j2" class="text-2xl font-black text-pink-500">0</p>
                    </div>
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-5 mt-4 shadow-inner">
                <div id="temporizador-barra" class="bg-gradient-to-r from-yellow-400 to-orange-500 h-5 rounded-full" style="width: 100%;"></div>
            </div>
        </header>

        <main id="panel-pregunta" class="flex-1 w-full max-w-2xl mx-auto text-center my-8 flex flex-col justify-center">
            <div class="bg-white p-8 rounded-2xl shadow-xl">
                <h3 id="pregunta-texto" class="text-3xl font-black leading-tight text-slate-800">¿Cuál es el resultado de <span class="text-blue-600">25 + 17</span>?</h3>
                <div id="opciones-respuesta" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
                    <button class="bg-gray-100 text-slate-700 border-b-4 border-gray-300 font-bold p-4 rounded-lg text-2xl hover:-translate-y-1 active:translate-y-0.5 active:border-b-0 transition-transform duration-150">32</button>
                    <button class="bg-gray-100 text-slate-700 border-b-4 border-gray-300 font-bold p-4 rounded-lg text-2xl hover:-translate-y-1 active:translate-y-0.5 active:border-b-0 transition-transform duration-150">42</button>
                    <button class="bg-gray-100 text-slate-700 border-b-4 border-gray-300 font-bold p-4 rounded-lg text-2xl hover:-translate-y-1 active:translate-y-0.5 active:border-b-0 transition-transform duration-150">45</button>
                    <button class="bg-gray-100 text-slate-700 border-b-4 border-gray-300 font-bold p-4 rounded-lg text-2xl hover:-translate-y-1 active:translate-y-0.5 active:border-b-0 transition-transform duration-150">38</button>
                </div>
            </div>
        </main>

        <div id="panel-resultados" class="w-full max-w-2xl mx-auto text-center my-8 hidden">
            </div>

        <footer class="w-full max-w-4xl mx-auto text-center text-xs text-gray-400 pb-4">
            <p>Mathsics - Duelo Matemático</p>
        </footer>
    </div>
    </body>
</html>