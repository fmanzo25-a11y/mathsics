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

$api_key = 'AIzaSyD8nY6AlAQLYJmhdFStp6epgG69pBWffgI'; // <--- COLOCA TU API KEY AQUÍ
// Cambia el nombre del modelo a "gemini-pro" que es el universal

// Fíjate que ahora dice "v1beta" en lugar de "v1"
// Cambiamos a gemini-2.5-flash que SÍ tiene capa gratuita
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;

if ($nivel_detalle === 'profundo') {
    $prompt = "Si el estudiante esta bien explicale el porque esta bien su respuesta o  Si  el estudiante tiene la respuesta erronea entonces:El estudiante quiere una explicación más detallada sobre el tema matemático relacionado con el ejercicio: '$ejercicio'. Explica paso a paso el concepto fundamental. Usa un tono amigable, motivador y claro para leerse en voz alta, sin usar símbolos matemáticos extraños que la voz de un navegador no sepa leer.   explica el tema y dale la respuesta correcta y una  explicación de por qué esa es la respuesta correcta. explicalo como si hablaras con alguien que desconoce mucho del tema o como si se lo explicaras a un niño, sin usar tecnicismos complicados. tambien evita que la respuesta sea tan larga, hazla clara y concisa pero que a la vez explique profundamente el tema.  ";
} else {
    $prompt = " Si el estudiante esta bien explicale el porque esta bien su respuesta de manera profunda o  Si  el estudiante tiene la respuesta erronea entonces: Un estudiante cometió un error en este ejercicio matemático: '$ejercicio'. Su respuesta incorrecta fue: '$respuesta_usuario'. Actúa como un tutor de matemáticas amigable. Explícale en un párrafo breve por qué su respuesta es incorrecta y dale el  resultado pero explica porque y como ademas de tambien explicarlo de forma que hasta un niño pueda entenderlo. Tu respuesta será leída en voz alta, así que hazla conversacional, sin asteriscos ni símbolos raros. evita que la explicacion sea muy técnica o complicada, hazla fácil de entender y motivadora. ademas de ser corta";
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