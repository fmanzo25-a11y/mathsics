-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 01-05-2026 a las 06:54:34
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `maths`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `ActualizarPosicionesRanking` ()   BEGIN

    SET @rank_counter = 0;


    UPDATE `ranking` r
    JOIN (
        SELECT 
            `id_usuario`,
            -- ROW_NUMBER() asigna un ranking basado en el orden de los puntos
            ROW_NUMBER() OVER (ORDER BY `puntos` DESC) AS `new_posicion`
        FROM `ranking`
    ) AS `ranked_users` ON r.`id_usuario` = ranked_users.`id_usuario`
    -- Finalmente, establecemos la nueva posición calculada
    SET r.`posicion` = ranked_users.`new_posicion`;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(11) NOT NULL,
  `id_publicacion` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `contenido` varchar(500) NOT NULL,
  `fecha_comentario` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`id_comentario`, `id_publicacion`, `id_usuario`, `contenido`, `fecha_comentario`) VALUES
(1, NULL, NULL, '', '2025-09-10 03:18:59'),
(2, NULL, NULL, '', '2025-09-10 03:27:35'),
(3, NULL, NULL, '', '2025-09-10 03:27:39'),
(4, NULL, 7, '', '2025-09-10 03:28:14'),
(5, NULL, 7, 'contenido', '2025-09-10 03:31:59'),
(6, NULL, 7, 'contenido', '2025-09-10 03:32:41'),
(7, 7, 7, 'contenido', '2025-09-10 03:38:46'),
(8, 7, 7, 'hola', '2025-09-10 03:39:15'),
(9, 7, NULL, 'holaaa que buen post', '2025-09-10 04:22:09'),
(10, 7, NULL, 'holaaa muy weno', '2025-09-10 04:25:55'),
(11, 7, 7, 'olaaa', '2025-09-10 04:26:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `desafios`
--

CREATE TABLE `desafios` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `recompensa_xp` int(11) DEFAULT 10,
  `icono` varchar(100) DEFAULT NULL,
  `objetivo_cantidad` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `desafios`
--

INSERT INTO `desafios` (`id`, `titulo`, `descripcion`, `recompensa_xp`, `icono`, `objetivo_cantidad`) VALUES
(1, 'Resuelve 5 ejercicios', 'Completa 5 ejercicios de cualquier tema', 15, 'fas fa-chart-line', 5),
(2, 'Gana un duelo', 'Vence a otro jugador en un duelo', 20, 'fas fa-sword', 1),
(3, 'Acumula 100 XP', 'Obtén 100 puntos de experiencia', 25, 'fas fa-star', 100);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `duelos`
--

CREATE TABLE `duelos` (
  `id` int(11) NOT NULL,
  `jugador1_id` int(11) NOT NULL,
  `jugador2_id` int(11) DEFAULT NULL,
  `tema` varchar(50) NOT NULL,
  `estado` enum('buscando','en_curso','finalizado') NOT NULL DEFAULT 'buscando',
  `ganador_id` int(11) DEFAULT NULL,
  `puntuacion_j1` int(11) DEFAULT 0,
  `puntuacion_j2` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_finalizacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `duelos`
--

INSERT INTO `duelos` (`id`, `jugador1_id`, `jugador2_id`, `tema`, `estado`, `ganador_id`, `puntuacion_j1`, `puntuacion_j2`, `fecha_creacion`, `fecha_finalizacion`) VALUES
(8, 29, 24, 'Aritmetica', 'en_curso', NULL, 0, 200, '2025-09-24 19:33:27', NULL),
(9, 29, NULL, 'Aritmetica', 'buscando', NULL, 0, 0, '2025-09-25 22:01:16', NULL),
(10, 29, NULL, 'Aritmetica', 'buscando', NULL, 0, 0, '2025-10-01 00:14:20', NULL),
(11, 29, NULL, 'Aritmetica', 'buscando', NULL, 0, 0, '2025-10-09 19:44:44', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ejercicios`
--

CREATE TABLE `ejercicios` (
  `id` int(11) NOT NULL,
  `tema` varchar(100) DEFAULT NULL,
  `subtema` varchar(100) DEFAULT NULL,
  `plantilla_texto` varchar(255) DEFAULT NULL,
  `parametros` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`parametros`)),
  `formula_solucion` varchar(255) NOT NULL,
  `dificultad` enum('Facil','Intermedio','Dificil','') NOT NULL,
  `explicacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ejercicios`
--

INSERT INTO `ejercicios` (`id`, `tema`, `subtema`, `plantilla_texto`, `parametros`, `formula_solucion`, `dificultad`, `explicacion`) VALUES
(1, 'Aritmética', 'Suma simple', '¿Cuánto es {num1} + {num2}?', '{\r\n      \"num1\": {\"min\": 10, \"max\": 50}, \r\n      \"num2\": {\"min\": 10, \"max\": 50},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"solucion + 10\",\r\n        \"distractor_2\": \"solucion - 10\",\r\n        \"distractor_3\": \"solucion + 2\"\r\n      }\r\n    }', 'num1 + num2', 'Facil', 'Para sumar dos números, simplemente júntalos para obtener el total. En este caso, {num1} más {num2} es igual a la solución.'),
(2, 'Aritmética', 'Resta simple', '¿Cuánto es {num1} - {num2}?', '{\r\n      \"num1\": {\"min\": 50, \"max\": 100}, \r\n      \"num2\": {\"min\": 10, \"max\": 49},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"solucion + 1\",\r\n        \"distractor_2\": \"solucion - 2\",\r\n        \"distractor_3\": \"solucion + 10\"\r\n      }\r\n    }', 'num1 - num2', 'Facil', 'La resta consiste en quitar una cantidad de otra. Aquí, al quitar {num2} de {num1}, obtenemos la respuesta correcta.'),
(3, 'Ecuaciones Lineales', 'Selecciona la respuesta correcta', 'Si {a}x + {b} = {c}, ¿cuál es el valor de x?', '{\r\n      \"a\": {\"min\": 2, \"max\": 5}, \r\n      \"b\": {\"min\": 1, \"max\": 10}, \r\n      \"x_sol\": {\"min\": 2, \"max\": 10},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"x_sol + 1\",\r\n        \"distractor_2\": \"x_sol - 1\",\r\n        \"distractor_3\": \"x_sol + 2\"\r\n      }\r\n    }', 'x_sol', 'Facil', 'Para resolver para x, primero resta {b} de {c}. Luego, divide ese resultado entre {a}. La fórmula es x = ({c} - {b}) / {a}.'),
(4, 'Algoritmos Matemáticos', 'Completa la secuencia', '¿Qué número sigue en la secuencia: {n1}, {n2}, {n3}, ...?', '{\r\n      \"inicio\": {\"min\": 1, \"max\": 10}, \r\n      \"incremento\": {\"min\": 2, \"max\": 5},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"solucion + 1\",\r\n        \"distractor_2\": \"solucion - 1\",\r\n        \"distractor_3\": \"solucion - incremento\"\r\n      }\r\n    }', 'n3 + incremento', 'Facil', 'Esta es una secuencia aritmética. Observa que cada número aumenta en una cantidad fija ({incremento}). Para encontrar el siguiente número, simplemente suma {incremento} a {n3}.'),
(5, 'Aritmética', 'Multiplicación', '¿Cuál es el resultado de {num1} x {num2}?', '{\r\n      \"num1\": {\"min\": 2, \"max\": 12}, \r\n      \"num2\": {\"min\": 2, \"max\": 12},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"solucion + num1\",\r\n        \"distractor_2\": \"solucion - num2\",\r\n        \"distractor_3\": \"solucion + 1\"\r\n      }\r\n    }', 'num1 * num2', 'Facil', 'La multiplicación es una suma repetida. Multiplicar {num1} por {num2} nos da el producto total.'),
(6, 'Factorización de Ecuaciones', 'Identifica el factor', '¿Cuál de los siguientes números es un factor de {numero}?', '{\r\n      \"factor1\": {\"min\": 2, \"max\": 5},\r\n      \"factor2\": {\"min\": 6, \"max\": 10},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"numero + 1\",\r\n        \"distractor_2\": \"numero - 1\",\r\n        \"distractor_3\": \"factor1 + 1\"\r\n      }\r\n    }', 'factor1', 'Intermedio', 'Un factor es un número que divide a otro sin dejar residuo. El número {numero} se obtiene multiplicando {factor1} por {factor2}, por lo que {factor1} es un factor.'),
(7, 'Ecuaciones Lineales', 'Ecuación con negativos', 'Si {a}x - {b} = {c}, ¿cuál es el valor de x?', '{\r\n      \"a\": {\"min\": 2, \"max\": 5},\r\n      \"b\": {\"min\": 1, \"max\": 10},\r\n      \"x_sol\": {\"min\": 2, \"max\": 10},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"x_sol + 1\",\r\n        \"distractor_2\": \"x_sol - 1\",\r\n        \"distractor_3\": \"(c-b)/a\"\r\n      }\r\n    }', 'x_sol', 'Intermedio', 'Para despejar x, primero suma {b} a {c}. Después, divide el resultado entre {a}. La fórmula es x = ({c} + {b}) / {a}.'),
(8, 'Algoritmos Matemáticos', 'Secuencia de multiplicación', '¿Qué número sigue en la secuencia: {n1}, {n2}, {n3}, ...?', '{\r\n      \"inicio\": {\"min\": 1, \"max\": 3},\r\n      \"multiplicador\": {\"min\": 2, \"max\": 4},\r\n      \"opciones\": {\r\n        \"distractor_1\": \"solucion + multiplicador\",\r\n        \"distractor_2\": \"solucion - multiplicador\",\r\n        \"distractor_3\": \"n3 + multiplicador\"\r\n      }\r\n    }', 'n3 * multiplicador', 'Intermedio', 'Esta es una secuencia geométrica. Cada número se obtiene multiplicando el anterior por {multiplicador}. Por lo tanto, el siguiente número es {n3} x {multiplicador}.'),
(9, 'Ecuaciones Lineales', 'Ecuación simple (ax + b = c)', 'Si {a}x + {b} = {c}, ¿cuál es el valor de x?', '{\"a\": {\"min\":2, \"max\":5}, \"b\": {\"min\":1, \"max\":10}, \"x_sol\": {\"min\":2, \"max\":10}, \"opciones\": {\"d1\": \"x_sol+1\", \"d2\": \"x_sol-1\", \"d3\": \"x_sol+2\"}}', 'x_sol', 'Facil', 'Para encontrar x, primero debes mover {b} al otro lado de la ecuación restándolo de {c}. Luego, divide el resultado por {a}. Fórmula: x = ({c} - {b}) / {a}.'),
(10, 'Ecuaciones Lineales', 'Ecuación con resta (ax - b = c)', 'Si {a}x - {b} = {c}, ¿cuál es el valor de x?', '{\"a\": {\"min\":2, \"max\":5}, \"b\": {\"min\":1, \"max\":10}, \"x_sol\": {\"min\":2, \"max\":10}, \"opciones\": {\"d1\": \"x_sol+1\", \"d2\": \"x_sol-1\", \"d3\": \"(c-b)/a\"}}', 'x_sol', 'Facil', 'Para despejar x, primero suma {b} a ambos lados de la ecuación ({c} + {b}). Luego, divide ese resultado entre {a}.'),
(11, 'Ecuaciones Lineales', 'Ecuación invertida (c = ax + b)', 'Si {c} = {a}x + {b}, ¿cuánto vale x?', '{\"a\": {\"min\":2, \"max\":5}, \"b\": {\"min\":1, \"max\":10}, \"x_sol\": {\"min\":2, \"max\":10}, \"opciones\": {\"d1\": \"x_sol+1\", \"d2\": \"x_sol-1\", \"d3\": \"c-b\"}}', 'x_sol', 'Facil', 'No importa el orden. Para aislar x, primero resta {b} de {c}. Luego, divide el resultado entre {a}. Fórmula: x = ({c} - {b}) / {a}.'),
(12, 'Ecuaciones Lineales', 'Problema verbal simple', 'El doble de un número más {b} es igual a {c}. ¿Cuál es ese número?', '{\"b\": {\"min\":1, \"max\":10}, \"x_sol\": {\"min\":2, \"max\":15}, \"opciones\": {\"d1\": \"x_sol-1\", \"d2\": \"x_sol+2\", \"d3\": \"c-b\"}}', 'x_sol', 'Facil', 'La frase \"el doble de un número\" se traduce como 2x. La ecuación es 2x + {b} = {c}. Resuelve para x restando {b} de {c} y luego dividiendo entre 2.'),
(13, 'Ecuaciones Lineales', 'Variable en ambos lados', 'Resuelve la ecuación: {a}x + {b} = {c}x + {d}', '{\"c\": {\"min\":1, \"max\":3}, \"b\": {\"min\":1, \"max\":10}, \"x_sol\": {\"min\":2, \"max\":8}, \"opciones\": {\"d1\": \"x_sol+1\", \"d2\": \"x_sol-1\", \"d3\": \"x_sol*2\"}}', 'x_sol', 'Intermedio', 'Agrupa los términos con x de un lado y los números del otro. Resta {c}x de {a}x y resta {b} de {d}. Luego, despeja x.'),
(14, 'Ecuaciones Lineales', 'Con paréntesis a(x + b) = c', 'Encuentra el valor de x en: {a}(x + {b}) = {c}', '{\"a\": {\"min\":2, \"max\":6}, \"b\": {\"min\":1, \"max\":5}, \"x_sol\": {\"min\":2, \"max\":10}, \"opciones\": {\"d1\": \"x_sol-1\", \"d2\": \"x_sol+1\", \"d3\": \"c/a\"}}', 'x_sol', 'Intermedio', 'Primero, divide {c} entre {a}. Esto te dará el valor de (x + {b}). Finalmente, resta {b} de ese resultado para encontrar x.'),
(15, 'Ecuaciones Lineales', 'Variable en ambos lados con resta', 'Resuelve la ecuación: {a}x - {b} = {c}x + {d}', '{\"c\": {\"min\":1, \"max\":3}, \"b\": {\"min\":1, \"max\":10}, \"x_sol\": {\"min\":2, \"max\":8}, \"opciones\": {\"d1\": \"x_sol+1\", \"d2\": \"x_sol-1\", \"d3\": \"x_sol+2\"}}', 'x_sol', 'Intermedio', 'Junta los términos con x en un lado y los números en el otro. La ecuación se simplifica para luego poder despejar x fácilmente.'),
(16, 'Ecuaciones Lineales', 'Problema de edades', 'Ana tiene {a} años más que Luis. Si la suma de sus edades es {suma}, ¿cuántos años tiene Luis?', '{\"a\": {\"min\":2, \"max\":10}, \"luis_edad\": {\"min\":5, \"max\":20}, \"opciones\": {\"d1\": \"luis_edad+1\", \"d2\": \"luis_edad-1\", \"d3\": \"suma-a\"}}', 'luis_edad', 'Dificil', 'Si la edad de Luis es x, la de Ana es x + {a}. La suma es x + (x + {a}) = {suma}. Resuelve para x: 2x = {suma} - {a}.'),
(17, 'Ecuaciones Lineales', 'Ecuación con fracción simple', 'Resuelve para x: (x / {a}) + {b} = {c}', '{\"a\": {\"min\":2, \"max\":5}, \"b\": {\"min\":1, \"max\":10}, \"x_sol\": {\"min\":6, \"max\":20}, \"opciones\": {\"d1\": \"x_sol+a\", \"d2\": \"x_sol-a\", \"d3\": \"c-b\"}}', 'x_sol', 'Dificil', 'Para resolver, primero resta {b} de {c}. Luego, multiplica el resultado por {a} para encontrar el valor de x.'),
(18, 'Ecuaciones Lineales', 'Problema de perímetro', 'El perímetro de un rectángulo es {p}. Si el largo es {largo}, ¿cuál es el ancho?', '{\"ancho\": {\"min\":5, \"max\":15}, \"largo\": {\"min\":16, \"max\":30}, \"opciones\": {\"d1\": \"ancho+1\", \"d2\": \"ancho-1\", \"d3\": \"p-largo\"}}', 'ancho', 'Dificil', 'El perímetro es P = 2*largo + 2*ancho. Con los datos: {p} = 2*{largo} + 2*ancho. Despeja el ancho.'),
(19, 'Álgebra', 'Evaluar expresión', 'Si x = {x_val}, ¿cuál es el valor de {a}x + {b}?', '{\"a\": {\"min\":2, \"max\":6}, \"b\": {\"min\":1, \"max\":15}, \"x_val\": {\"min\":2, \"max\":10}, \"opciones\": {\"d1\": \"solucion+1\", \"d2\": \"solucion-1\", \"d3\": \"a+b\"}}', 'a*x_val + b', 'Facil', 'Simplemente sustituye el valor de x en la expresión. Multiplica {a} por {x_val} y luego suma {b} al resultado.'),
(20, 'Álgebra', 'Simplificar términos semejantes', 'Simplifica la expresión: {a}x + {b}x', '{\"a\": {\"min\":2, \"max\":15}, \"b\": {\"min\":2, \"max\":15}, \"opciones\": {\"d1\": \"a*b\", \"d2\": \"a-b\", \"d3\": \"a\"}}', 'a+b', 'Facil', 'Como ambos términos tienen \"x\", son semejantes. Solo necesitas sumar los coeficientes (los números que los acompañan): {a} + {b}.'),
(21, 'Álgebra', 'Resolver x² = a', 'Si x² = {a_squared}, ¿cuál es el valor positivo de x?', '{\"a\": {\"min\":3, \"max\":12}, \"opciones\": {\"d1\": \"a*2\", \"d2\": \"a_squared\", \"d3\": \"a-1\"}}', 'a', 'Facil', 'Para encontrar x, debes calcular la raíz cuadrada de {a_squared}. El número que, multiplicado por sí mismo, da {a_squared} es la respuesta.'),
(22, 'Álgebra', 'Suma de polinomios', 'Suma los siguientes polinomios: ({a}x + {b}) + ({c}x + {d})', '{\"a\": {\"min\":1, \"max\":8}, \"b\": {\"min\":1, \"max\":8}, \"c\": {\"min\":1, \"max\":8}, \"d\": {\"min\":1, \"max\":8}, \"opciones\": {\"d1\": \"a+c-1\", \"d2\": \"b+d+1\", \"d3\": \"a+d\"}}', '(a+c)x + (b+d)', 'Facil', 'Suma los términos con x ({a}x + {c}x) y los términos numéricos ({b} + {d}) por separado para obtener el resultado final.'),
(23, 'Álgebra', 'Factor común', '¿Cuál es el factor común en la expresión: {a}x + {ab}?', '{\"a\": {\"min\":2, \"max\":7}, \"b\": {\"min\":2, \"max\":7}, \"opciones\": {\"d1\": \"b\", \"d2\": \"x\", \"d3\": \"ab\"}}', 'a', 'Intermedio', 'El factor común es el número o variable que puede dividir a ambos términos. En este caso, tanto {a}x como {ab} son divisibles entre {a}.'),
(24, 'Álgebra', 'Diferencia de cuadrados', 'Factoriza la expresión: x² - {a_squared}', '{\"a\": {\"min\":3, \"max\":12}, \"opciones\": {\"d1\": \"(x-a)(x-a)\", \"d2\": \"(x+a)(x+a)\", \"d3\": \"x-a\"}}', '(x - a)(x + a)', 'Intermedio', 'Esto es una diferencia de cuadrados. Se factoriza como el producto de dos binomios conjugados: (x - a)(x + a), donde a es la raíz cuadrada de {a_squared}.'),
(25, 'Álgebra', 'Producto de binomios', 'Multiplica: (x + {a})(x + {b})', '{\"a\": {\"min\":1, \"max\":6}, \"b\": {\"min\":1, \"max\":6}, \"opciones\": {\"d1\": \"x^2 + (a-b)x + a*b\", \"d2\": \"x^2 + (a+b)x - a*b\", \"d3\": \"x + a+b\"}}', 'x^2 + (a+b)x + a*b', 'Intermedio', 'Usa el método FOIL: Multiplica los Primeros términos (x*x), los Exteriores (x*{b}), los Interiores ({a}*x) y los Últimos ({a}*{b}). Luego suma los términos semejantes.'),
(26, 'Álgebra', 'Sistema de ecuaciones simple', 'Si x + y = {suma} y x - y = {resta}, ¿cuál es el valor de x?', '{\"x_sol\": {\"min\":5, \"max\":15}, \"y_sol\": {\"min\":1, \"max\":10}, \"opciones\": {\"d1\": \"x_sol-1\", \"d2\": \"y_sol\", \"d3\": \"suma\"}}', 'x_sol', 'Intermedio', 'Este es un sistema de ecuaciones. Una forma rápida de resolverlo es sumar las dos ecuaciones: (x+y) + (x-y) = {suma} + {resta}. Esto simplifica a 2x = {suma} + {resta}.'),
(27, 'Álgebra', 'Resta de polinomios', 'Resta: ({a}x² + {b}x) - ({c}x² + {d}x)', '{\"a\": {\"min\":5, \"max\":15}, \"b\": {\"min\":5, \"max\":15}, \"c\": {\"min\":1, \"max\":4}, \"d\": {\"min\":1, \"max\":4}, \"opciones\": {\"d1\": \"a+c\", \"d2\": \"b+d\", \"d3\": \"a-d\"}}', '(a-c)x^2 + (b-d)x', 'Dificil', 'Resta los coeficientes de los términos semejantes: ({a} - {c}) para x² y ({b} - {d}) para x. Recuerda que el signo negativo afecta a todo el segundo polinomio.'),
(28, 'Álgebra', 'Resolver cuadrática factorizada', '¿Cuáles son las soluciones de la ecuación (x - {a})(x + {b}) = 0?', '{\"a\": {\"min\":1, \"max\":10}, \"b\": {\"min\":1, \"max\":10}, \"opciones\": {\"d1\": \"-a y b\", \"d2\": \"-a y -b\", \"d3\": \"a y b\"}}', 'a y -b', 'Dificil', 'Para que el producto de dos factores sea cero, al menos uno de ellos debe ser cero. Las soluciones son cuando x - {a} = 0 (x={a}) y cuando x + {b} = 0 (x=-{b}).'),
(29, 'Aritmética', 'División exacta', '¿Cuál es el resultado de {num1} / {num2}?', '{\r\n      \"solucion\": {\"min\": 2, \"max\": 12}, \r\n      \"num2\": {\"min\": 2, \"max\": 10}\r\n    }', 'solucion', 'Facil', 'La división consiste en repartir una cantidad en partes iguales. Debes encontrar qué número, multiplicado por {num2}, da como resultado {num1}.'),
(30, 'Aritmética', 'Operaciones combinadas', 'Resuelve la operación: ({num1} + {num2}) x {num3}', '{\r\n      \"num1\": {\"min\": 2, \"max\": 10},\r\n      \"num2\": {\"min\": 2, \"max\": 10},\r\n      \"num3\": {\"min\": 2, \"max\": 5}\r\n    }', '(num1 + num2) * num3', 'Intermedio', 'Según el orden de las operaciones, primero se resuelve lo que está dentro del paréntesis ({num1} + {num2}) y luego se multiplica el resultado por {num3}.'),
(31, 'Aritmética', 'Cálculo de porcentaje', '¿Cuánto es el {porcentaje}% de {numero}?', '{\r\n      \"porcentaje_options\": [10, 20, 25, 50],\r\n      \"multiplo\": {\"min\": 2, \"max\": 10}\r\n    }', '(numero * porcentaje) / 100', 'Facil', 'Para calcular un porcentaje, multiplica el número ({numero}) por el porcentaje ({porcentaje}) y luego divide el resultado entre 100.'),
(32, 'Aritmética', 'Problema verbal de reparto', 'Si se reparten {num1} caramelos entre {num2} niños, ¿cuántos caramelos recibe cada uno?', '{\r\n      \"solucion\": {\"min\": 2, \"max\": 12}, \r\n      \"num2\": {\"min\": 2, \"max\": 10}\r\n    }', 'solucion', 'Facil', 'Este es un problema de división simple. Divide el número total de caramelos ({num1}) entre el número de niños ({num2}) para saber cuántos le tocan a cada uno.'),
(33, 'Aritmética', 'Encontrar el número que falta (suma)', '{num1} + ? = {resultado}', '{\r\n      \"num1\": {\"min\": 10, \"max\": 50},\r\n      \"num_faltante\": {\"min\": 5, \"max\": 25}\r\n    }', 'num_faltante', 'Facil', 'Para encontrar el número que falta, simplemente resta el número conocido ({num1}) del resultado total ({resultado}).'),
(34, 'Aritmética', 'Comparación de operaciones', '¿Qué resultado es mayor, {a} x {b} o {c} + {d}?', '{\r\n      \"a\": {\"min\": 3, \"max\": 8},\r\n      \"b\": {\"min\": 3, \"max\": 8},\r\n      \"c\": {\"min\": 10, \"max\": 30},\r\n      \"d\": {\"min\": 10, \"max\": 30}\r\n    }', 'comparacion', 'Intermedio', 'Debes resolver ambas operaciones por separado y luego comparar los dos resultados para ver cuál es el número mayor.'),
(35, 'Geometría', 'Área de un cuadrado', '¿Cuál es el área de un cuadrado con un lado de {lado} unidades?', '{\"lado\": {\"min\": 2, \"max\": 15}, \"opciones\": {\"d1\": \"solucion+lado\", \"d2\": \"lado*4\"}}', 'lado * lado', 'Facil', 'El área de un cuadrado se calcula multiplicando la longitud de un lado por sí mismo (lado²). En este caso, {lado} × {lado} = {solucion}.'),
(36, 'Geometría', 'Perímetro de un rectángulo', 'Calcula el perímetro de un rectángulo de {largo} de largo y {ancho} de ancho.', '{\"largo\": {\"min\": 10, \"max\": 20}, \"ancho\": {\"min\": 5, \"max\": 9}, \"opciones\": {\"d1\": \"largo*ancho\", \"d2\": \"solucion-ancho\"}}', '2 * (largo + ancho)', 'Facil', 'El perímetro de un rectángulo es la suma de todos sus lados, o 2 veces el largo más 2 veces el ancho. La fórmula es P = 2(largo + ancho).'),
(37, 'Geometría', 'Área de un triángulo', 'Un triángulo tiene una base de {base} y una altura de {altura}. ¿Cuál es su área?', '{\"base\": {\"min\": 6, \"max\": 20}, \"altura\": {\"min\": 5, \"max\": 10}, \"opciones\": {\"d1\": \"base*altura\", \"d2\": \"solucion*2\"}}', '(base * altura) / 2', 'Intermedio', 'El área de un triángulo se calcula multiplicando su base por su altura y dividiendo el resultado entre 2. Fórmula: A = (b × h) / 2.'),
(38, 'Geometría', 'Circunferencia de un círculo', 'Calcula la circunferencia de un círculo con un radio de {radio} (usa π ≈ 3.14).', '{\"radio\": {\"min\": 3, \"max\": 10}, \"opciones\": {\"d1\": \"3.14 * radio * radio\", \"d2\": \"solucion/2\"}}', '2 * 3.14 * radio', 'Intermedio', 'La circunferencia de un círculo es la distancia alrededor de él. Se calcula con la fórmula C = 2πr. En este caso, 2 × 3.14 × {radio} = {solucion}.'),
(39, 'Geometría', 'Volumen de un cubo', '¿Cuál es el volumen de un cubo cuyo lado mide {lado} unidades?', '{\"lado\": {\"min\": 3, \"max\": 8}, \"opciones\": {\"d1\": \"lado*lado\", \"d2\": \"lado*6\"}}', 'lado * lado * lado', 'Dificil', 'El volumen de un cubo se encuentra elevando la longitud de su lado al cubo (lado³). En este caso, {lado} × {lado} × {lado} = {solucion}.'),
(40, 'Estadística', 'Calcular la media', 'Calcula el promedio (media) de los siguientes números: {n1}, {n2}, {n3}.', '{\"n1\": {\"min\": 5, \"max\": 20}, \"n2\": {\"min\": 5, \"max\": 20}, \"n3\": {\"min\": 5, \"max\": 20}, \"opciones\": {\"d1\": \"solucion+2\", \"d2\": \"solucion-2\"}}', '(n1 + n2 + n3) / 3', 'Facil', 'La media o promedio se encuentra sumando todos los números del conjunto y dividiendo el total entre la cantidad de números. ({n1} + {n2} + {n3}) / 3 = {solucion}.'),
(41, 'Estadística', 'Calcular el rango', 'Calcula el rango del siguiente conjunto de datos: {n1}, {n2}, {n3}, {n4}.', '{\"n1\": {\"min\": 1, \"max\": 10}, \"n2\": {\"min\": 11, \"max\": 20}, \"n3\": {\"min\": 21, \"max\": 30}, \"n4\": {\"min\": 31, \"max\": 40}}', 'rango', 'Facil', 'El rango es la diferencia entre el valor máximo y el valor mínimo en un conjunto de datos. Primero, ordena los números para encontrar el más grande y el más pequeño, luego réstalos.'),
(42, 'Estadística', 'Probabilidad simple', 'En una bolsa hay {rojas} canicas rojas y {azules} canicas azules. ¿Cuál es la probabilidad de sacar una canica roja?', '{\"rojas\": {\"min\": 2, \"max\": 8}, \"azules\": {\"min\": 2, \"max\": 8}, \"opciones\": {\"d1\": \"azules / (rojas+azules)\", \"d2\": \"rojas/azules\"}}', 'rojas / (rojas + azules)', 'Intermedio', 'La probabilidad se calcula dividiendo el número de resultados favorables (canicas rojas) entre el número total de resultados posibles (todas las canicas).'),
(43, 'Estadística', 'Encontrar la mediana', 'Encuentra la mediana del conjunto de datos: {datos_str}.', '{\"n1\": {\"min\": 1, \"max\": 10}, \"n2\": {\"min\": 11, \"max\": 20}, \"n3\": {\"min\": 21, \"max\": 30}, \"n4\": {\"min\": 31, \"max\": 40}, \"n5\": {\"min\": 41, \"max\": 50}}', 'mediana', 'Intermedio', 'La mediana es el valor central de un conjunto de datos ordenado. Primero, ordena los números de menor a mayor y el número que queda justo en medio es la mediana.'),
(44, 'Estadística', 'Encontrar la moda', 'Identifica la moda en el siguiente conjunto de números: {datos_str}.', '{\"base1\": {\"min\": 1, \"max\": 5}, \"base2\": {\"min\": 6, \"max\": 10}, \"moda_val\": {\"min\": 11, \"max\": 15}}', 'moda_val', 'Dificil', 'La moda es el número que aparece con mayor frecuencia en un conjunto de datos. Simplemente busca el número que más se repite.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes`
--

CREATE TABLE `likes` (
  `id_like` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_publicacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `likes`
--

INSERT INTO `likes` (`id_like`, `id_usuario`, `id_publicacion`) VALUES
(1, 7, 7),
(2, 7, 6),
(3, 14, 7),
(4, 14, 6),
(5, 25, 7),
(6, 29, 7),
(7, 29, 6),
(8, 29, 5);

--
-- Disparadores `likes`
--
DELIMITER $$
CREATE TRIGGER `aumentar_like_post` AFTER INSERT ON `likes` FOR EACH ROW BEGIN
    -- Actualizamos la tabla 'posts'
    -- Le sumamos 1 a la columna 'likes'
    -- La condición WHERE asegura que solo actualicemos el post correcto.
    -- La palabra clave 'NEW' se refiere a los datos de la fila que acaba de ser insertada en la tabla 'likes'.
    UPDATE `posts`
    SET `likes` = `likes` + 1
    WHERE `id_publicacion` = NEW.id_publicacion;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `disminuir_like_post` AFTER DELETE ON `likes` FOR EACH ROW BEGIN
    -- Actualizamos la tabla 'posts'
    -- Le restamos 1 a la columna 'likes'
    -- La palabra clave 'OLD' se refiere a los datos de la fila que acaba de ser eliminada de la tabla 'likes'.
    UPDATE `posts`
    SET `likes` = `likes` - 1
    WHERE `id_publicacion` = OLD.id_publicacion;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id_mensaje` int(11) NOT NULL,
  `id_remitente` int(11) DEFAULT NULL,
  `id_destinatario` int(11) DEFAULT NULL,
  `contenido` varchar(1000) NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `leido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_usuario_origen` int(11) DEFAULT NULL,
  `tipo` enum('aviso','peticion','noticia','informe') DEFAULT NULL,
  `mensaje` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `leida` enum('1','0') DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `id_usuario_origen`, `tipo`, `mensaje`, `fecha`, `leida`) VALUES
(1, 7, NULL, 'aviso', 'hola esta es una prueba', '2025-09-06 23:48:33', '0'),
(4, 7, 9, 'peticion', 'El usuario po ha solicitado que actives su cuenta de niño.', '2025-09-07 07:39:11', '1'),
(5, 7, 28, 'peticion', 'El usuario poooo ha solicitado que actives su cuenta de niño.', '2025-09-10 18:26:55', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres`
--

CREATE TABLE `padres` (
  `id_padre` int(11) DEFAULT NULL,
  `id_hijo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posts`
--

CREATE TABLE `posts` (
  `id_publicacion` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `contenido` varchar(900) NOT NULL,
  `likes` int(100) DEFAULT 0,
  `autenticado` enum('0','1') DEFAULT '0',
  `categoria` enum('algebra','aritmetica','geometria','ninguna') DEFAULT 'ninguna',
  `imagen_url` varchar(255) DEFAULT NULL,
  `num_comentarios` int(100) DEFAULT 0,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `posts`
--

INSERT INTO `posts` (`id_publicacion`, `id_usuario`, `titulo`, `contenido`, `likes`, `autenticado`, `categoria`, `imagen_url`, `num_comentarios`, `fecha_publicacion`) VALUES
(2, 7, 'hola', 'esta es una prueba', 0, '0', 'aritmetica', NULL, 0, '2025-09-07 00:37:45'),
(3, 7, 'l', 'l', 0, '0', 'ninguna', NULL, 0, '2025-09-07 01:02:46'),
(4, 7, 'Prueba', 'Hola este es un nuevo post de prueba para probar que tan bien se ve', 0, '0', 'aritmetica', 'matematicas/uploads/post_68bdbea997e498.53720315.jpg', 0, '2025-09-07 17:19:37'),
(5, 7, 'lalalala', 'aver si yaa', 2, '0', 'aritmetica', 'matematicas/uploads/post_68bdc0e3c3f8f0.31727134.jpg', 0, '2025-09-07 17:29:07'),
(6, 7, 'ola', 'vengo a explicarte como mejorar una tecnica que podrias usar seria esta:', 6, '0', 'ninguna', 'uploads/post_68bdcdce047775.62120843.jpg', 0, '2025-09-07 18:24:14'),
(7, 10, 'ola', 'soy nuevo', 8, '0', 'aritmetica', 'uploads/post_68bdce618c6ee0.65708860.png', 0, '2025-09-07 18:26:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ranking`
--

CREATE TABLE `ranking` (
  `id_usuario` int(11) DEFAULT NULL,
  `puntos` int(200) DEFAULT NULL,
  `posicion` int(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ranking`
--

INSERT INTO `ranking` (`id_usuario`, `puntos`, `posicion`) VALUES
(2, 0, NULL),
(1, 20, NULL),
(3, 0, NULL),
(4, 0, NULL),
(5, 0, NULL),
(6, 0, NULL),
(7, 105, NULL),
(8, 0, NULL),
(9, 0, NULL),
(10, 0, NULL),
(11, 4725, NULL),
(12, 375, NULL),
(13, 0, NULL),
(14, 240, NULL),
(15, 0, NULL),
(16, 0, NULL),
(17, 0, NULL),
(18, 0, NULL),
(19, 225, NULL),
(20, 2760, NULL),
(21, 60, NULL),
(22, 0, NULL),
(23, 10005, NULL),
(24, 0, NULL),
(25, 180, NULL),
(26, 0, NULL),
(27, 0, NULL),
(28, 0, NULL),
(29, 375, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_ejercicios`
--

CREATE TABLE `resultados_ejercicios` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_ejercicio` int(11) NOT NULL,
  `respuesta_correcta` tinyint(1) NOT NULL,
  `tema` varchar(100) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resultados_ejercicios`
--

INSERT INTO `resultados_ejercicios` (`id`, `id_usuario`, `id_ejercicio`, `respuesta_correcta`, `tema`, `fecha`) VALUES
(1, 29, 31, 1, 'Aritmética', '2025-10-16 20:25:28'),
(2, 29, 2, 0, 'Aritmética', '2025-10-16 20:25:28'),
(3, 29, 30, 1, 'Aritmética', '2025-10-16 20:25:28'),
(4, 29, 5, 1, 'Aritmética', '2025-10-16 20:25:28'),
(5, 29, 33, 1, 'Aritmética', '2025-10-16 20:25:28'),
(6, 29, 1, 0, 'Aritmética', '2025-10-16 20:31:22'),
(7, 29, 5, 1, 'Aritmética', '2025-10-16 20:31:22'),
(8, 29, 29, 1, 'Aritmética', '2025-10-16 20:31:22'),
(9, 29, 31, 1, 'Aritmética', '2025-10-16 20:31:22'),
(10, 29, 2, 0, 'Aritmética', '2025-10-16 20:31:22'),
(11, 29, 30, 0, 'Aritmética', '2025-10-19 21:13:37'),
(12, 29, 2, 1, 'Aritmética', '2025-10-19 21:13:37'),
(13, 29, 1, 1, 'Aritmética', '2025-10-19 21:13:37'),
(14, 29, 5, 1, 'Aritmética', '2025-10-19 21:13:37'),
(15, 29, 33, 1, 'Aritmética', '2025-10-19 21:13:37'),
(16, 29, 31, 0, 'Aritmética', '2025-10-19 21:18:08'),
(17, 29, 30, 1, 'Aritmética', '2025-10-19 21:18:08'),
(18, 29, 34, 0, 'Aritmética', '2025-10-19 21:18:08'),
(19, 29, 29, 1, 'Aritmética', '2025-10-19 21:18:08'),
(20, 29, 1, 1, 'Aritmética', '2025-10-19 21:18:08'),
(21, 29, 1, 0, 'Aritmética', '2025-10-20 02:52:52'),
(22, 29, 30, 0, 'Aritmética', '2025-10-20 02:52:52'),
(23, 29, 5, 0, 'Aritmética', '2025-10-20 02:52:52'),
(24, 29, 29, 1, 'Aritmética', '2025-10-20 02:52:52'),
(25, 29, 34, 0, 'Aritmética', '2025-10-20 02:52:52'),
(26, 25, 1, 1, 'Aritmética', '2026-03-25 22:53:21'),
(27, 25, 32, 1, 'Aritmética', '2026-03-25 22:53:21'),
(28, 25, 31, 1, 'Aritmética', '2026-03-25 22:53:21'),
(29, 25, 2, 1, 'Aritmética', '2026-03-25 22:53:21'),
(30, 25, 29, 1, 'Aritmética', '2026-03-25 22:53:21'),
(31, 25, 34, 0, 'Aritmética', '2026-03-26 00:18:25'),
(32, 25, 29, 0, 'Aritmética', '2026-03-26 00:18:25'),
(33, 25, 32, 0, 'Aritmética', '2026-03-26 00:18:25'),
(34, 25, 5, 1, 'Aritmética', '2026-03-26 00:18:25'),
(35, 25, 1, 0, 'Aritmética', '2026-03-26 00:18:25'),
(36, 25, 32, 0, 'Aritmética', '2026-03-26 04:21:55'),
(37, 25, 29, 0, 'Aritmética', '2026-03-26 04:21:55'),
(38, 25, 33, 0, 'Aritmética', '2026-03-26 04:21:55'),
(39, 25, 30, 1, 'Aritmética', '2026-03-26 04:21:55'),
(40, 25, 1, 1, 'Aritmética', '2026-03-26 04:21:55'),
(41, 25, 1, 1, 'Aritmética', '2026-03-26 18:26:26'),
(42, 25, 29, 1, 'Aritmética', '2026-03-26 18:26:26'),
(43, 25, 32, 1, 'Aritmética', '2026-03-26 18:26:26'),
(44, 25, 2, 1, 'Aritmética', '2026-03-26 18:26:26'),
(45, 25, 5, 0, 'Aritmética', '2026-03-26 18:26:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `scratch_games`
--

CREATE TABLE `scratch_games` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `scratch_id` varchar(255) NOT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `likes` int(11) DEFAULT 0,
  `num_comentarios` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `scratch_games`
--

INSERT INTO `scratch_games` (`id`, `id_usuario`, `titulo`, `scratch_id`, `fecha_publicacion`, `likes`, `num_comentarios`) VALUES
(1, 29, 'wow', '1226692283', '2025-10-08 22:55:23', 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `scratch_likes`
--

CREATE TABLE `scratch_likes` (
  `id` int(11) NOT NULL,
  `id_juego` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trofeos`
--

CREATE TABLE `trofeos` (
  `id_usuario` int(11) DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_otorgamiento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contrasena` varchar(100) DEFAULT NULL,
  `edad` varchar(20) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `nivel` int(10) NOT NULL DEFAULT 1,
  `limite_xp` int(10) NOT NULL DEFAULT 20,
  `xp` int(11) NOT NULL DEFAULT 0,
  `foto_de_perfil` varchar(1000) NOT NULL DEFAULT 'images/sinfoto.jpeg',
  `tipo` enum('Admin','Normal','Nino','') NOT NULL DEFAULT 'Normal',
  `estado` enum('Activa','Inactiva') NOT NULL DEFAULT 'Activa',
  `token_verificacion` varchar(255) DEFAULT NULL,
  `verificado` int(1) NOT NULL DEFAULT 0,
  `racha` int(10) NOT NULL DEFAULT 0,
  `ultima_actividad` date DEFAULT NULL COMMENT 'Registra la última fecha en que el usuario ganó XP',
  `insignia_racha` enum('1','0') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `contrasena`, `edad`, `fecha_registro`, `nivel`, `limite_xp`, `xp`, `foto_de_perfil`, `tipo`, `estado`, `token_verificacion`, `verificado`, `racha`, `ultima_actividad`, `insignia_racha`) VALUES
(1, ' Jarem ', '0', '7117', '19', '2025-09-04 01:59:08', 1, 20, 1, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(2, 'carlitos ', 'carlos@gmail.com', '1612', '16', '2025-09-05 02:37:18', 1, 10, 5, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(3, 'jaremcito', 'mmanzo@gmail.com', '$2y$10$a1Yoav3pa3DTVZ7/RaR12OIA2Xb/ydBBITg8ysJPzMg702iH4w0Ni', '19', '2025-09-06 23:14:48', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(4, 'jaremcito', 'menzo@gmail.com', '$2y$10$xSx0nLuXOOlizEbNaoajFuJFXH935/g9nsT4GE3QXh.fAWctlkVfm', '12', '2025-09-06 23:25:39', 1, 20, 0, 'images/sinfoto.jpeg', 'Nino', 'Inactiva', NULL, 0, 0, NULL, '1'),
(5, 'jaremcito', 'lol@gmail.com', '$2y$10$9NsAVf7ycvgkSn4mggblRei.weZywKHix4QMdDip6hk5NeHvcTTj2', '19', '2025-09-06 23:28:44', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(6, 'ala', 'ala@gmail.com', '$2y$10$RhwUD0qZR/eFpVLURKDDyOeAO1wE5yoJ54IrGQxipQpy3/DDxtBRK', '19', '2025-09-06 23:32:43', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(7, 'jaremcito', 'andrea@gmail.com', '$2y$10$rWqsjg1JvmmK/BofQZdoTuEbs02kdKnV0FIR3Ih.emQ5Cn50T7JK2', '19', '2025-09-06 23:39:15', 7, 225, 158, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 3, '2025-09-08', '1'),
(8, 'lala', 'po@gmail.com', '$2y$10$i16ngAmO6jmxi.fRVcJ9AOhoKJtEjXeA00fE2bNvoTkM8zDQu7NI6', '10', '2025-09-07 07:37:50', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(9, 'po', 'lala@gmail.com', '$2y$10$ahTpHOgvTqEso.ZtL9/5u.tsMy/KGJlYix6xD7iTKA.nzmFbqPbO6', '11', '2025-09-07 07:39:11', 1, 20, 0, 'images/sinfoto.jpeg', 'Nino', 'Activa', NULL, 0, 0, NULL, '1'),
(10, 'Pollito rico', 'jaremcito@gmail.com', '$2y$10$Vlsq.fdUC.K9BvTq4VcHrepjLtZOq4SJU5TDd4HqoArxxqepFAKUW', '19', '2025-09-07 17:22:07', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(11, 'Samuel', 'samuelcanorosas@gmail.com', '$2y$10$glDUGHfPCmWo2w.Dp3TekO3MObNl2rZoNezIdXhMbg96ac/s/ZaY6', '18', '2025-09-08 13:56:05', 12, 1702, 1354, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(12, 'Alain', 'alaincontreras@hotmail.com', '$2y$10$9dbbzXLi/IsrhI3S4hyXNujLT/uLYUThlJjWkLElvmRkuInUpLXNy', '25', '2025-09-08 14:02:32', 8, 337, 38, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(13, 'jaremcito', 'jaremmanzo8989@gmail.com', '$2y$10$EoZme2o/nX7uYji.Y8h1puulJ9FQH4PsylAIY6fx0p3Ra4lR6gDPK', '18', '2025-09-08 14:11:28', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(14, 'piter', 'piter@gmail.com', '$2y$10$Z/f6BnCISt/ATuAyo0i2feWj7vITN2HwSZeu8jW11xZrVAnE0Ox2m', '18', '2025-09-08 14:13:32', 6, 150, 23, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(15, 'Joss', 'joselinepitacio@gmail.com', '$2y$10$oQ3OgcrpTvDr8Uu8cD/vse21/RmnRz01da/BHxU.nRNc2RPwME5T6', '18', '2025-09-08 14:20:59', 3, 45, 10, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(16, 'crakaliasma', 'crakalisma@gmail.com', '$2y$10$B6hBbIjwh39TBmhET/fnQu2M4Ze7YGwF6WngCLTgswjUYPvIKzubS', '17', '2025-09-08 14:21:31', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(17, 'Crakaliasma', 'crakaliasma@gmail.com', '$2y$10$YeOwgran0XV33JIALH/0GekFG2CucuHbFb3xp8ERBObvam/2W1AGq', '19', '2025-09-08 14:23:54', 4, 67, 25, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(18, 'David', 'david13.alejandrobcs@gmail.com', '$2y$10$zxjhCHrHanFpCrk9P0tU4OLaoX4D3/rg7sOWPzrSOsoLpX51jYqa2', '18', '2025-09-08 14:24:17', 3, 45, 10, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(19, 'Jazmín', 'taecitodejazmin@gmail.com', '$2y$10$Q.bLdY2LIz8xrVGiIKJqUuZXVPNmyI7DhzVXd2./lp2Jia.WknTOy', '21', '2025-09-08 14:37:08', 6, 150, 113, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(20, 'Piter2', 'pedritouwu@gmail.com', '$2y$10$AiheBSKCv.ju.mtwnes1KOWdkaNsXgKyPEqATGNWvZkuqcnygok/q', '18', '2025-09-08 17:29:06', 11, 1135, 524, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(21, 'Luis', 'adal@hotmail.com', '$2y$10$nGVMFx6FWW2gemPq9MxoxOTH76sUIEZRao0B9eTBc8/q8WzI5vzPy', '48', '2025-09-08 17:33:00', 3, 45, 10, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(22, 'Alex', 'alexprosito@gmail.com', '$2y$10$7u9F3veuIYkJf0ON7v5BK.fXmNUJQ/GN2avjiJG0n63/Ea0AAOm2K', '18', '2025-09-08 17:36:38', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 0, NULL, '1'),
(23, 'Alex', 'alexander@gmail.com', '$2y$10$pUhbZNzncQ5rNcUpPUR3G.nK/CO2SncPZo6O51QtpprufcNXpCZwG', '18', '2025-09-08 17:38:22', 14, 3829, 2379, 'images/sinfoto.jpeg', 'Normal', 'Activa', NULL, 0, 1, '2025-09-08', '1'),
(24, 'jarem', 'xxbranxxterxx@gmail.com', '$2y$10$AKHQelcBj2w/etVhJOVrK.3XmwryR6ZcToqB/pCCIhKAuXqVamwgW', '19', '2025-09-10 05:18:07', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Activa', '2130bb867d637553b78ca36adff6322579c7c7d2ff7df9866284a9dd219254da', 1, 0, NULL, '1'),
(25, 'jarem', 'anghle-@outlook.com', '$2a$10$ZruM8p/MvrrH4He/OwgDVOxTH2lKRO5HofP.Ksz2HSOoBE/c4sXbC', '19', '2025-09-10 05:22:05', 5, 100, 18, 'images/sinfoto.jpeg', 'Normal', 'Activa', '25e5cb9eee09fb391c0ec0c7df44a4424aa068e28db53fab98aee8602b5549ac', 1, 2, '2026-03-26', '1'),
(26, 'andreabbella', 'yeny.palma.suastegui@gmail.com', '$2y$10$4UFhq7kUmEWSLUf5MW2Z..aIeLM5VLg7ZIu/WmsvdSUFVKaL/57jG', '19', '2025-09-10 05:25:09', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Inactiva', '56390dc9e35eac5cfa786b47e24717bfcf1aab259a6ed4bab66b2334bf4e2834', 0, 0, NULL, '1'),
(27, 'andiyjarem', 'yjaremcitoandi@gmail.com', '$2y$10$pX3nsx5BeOz1KSuYn.mLje8ZwkCVwyg5vSXHi0U7wJRj3I9fHDmGi', '19', '2025-09-10 05:31:33', 1, 20, 0, 'images/sinfoto.jpeg', 'Normal', 'Inactiva', 'f2ca962e89033df80b659c8d4ab0ec68c8c8a6abd56e7df4405096cedc3109bc', 0, 0, NULL, '1'),
(28, 'poooo', 'nueo@gmail.com', '$2y$10$NKpChYiknzw3C0u9hX0CmOdtVydFIRIV.GLW2gwVj5pxxY1vjbx9u', '14', '2025-09-10 18:26:55', 1, 20, 0, 'images/sinfoto.jpeg', 'Nino', 'Activa', '982b53cb60c66f087f73819ed61a4f442700e9ef0850c69460fa8168f377c289', 0, 0, NULL, '1'),
(29, 'Jarem', 'jaremmanzo@gmail.com', '$2y$10$.95R3KoB3qD7gd1haPcknujhcqDibFjb1F9rE8lx92ZG.nHgSAK7G', '19', '2025-09-24 19:18:34', 6, 150, 113, 'images/sinfoto.jpeg', 'Normal', 'Activa', '58dbf2d2e7fb28c7cec6b3bbe5211a883e84c55fa6113981686ed5fc65f30261', 1, 1, '2025-10-19', '0');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `DesactivarCuentaNinoAlCrear` BEFORE INSERT ON `usuarios` FOR EACH ROW BEGIN

    IF NEW.tipo = 'Nino' THEN
     
        SET NEW.estado = 'Inactiva';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `GestionarRachaUsuario` BEFORE UPDATE ON `usuarios` FOR EACH ROW BEGIN
    -- El trigger solo se activa si la columna 'xp' realmente ha cambiado.
    IF NEW.xp <> OLD.xp THEN
    
        -- Si es la primera vez que el usuario hace algo, su racha empieza en 1.
        IF OLD.ultima_actividad IS NULL THEN
            SET NEW.racha = 1;
            
        -- Si ya había jugado antes, comparamos las fechas.
        ELSE
            -- Si la actividad de hoy es exactamente un día después de la última, la racha continúa.
            IF DATEDIFF(CURDATE(), OLD.ultima_actividad) = 1 THEN
                SET NEW.racha = OLD.racha + 1;
                
            -- Si ha pasado más de un día, la racha se rompió y se reinicia a 1.
            ELSEIF DATEDIFF(CURDATE(), OLD.ultima_actividad) > 1 THEN
                SET NEW.racha = 1;
                
            -- Si la actividad es en el mismo día (DATEDIFF = 0), no hacemos nada a la racha.
            END IF;
        END IF;
        
        -- Finalmente, actualizamos siempre la fecha de la última actividad a la fecha de hoy.
        SET NEW.ultima_actividad = CURDATE();
        
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `registro_nuevo_usuario` AFTER INSERT ON `usuarios` FOR EACH ROW BEGIN
    -- Aquí va el código SQL que se ejecutará
    -- Por ejemplo, insertar un log en otra tabla:
    INSERT INTO ranking (id_usuario, puntos)
    VALUES (NEW.id, 0);

    -- O enviar un correo, o actualizar otra tabla, etc.
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_desafios`
--

CREATE TABLE `usuario_desafios` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_desafio` int(11) NOT NULL,
  `fecha_asignado` date NOT NULL,
  `progreso` int(11) DEFAULT 0,
  `estado` enum('activo','completado','expirado') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_desafios`
--

INSERT INTO `usuario_desafios` (`id`, `id_usuario`, `id_desafio`, `fecha_asignado`, `progreso`, `estado`) VALUES
(1, 25, 1, '2026-03-25', 0, 'activo'),
(2, 25, 2, '2026-03-25', 0, 'activo'),
(3, 25, 3, '2026-03-25', 0, 'activo'),
(4, 25, 1, '2026-03-26', 0, 'activo'),
(5, 25, 3, '2026-03-26', 0, 'activo'),
(6, 25, 2, '2026-03-26', 0, 'activo'),
(7, 25, 1, '2026-04-08', 0, 'activo'),
(8, 25, 3, '2026-04-08', 0, 'activo'),
(9, 25, 2, '2026-04-08', 0, 'activo'),
(10, 25, 1, '2026-04-18', 0, 'activo'),
(11, 25, 2, '2026-04-18', 0, 'activo'),
(12, 25, 3, '2026-04-18', 0, 'activo'),
(13, 25, 3, '2026-04-28', 0, 'activo'),
(14, 25, 2, '2026-04-28', 0, 'activo'),
(15, 25, 1, '2026-04-28', 0, 'activo'),
(16, 25, 1, '2026-04-30', 0, 'activo'),
(17, 25, 2, '2026-04-30', 0, 'activo'),
(18, 25, 3, '2026-04-30', 0, 'activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `id_publicacion` (`id_publicacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `desafios`
--
ALTER TABLE `desafios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `duelos`
--
ALTER TABLE `duelos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jugador1_id` (`jugador1_id`),
  ADD KEY `jugador2_id` (`jugador2_id`),
  ADD KEY `ganador_id` (`ganador_id`);

--
-- Indices de la tabla `ejercicios`
--
ALTER TABLE `ejercicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id_like`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_publicacion` (`id_publicacion`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `id_remitente` (`id_remitente`),
  ADD KEY `id_destinatario` (`id_destinatario`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_usuario_origen` (`id_usuario_origen`);

--
-- Indices de la tabla `padres`
--
ALTER TABLE `padres`
  ADD KEY `id_padre` (`id_padre`),
  ADD KEY `id_hijo` (`id_hijo`);

--
-- Indices de la tabla `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id_publicacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `resultados_ejercicios`
--
ALTER TABLE `resultados_ejercicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_ejercicio` (`id_ejercicio`);

--
-- Indices de la tabla `scratch_games`
--
ALTER TABLE `scratch_games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `scratch_likes`
--
ALTER TABLE `scratch_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`id_juego`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `trofeos`
--
ALTER TABLE `trofeos`
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuario_desafios`
--
ALTER TABLE `usuario_desafios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_desafio` (`id_desafio`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `desafios`
--
ALTER TABLE `desafios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `duelos`
--
ALTER TABLE `duelos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `ejercicios`
--
ALTER TABLE `ejercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `likes`
--
ALTER TABLE `likes`
  MODIFY `id_like` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id_publicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `resultados_ejercicios`
--
ALTER TABLE `resultados_ejercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `scratch_games`
--
ALTER TABLE `scratch_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `scratch_likes`
--
ALTER TABLE `scratch_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `usuario_desafios`
--
ALTER TABLE `usuario_desafios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_publicacion`) REFERENCES `posts` (`id_publicacion`),
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `duelos`
--
ALTER TABLE `duelos`
  ADD CONSTRAINT `duelos_ibfk_1` FOREIGN KEY (`jugador1_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `duelos_ibfk_2` FOREIGN KEY (`jugador2_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `duelos_ibfk_3` FOREIGN KEY (`ganador_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`id_publicacion`) REFERENCES `posts` (`id_publicacion`);

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`id_remitente`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensajes_ibfk_2` FOREIGN KEY (`id_destinatario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `notificaciones_ibfk_2` FOREIGN KEY (`id_usuario_origen`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `padres`
--
ALTER TABLE `padres`
  ADD CONSTRAINT `padres_ibfk_1` FOREIGN KEY (`id_padre`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `padres_ibfk_2` FOREIGN KEY (`id_hijo`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ranking`
--
ALTER TABLE `ranking`
  ADD CONSTRAINT `ranking_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `resultados_ejercicios`
--
ALTER TABLE `resultados_ejercicios`
  ADD CONSTRAINT `resultados_ejercicios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `resultados_ejercicios_ibfk_2` FOREIGN KEY (`id_ejercicio`) REFERENCES `ejercicios` (`id`);

--
-- Filtros para la tabla `scratch_games`
--
ALTER TABLE `scratch_games`
  ADD CONSTRAINT `scratch_games_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `scratch_likes`
--
ALTER TABLE `scratch_likes`
  ADD CONSTRAINT `scratch_likes_ibfk_1` FOREIGN KEY (`id_juego`) REFERENCES `scratch_games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scratch_likes_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `trofeos`
--
ALTER TABLE `trofeos`
  ADD CONSTRAINT `trofeos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuario_desafios`
--
ALTER TABLE `usuario_desafios`
  ADD CONSTRAINT `usuario_desafios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `usuario_desafios_ibfk_2` FOREIGN KEY (`id_desafio`) REFERENCES `desafios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
