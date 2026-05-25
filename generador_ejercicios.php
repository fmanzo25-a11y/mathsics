<?php
include_once 'conexion.php';

// Diccionarios Dinámicos Educativos
$NOMBRES = ['Ana', 'Carlos', 'Luis', 'María', 'Pedro', 'Sofía', 'Miguel', 'Lucía', 'Jorge', 'Elena', 'Diego', 'Carmen'];
$OBJETOS = ['libros', 'lápices', 'manzanas', 'galletas', 'cuadernos', 'chocolates', 'estampas', 'canicas'];
$COMIDAS = ['pizzas', 'pasteles', 'tartas', 'litros de agua', 'kilogramos de fruta'];
$MATERIAS = ['Matemáticas', 'Historia', 'Ciencias', 'Geografía', 'Literatura'];

// Nuevo Algoritmo Adaptativo (MMR)
function calcularNivelDominio($user_id, $tema) {
    if (!$user_id) return 1;
    try {
        $conn = Db::conectar();
        if (is_array($user_id)) {
            $nivel_total = 0;
            foreach($user_id as $uid) {
                $nivel_total += calcularNivelIndividual($conn, $uid, $tema);
            }
            return max(1, round($nivel_total / count($user_id)));
        } else {
            return calcularNivelIndividual($conn, $user_id, $tema);
        }
    } catch(Exception $e) {
        return 1;
    }
}

function calcularNivelIndividual($conn, $uid, $tema) {
    if ($tema === 'Aleatorio' || $tema === 'Todos') {
        $stmt = $conn->prepare("SELECT respuesta_correcta FROM resultados_ejercicios WHERE id_usuario = :uid ORDER BY fecha DESC LIMIT 30");
        $stmt->execute([':uid' => $uid]);
    } else {
        $stmt = $conn->prepare("SELECT respuesta_correcta FROM resultados_ejercicios WHERE id_usuario = :uid AND tema = :tema ORDER BY fecha DESC LIMIT 30");
        $stmt->execute([':uid' => $uid, ':tema' => $tema]);
    }
    
    $resultados = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $total = count($resultados);
    
    if ($total < 5) return 1; // Fase de calibración
    
    // Decaimiento Temporal (Recent Bias)
    // Los últimos 10 valen doble
    $score_ponderado = 0;
    $peso_total = 0;
    $racha_actual = 0;
    $racha_rota = false;
    
    foreach ($resultados as $index => $res) {
        $es_reciente = $index < 10;
        $peso = $es_reciente ? 2 : 1;
        $score_ponderado += ($res ? 1 : 0) * $peso;
        $peso_total += $peso;
        
        // Calcular racha (streak) basada en los más recientes
        if (!$racha_rota) {
            if ($res) {
                $racha_actual++;
            } else {
                $racha_rota = true;
            }
        }
    }
    
    $win_rate_ponderado = $score_ponderado / $peso_total;
    
    $level = 1;
    if ($win_rate_ponderado >= 0.3) $level = 2;
    if ($win_rate_ponderado >= 0.4) $level = 3;
    if ($win_rate_ponderado >= 0.5) $level = 4;
    if ($win_rate_ponderado >= 0.6) $level = 5;
    if ($win_rate_ponderado >= 0.7) $level = 6;
    if ($win_rate_ponderado >= 0.75) $level = 7;
    if ($win_rate_ponderado >= 0.8) $level = 8;
    if ($win_rate_ponderado >= 0.85) $level = 9;
    if ($win_rate_ponderado >= 0.9) $level = 10;
    
    // Bonificación por racha
    if ($racha_actual >= 5 && $level < 10) $level += 1;
    if ($racha_actual >= 10 && $level < 10) $level += 1; // Doble bonificación
    
    return max(1, min(10, $level));
}

// Reductor de fracciones
function simplificarFraccion($num, $den) {
    $a = abs($num);
    $b = abs($den);
    while ($b != 0) {
        $temp = $b;
        $b = $a % $b;
        $a = $temp;
    }
    $mcd = $a;
    $num_simp = $num / $mcd;
    $den_simp = $den / $mcd;
    if ($den_simp < 0) {
        $num_simp = -$num_simp;
        $den_simp = -$den_simp;
    }
    if ($den_simp == 1) return (string)$num_simp;
    return "$num_simp/$den_simp";
}

