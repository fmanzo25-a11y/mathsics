<?php
include_once 'conexion.php';

// --- MEJORA: Función para generar opciones de forma inteligente ---
function generarOpciones($solucion, $parametrosGenerados, $parametrosPlantilla) {
    $opciones = [$solucion];
    
    if (isset($parametrosPlantilla['opciones'])) {
        foreach ($parametrosPlantilla['opciones'] as $formula) {
            $formula = str_replace('solucion', is_numeric($solucion) ? $solucion : 0, $formula); // Usar 0 si la solucion no es numerica
            foreach ($parametrosGenerados as $param => $valor) {
                if (is_numeric($valor)) {
                    $formula = str_replace($param, $valor, $formula);
                }
            }
            if (is_string($formula) && preg_match('/^[0-9\+\-\*\/\(\)\. ]+$/', $formula)) {
                 @eval('$opciones[] = round(' . $formula . ', 2);');
            } else {
                $opciones[] = $formula;
            }
        }
    }

    while (count($opciones) < 4 && is_numeric($solucion)) {
        $distractor = $solucion + rand(-5, 5);
        if ($distractor !== $solucion) $opciones[] = round($distractor, 2);
    }

    $opciones = array_values(array_unique($opciones));
    shuffle($opciones);
    
    return array_slice($opciones, 0, 4);
}

