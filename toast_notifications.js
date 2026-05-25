document.addEventListener('DOMContentLoaded', () => {
    // 1. Inyectar contenedor de Toasts en el body
    const toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.className = 'fixed bottom-4 right-4 z-50 flex flex-col gap-3 pointer-events-none';
    document.body.appendChild(toastContainer);

    // 2. Pre-cargar el sonido
    const toastAudio = new Audio('sonidos/xp.mp3');
    toastAudio.volume = 0.5;

    // Función para crear y mostrar un toast
    function showToast(notificacion) {
        // Variables de diseño según tipo
        let bgColor = '';
        let iconClass = '';
        let title = '';
        let redirectUrl = 'notificaciones.php';

        if (notificacion.tipo === 'duelo') {
            bgColor = 'from-red-500 to-orange-500';
            iconClass = 'fa-swords';
            title = '¡Nuevo Reto a Duelo!';
            redirectUrl = 'menu_duelo.php';
        } else if (notificacion.tipo === 'peticion') {
            bgColor = 'from-orange-400 to-amber-500';
            iconClass = 'fa-user-plus';
            title = '¡Nueva Solicitud!';
        } else {
            bgColor = 'from-blue-500 to-indigo-500';
            iconClass = 'fa-bell';
            title = 'Notificación';
        }

        // Crear el elemento HTML
        const toastEl = document.createElement('div');
        toastEl.className = `transform translate-x-full opacity-0 pointer-events-auto bg-white rounded-xl shadow-2xl border-l-4 overflow-hidden flex flex-col w-72 sm:w-80 transition-all duration-500 ease-out cursor-pointer hover:-translate-y-1 hover:shadow-xl`;
        
        // Asignamos un color de borde según el tipo (Tailwind no permite interpolar clases parciales fácilmente, pero sí podemos inyectar un border-color)
        if (notificacion.tipo === 'duelo') toastEl.style.borderColor = '#ef4444'; // red-500
        else toastEl.style.borderColor = '#f97316'; // orange-500

        toastEl.innerHTML = `
            <div class="bg-gradient-to-r ${bgColor} px-4 py-2 flex items-center justify-between text-white">
                <div class="font-bold flex items-center gap-2">
                    <i class="fas ${iconClass}"></i> ${title}
                </div>
                <button class="toast-close text-white/80 hover:text-white transition focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 text-sm text-gray-700 font-semibold bg-white/90 backdrop-blur-sm">
                ${notificacion.mensaje}
            </div>
        `;

        toastContainer.appendChild(toastEl);

        // Reproducir sonido
        toastAudio.play().catch(e => console.log('Audio autoplay prevented:', e));

        // Animación de entrada
        requestAnimationFrame(() => {
            toastEl.classList.remove('translate-x-full', 'opacity-0');
        });

        // Click principal para redireccionar
        toastEl.addEventListener('click', (e) => {
            if (!e.target.closest('.toast-close')) {
                window.location.href = redirectUrl;
            }
        });

        // Click en la X para cerrar
        const closeBtn = toastEl.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            closeToast(toastEl);
        });

        // Autocerrar después de 8 segundos
        setTimeout(() => {
            if (toastEl.parentNode) {
                closeToast(toastEl);
            }
        }, 8000);
    }

    function closeToast(toastEl) {
        toastEl.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (toastEl.parentNode) toastContainer.removeChild(toastEl);
        }, 500);
    }

    // 3. Polling de notificaciones
    async function fetchToasts() {
        try {
            const response = await fetch('toast_api.php');
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.toasts && data.toasts.length > 0) {
                    data.toasts.forEach((toastData, index) => {
                        // Desfasar un poco las notificaciones si llegan varias al mismo tiempo
                        setTimeout(() => {
                            showToast(toastData);
                        }, index * 800);
                    });
                }
            }
        } catch (error) {
            console.error("Error fetching toasts:", error);
        }
    }

    // Revisar cada 10 segundos
    setInterval(fetchToasts, 10000);
    // Y una vez al iniciar (con un pequeño retraso de 2s)
    setTimeout(fetchToasts, 2000);
});
