<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$ejercicio = $data['ejercicio'] ?? '';
$respuesta_usuario = $data['respuesta_usuario'] ?? '';
$nivel_detalle = $data['nivel_detalle'] ?? 'basico';

if (is_array($ejercicio)) {
    $pregunta = trim((string)($ejercicio['pregunta'] ?? ''));
    $opciones = $ejercicio['opciones'] ?? [];
    $solucion = trim((string)($ejercicio['solucion'] ?? ''));
    $tema = trim((string)($ejercicio['tema'] ?? ''));
    $explicacion = trim((string)($ejercicio['explicacion'] ?? ''));

    $opciones_texto = '';
    if (is_array($opciones) && count($opciones) > 0) {
        $opciones_limpias = array_map(static fn($op) => trim((string)$op), $opciones);
        $opciones_texto = implode(', ', $opciones_limpias);
    }

    $partes = [];
    if ($tema !== '') $partes[] = "Tema: $tema";
    if ($pregunta !== '') $partes[] = "Pregunta: $pregunta";
    if ($opciones_texto !== '') $partes[] = "Opciones: $opciones_texto";
    if ($solucion !== '') $partes[] = "Respuesta correcta: $solucion";
    if ($explicacion !== '') $partes[] = "Explicación base: $explicacion";

    $ejercicio = implode("\n", $partes);
} else {
    $ejercicio = trim((string)$ejercicio);
}

$respuesta_usuario = trim((string)$respuesta_usuario);
if ($respuesta_usuario === '') {
    $respuesta_usuario = 'Sin respuesta del estudiante';
}

$api_key = 'AIzaSyBYIXHnktk95AfK-rJFJqVfwLQLOdExcVk'; // <--- COLOCA TU API KEY AQUÍ
// Cambia el nombre del modelo a "gemini-pro" que es el universal

// Fíjate que ahora dice "v1beta" en lugar de "v1"
// Cambiamos a gemini-2.5-flash que SÍ tiene capa gratuita
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;

if ($nivel_detalle === 'profundo') {
    $prompt = "Eres el tutor virtual de 'Mathsics'. Tu objetivo es enseñar de forma clara, empática y muy paciente. 
    Aquí tienes el contexto del ejercicio: '$ejercicio'. 
    El estudiante respondió: '$respuesta_usuario'. 
    
    Primero, evalúa si su respuesta es correcta. 
    - Si es CORRECTA: Felicítalo con entusiasmo y explícale brevemente por qué el concepto matemático detrás de su respuesta es el adecuado.
    - Si es INCORRECTA: No lo desanimes. Explícale el concepto fundamental paso a paso utilizando analogías sencillas de la vida diaria (como si le explicaras a un niño). Revélale la respuesta correcta y cómo llegar a ella lógicamente.
    
    REGLA ESTRICTA: Tu respuesta será leída en voz alta por un sintetizador de voz. 
    - NO uses Markdown (nada de asteriscos, negritas o guiones). 
    - Escribe las ecuaciones o fórmulas con palabras para que suenen naturales (ejemplo: 'x al cuadrado' en vez de 'x^2', o 'un medio' en vez de '1/2').
    - Mantén un tono conversacional, motivador y conciso pero profundo en el aprendizaje.";
} else {
    $prompt = "Eres el tutor amigable de 'Mathsics'. 
    Ejercicio: '$ejercicio'. 
    Respuesta del estudiante: '$respuesta_usuario'. 
    
    - Si acertó: Felicítalo brevemente y refuerza su logro.
    - Si falló: Explícale en un solo párrafo corto por qué falló, cuál es la respuesta correcta y dale un tip rápido para que no vuelva a equivocarse. Usa un lenguaje facilísimo de entender.
    
    REGLA ESTRICTA: Tu texto se leerá en voz alta. 
    - NO uses caracteres especiales, asteriscos, ni formato matemático complejo. Escribe todo como si fuera un guion de radio. 
    - Sé directo, muy motivador y mantén tu respuesta corta.";
}

$payload = [
    "contents" => [[ "parts" => [ ["text" => $prompt] ] ]]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

$gemini_data = json_decode($response, true);
$explicacion = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? 'Hubo un error al conectar con el tutor virtual.';


// Esto te dirá el error real en la consola de red del navegador:
if (isset($gemini_data['error'])) {
    echo json_encode(['explicacion' => "Error de API: " . $gemini_data['error']['message']]);
    exit;
}

echo json_encode(['explicacion' => $explicacion]);