function generarEjercicios($tema, $cantidad = 5) {
    $tandaDeEjercicios = [];
    try {
        $conn = Db::conectar();
        $sql = "SELECT id, tema, subtema, plantilla_texto, parametros, formula_solucion, explicacion FROM ejercicios";
        if ($tema && $tema !== 'Aleatorio') {
            $sql .= " WHERE tema = :tema";
        }
        $sql .= " ORDER BY RAND() LIMIT :cantidad";

        $stmt = $conn->prepare($sql);
        if ($tema && $tema !== 'Aleatorio') {
            $stmt->bindValue(':tema', $tema, PDO::PARAM_STR);
        }
        $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->execute();
        
        $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($plantillas)) {
            throw new Exception("No se encontraron ejercicios para el tema '$tema'.");
        }

        foreach ($plantillas as $plantilla) {
            $parametrosPlantilla = json_decode($plantilla['parametros'], true);
            $preguntaFinal = $plantilla['plantilla_texto'];
            $solucionFinal = null;
            $valoresGenerados = [];

            foreach ($parametrosPlantilla as $key => $value) {
                if (is_array($value) && isset($value['min'])) {
                    $valoresGenerados[$key] = rand($value['min'], $value['max']);
                }
            }
            
            // Lógica de generación de ejercicios...
            // (Todo el switch que tenías en ejercicios.php va aquí)
            switch ($plantilla['subtema']) {
                case 'Suma simple':
                case 'Resta simple':
                case 'Multiplicación':
                    $num1 = $valoresGenerados['num1'];
                    $num2 = $valoresGenerados['num2'];
                    $preguntaFinal = str_replace(['{num1}', '{num2}'], [$num1, $num2], $preguntaFinal);
                    eval('$solucionFinal = ' . str_replace(['num1', 'num2'], [$num1, $num2], $plantilla['formula_solucion']) . ';');
                    break;
                
                case 'División exacta':
                    $solucion = $valoresGenerados['solucion'];
                    $num2 = $valoresGenerados['num2'];
                    $num1 = $solucion * $num2;
                    $valoresGenerados['num1'] = $num1; // Añadir para la explicación
                    $preguntaFinal = str_replace(['{num1}', '{num2}'], [$num1, $num2], $preguntaFinal);
                    $solucionFinal = $solucion;
                    break;
    
                // ... (y así con todos los demás cases)
                 case 'Operaciones combinadas':
                $num1 = $valoresGenerados['num1'];
                $num2 = $valoresGenerados['num2'];
                $num3 = $valoresGenerados['num3'];
                $preguntaFinal = str_replace(['{num1}', '{num2}', '{num3}'], [$num1, $num2, $num3], $preguntaFinal);
                $solucionFinal = ($num1 + $num2) * $num3;
                break;
            
            case 'Cálculo de porcentaje':
                $porcentaje = $parametrosPlantilla['porcentaje_options'][array_rand($parametrosPlantilla['porcentaje_options'])];
                $numero = 100 * $valoresGenerados['multiplo'] / $porcentaje; // Asegura que el resultado sea entero
                $valoresGenerados['porcentaje'] = $porcentaje;
                $valoresGenerados['numero'] = $numero;
                $preguntaFinal = str_replace(['{porcentaje}', '{numero}'], [$porcentaje, $numero], $preguntaFinal);
                $solucionFinal = ($numero * $porcentaje) / 100;
                break;

            case 'Problema verbal de reparto':
                $solucion = $valoresGenerados['solucion'];
                $num2 = $valoresGenerados['num2'];
                $num1 = $solucion * $num2;
                $valoresGenerados['num1'] = $num1;
                $preguntaFinal = str_replace(['{num1}', '{num2}'], [$num1, $num2], $preguntaFinal);
                $solucionFinal = $solucion;
                break;
            
            case 'Encontrar el número que falta (suma)':
                $num1 = $valoresGenerados['num1'];
                $num_faltante = $valoresGenerados['num_faltante'];
                $resultado = $num1 + $num_faltante;
                $valoresGenerados['resultado'] = $resultado;
                $preguntaFinal = str_replace(['{num1}', '{resultado}'], [$num1, $resultado], $preguntaFinal);
                $solucionFinal = $num_faltante;
                break;

            case 'Comparación de operaciones':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b'];
                $c = $valoresGenerados['c']; $d = $valoresGenerados['d'];
                $res1 = $a * $b; $res2 = $c + $d;
                $preguntaFinal = str_replace(['{a}', '{b}', '{c}', '{d}'], [$a, $b, $c, $d], $preguntaFinal);
                $solucionFinal = max($res1, $res2);
                break;
                
            // (Aquí van todos los casos de Álgebra y Ecuaciones que ya tenías)
            // ...

            // --- CASOS DE GEOMETRÍA ---
            case 'Área de un cuadrado':
                $lado = $valoresGenerados['lado'];
                $preguntaFinal = str_replace('{lado}', $lado, $preguntaFinal);
                $solucionFinal = $lado * $lado;
                break;
            case 'Perímetro de un rectángulo':
                $largo = $valoresGenerados['largo'];
                $ancho = $valoresGenerados['ancho'];
                $preguntaFinal = str_replace(['{largo}', '{ancho}'], [$largo, $ancho], $preguntaFinal);
                $solucionFinal = 2 * ($largo + $ancho);
                break;
            case 'Área de un triángulo':
                $base = $valoresGenerados['base'];
                $altura = $valoresGenerados['altura'];
                $preguntaFinal = str_replace(['{base}', '{altura}'], [$base, $altura], $preguntaFinal);
                $solucionFinal = ($base * $altura) / 2;
                break;
            case 'Circunferencia de un círculo':
                $radio = $valoresGenerados['radio'];
                $preguntaFinal = str_replace('{radio}', $radio, $preguntaFinal);
                $solucionFinal = round(2 * 3.14 * $radio, 2);
                break;
            case 'Volumen de un cubo':
                $lado = $valoresGenerados['lado'];
                $preguntaFinal = str_replace('{lado}', $lado, $preguntaFinal);
                $solucionFinal = $lado * $lado * $lado;
                break;
            
            // --- CASOS DE ESTADÍSTICA ---
            case 'Calcular la media':
                $n1 = $valoresGenerados['n1']; $n2 = $valoresGenerados['n2']; $n3 = $valoresGenerados['n3'];
                $preguntaFinal = str_replace(['{n1}', '{n2}', '{n3}'], [$n1, $n2, $n3], $preguntaFinal);
                $solucionFinal = round(($n1 + $n2 + $n3) / 3, 2);
                break;
            case 'Calcular el rango':
                $datos = [$valoresGenerados['n1'], $valoresGenerados['n2'], $valoresGenerados['n3'], $valoresGenerados['n4']];
                $preguntaFinal = str_replace(['{n1}', '{n2}', '{n3}', '{n4}'], $datos, $preguntaFinal);
                $solucionFinal = max($datos) - min($datos);
                break;
            case 'Probabilidad simple':
                $rojas = $valoresGenerados['rojas']; $azules = $valoresGenerados['azules'];
                $preguntaFinal = str_replace(['{rojas}', '{azules}'], [$rojas, $azules], $preguntaFinal);
                $solucionFinal = round($rojas / ($rojas + $azules), 2);
                break;
            case 'Encontrar la mediana':
                $datos = [$valoresGenerados['n1'], $valoresGenerados['n2'], $valoresGenerados['n3'], $valoresGenerados['n4'], $valoresGenerados['n5']];
                sort($datos);
                $solucionFinal = $datos[2];
                shuffle($datos);
                $preguntaFinal = str_replace('{datos_str}', implode(', ', $datos), $preguntaFinal);
                break;
            case 'Encontrar la moda':
                $moda = $valoresGenerados['moda_val'];
                $datos = [$valoresGenerados['base1'], $valoresGenerados['base2'], $moda, $moda, $moda];
                shuffle($datos);
                $preguntaFinal = str_replace('{datos_str}', implode(', ', $datos), $preguntaFinal);
                $solucionFinal = $moda;
                break;
            case 'Selecciona la respuesta correcta': // Añadido para agrupar
            case 'Ecuación con negativos': // Añadido para agrupar
            case 'Ecuación simple (ax + b = c)':
            case 'Ecuación con resta (ax - b = c)':
            case 'Ecuación invertida (c = ax + b)':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b']; $x_sol = $valoresGenerados['x_sol'];
                // La lógica determina si es suma o resta basándose en la plantilla de texto
                if (strpos($plantilla['plantilla_texto'], '-') !== false) {
                    $c = ($a * $x_sol) - $b;
                } else {
                    $c = ($a * $x_sol) + $b;
                }
                $valoresGenerados['c'] = $c;
                $preguntaFinal = str_replace(['{a}', '{b}', '{c}'], [$a, $b, $c], $preguntaFinal);
                $solucionFinal = $x_sol;
                break;
            
            case 'Problema verbal simple':
                $b = $valoresGenerados['b']; $x_sol = $valoresGenerados['x_sol'];
                $c = (2 * $x_sol) + $b;
                $valoresGenerados['c'] = $c;
                $preguntaFinal = str_replace(['{b}', '{c}'], [$b, $c], $preguntaFinal);
                $solucionFinal = $x_sol;
                break;

            case 'Variable en ambos lados':
            case 'Variable en ambos lados con resta':
                $c_val = $valoresGenerados['c']; $b = $valoresGenerados['b']; $x_sol = $valoresGenerados['x_sol'];
                $a = $c_val + rand(1, 3);
                // Determina el signo de 'd' basado en la plantilla
                if (strpos($plantilla['plantilla_texto'], '{c}x + {d}') !== false) {
                    $d = ($a * $x_sol + $b) - ($c_val * $x_sol);
                } else { // Asume que es {c}x - {d} o similar
                    $d = ($c_val * $x_sol) - ($a * $x_sol - $b);
                }
                $valoresGenerados['a'] = $a; $valoresGenerados['d'] = $d;
                $preguntaFinal = str_replace(['{a}', '{b}', '{c}', '{d}'], [$a, $b, $c_val, $d], $preguntaFinal);
                $solucionFinal = $x_sol;
                break;

            case 'Con paréntesis a(x + b) = c':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b']; $x_sol = $valoresGenerados['x_sol'];
                $c = $a * ($x_sol + $b);
                $valoresGenerados['c'] = $c;
                $preguntaFinal = str_replace(['{a}', '{b}', '{c}'], [$a, $b, $c], $preguntaFinal);
                $solucionFinal = $x_sol;
                break;

            case 'Problema de edades':
                $a = $valoresGenerados['a']; $luis_edad = $valoresGenerados['luis_edad'];
                $suma = (2 * $luis_edad) + $a;
                $valoresGenerados['suma'] = $suma;
                $preguntaFinal = str_replace(['{a}', '{suma}'], [$a, $suma], $preguntaFinal);
                $solucionFinal = $luis_edad;
                break;
            
            case 'Ecuación con fracción simple': // Añadido
                $a = $valoresGenerados['a'];
                $b = $valoresGenerados['b'];
                $x_sol = $valoresGenerados['x_sol'];
                $c = ($x_sol / $a) + $b;
                $valoresGenerados['c'] = round($c, 2);
                $preguntaFinal = str_replace(['{a}', '{b}', '{c}'], [$a, $b, round($c, 2)], $preguntaFinal);
                $solucionFinal = $x_sol;
                break;

            case 'Evaluar expresión':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b']; $x_val = $valoresGenerados['x_val'];
                $preguntaFinal = str_replace(['{a}', '{b}', '{x_val}'], [$a, $b, $x_val], $preguntaFinal);
                $solucionFinal = ($a * $x_val) + $b;
                break;
            
            case 'Simplificar términos semejantes':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b'];
                $preguntaFinal = str_replace(['{a}', '{b}'], [$a, $b], $preguntaFinal);
                $solucionFinal = ($a + $b) . "x";
                break;
            
            case 'Resolver x² = a':
                $a = $valoresGenerados['a'];
                $a_squared = $a * $a;
                $valoresGenerados['a_squared'] = $a_squared;
                $preguntaFinal = str_replace('{a_squared}', $a_squared, $preguntaFinal);
                $solucionFinal = $a;
                break;

            case 'Suma de polinomios':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b']; $c = $valoresGenerados['c']; $d = $valoresGenerados['d'];
                $preguntaFinal = str_replace(['{a}', '{b}', '{c}', '{d}'], [$a, $b, $c, $d], $preguntaFinal);
                $solucionFinal = ($a + $c) . "x + " . ($b + $d);
                break;

            case 'Factor común':
            case 'Identifica el factor': // Agrupado
                $a = $valoresGenerados['factor1'] ?? $valoresGenerados['a'];
                $b = $valoresGenerados['factor2'] ?? $valoresGenerados['b'];
                $numero = $a * $b;
                $valoresGenerados['numero'] = $numero;
                $valoresGenerados['ab'] = $numero;
                $preguntaFinal = str_replace(['{a}', '{ab}', '{numero}'], [$a, $numero, $numero], $preguntaFinal);
                $solucionFinal = $a;
                break;

            case 'Diferencia de cuadrados':
                $a = $valoresGenerados['a'];
                $a_squared = $a * $a;
                $valoresGenerados['a_squared'] = $a_squared;
                $preguntaFinal = str_replace('{a_squared}', $a_squared, $preguntaFinal);
                $solucionFinal = "(x - $a)(x + $a)";
                break;

            case 'Producto de binomios':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b'];
                $sum_ab = $a + $b;
                $prod_ab = $a * $b;
                $preguntaFinal = str_replace(['{a}', '{b}'], [$a, $b], $preguntaFinal);
                $solucionFinal = "x^2 + {$sum_ab}x + {$prod_ab}";
                break;

            case 'Sistema de ecuaciones simple':
                $x_sol = $valoresGenerados['x_sol']; $y_sol = $valoresGenerados['y_sol'];
                $suma = $x_sol + $y_sol; $resta = $x_sol - $y_sol;
                $valoresGenerados['suma'] = $suma; $valoresGenerados['resta'] = $resta;
                $preguntaFinal = str_replace(['{suma}', '{resta}'], [$suma, $resta], $preguntaFinal);
                $solucionFinal = $x_sol;
                break;
            
            case 'Resta de polinomios':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b']; $c = $valoresGenerados['c']; $d = $valoresGenerados['d'];
                $preguntaFinal = str_replace(['{a}', '{b}', '{c}', '{d}'], [$a, $b, $c, $d], $preguntaFinal);
                $res_x2 = $a - $c;
                $res_x = $b - $d;
                $solucionFinal = "{$res_x2}x^2 + {$res_x}x";
                break;
            
            case 'Resolver cuadrática factorizada':
                $a = $valoresGenerados['a']; $b = $valoresGenerados['b'];
                $preguntaFinal = str_replace(['{a}', '{b}'], [$a, $b], $preguntaFinal);
                $solucionFinal = "$a y -$b";
                break;

                default:
                    continue 2;
            }

            $explicacionFinal = $plantilla['explicacion'];
            if ($explicacionFinal) {
                foreach ($valoresGenerados as $key => $value) {
                    $explicacionFinal = str_replace('{'.$key.'}', $value, $explicacionFinal);
                }
                $explicacionFinal = str_replace('{solucion}', is_numeric($solucionFinal) ? round($solucionFinal, 2) : $solucionFinal, $explicacionFinal);
            }
            $opciones = generarOpciones($solucionFinal, $valoresGenerados, $parametrosPlantilla);

            $tandaDeEjercicios[] = [
                'id_ejercicio' => $plantilla['id'],
                'pregunta' => $preguntaFinal,
                'opciones' => $opciones,
                'tema' => $plantilla['tema'],
                'solucion' => $solucionFinal,
                'explicacion' => $explicacionFinal
            ];
        }
        return $tandaDeEjercicios;

    } catch (Exception $e) {
        // En lugar de imprimir, devolvemos el error para que quien llame a la función decida qué hacer
        return ['error' => 'Ocurrió un error al generar los ejercicios: ' . $e->getMessage()];
    }
}
?>