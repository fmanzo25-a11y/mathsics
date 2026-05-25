<?php
$api_key = 'AIzaSyD8nY6AlAQLYJmhdFStp6epgG69pBWffgI'; // Tu API Key
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para evitar problemas locales

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<h1>Modelos disponibles:</h1>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: sans-serif;'>";
echo "<tr><th>Nombre API (Este es el que debes usar)</th><th>Nombre Comercial</th><th>Soporta Texto?</th></tr>";

if (isset($data['models'])) {
    foreach ($data['models'] as $modelo) {
        if (strpos($modelo['name'], 'gemini') !== false) {
            $metodos = implode(", ", $modelo['supportedGenerationMethods'] ?? []);
            echo "<tr>";
            echo "<td><strong>" . str_replace('models/', '', $modelo['name']) . "</strong></td>";
            echo "<td>" . $modelo['displayName'] . "</td>";
            echo "<td>" . $metodos . "</td>";
            echo "</tr>";
        }
    }
} else {
    echo "Error: <pre>" . print_r($data, true) . "</pre>";
}
echo "</table>";
?>