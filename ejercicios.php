<?php
session_start();
include_once 'generador_ejercicios.php';

header('Content-Type: application/json');

$tema = $_GET['tema'] ?? null;
$cantidad = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 5;

$ejercicios = generarEjercicios($tema, $cantidad);

if (isset($ejercicios['error'])) {
    http_response_code(500);
    echo json_encode($ejercicios);
} else {
    echo json_encode($ejercicios);
}
?>
