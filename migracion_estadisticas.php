<?php
include 'conexion.php';

try {
    $conn = Db::conectar();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS `resultados_ejercicios` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `id_usuario` INT NOT NULL,
        `id_ejercicio` INT NOT NULL,
        `respuesta_correcta` BOOLEAN NOT NULL,
        `tema` VARCHAR(100) NOT NULL,
        `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`),
        FOREIGN KEY (`id_ejercicio`) REFERENCES `ejercicios`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $conn->exec($sql);
    echo "¡Tabla 'resultados_ejercicios' creada exitosamente (o ya existía)!";

    echo "<br>Migración completada.";

} catch(PDOException $e) {
    echo "Error al crear la tabla: " . $e->getMessage();
}

$conn = null;
?>