// Genera distractores "inteligentes"
function generarOpciones($solucion, $contexto = []) {
    $opciones = [(string)$solucion]; // Forzar a string para evitar type strictness
    
    // Si la solución es una fracción (contiene "/")
    if (is_string($solucion) && strpos($solucion, '/') !== false) {
        list($num, $den) = explode('/', $solucion);
        $num = (int)$num; $den = (int)$den;
        
        while(count($opciones) < 4) {
            $tipo = rand(1, 4);
            $dist_str = "";
            switch($tipo) {
                case 1: $dist_str = simplificarFraccion($num + rand(1,3), $den); break; // Error en numerador
                case 2: $dist_str = simplificarFraccion($num, $den + rand(1,3)); break; // Error en denominador
                case 3: $dist_str = simplificarFraccion($num + $den, $den); break; // Suma incorrecta
                case 4: $dist_str = simplificarFraccion($num * 2, $den); break; // Multiplicación en lugar de suma
            }
            if(!in_array($dist_str, $opciones)) {
                $opciones[] = $dist_str;
            }
        }
        shuffle($opciones);
        return $opciones;
    }
    
    if (is_numeric($solucion)) {
        if (isset($contexto['distractores_comunes'])) {
            foreach ($contexto['distractores_comunes'] as $dist) {
                if (count($opciones) < 4 && !in_array((string)$dist, $opciones)) {
                    $opciones[] = (string)$dist;
                }
            }
        }

        $intentos_op=0; 
        while (count($opciones) < 4) { 
            $intentos_op++; 
            if($intentos_op > 50){
                $opciones[] = (string)($solucion + (rand(1, 100))); 
                $opciones = array_unique($opciones);
                continue;
            }
            $tipo = rand(1, 6);
            $distractor = $solucion;
            switch($tipo) {
                case 1: $distractor = $solucion + rand(1, 10); break;
                case 2: $distractor = $solucion - rand(1, 10); break;
                case 3: $distractor = $solucion * 10; break;
                case 4: $distractor = -$solucion; break;
                case 5: $distractor = $solucion + 10; break;
                case 6: $distractor = ($solucion == 0) ? rand(1,5) : $solucion * rand(2,3); break;
            }
            if (is_int($solucion) || floor($solucion) == $solucion) {
                $distractor = round($distractor);
            } else {
                $distractor = round((float)$distractor, 2);
            }

            if (!in_array((string)$distractor, $opciones)) {
                $opciones[] = (string)$distractor;
            }
        }
    } else {
        if (isset($contexto['distractores_comunes'])) {
            foreach ($contexto['distractores_comunes'] as $dist) {
                if (count($opciones) < 4 && !in_array((string)$dist, $opciones)) $opciones[] = (string)$dist;
            }
        }
        
        if (preg_match('/([0-9]+)/', $solucion, $matches)) {
            $num = $matches[1];
            $intentos_op=0; 
            while (count($opciones) < 4) { 
                $intentos_op++; 
                if($intentos_op > 50){
                    $opciones[] = $solucion . "_" . count($opciones); 
                    $opciones = array_unique($opciones);
                    continue;
                }
                $mutado = str_replace($num, $num + rand(1, 5), $solucion);
                if (!in_array($mutado, $opciones)) $opciones[] = $mutado;
            }
        } else {
            $backups = ["No tiene solución", "Infinito", "0", "1", "x", "-x", "y", "Ninguna de las anteriores"];
            shuffle($backups);
            foreach($backups as $b) {
                if(count($opciones) < 4 && !in_array($b, $opciones)) $opciones[] = $b;
            }
        }
    }

    shuffle($opciones);
    return array_slice($opciones, 0, 4);
}

