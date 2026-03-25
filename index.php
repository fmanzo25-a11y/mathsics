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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathsics - Plataforma Interactiva de Matemáticas</title>
    <link rel="icon" href="images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f0f9ff;
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
        @keyframes fade-in-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in-up { animation: fade-in-up 0.8s ease-out forwards; opacity: 0; }

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
            background: linear-gradient(145deg, #3b82f6, #1e40af);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            cursor: pointer;
            transform-style: preserve-3d;
            transition: transform 1s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2), inset 0 0 20px rgba(255, 255, 255, 0.1);
        }
        .circle-main.expanded { transform: rotateY(180deg) scale(0.9); }
        .circle-text {
            color: white;
            font-size: 2rem;
            font-weight: 900;
            transition: opacity 0.5s, transform 0.5s;
            backface-visibility: hidden;
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
            background-color: rgba(255, 255, 255, 0.95);
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94), background-color 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .segment:hover .segment-content {
            background-color: white; transform: scale(1.1) translateZ(20px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        /* Tooltip Bubble Styles */
        .tooltip-bubble {
            position: absolute; background-color: #1e40af; color: white;
            padding: 10px 15px; border-radius: 8px; width: 200px;
            text-align: left; font-size: 0.9rem; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 10; opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease; pointer-events: none; transform: translateY(-10px);
        }
        .tooltip-bubble.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
        .tooltip-bubble::after {
            content: ''; position: absolute; bottom: 100%; left: 50%; margin-left: -8px;
            border-width: 8px; border-style: solid; border-color: transparent transparent #1e40af transparent;
        }

        /* === CAMBIO 1: CSS Adaptativo para el círculo en móviles === */
        @media (max-width: 640px) {
            .circle-main { width: 200px; height: 200px; }
            .segment { width: 90px; height: 90px; font-size: 0.8rem; }
            .scene-container { height: 320px; }
        }
    </style>
</head>
<body class="text-slate-700">

    <header class="bg-white/80 backdrop-blur-sm sticky top-0 z-50 border-b border-gray-200">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="text-3xl font-black text-blue-600 tracking-wide">Mathsics</a>
            <nav class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-gray-600 hover:text-blue-600 font-bold">Características</a>
                <a href="#about" class="text-gray-600 hover:text-blue-600 font-bold">Creador</a>
                <a href="#tech" class="text-gray-600 hover:text-blue-600 font-bold">Tecnologías</a>
            </nav>
            <a href="inicio_de_sesion.php" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-full hover:bg-blue-700 transition-transform duration-300 hover:scale-105">
                Ingresar
            </a>
        </div>
    </header>

    <main>
        <section class="text-center py-16 md:py-32 px-6" style="background: linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%);">
            <div class="container mx-auto">
                <h1 class="text-4xl md:text-6xl font-black text-slate-800 leading-tight">
                    La Aventura del Conocimiento <br class="hidden md:block"> te <span class="gradient-text">Espera</span>.
                </h1>
                <p class="mt-6 text-lg md:text-xl text-gray-600 max-w-3xl mx-auto">
                    Aprende, practica y compite. Lleva tus habilidades matemáticas al siguiente nivel con nuestros desafíos interactivos y comunidad global.
                </p>
                <div class="mt-10 flex justify-center gap-4">
                    <a href="registro.php" class="bg-blue-600 text-white font-bold py-3 px-8 rounded-full hover:bg-blue-700 transition-transform duration-300 hover:scale-105 text-lg">
                        Crear Cuenta
                    </a>
                    <a href="inicio_de_sesion.php" class="bg-white text-blue-600 font-bold py-3 px-8 rounded-full hover:bg-gray-100 transition-transform duration-300 hover:scale-105 text-lg border-2 border-blue-100">
                        Iniciar Sesión
                    </a>
                </div>
            </div>
        </section>

        <section id="features" class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-black text-center text-slate-800 mb-12">¿Qué puedes hacer en Mathsics?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="card-3d bg-indigo-500 text-white p-8 rounded-2xl border-b-8 border-indigo-700">
                        <i class="fas fa-calculator text-4xl mb-4 opacity-70"></i>
                        <h3 class="text-2xl font-black mb-2">Ejercicios Interactivos</h3>
                        <p class="font-bold opacity-90">Practica aritmética, álgebra, geometría y más con problemas que se adaptan a tu nivel.</p>
                    </div>
                    <div class="card-3d bg-orange-500 text-white p-8 rounded-2xl border-b-8 border-orange-700">
                        <i class="fas fa-user-friends text-4xl mb-4 opacity-70"></i>
                        <h3 class="text-2xl font-black mb-2">Duelos Matemáticos</h3>
                        <p class="font-bold opacity-90">Reta a tus amigos o a otros jugadores en emocionantes duelos de velocidad y precisión en tiempo real.</p>
                    </div>
                    <div class="card-3d bg-sky-500 text-white p-8 rounded-2xl border-b-8 border-sky-700">
                        <i class="fas fa-comments text-4xl mb-4 opacity-70"></i>
                        <h3 class="text-2xl font-black mb-2">Comunidad y Foros</h3>
                        <p class="font-bold opacity-90">Comparte tus dudas, ayuda a otros y publica tus propios juegos y creaciones.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="temas" class="py-20 bg-slate-50">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl md:text-4xl font-black text-slate-800 mb-12">Explora los Temas</h2>
                <div class="scene-container">
                    <div class="circle-main">
                        <div class="circle-text">Matemáticas</div>
                        <div class="segment-container">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="flex flex-col md:flex-row items-center gap-12">
                    <div class="md:w-1/3 text-center">
                        <img src="images/sinfoto.jpeg" alt="Foto del creador" class="w-48 h-48 md:w-64 md:h-64 rounded-full mx-auto object-cover shadow-2xl ring-4 ring-white">
                    </div>
                    <div class="md:w-2/3">
                        <h2 class="text-3xl md:text-4xl font-black text-slate-800">Sobre el creador</h2>
                        <p class="mt-4 text-lg text-gray-600">
                            ¡Hola! Soy el creador de Mathsics. Este proyecto nació de mi pasión por la programación y la educación. Mi objetivo es crear herramientas que hagan el aprendizaje de las matemáticas una experiencia divertida, accesible y retadora para estudiantes de todos los niveles.
                        </p>
                        <p class="mt-4 text-lg text-gray-600">
                            Espero que disfrutes la plataforma tanto como yo disfruté creándola.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="tech" class="py-20 bg-white">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl md:text-4xl font-black text-slate-800 mb-12">Tecnologías Utilizadas</h2>
                <div class="flex flex-wrap justify-center items-center gap-x-12 gap-y-8">
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fab fa-php text-7xl text-indigo-400"></i>
                        <p class="mt-2 font-bold text-lg">PHP</p>
                    </div>
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fas fa-database text-7xl text-blue-500"></i>
                        <p class="mt-2 font-bold text-lg">MySQL</p>
                    </div>
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fab fa-js-square text-7xl text-yellow-400"></i>
                        <p class="mt-2 font-bold text-lg">JavaScript</p>
                    </div>
                    <div class="text-center transition-transform hover:scale-110">
                        <i class="fab fa-html5 text-7xl text-orange-500"></i>
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
                    <p class="text-slate-400">© 2025. Todos los derechos reservados.</p>
                </div>
                <div class="flex items-center gap-6">
                    <p class="font-bold">Apoyado por:</p>
                    <div class="bg-white h-16 w-32 rounded-lg flex items-center justify-center">
                        <span class="text-slate-500 font-bold text-sm">Logo U</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Script para animaciones de entrada
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in-up');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('section > div').forEach(section => {
                observer.observe(section);
            });
        });
    </script>

    <script>
        // Script para el círculo 3D interactivo
        document.addEventListener('DOMContentLoaded', () => {
            const scene = document.querySelector('.scene-container');
            const circle = document.querySelector('.circle-main');
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
                
                const segmentContent = document.createElement('div');
                segmentContent.classList.add('segment-content');
                segmentContent.textContent = topic;
                segment.appendChild(segmentContent);
                segmentContainer.appendChild(segment);

                segment.style.transform = 'translate(-50%, -50%) translateZ(-100px) scale(0.5)';
                segment.dataset.transformExpanded = `translate(-50%, -50%) rotate(${angle}deg) translateY(-${radius}px) rotate(${-angle}deg) rotateY(0deg) translateZ(30px)`;

                segment.addEventListener('click', (e) => {
                    e.stopPropagation();
                    showTooltip(segment, topics[topic]);
                });
            });

            function showTooltip(parentSegment, text) {
                const existingTooltip = scene.querySelector('.tooltip-bubble');
                if (existingTooltip) existingTooltip.remove();

                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip-bubble';
                tooltip.textContent = text;
                scene.appendChild(tooltip);

                setTimeout(() => {
                    const parentRect = parentSegment.getBoundingClientRect();
                    const sceneRect = scene.getBoundingClientRect();
                    
                    tooltip.style.left = `${parentRect.left - sceneRect.left + (parentRect.width / 2) - (tooltip.offsetWidth / 2)}px`;
                    tooltip.style.top = `${parentRect.top - sceneRect.top - tooltip.offsetHeight - 15}px`;
                    tooltip.classList.add('visible');
                }, 10);

                document.addEventListener('click', () => tooltip.remove(), { once: true });
                tooltip.addEventListener('click', e => e.stopPropagation());
            }

            circle.addEventListener('click', () => {
                circle.classList.toggle('expanded');
                const segments = document.querySelectorAll('.segment');
                const isExpanded = circle.classList.contains('expanded');

                if (!isExpanded) {
                    const existingTooltip = scene.querySelector('.tooltip-bubble');
                    if (existingTooltip) existingTooltip.remove();
                }

                segments.forEach(segment => {
                    if (isExpanded) {
                        segment.style.transform = segment.dataset.transformExpanded;
                    } else {
                        segment.style.transform = 'translate(-50%, -50%) translateZ(-100px) scale(0.5)';
                    }
                });
            });
        });
    </script>
</body>
</html>