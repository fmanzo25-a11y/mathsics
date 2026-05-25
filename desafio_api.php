<?php
session_start();
include_once 'generador_ejercicios.php';

header('Content-Type: application/json');

$tema = $_GET['tema'] ?? 'Aleatorio';
$nivel_forzado = isset($_GET['nivel']) ? (int)$_GET['nivel'] : 1;
$user_id = $_SESSION['user_id'] ?? null;

// En modo desafío pedimos de 1 en 1, forzando el nivel progresivo
$ejercicios = generarEjercicios($tema, 1, $user_id, $nivel_forzado);

if (isset($ejercicios['error'])) {
    http_response_code(500);
    echo json_encode($ejercicios);
} else {
    echo json_encode($ejercicios[0]); // Devolvemos solo el objeto
}
?>
