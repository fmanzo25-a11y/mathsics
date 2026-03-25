<?php
// Para depurar errores fatales, puedes descomentar temporalmente estas líneas
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Asegúrate de que las rutas a estos archivos sean correctas desde google_login_handler.php
require_once 'lib/JWT.php'; // La biblioteca para verificar el token
include_once 'conexion.php'; // Tu clase de conexión a la BD

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Importamos las clases de la biblioteca para usarlas
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');
$response = ['success' => false, 'error' => 'Petición inválida.'];

// --- Función para obtener las llaves públicas de Google (con caché) ---
// (Esta función no cambia y es necesaria para la validación)
function getGooglePublicKeys() {
    $cacheFile = 'google_keys.json';
    if (file_exists($cacheFile) && (filemtime($cacheFile) > (time() - 3600))) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    $json = file_get_contents('https://www.googleapis.com/oauth2/v3/certs');
    $keys = json_decode($json, true)['keys'];
    $publicKeys = [];
    foreach ($keys as $key) {
        $pkey = openssl_pkey_get_details(openssl_get_publickey(
            "-----BEGIN CERTIFICATE-----\n" . $key['x5c'][0] . "\n-----END CERTIFICATE-----"
        ));
        $publicKeys[$key['kid']] = new Key($pkey['key'], 'RS256');
    }
    file_put_contents($cacheFile, json_encode($publicKeys, JSON_UNESCAPED_SLASHES));
    return $publicKeys;
}

// --- Lógica principal del script ---
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? null;

if (!$token) {
    $response['error'] = 'No se recibió el token.';
    echo json_encode($response);
    exit();
}

$CLIENT_ID = '137288240716-5brrj54vv6bvncaroql5apraa90ig0v2.apps.googleusercontent.com'; // No olvides poner tu ID real


try {
    $keys = getGooglePublicKeys();
    $payload = JWT::decode($token, $keys, ['RS256']);

    if ($payload->aud !== $CLIENT_ID) throw new Exception('Audiencia de token incorrecta.');
    if ($payload->iss !== 'https://accounts.google.com' && $payload->iss !== 'accounts.google.com') throw new Exception('Emisor de token incorrecto.');

    // El token es válido, procedemos
    $email = $payload->email;
    $name = $payload->name;
    $picture = $payload->picture;

    $conn = Db::conectar();

    // 1. Buscamos si el usuario ya existe con ese correo
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // --- El usuario ya existe, verificamos su estado ---
        if ($user['estado'] === 'Activa') {
            // La cuenta está activa, iniciamos sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['tipo_cuenta'] = $user['tipo'];

            $response = ['success' => true, 'redirect' => 'menu.php'];
        } else {
            // La cuenta existe pero está inactiva
            $response['error'] = 'Esta cuenta está inactiva y no puede iniciar sesión.';
        }
    } else {
        // --- El usuario NO existe, lo creamos ---
        $stmt = $conn->prepare(
            "INSERT INTO usuarios (nombre, correo, contrasena, foto_de_perfil, tipo) 
             VALUES (:nombre, :correo, NULL, :foto_de_perfil, 'Normal')" // Se crea como tipo 'Normal' por defecto
        );
        $stmt->execute([
            ':nombre' => $name,
            ':correo' => $email,
            ':foto_de_perfil' => $picture
        ]);
        
        $new_user_id = $conn->lastInsertId();
        
        // Iniciamos sesión para el nuevo usuario
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['tipo_cuenta'] = 'Normal'; // Asignamos el tipo por defecto a la sesión

        $response = ['success' => true, 'redirect' => 'menu.php'];
    }

} catch (Exception $e) {
    error_log("Error de validación de token: " . $e->getMessage());
    $response['error'] = 'Token de Google inválido o la sesión ha expirado.';
}

echo json_encode($response);
?>