// Generador procedural principal
function generarEjercicioProcedural($tema, $nivel, &$historial_tipos = []) {
    global $NOMBRES, $OBJETOS, $COMIDAS, $MATERIAS;
    
    if ($tema === 'Aleatorio' || $tema === 'Todos') {
        $temas_disponibles = ['Aritmética', 'Álgebra', 'Geometría', 'Estadística'];
        $tema = $temas_disponibles[array_rand($temas_disponibles)];
    }
    
    $pregunta = "";
    $solucion = 0;
    $explicacion = "";
    $contexto = [];
    
    // Nombres aleatorios para la inmersión
    $n1 = $NOMBRES[array_rand($NOMBRES)];
    $n2 = $NOMBRES[array_rand($NOMBRES)];
    while($n1 == $n2) $n2 = $NOMBRES[array_rand($NOMBRES)];
    $obj = $OBJETOS[array_rand($OBJETOS)];
    $comida = $COMIDAS[array_rand($COMIDAS)];
    
    switch ($tema) {
        case 'Aritmética':
            if ($nivel <= 3) {
                $tipo_problema = rand(1, 4);
                if ($tipo_problema == 1) {
                    $a = rand(2, 20); $b = rand(2, 20); // Números pequeños para cálculo mental rápido
                    $pregunta = "En la tienda escolar, $n1 gastó $$a y luego $$b. ¿Cuánto dinero gastó en total?";
                    $solucion = $a + $b;
                    $explicacion = "Suma los gastos: $a + $b = $solucion.";
                    $contexto['distractores_comunes'] = [$a - $b, $a + $b + 10];
                } elseif ($tipo_problema == 2) {
                    $a = rand(20, 50); $b = rand(5, 15);
                    $pregunta = "$n2 tenía $a $obj y repartió $b entre sus compañeros. ¿Cuántos le quedaron?";
                    $solucion = $a - $b;
                    $explicacion = "Es una resta simple: $a - $b = $solucion.";
                } elseif ($tipo_problema == 3) {
                    $a = rand(2, 6); $b = rand(2, 6); // Tablas de multiplicar básicas (hasta 6x6)
                    $pregunta = "Si cada caja tiene $b $obj, ¿cuántos $obj hay en $a cajas?";
                    $solucion = $a * $b;
                    $explicacion = "Multiplicación: $a cajas × $b $obj = $solucion.";
                    $contexto['distractores_comunes'] = [$a + $b]; 
                } else {
                    $a = rand(2, 5); $num1 = rand(1,3); $num2 = rand(1,3);
                    $pregunta = "¿Cuánto es $num1/$a + $num2/$a? (Expresa en fracción)";
                    $solucion = simplificarFraccion($num1 + $num2, $a);
                    $explicacion = "Con mismo denominador ($a), suma los numeradores: " . ($num1+$num2) . "/$a. Simplificado es $solucion.";
                }
            } elseif ($nivel <= 6) {
                $tipo_problema = rand(1, 5);
                if ($tipo_problema == 1) {
                    $p_arr = [10=>10, 20=>5, 25=>4, 50=>2]; // Porcentajes y su divisor para que sea exacto
                    $p = array_rand($p_arr);
                    $mult = rand(1, 10);
                    $a = $p_arr[$p] * $mult; // Garantiza que sea número entero exacto y calculable rápido
                    $pregunta = "¿Cuál es el $p% de $a?";
                    $solucion = ($p / 100) * $a;
                    $explicacion = "Multiplica $a por 0.$p = $solucion.";
                } elseif ($tipo_problema == 2) {
                    $b = rand(2, 5); $solucion = rand(2, 6); $a = $b * $solucion; // División rápida mental
                    $pregunta = "Un colegio repartió $a $obj entre $b salones por partes iguales. ¿Cuántos le tocaron a cada salón?";
                    $explicacion = "$a dividido entre $b es $solucion.";
                } elseif ($tipo_problema == 3) {
                    $a = rand(2, 10); $b = rand(2, 5); $c = rand(2, 3); // Cifras bajísimas
                    $pregunta = "Evalúa la expresión: $a + $b × $c";
                    $solucion = $a + ($b * $c);
                    $explicacion = "Por jerarquía de operaciones, multiplica primero: $b × $c = " . ($b*$c) . ". Luego suma $a = $solucion.";
                    $contexto['distractores_comunes'] = [($a + $b) * $c]; 
                } elseif ($tipo_problema == 4) {
                    $a = rand(2, 4); $b = rand(2, 3); // Cuadrados de 2,3,4 (muy rápidos)
                    $pregunta = "Un alumno estudió " . pow($a, 2) . " horas el lunes y " . pow($b, 2) . " horas el martes. ¿Cuántas horas estudió en total?";
                    $solucion = pow($a, 2) + pow($b, 2);
                    $explicacion = "Resuelve los cuadrados: " . pow($a,2) . " + " . pow($b,2) . " = $solucion.";
                } else {
                    $d1 = rand(2,3); $d2 = rand(2,4); while($d1 == $d2) $d2 = rand(2,4);
                    $n1_frac = 1; $n2_frac = 1; // Simplificando para cálculo en <30s
                    $num_final = ($n1_frac * $d2) + ($n2_frac * $d1);
                    $den_final = $d1 * $d2;
                    $pregunta = "Suma las fracciones: 1/$d1 + 1/$d2";
                    $solucion = simplificarFraccion($num_final, $den_final);
                    $explicacion = "Multiplica denominadores ($d1 × $d2 = $den_final). Productos cruzados: 1×$d2 + 1×$d1 = $num_final. Fracción: $num_final/$den_final.";
                }
            } else {
                $tipo_problema = rand(1, 4);
                if ($tipo_problema == 1) {
                    $a = rand(-10, -1); $b = rand(-10, 10); // Menos estrés en restar
                    $pregunta = "En una ciudad la temperatura era de $a°C y descendió $b°C más. ¿Cuál es la temperatura final?"; 
                    $solucion = $a - $b;
                    $explicacion = "$a - $b = $solucion.";
                } elseif ($tipo_problema == 2) {
                    $p_arr = [10=>10, 20=>5, 25=>4, 50=>2];
                    $p = array_rand($p_arr);
                    $precio = $p_arr[$p] * rand(2, 10); // Fácil de sacar porcentaje mental
                    $pregunta = "Algo cuesta $$precio, pero tiene un $p% de descuento. ¿Cuál es el precio final?";
                    $solucion = $precio - ($precio * ($p/100));
                    $explicacion = "El descuento es " . ($precio*($p/100)) . ". Restado del original: $solucion.";
                } elseif ($tipo_problema == 3) {
                    $d1 = rand(2,4); $d2 = rand(2,4); // Fracciones chiquitas
                    $n1_frac = rand(1,$d1-1); $n2_frac = rand(1,$d2-1);
                    $pregunta = "Multiplica: ($n1_frac/$d1) × ($n2_frac/$d2)";
                    $solucion = simplificarFraccion($n1_frac * $n2_frac, $d1 * $d2);
                    $explicacion = "Multiplica directo: Numeradores ($n1_frac × $n2_frac) y denominadores ($d1 × $d2).";
                } else {
                    $a = rand(1, 3); $b = rand(1, 2); $c = rand(1, 3);
                    $pregunta = "Calcula: ($a + $b)² - $c³";
                    $solucion = pow($a + $b, 2) - pow($c, 3);
                    $explicacion = "($a + $b)² = " . pow($a+$b, 2) . ". $c³ = " . pow($c, 3) . ". Resta: $solucion.";
                }
            }
            break;
            
        case 'Álgebra':
            if ($nivel <= 3) {
                $tipo_problema = rand(1, 3);
                $x = rand(2, 10);
                if ($tipo_problema == 1) {
                    $b = rand(1, 10); $c = $x + $b;
                    $pregunta = "Si la edad de $n1 más $b años es $c, ¿qué edad tiene $n1? (x + $b = $c)"; 
                    $solucion = $x;
                    $explicacion = "Resta $b de ambos lados: x = $c - $b = $solucion.";
                } elseif ($tipo_problema == 2) {
                    $b = rand(1, 10); $c = $x - $b;
                    $pregunta = "Pense un número, le resté $b y obtuve $c. ¿Qué número pensé? (x - $b = $c)"; 
                    $solucion = $x;
                    $explicacion = "Suma $b a ambos lados: x = $c + $b = $solucion.";
                } else {
                    $a = rand(2, 4); $c = $a * $x;
                    $pregunta = "Si compro $a $obj iguales y pago $$c, ¿cuánto cuesta cada uno? ({$a}x = $c)";
                    $solucion = $x;
                    $explicacion = "Divide $c entre $a para encontrar el precio unitario: $solucion.";
                }
            } elseif ($nivel <= 6) {
                $tipo_problema = rand(1, 4);
                $x = rand(-3, 5); // x pequeño
                if ($tipo_problema == 1) {
                    $a = rand(2, 4); $b = rand(1, 5); $c = ($a * $x) + $b;
                    $pregunta = "Resuelve para x: {$a}x + $b = $c"; $solucion = $x;
                    $explicacion = "Pasa $b restando: {$a}x = " . ($c-$b) . ". Divide entre $a: x = $solucion.";
                } elseif ($tipo_problema == 2) {
                    $a = rand(3, 5); $c = rand(1, 2);
                    $b = rand(1, 5); $d = ($a * $x + $b) - ($c * $x); 
                    $pregunta = "Resuelve para x: {$a}x + $b = {$c}x + $d"; $solucion = $x;
                    $explicacion = "Agrupa las x: " . ($a-$c) . "x = " . ($d-$b) . ". Divide: x = $solucion.";
                } elseif ($tipo_problema == 3) {
                    $a = rand(1, 3); $b = rand(1, 3);
                    $pregunta = "Expande el binomio: (x + $a)(x + $b)";
                    $solucion = "x² + " . ($a+$b) . "x + " . ($a*$b);
                    $explicacion = "Término central: ($a + $b)x. Término independiente: $a × $b.";
                    $contexto['distractores_comunes'] = ["x² + " . ($a*$b) . "x + " . ($a+$b), "x² + " . ($a+$b)];
                } else {
                    $x_pos = rand(2, 6);
                    $val = pow($x_pos, 2);
                    $pregunta = "El área de un cuadrado es $val. ¿Cuánto mide su lado x? (x² = $val)"; $solucion = $x_pos;
                    $explicacion = "Aplica raíz cuadrada a ambos lados. La raíz de $val es $x_pos.";
                }
            } else {
                $tipo_problema = rand(1, 4);
                if ($tipo_problema == 1) {
                    $x_perfecto = rand(2, 5); // Así x es 4, 9, 16, 25
                    $x = $x_perfecto * $x_perfecto;
                    $b = rand(2, 5);
                    $val = $x_perfecto + $b;
                    $pregunta = "Resuelve la ecuación radical: √x + $b = $val"; $solucion = $x;
                    $explicacion = "Resta $b: √x = " . ($val-$b) . ". Eleva al cuadrado: x = $solucion.";
                } elseif ($tipo_problema == 2) {
                    $x = rand(1, 5); $y = rand(1, 5);
                    $sum = $x + $y; $dif = $x - $y;
                    $pregunta = "Un sistema de ecuaciones: x + y = $sum, y x - y = $dif. ¿Cuánto vale x?";
                    $solucion = $x;
                    $explicacion = "Sumando ambas ecuaciones eliminas 'y': 2x = " . ($sum+$dif) . ". Por tanto x = $solucion.";
                    $contexto['distractores_comunes'] = [$y]; 
                } elseif ($tipo_problema == 3) {
                    $a = rand(1, 3); 
                    $pregunta = "Desarrolla el binomio al cuadrado: (x + $a)²";
                    $solucion = "x² + " . (2*$a) . "x + " . pow($a,2);
                    $explicacion = "Fórmula (a+b)² = a² + 2ab + b².";
                    $contexto['distractores_comunes'] = ["x² + " . pow($a,2), "x² + {$a}x + " . pow($a,2)];
                } else {
                    $x = rand(1, 5); $a = rand(2, 3); $b = rand(1, 4);
                    $val = ($a * $x + $b) * 2;
                    $pregunta = "Resuelve para x: ({$a}x + $b) / 2 = " . ($val/2); $solucion = $x;
                    $explicacion = "Multiplica por 2: {$a}x + $b = $val. Despeja x: x = $solucion.";
                }
            }
            break;
            
        case 'Geometría':
            if ($nivel <= 4) {
                $tipo_problema = rand(1, 4);
                if ($tipo_problema == 1) {
                    $l = rand(3, 8);
                    $pregunta = "El patio escolar es cuadrado y mide $l m por lado. ¿Cuál es su área?"; $solucion = $l * $l;
                    $explicacion = "Área = lado × lado = $l × $l = $solucion m².";
                    $contexto['distractores_comunes'] = [$l * 4]; 
                } elseif ($tipo_problema == 2) {
                    $b = rand(4, 10); $h = rand(3, 6);
                    $pregunta = "Una cancha rectangular mide $b m de largo y $h m de ancho. ¿Cuál es su perímetro?"; $solucion = 2 * ($b + $h);
                    $explicacion = "Perímetro = 2 × ($b + $h) = $solucion.";
                    $contexto['distractores_comunes'] = [$b * $h]; 
                } elseif ($tipo_problema == 3) {
                    $ang1 = rand(30, 60); $ang2 = rand(30, 60); // Evitar que sumen mas de 180
                    $pregunta = "En un triángulo, dos ángulos son de {$ang1}° y {$ang2}°. ¿Cuánto mide el tercer ángulo?";
                    $solucion = 180 - $ang1 - $ang2;
                    $explicacion = "La suma de los ángulos internos es 180°. 180 - $ang1 - $ang2 = $solucion.";
                } else {
                    $r = rand(2, 6);
                    $pregunta = "El radio de una llanta es $r cm. ¿Cuánto mide su diámetro?";
                    $solucion = $r * 2;
                    $explicacion = "El diámetro es el doble del radio: $r × 2 = $solucion.";
                }
            } else {
                $tipo_problema = rand(1, 4);
                if ($tipo_problema == 1) {
                    $b = rand(4, 10); $h = rand(2, 6); // Multiplicacion mental rapida
                    $pregunta = "¿Cuál es el área de un triángulo con base $b cm y altura $h cm?"; $solucion = ($b * $h) / 2;
                    $explicacion = "Área = (base × altura) / 2 = ($b × $h) / 2 = $solucion.";
                    $contexto['distractores_comunes'] = [$b * $h]; 
                } elseif ($tipo_problema == 2) {
                    $trios = [[3,4,5], [6,8,10]]; // Los mas basicos de la historia
                    $trio = $trios[array_rand($trios)];
                    $pregunta = "$n1 camina {$trio[0]} km al Norte y {$trio[1]} km al Este. ¿A qué distancia está? (Teorema de Pitágoras)";
                    $solucion = $trio[2];
                    $explicacion = "Pitágoras: c² = " . pow($trio[0],2) . " + " . pow($trio[1],2) . " = " . pow($trio[2],2) . ". Distancia = $solucion.";
                    $contexto['distractores_comunes'] = [$trio[0]+$trio[1]];
                } elseif ($tipo_problema == 3) {
                    $l = rand(2, 5); // 2^3=8, 3^3=27, 4^3=64, 5^3=125 (Mental math)
                    $pregunta = "Una caja cúbica tiene aristas de $l cm. ¿Cuál es su volumen?";
                    $solucion = pow($l, 3);
                    $explicacion = "Volumen = lado³ = $l³ = $solucion.";
                    $contexto['distractores_comunes'] = [pow($l, 2), $l * 12];
                } else {
                    $r = rand(2, 5);
                    $pregunta = "¿Cuál es el área de un círculo con radio $r? (Usa π ≈ 3)"; // Pi=3 es mas rápido
                    $solucion = 3 * pow($r, 2);
                    $explicacion = "Área = π × r² ≈ 3 × " . pow($r, 2) . " = $solucion.";
                    $contexto['distractores_comunes'] = [3 * $r * 2]; // Perímetro con pi=3
                }
            }
            break;
            
        case 'Estadística':
            if ($nivel <= 5) {
                $tipo_problema = rand(1, 3);
                if ($tipo_problema == 1) {
                    // Garantizar suma multiplo de 3
                    $n1 = rand(2, 8); $n2 = rand(2, 8);
                    $suma_parcial = $n1 + $n2;
                    $n3 = (3 - ($suma_parcial % 3)) % 3; // Cuanto falta para ser multiplo
                    if ($n3 == 0) $n3 = 3;
                    $n3 += rand(0,2) * 3; // Añadir un multiplo de 3 para darle entropía
                    $pregunta = "Calificaciones de $n1 en matemáticas: $n1, $n2, $n3. Calcula su promedio."; 
                    $solucion = ($n1 + $n2 + $n3) / 3;
                    $explicacion = "Suma los números ($n1+$n2+$n3) y divide entre 3 = $solucion.";
                } elseif ($tipo_problema == 2) {
                    $datos = [rand(1, 10), rand(1, 10), rand(1, 10)]; // Menos datos para leer mas rapido
                    $pregunta = "Estudio sobre edades: " . implode(", ", $datos) . ". Calcula el rango."; 
                    $solucion = max($datos) - min($datos);
                    $explicacion = "Resta el valor mínimo (".min($datos).") del valor máximo (".max($datos).") = $solucion.";
                } else {
                    $dado = 6;
                    $pregunta = "Si lanzas un dado de 6 caras, ¿qué probabilidad hay de sacar un número par? (Fracción)";
                    $solucion = "1/2"; // 3/6
                    $explicacion = "Hay 3 pares (2,4,6) de 6 posibles. 3/6 se simplifica a 1/2.";
                    $contexto['distractores_comunes'] = ["1/3", "1/6", "2/3"];
                }
            } else {
                $tipo_problema = rand(1, 4);
                if ($tipo_problema == 1) {
                    $rojas = rand(1, 4); $azules = rand(1, 4); $verdes = rand(1, 4);
                    $total_bolas = $rojas + $azules + $verdes;
                    $pregunta = "Urna: $rojas canicas rojas, $azules azules y $verdes verdes. ¿Probabilidad de sacar una roja? (Fracción)";
                    $solucion = simplificarFraccion($rojas, $total_bolas);
                    $explicacion = "Rojas ($rojas) / Total ($total_bolas) = $solucion.";
                } elseif ($tipo_problema == 2) {
                    $moda = rand(2, 9);
                    $datos = [$moda, $moda, rand(1,9), rand(1,9)];
                    shuffle($datos);
                    $pregunta = "Alumnos corrieron en minutos: " . implode(", ", $datos) . ". ¿Cuál es la moda?"; $solucion = $moda;
                    $explicacion = "La moda es el valor que más se repite, es decir, el $moda.";
                } elseif ($tipo_problema == 3) {
                    $base = rand(5, 10);
                    $datos = [$base, $base+1, $base+2, 100]; 
                    shuffle($datos);
                    $pregunta = "Muestra de encuestas: " . implode(", ", $datos) . ". ¿Cuál es el dato atípico (outlier)?";
                    $solucion = 100;
                    $explicacion = "El dato que se desvía drásticamente de los demás es 100.";
                } else {
                    $total = rand(10, 50) * 10; // 100, 200, 300, 400, 500
                    $mujeres = $total / 2; // Exactamente la mitad
                    $pregunta = "En un congreso hay $total personas, de las cuales $mujeres son mujeres. ¿Probabilidad de elegir un hombre? (Decimal)";
                    $solucion = 0.5;
                    $explicacion = "Hombres = $total - $mujeres = " . ($total-$mujeres) . ". Dividido por $total = 0.5.";
                }
            }
            break;
    }
    
    $opciones = generarOpciones($solucion, $contexto);
    
    return [
        'id_ejercicio' => rand(100000, 999999), 
        'pregunta' => $pregunta,
        'opciones' => $opciones,
        'tema' => $tema,
        'solucion' => $solucion,
        'explicacion' => $explicacion
    ];
}

