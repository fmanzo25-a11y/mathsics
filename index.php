<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: menu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script>if(localStorage.getItem('lowPerf')==='1') document.documentElement.classList.add('low-perf');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Plataforma Interactiva de Matemáticas</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
            overflow-x: hidden;
        }
        .gradient-text {
            background: linear-gradient(to right, #3b82f6, #1e40af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), box-shadow 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .card-3d:hover {
            transform: translateY(-10px) translateZ(20px) rotateX(5deg);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.2);
        }
        .animate-fade-in-up { animation: fade-in-up 0.8s ease-out forwards; opacity: 0; }
        
        /* Glassmorphism & Blobs */
        .glass-panel {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        .floating { animation: float 4s ease-in-out infinite; }

        /* 3D Circle Styles */
        .scene-container {
            perspective: 1000px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 350px; /* Reducido para móvil */
        }
        .circle-main {
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            cursor: pointer;
            transform-style: preserve-3d;
            transition: transform 1s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15), inset 0 0 20px rgba(255, 255, 255, 0.3);
        }
        .circle-main.expanded { transform: rotateY(180deg) scale(0.9); }
        .circle-text {
            color: #1e293b;
            font-size: 2.2rem;
            font-weight: 900;
            transition: opacity 0.5s, transform 0.5s;
            backface-visibility: hidden;
            text-shadow: 0 2px 5px rgba(255,255,255,0.8);
        }
        .circle-main.expanded .circle-text { opacity: 0; transform: scale(0.5); }
        .segment-container {
            position: absolute; width: 100%; height: 100%;
            transform-style: preserve-3d; transform: rotateY(-180deg);
            backface-visibility: hidden; pointer-events: none;
        }
        .circle-main.expanded .segment-container { pointer-events: auto; }
        .segment {
            position: absolute; top: 50%; left: 50%;
            width: 120px; height: 120px;
            background-color: rgba(255, 255, 255, 0.95); border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            text-align: center; font-weight: bold; color: #1e40af;
            transition: transform 1s cubic-bezier(0.68, -0.55, 0.27, 1.55), opacity 0.5s ease 0.3s, box-shadow 0.3s;
            transform-origin: center center; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 5px; opacity: 0;
        }
        .circle-main.expanded .segment { opacity: 1; }
        .segment-content {
            width: 100%; height: 100%; display: flex;
            justify-content: center; align-items: center; border-radius: 50%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            color: #3b82f6;
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94), background 0.3s, color 0.3s;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        .segment:hover .segment-content {
            background: rgba(255, 255, 255, 0.95); transform: scale(1.1) translateZ(20px);
            color: #ec4899;
            box-shadow: 0 10px 25px rgba(31, 38, 135, 0.2);
        }

        /* === CAMBIO 1: CSS Adaptativo para el círculo en móviles === */
        @media (max-width: 640px) {
            .circle-main { width: 200px; height: 200px; }
            .segment { width: 90px; height: 90px; font-size: 0.8rem; }
            .scene-container { height: 320px; }
        }

        /* === INTRO CINEMATICA CSS === */
        #intro-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: #ffffff; z-index: 9999; overflow: hidden;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            transition: opacity 1s ease-out, visibility 1s;
            perspective: 1000px;
        }
        #intro-overlay.hidden { opacity: 0; visibility: hidden; }

        .particle {
            position: absolute; color: #3b82f6; font-weight: bold; font-family: 'Nunito', monospace;
            opacity: 0; pointer-events: none; transition: all 2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: transform, opacity, left, top;
        }

        #intro-scene {
            position: relative; width: 100%; height: 100%;
            transform-style: preserve-3d;
            transition: transform 1.5s cubic-bezier(0.77, 0, 0.175, 1);
        }

        #intro-title-container {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            text-align: center; opacity: 0; transition: opacity 0.5s; z-index: 10;
        }
        #intro-main-title {
            font-size: clamp(2.5rem, 10vw, 4rem); font-weight: 900; 
            background: linear-gradient(to right, #3b82f6, #ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            cursor: pointer; transition: transform 0.3s;
        }
        @media (min-width: 768px) { #intro-main-title { font-size: 6rem; } }
        #intro-main-title:hover { transform: scale(1.05); }

        .blink-text { animation: blink 2s infinite; color: #64748b; font-weight: bold; margin-top: 10px; cursor: pointer; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        .scene-panel {
            position: absolute; top: 50%; left: 50%; width: 90%; max-width: 800px;
            max-height: 85vh; overflow-y: auto;
            transform: translate(-50%, -50%) translateZ(-1000px) scale(0.5);
            opacity: 0; text-align: center; pointer-events: none;
            transition: all 1.5s cubic-bezier(0.77, 0, 0.175, 1);
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px);
            padding: 2rem; border-radius: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        /* Ocultar barra de scroll para paneles limpios */
        .scene-panel::-webkit-scrollbar { width: 0px; background: transparent; }
        @media (min-width: 768px) { .scene-panel { padding: 3rem; } }
        .scene-panel.active { transform: translate(-50%, -50%) translateZ(0) scale(1); opacity: 1; pointer-events: auto; }

        .water-drop {
            position: absolute; top: 50%; left: 50%; width: 4px; height: 4px;
            background: #3b82f6; border-radius: 50%;
            transform: translate(-50%, -100vh); opacity: 0;
        }
        .water-drop.fall { animation: dropFall 0.8s cubic-bezier(0.55, 0.085, 0.68, 0.53) forwards; }
        @keyframes dropFall {
            0% { transform: translate(-50%, -100vh) scaleY(3); opacity: 1; }
            90% { transform: translate(-50%, -50%) scaleY(1); opacity: 1; }
            100% { transform: translate(-50%, -50%) scaleX(4) scaleY(0.2); opacity: 0; }
        }

        .water-ripple {
            position: absolute; top: 50%; left: 50%; width: 0; height: 0;
            border: 4px solid #3b82f6; border-radius: 50%;
            transform: translate(-50%, -50%); opacity: 0;
        }
        .water-ripple.expand { animation: rippleExpand 1s ease-out forwards; }
        @keyframes rippleExpand {
            0% { width: 0; height: 0; opacity: 1; border-width: 10px; }
            100% { width: 100vw; height: 100vw; opacity: 0; border-width: 1px; }
        }
    </style>
</head>
<body class="text-slate-700 relative">

    <!-- Cinematic Intro Overlay -->
    <div id="intro-overlay" style="display: none;">
        <button id="skip-intro" class="absolute top-6 right-6 px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-full z-50 transition">Omitir</button>
        
        <div id="intro-scene">
            <div id="intro-particles-container" class="absolute inset-0"></div>
            
            <div id="intro-title-container">
                <h1 id="intro-main-title"></h1>
                <p id="intro-click-more" class="blink-text hidden">Da click para saber más</p>
            </div>
            
            <div id="scene-1" class="scene-panel border-t-4 border-blue-500">
                <h2 class="text-3xl md:text-5xl font-black text-slate-800 mb-6 text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">La Aventura del Conocimiento te Espera.</h2>
                <p class="text-lg md:text-xl text-slate-600 font-bold mb-8">Aprende, practica y compite. Lleva tus habilidades matemáticas al siguiente nivel con nuestros desafíos interactivos y comunidad global.</p>
                <button class="next-scene-btn px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-full text-lg shadow-[0_0_20px_rgba(59,130,246,0.5)] hover:scale-105 transition">Siguiente</button>
            </div>
            
            <div id="scene-2" class="scene-panel border-t-4 border-pink-500">
                <h2 class="text-3xl md:text-5xl font-black text-slate-800 mb-8">¿Qué puedes hacer en Mathsics?</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left mb-8">
                    <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 text-center">
                        <i class="fas fa-calculator text-4xl text-indigo-600 mb-4 drop-shadow-sm"></i>
                        <h3 class="font-black text-lg text-slate-800">Ejercicios Interactivos</h3>
                        <p class="text-sm text-slate-600 font-bold mt-2">Practica aritmética, álgebra, geometría y más.</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 text-center">
                        <i class="fas fa-user-friends text-4xl text-orange-600 mb-4 drop-shadow-sm"></i>
                        <h3 class="font-black text-lg text-slate-800">Duelos Matemáticos</h3>
                        <p class="text-sm text-slate-600 font-bold mt-2">Reta a tus amigos en emocionantes duelos.</p>
                    </div>
                    <div class="bg-sky-50 p-4 rounded-xl border border-sky-100 text-center">
                        <i class="fas fa-comments text-4xl text-sky-600 mb-4 drop-shadow-sm"></i>
                        <h3 class="font-black text-lg text-slate-800">Comunidad</h3>
                        <p class="text-sm text-slate-600 font-bold mt-2">Comparte tus dudas y ayuda a otros en los foros.</p>
                    </div>
                </div>
                <button class="next-scene-btn px-8 py-3 bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-600 hover:to-rose-600 text-white font-bold rounded-full text-lg shadow-[0_0_20px_rgba(236,72,153,0.5)] hover:scale-105 transition">Siguiente</button>
            </div>
            
            <div id="scene-3" class="scene-panel !bg-transparent !shadow-none !backdrop-blur-none border-none pointer-events-none flex flex-col items-center justify-center">
                 <div class="water-drop" id="water-drop"></div>
                 <div class="water-ripple" id="water-ripple"></div>
                 <div id="water-circle" class="circle-main mt-4 opacity-0 transform scale-50 transition-all duration-1000" style="width: 250px; height: 250px; cursor: pointer; margin-left: auto; margin-right: auto;">
                     <div class="circle-text">temas</div>
                 </div>
                 <div class="mt-8 text-center opacity-0 pointer-events-auto" id="welcome-text-container" style="transition: opacity 1s 0.5s;">
                    <h2 class="text-4xl md:text-6xl font-black text-slate-800 mb-8 drop-shadow-md">Bienvenido a Mathsics</h2>
                    <button id="finish-intro-btn" class="px-10 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-full text-xl shadow-[0_0_20px_rgba(59,130,246,0.5)] hover:scale-105 transition">Entrar</button>
                 </div>
            </div>
        </div>
    </div>

    <!-- Background Blobs -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-4000"></div>
    </div>

    <header class="bg-white/60 backdrop-blur-md sticky top-0 z-50 border-b border-white/50 shadow-sm" data-aos="fade-down" data-aos-duration="800">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="text-3xl font-black text-blue-600 tracking-wide">Mathsics</a>
            <nav class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-gray-600 hover:text-blue-600 font-bold">Características</a>
                <a href="#why-mathsics" class="text-gray-600 hover:text-blue-600 font-bold">Por qué Mathsics</a>
                <a href="#tech" class="text-gray-600 hover:text-blue-600 font-bold">Tecnologías</a>
            </nav>
            <a href="inicio_de_sesion.php" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-full hover:bg-blue-700 transition-transform duration-300 hover:scale-105">
                Ingresar
            </a>
        </div>
    </header>

    <main>
        <section class="text-center py-20 md:py-36 px-6 relative">
            <div class="container mx-auto relative z-10 flex flex-col items-center">
                <!-- Título que se forma con matemáticas -->
                <div id="math-title-container" class="h-32 flex items-center justify-center mb-4">
                    <h1 id="math-title" class="text-6xl md:text-8xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-pink-600 drop-shadow-xl" aria-label="Mathsics">
                        <!-- El script JS insertará las letras aquí -->
                    </h1>
                </div>
                
                <h2 class="text-2xl md:text-4xl font-black text-slate-700 leading-tight mt-2 mb-6" data-aos="fade-up" data-aos-delay="1500">
                    La Aventura del Conocimiento te <span class="gradient-text">Espera</span>.
                </h2>
                
                <p class="mt-4 text-lg md:text-xl text-slate-600 max-w-3xl mx-auto font-bold glass-panel py-4 px-6 rounded-2xl" data-aos="fade-up" data-aos-delay="1800">
                    Aprende, practica y compite. Lleva tus habilidades matemáticas al siguiente nivel con nuestros desafíos interactivos y comunidad global.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row justify-center gap-6 w-full max-w-md mx-auto" data-aos="zoom-in" data-aos-delay="2000">
                    <a href="registro.php" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-black py-4 px-8 rounded-full hover:shadow-[0_0_20px_rgba(79,70,229,0.5)] transition-all duration-300 hover:scale-105 text-lg text-center">
                        Crear Cuenta
                    </a>
                    <a href="inicio_de_sesion.php" class="flex-1 glass-panel text-indigo-700 font-black py-4 px-8 rounded-full hover:bg-white/60 transition-all duration-300 hover:scale-105 text-lg text-center border-2 border-indigo-200">
                        Iniciar Sesión
                    </a>
                </div>
            </div>
        </section>

        <section id="features" class="py-20 relative">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-black text-center text-slate-800 mb-12" data-aos="fade-up">¿Qué puedes hacer en Mathsics?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="card-3d glass-panel p-8 rounded-3xl border-t-4 border-indigo-500" data-aos="fade-up" data-aos-delay="100">
                        <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center mb-6 shadow-inner">
                            <i class="fas fa-calculator text-3xl text-indigo-600" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-2xl font-black mb-3 text-slate-800">Ejercicios Interactivos</h3>
                        <p class="font-bold text-slate-600">Practica aritmética, álgebra, geometría y más con problemas que se adaptan a tu nivel.</p>
                    </div>
                    <div class="card-3d glass-panel p-8 rounded-3xl border-t-4 border-orange-500" data-aos="fade-up" data-aos-delay="200">
                        <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center mb-6 shadow-inner">
                            <i class="fas fa-user-friends text-3xl text-orange-600" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-2xl font-black mb-3 text-slate-800">Duelos Matemáticos</h3>
                        <p class="font-bold text-slate-600">Reta a tus amigos o a otros jugadores en emocionantes duelos de velocidad y precisión en tiempo real.</p>
                    </div>
                    <div class="card-3d glass-panel p-8 rounded-3xl border-t-4 border-sky-500" data-aos="fade-up" data-aos-delay="300">
                        <div class="w-16 h-16 rounded-full bg-sky-100 flex items-center justify-center mb-6 shadow-inner">
                            <i class="fas fa-comments text-3xl text-sky-600" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-2xl font-black mb-3 text-slate-800">Comunidad y Foros</h3>
                        <p class="font-bold text-slate-600">Comparte tus dudas, ayuda a otros y publica tus propios juegos y creaciones.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="temas" class="py-20 relative">
            <div class="container mx-auto px-6 text-center flex flex-col items-center">
                <h2 class="text-3xl md:text-4xl font-black text-slate-800 mb-12" data-aos="zoom-in">Explora los Temas</h2>
                <div class="scene-container mb-2 text-center flex justify-center w-full">
                    <div class="circle-main" tabindex="0" role="button" aria-expanded="false" aria-label="Mostrar temas de Matemáticas">
                        <div class="circle-text">Matemáticas</div>
                        <div class="segment-container">
                        </div>
                    </div>
                </div>
                
                <!-- Panel de información dinámica -->
                <div id="topic-info-panel" class="w-full max-w-xl glass-panel p-6 rounded-3xl opacity-0 transition-all duration-500 transform translate-y-4 pointer-events-none border-t-4 border-blue-500">
                    <h3 id="topic-info-title" class="text-2xl font-black text-blue-600 mb-3 drop-shadow-sm"></h3>
                    <p id="topic-info-desc" class="text-slate-700 font-bold text-lg"></p>
                </div>
            </div>
        </section>

        <section id="why-mathsics" class="py-20 relative">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16" data-aos="fade-up">
                    <h2 class="text-3xl md:text-5xl font-black text-slate-800 mb-4">¿Las matemáticas no son lo tuyo?</h2>
                    <p class="text-xl text-slate-600 font-bold max-w-2xl mx-auto">
                        Mathsics está diseñado específicamente para eliminar la frustración y ayudarte a entender desde cero.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Razón 1 -->
                    <div class="glass-panel p-8 rounded-3xl border-l-4 border-blue-500 relative overflow-hidden group" data-aos="fade-up" data-aos-delay="100">
                        <div class="absolute -right-6 -top-6 text-6xl text-blue-100 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center mb-6 relative z-10">
                            <i class="fas fa-brain text-2xl text-blue-600"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-4 relative z-10">Tutor de IA Integrado</h3>
                        <p class="font-bold text-slate-600 relative z-10">
                            Si te equivocas, no te dejamos solo. Nuestra Inteligencia Artificial impulsada por la API de google GEMINI la analiza tu error y te explica paso a paso cómo resolverlo de la forma más sencilla.
                        </p>
                    </div>

                    <!-- Razón 2 -->
                    <div class="glass-panel p-8 rounded-3xl border-l-4 border-pink-500 relative overflow-hidden group" data-aos="fade-up" data-aos-delay="200">
                        <div class="absolute -right-6 -top-6 text-6xl text-pink-100 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-pink-100 flex items-center justify-center mb-6 relative z-10">
                            <i class="fas fa-trophy text-2xl text-pink-600"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-4 relative z-10">Aprende Jugando</h3>
                        <p class="font-bold text-slate-600 relative z-10">
                            Olvídate de los libros aburridos. Aquí subes de nivel, ganas puntos y participas en duelos, transformando el estudio en un juego altamente adictivo.
                        </p>
                    </div>

                    <!-- Razón 3 -->
                    <div class="glass-panel p-8 rounded-3xl border-l-4 border-indigo-500 relative overflow-hidden group" data-aos="fade-up" data-aos-delay="300">
                        <div class="absolute -right-6 -top-6 text-6xl text-indigo-100 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center mb-6 relative z-10">
                            <i class="fas fa-stairs text-2xl text-indigo-600"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mb-4 relative z-10">A tu Propio Ritmo</h3>
                        <p class="font-bold text-slate-600 relative z-10">
                            Desde sumas básicas hasta cálculo avanzado. Empezamos en tu nivel actual y la dificultad escala orgánicamente conforme dominas cada tema.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="tech" class="py-20 relative">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl md:text-4xl font-black text-slate-800 mb-12" data-aos="fade-up">Tecnologías Utilizadas</h2>
                <div class="flex flex-wrap justify-center items-center gap-x-12 gap-y-8" data-aos="zoom-in" data-aos-delay="200">
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fab fa-php text-7xl text-indigo-400" aria-hidden="true"></i>
                        <p class="mt-2 font-bold text-lg">PHP</p>
                    </div>
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fas fa-database text-7xl text-blue-500" aria-hidden="true"></i>
                        <p class="mt-2 font-bold text-lg">MySQL</p>
                    </div>
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fab fa-js-square text-7xl text-yellow-400" aria-hidden="true"></i>
                        <p class="mt-2 font-bold text-lg">JavaScript</p>
                    </div>
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fab fa-html5 text-7xl text-orange-500" aria-hidden="true"></i>
                        <p class="mt-2 font-bold text-lg">HTML5</p>
                    </div>
                    <div class="text-center transition-transform hover:scale-110">
                        <img src="https://tailwindcss.com/favicons/favicon-32x32.png?v=3" alt="Tailwind CSS" class="h-16 w-16 mx-auto">
                        <p class="mt-2 font-bold text-lg">Tailwind CSS</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-slate-800 text-white">
        <div class="container mx-auto px-6 py-12">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-center md:text-left mb-6 md:mb-0">
                    <h3 class="text-2xl font-black">Mathsics</h3>
                    <p class="text-slate-300">© 2025. Todos los derechos reservados.</p>
                </div>
                <div class="flex items-center gap-6">
                    <p class="font-bold">Apoyado por:</p>
                    <div class="bg-white h-16 w-32 rounded-lg flex items-center justify-center">
                        <span class="text-slate-700 font-bold text-sm">Logo U</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Inicializar AOS
        AOS.init({
            once: true,
            offset: 50,
            duration: 800,
            easing: 'ease-out-cubic',
        });

        // === LÓGICA DE LA INTRO CINEMÁTICA ===
        document.addEventListener('DOMContentLoaded', () => {
            const introOverlay = document.getElementById('intro-overlay');
            const hasSeenIntro = localStorage.getItem('mathsics_intro_seen');

            if (!hasSeenIntro) {
                introOverlay.style.display = 'flex';
                document.body.style.overflow = 'hidden'; 
                runIntroSequence();
            } else {
                runNormalHeroAnimation();
            }

            function runIntroSequence() {
                const container = document.getElementById('intro-particles-container');
                const symbols = ['∑', '∫', '∆', '∞', '√', 'π', 'Ω', 'μ', 'θ', '≈', '≠', '1', '2', '3', '4', 'x', 'y', '+', '-', '='];
                const particles = [];
                const isMobile = window.innerWidth < 768;
                const numParticles = isMobile ? 60 : 150; // Optimización móvil
                
                // 1. Generar lluvia
                for(let i = 0; i < numParticles; i++) {
                    const p = document.createElement('div');
                    p.className = 'particle';
                    p.textContent = symbols[Math.floor(Math.random() * symbols.length)];
                    p.style.left = `${Math.random() * 100}vw`;
                    p.style.top = `${Math.random() * 100}vh`;
                    p.style.fontSize = `${Math.random() * 2 + 1}rem`;
                    // Estandarizar transform para que CSS pueda interpolarlo correctamente
                    p.style.transform = `translate(-50%, -50%) scale(1) rotate(${Math.random() * 360}deg)`;
                    container.appendChild(p);
                    particles.push(p);
                    setTimeout(() => p.style.opacity = '0.7', i * 15);
                }
                
                // 2. Atraer al centro
                setTimeout(() => {
                    particles.forEach(p => {
                        p.style.left = '50vw';
                        p.style.top = '50vh';
                        // Reducir tamaño levemente (scale(0.1)) en vez de scale(0) para evitar que desaparezcan de golpe
                        p.style.transform = 'translate(-50%, -50%) scale(0.1) rotate(720deg)';
                        p.style.opacity = '1';
                        p.style.color = '#ec4899';
                    });
                }, 3500);
                
                // 3. Explosión y Título
                setTimeout(() => {
                    particles.forEach(p => {
                        p.style.transition = 'all 0.8s cubic-bezier(0.1, 0.8, 0.3, 1)';
                        p.style.left = `${Math.random() * 200 - 50}vw`;
                        p.style.top = `${Math.random() * 200 - 50}vh`;
                        p.style.transform = `translate(-50%, -50%) scale(3) rotate(${Math.random() * 360}deg)`;
                        p.style.opacity = '0';
                    });
                    
                    const titleContainer = document.getElementById('intro-title-container');
                    const mainTitle = document.getElementById('intro-main-title');
                    const clickMore = document.getElementById('intro-click-more');
                    
                    titleContainer.style.opacity = '1';
                    
                    let iterations = 0;
                    const targetWord = "MATHSICS";
                    const scramble = setInterval(() => {
                        mainTitle.textContent = targetWord.split('').map((c, i) => 
                            (iterations > i + 5) ? c : symbols[Math.floor(Math.random() * symbols.length)]
                        ).join('');
                        iterations++;
                        if(iterations > targetWord.length + 15) {
                            clearInterval(scramble);
                            mainTitle.textContent = targetWord;
                            clickMore.classList.remove('hidden');
                        }
                    }, 50);

                    const goToScene1 = () => {
                        mainTitle.style.opacity = '0';
                        clickMore.style.opacity = '0';
                        clickMore.style.animation = 'none'; // Arregla el bug de la animación css sobreescribiendo opacity
                        setTimeout(() => clickMore.style.display = 'none', 300); // Doble seguridad
                        titleContainer.style.pointerEvents = 'none';
                        
                        const scene = document.getElementById('intro-scene');
                        scene.style.transform = 'translateZ(500px)'; // Zoom in
                        
                        setTimeout(() => {
                            scene.style.transform = 'translateZ(0px)';
                            document.getElementById('scene-1').classList.add('active');
                        }, 800);
                    };
                    
                    mainTitle.addEventListener('click', goToScene1);
                    clickMore.addEventListener('click', goToScene1);
                    
                }, 5500);
                
                // Botones "Siguiente"
                document.querySelectorAll('.next-scene-btn').forEach((btn, index) => {
                    btn.addEventListener('click', () => {
                        const currentScene = document.getElementById(`scene-${index + 1}`);
                        const nextScene = document.getElementById(`scene-${index + 2}`);
                        
                        currentScene.style.transform = 'translate(-50%, -50%) translateZ(1000px) scale(2)';
                        currentScene.style.opacity = '0';
                        currentScene.style.pointerEvents = 'none';
                        
                        setTimeout(() => {
                            if(nextScene) {
                                nextScene.classList.add('active');
                                if(index + 1 === 2) {
                                    startWaterDrop();
                                }
                            }
                        }, 800);
                    });
                });
                
                function startWaterDrop() {
                    const drop = document.getElementById('water-drop');
                    const ripple = document.getElementById('water-ripple');
                    const circle = document.getElementById('water-circle');
                    const textContainer = document.getElementById('welcome-text-container');
                    
                    drop.classList.add('fall');
                    
                    setTimeout(() => {
                        ripple.classList.add('expand');
                        circle.style.opacity = '1';
                        circle.style.transform = 'scale(1)';
                        textContainer.style.opacity = '1';
                    }, 800);
                }
                
                document.getElementById('finish-intro-btn').addEventListener('click', endIntro);
                document.getElementById('water-circle').addEventListener('click', endIntro);
                document.getElementById('skip-intro').addEventListener('click', endIntro);
                
                function endIntro() {
                    localStorage.setItem('mathsics_intro_seen', 'true');
                    introOverlay.style.opacity = '0';
                    setTimeout(() => {
                        introOverlay.style.display = 'none';
                        document.body.style.overflow = 'auto'; 
                        runNormalHeroAnimation(); // Activa la animación del hero normal
                    }, 1000);
                }
            }

            function runNormalHeroAnimation() {
                const mathTitle = document.getElementById('math-title');
                const targetWord = "MATHSICS";
                const mathSymbols = ['∑', '∫', '∆', '∞', '√', 'π', 'Ω', 'μ', 'θ', '≈', '≠', '≤', '≥', '±'];
                let currentIteration = 0;
                const maxIterations = 20;

                const scrambleInterval = setInterval(() => {
                    let scrambledWord = "";
                    for (let i = 0; i < targetWord.length; i++) {
                        if (currentIteration > maxIterations && i < (currentIteration - maxIterations) / 3) {
                            scrambledWord += targetWord[i];
                        } else {
                            scrambledWord += mathSymbols[Math.floor(Math.random() * mathSymbols.length)];
                        }
                    }
                    mathTitle.textContent = scrambledWord;

                    if (currentIteration >= maxIterations + (targetWord.length * 3)) {
                        clearInterval(scrambleInterval);
                        mathTitle.textContent = targetWord;
                        mathTitle.classList.add('floating');
                    }
                    currentIteration++;
                }, 50);
            }
        });
    </script>

    <script>
        // Script para el círculo 3D interactivo
        document.addEventListener('DOMContentLoaded', () => {
            const scene = document.querySelector('.scene-container');
            const circle = document.querySelector('#temas .circle-main');
            const segmentContainer = document.querySelector('.segment-container');

            const topics = {
                "Álgebra": "Estudia las estructuras, relaciones y cantidades. ¡Las variables y ecuaciones son tus herramientas!",
                "Aritmética": "La base de todo. Aprende a dominar los números y las operaciones fundamentales.",
                "Geometría": "Explora las formas, tamaños y propiedades del espacio. ¡Desde puntos y líneas hasta figuras complejas!",
                "Cálculo": "El estudio del cambio. Descubre los secretos de las derivadas y las integrales.",
                "Estadística": "Recolecta, analiza e interpreta datos para tomar decisiones. ¡El poder de la información!",
                "Trigonometría": "Mide los ángulos y las relaciones en los triángulos. ¡Esencial para la astronomía y la física!"
            };

            const topicKeys = Object.keys(topics);
            const numSegments = topicKeys.length;
            const angleStep = 360 / numSegments;
            
            // === CAMBIO 2: El radio ahora es dinámico según el tamaño de la pantalla ===
            const radius = window.innerWidth < 640 ? 110 : 150;

            topicKeys.forEach((topic, i) => {
                const angle = angleStep * i;
                const segment = document.createElement('div');
                segment.classList.add('segment');
                segment.setAttribute('role', 'button');
                segment.setAttribute('tabindex', '-1');
                segment.setAttribute('aria-label', `Mostrar información sobre ${topic}`);
                
                const segmentContent = document.createElement('div');
                segmentContent.classList.add('segment-content');
                segmentContent.textContent = topic;
                segment.appendChild(segmentContent);
                segmentContainer.appendChild(segment);

                segment.style.transform = 'translate(-50%, -50%) translateZ(-100px) scale(0.5)';
                segment.dataset.transformExpanded = `translate(-50%, -50%) rotate(${angle}deg) translateY(-${radius}px) rotate(${-angle}deg) rotateY(0deg) translateZ(30px)`;

                const handleActivation = (e) => {
                    e.stopPropagation();
                    showTooltip(segment, topic, topics[topic]);
                };
                segment.addEventListener('click', handleActivation);
                segment.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        handleActivation(e);
                    }
                });
            });

            function showTooltip(parentSegment, topicName, text) {
                const infoPanel = document.getElementById('topic-info-panel');
                const titleEl = document.getElementById('topic-info-title');
                const descEl = document.getElementById('topic-info-desc');
                
                // Efecto de desvanecimiento antes de cambiar texto
                infoPanel.style.opacity = '0';
                infoPanel.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    titleEl.textContent = topicName;
                    descEl.textContent = text;
                    infoPanel.style.opacity = '1';
                    infoPanel.style.transform = 'translateY(0)';
                }, 300);
            }

            const toggleCircle = () => {
                circle.classList.toggle('expanded');
                const segments = document.querySelectorAll('.segment');
                const isExpanded = circle.classList.contains('expanded');
                circle.setAttribute('aria-expanded', isExpanded);

                const infoPanel = document.getElementById('topic-info-panel');

                if (!isExpanded) {
                    // Ocultar panel al cerrar el círculo
                    if (infoPanel) {
                        infoPanel.style.opacity = '0';
                        infoPanel.style.transform = 'translateY(10px)';
                    }
                }

                segments.forEach(segment => {
                    if (isExpanded) {
                        segment.style.transform = segment.dataset.transformExpanded;
                        segment.setAttribute('tabindex', '0');
                    } else {
                        segment.style.transform = 'translate(-50%, -50%) translateZ(-100px) scale(0.5)';
                        segment.setAttribute('tabindex', '-1');
                    }
                });
            };

            circle.addEventListener('click', toggleCircle);
            circle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleCircle();
                }
            });
        });
    </script>
</body>
</html>