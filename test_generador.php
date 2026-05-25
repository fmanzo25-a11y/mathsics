<?php
include_once 'generador_ejercicios.php';

echo "Start...\n";
$start = microtime(true);
for ($i=0; $i<100; $i++) {
    $res = generarEjercicios('Todos', 10);
    if (isset($res['error'])) {
        echo "Error: " . $res['error'] . "\n";
    }
}
$end = microtime(true);
echo "Done in " . ($end - $start) . " seconds.\n";