// Interfaz principal
function generarEjercicios($tema, $cantidad = 5, $user_id = null, $nivelForzado = null) {
    $tandaDeEjercicios = [];
    $nivelBase = $nivelForzado !== null ? $nivelForzado : calcularNivelDominio($user_id, $tema);
    $temasDisponibles = ['Aritmética', 'Álgebra', 'Geometría', 'Estadística'];
    
    $historial_tipos = [];

    try {
        for ($i = 0; $i < $cantidad; $i++) {
            $temaActual = ($tema === 'Aleatorio' || $tema === 'Todos') ? $temasDisponibles[array_rand($temasDisponibles)] : $tema;
            // Variación sutil de nivel para mantener el reto vivo
            $nivelAjustado = max(1, min(10, $nivelBase + rand(-1, 1)));
            $ejercicio = generarEjercicioProcedural($temaActual, $nivelAjustado, $historial_tipos);
            $ejercicio['nivel'] = $nivelAjustado;
            $tandaDeEjercicios[] = $ejercicio;
        }
    } catch (Exception $e) {
        error_log("Error al generar ejercicios: " . $e->getMessage());
    }

    return $tandaDeEjercicios;
}

if (isset($_GET['api']) && $_GET['api'] == '1') {
    header('Content-Type: application/json');
    $tema = $_GET['tema'] ?? 'Aleatorio';
    $cantidad = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 5;
    $nivel = isset($_GET['nivel']) ? (int)$_GET['nivel'] : null;
    
    echo json_encode([
        'success' => true,
        'tema' => $tema,
        'cantidad' => $cantidad,
        'nivel_base' => $nivel,
        'ejercicios' => generarEjercicios($tema, $cantidad, null, $nivel)
    ]);
    exit;
}
?>