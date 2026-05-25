document.addEventListener('DOMContentLoaded', () => {
    // --- REFERENCIAS A ELEMENTOS DEL DOM ---
    const panelPregunta = document.getElementById('panel-pregunta');
    const panelResultados = document.getElementById('panel-resultados');
    const numeroPreguntaElem = document.getElementById('numero-pregunta');
    const preguntaTextoElem = panelPregunta.querySelector('h3');
    const opcionesRespuestaElem = document.getElementById('opciones-respuesta');
    const temporizadorBarra = document.getElementById('temporizador-barra');
    const puntuacionJ1Elem = document.getElementById('puntuacion-j1');
    const puntuacionJ2Elem = document.getElementById('puntuacion-j2');

    const oponenteNombreElem = puntuacionJ2Elem.previousElementSibling;
    const oponenteAvatarElem = oponenteNombreElem.previousElementSibling;
    const temaElem = document.querySelector('h1');

    // --- ESTADO DEL JUEGO ---
    let id_duelo = null;
    let preguntas = [];
    let preguntaActualIndex = 0;
    let miPuntuacion = 0;
    let temporizador;

    // --- FUNCIÓN PRINCIPAL PARA INICIAR EL DUELO ---
    async function iniciarDuelo() {
        const urlParams = new URLSearchParams(window.location.search);
        id_duelo = urlParams.get('id_duelo');

        if (!id_duelo) {
            alert("ID de duelo no encontrado. Volviendo al menú.");
            window.location.href = 'menu_duelo.php';
            return;
        }

        try {
            const response = await fetch(`duelo_api.php?action=iniciar&id_duelo=${id_duelo}`);
            const data = await response.json();

            if (data.status === 'listo') {
                preguntas = data.preguntas;
                oponenteNombreElem.textContent = data.oponente.nombre;
                oponenteAvatarElem.src = data.oponente.avatar || 'images/sinfoto.jpeg';
                temaElem.textContent = `Duelo de ${data.tema}`;
                mostrarSiguientePregunta();
            } else {
                throw new Error(data.message || "No se pudo iniciar el duelo.");
            }
        } catch (error) {
            console.error("Error al iniciar el duelo:", error);
            alert(error.message);
            window.location.href = 'menu_duelo.php';
        }
    }

    // --- FUNCIÓN PARA MOSTRAR PREGUNTAS ---
    function mostrarSiguientePregunta() {
        if (preguntaActualIndex >= preguntas.length) {
            finalizarDuelo();
            return;
        }

        const pregunta = preguntas[preguntaActualIndex];
        numeroPreguntaElem.textContent = `Pregunta ${preguntaActualIndex + 1}/${preguntas.length}`;
        preguntaTextoElem.innerHTML = pregunta.pregunta; // Usar innerHTML por si la pregunta contiene formato

        opcionesRespuestaElem.innerHTML = '';
        pregunta.opciones.forEach((opcion, index) => {
            const button = document.createElement('button');
            button.className = 'bg-white/20 p-4 rounded-lg text-2xl font-semibold hover:bg-white/40 transition duration-200';
            button.textContent = opcion;
            button.addEventListener('click', () => seleccionarRespuesta(opcion, index));
            opcionesRespuestaElem.appendChild(button);
        });

        iniciarTemporizador();
    }

    // --- FUNCIÓN PARA MANEJAR LA SELECCIÓN DE RESPUESTA ---
    async function seleccionarRespuesta(respuestaSeleccionada, indexSeleccionado) {
        clearTimeout(temporizador);

        const formData = new FormData();
        formData.append('id_duelo', id_duelo);
        formData.append('pregunta_index', preguntaActualIndex);
        formData.append('respuesta', respuestaSeleccionada);

        try {
            const response = await fetch('duelo_api.php?action=responder', { method: 'POST', body: formData });
            const data = await response.json();
            console.log('Respuesta de la API:', data); // Para depuración

            if (data.status === 'ok') {
                if (data.puntos_ganados > 0) {
                    miPuntuacion += data.puntos_ganados;
                    puntuacionJ1Elem.textContent = miPuntuacion;
                }
            }

            // Feedback visual mejorado
            const botones = opcionesRespuestaElem.querySelectorAll('button');
            botones.forEach((btn, index) => {
                btn.disabled = true;
                // Marcar la respuesta correcta en verde
                if (btn.textContent == data.respuesta_correcta) {
                    btn.classList.add('bg-green-500', 'text-white');
                }
                // Si la seleccionada es incorrecta, marcarla en rojo
                if (index === indexSeleccionado && btn.textContent != data.respuesta_correcta) {
                    btn.classList.add('bg-red-500', 'text-white');
                }
            });

            // Decidir si continuar o finalizar
            if (data.duelo_terminado) {
                setTimeout(finalizarDuelo, 1500); // Un poco más de tiempo para ver la corrección
            } else {
                preguntaActualIndex++;
                setTimeout(mostrarSiguientePregunta, 1500);
            }

        } catch (error) {
            console.error("Error al responder:", error);
            alert("Se perdió la conexión. El duelo no puede continuar.");
            window.location.href = 'menu_duelo.php';
        }
    }

    // --- FUNCIÓN PARA EL TEMPORIZADOR ---
    function iniciarTemporizador() {
        let tiempo = 100;
        temporizadorBarra.style.width = '100%';
        temporizadorBarra.classList.remove('bg-red-500', 'bg-yellow-400');
        temporizadorBarra.classList.add('bg-green-500');

        const intervalo = 100; // ms
        temporizador = setInterval(() => {
            tiempo -= (intervalo / 100); // Ajustado para 10 segundos
            temporizadorBarra.style.width = `${tiempo}%`;
            if (tiempo <= 50) temporizadorBarra.classList.replace('bg-green-500', 'bg-yellow-400');
            if (tiempo <= 25) temporizadorBarra.classList.replace('bg-yellow-400', 'bg-red-500');
            
            if (tiempo <= 0) {
                clearInterval(temporizador);
                seleccionarRespuesta('tiempo_agotado', -1);
            }
        }, intervalo);
    }

    // --- FUNCIÓN PARA FINALIZAR EL DUELO ---
    async function finalizarDuelo() {
        panelPregunta.classList.add('hidden');
        panelResultados.classList.remove('hidden');

        const formData = new FormData();
        formData.append('id_duelo', id_duelo);

        try {
            const response = await fetch('duelo_api.php?action=finalizar', { method: 'POST', body: formData });
            const data = await response.json();

            const resultadoTexto = panelResultados.querySelector('h3');
            const miPuntuacionFinalElem = panelResultados.querySelector('#mi-puntuacion-final');
            const xpGanadaElem = panelResultados.querySelector('#xp-ganada');
            const nombreOponente = oponenteNombreElem.textContent;

            if (data.status === 'finalizado') {
                let mensaje = `¡Empate contra ${nombreOponente}!`;
                resultadoTexto.classList.remove('text-green-500', 'text-red-500');

                if (data.resultado === 'victoria') {
                    mensaje = `¡Victoria sobre ${nombreOponente}!`;
                    resultadoTexto.classList.add('text-green-500');
                } else if (data.resultado === 'derrota') {
                    mensaje = `Derrota ante ${nombreOponente}`;
                    resultadoTexto.classList.add('text-red-500');
                }
                resultadoTexto.textContent = mensaje;

                miPuntuacionFinalElem.textContent = data.mi_puntuacion;
                puntuacionJ2Elem.textContent = data.puntuacion_oponente;
                xpGanadaElem.textContent = `+${data.xp_ganada} XP`;
            } else {
                throw new Error(data.message || 'Error al finalizar el duelo');
            }
        } catch (error) {
            console.error("Error al finalizar el duelo:", error);
            panelResultados.querySelector('h3').textContent = 'Error al cargar los resultados';
        }
    }

    // --- INICIAMOS EL PROCESO ---
    iniciarDuelo();
});