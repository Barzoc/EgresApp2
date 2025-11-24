<?php
// validar.php
// Endpoint simple para validar RUT (Módulo 11).
// Uso: POST/GET 'rut' => devuelve JSON { success: bool, valid: bool, message: string }

require_once __DIR__ . '/modelo/Utils.php';
require_once __DIR__ . '/modelo/Conexion.php';
require_once __DIR__ . '/modelo/Egresado.php';

function validar_rut_endpoint(string $rut): array {
    $rut = trim($rut);
    if ($rut === '') {
        return [
            'success' => false,
            'valid' => false,
            'message' => 'Parámetro "rut" vacío o faltante.'
        ];
    }

    $esValido = Utils::validarRut($rut);

    $response = [
        'success' => true,
        'valid' => $esValido,
        'message' => $esValido ? 'RUT válido.' : 'RUT inválido.'
    ];

    // Si es válido, intentar buscar el nombre asociado en la base de datos
    if ($esValido) {
        // Normalizar: quitar todo lo que no sea dígito o K/k
        $clean = preg_replace('/[^0-9kK]/', '', $rut);
        $numero = substr($clean, 0, -1); // quitar DV
        // Convertir a entero para buscar en campo identificacion (int)
        $numero_int = intval($numero);

        try {
            // Primero, intentar buscar por campo 'carnet' ignorando formato (puntos/guión)
            $db = new Conexion();
            $pdo = $db->pdo;
            $clean_full = strtoupper($clean); // dígitos + DV
            $sql = "SELECT e.nombreCompleto, e.dirResidencia, e.correoPrincipal, e.correoSecundario,
                           e.carnet as rut,
                           e.telResidencia as telefono,
                           e.tituloObtenido AS titulo_obtenido,
                           e.fechaEntregaCertificado AS fecha_entrega_certificado,
                           e.numeroCertificado AS numero_certificado,
                           GROUP_CONCAT(t.nombre) as titulos,
                           GROUP_CONCAT(te.fechaGrado) as fechas_grado,
                           GROUP_CONCAT(te.numero_documento) as numeros_documento
                    FROM egresado e
                    LEFT JOIN tituloegresado te ON e.identificacion = te.identificacion
                    LEFT JOIN titulo t ON te.id = t.id
                    WHERE REPLACE(REPLACE(UPPER(e.carnet),'.',''),'-','') = :clean
                    GROUP BY e.identificacion";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':clean' => $clean_full]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // PDO en Conexion puede convertir nombres en minúsculas; comprobar ambas variantes
                $nombre = null;
                if (isset($row['nombreCompleto'])) {
                    $nombre = $row['nombreCompleto'];
                } elseif (isset($row['nombrecompleto'])) {
                    $nombre = $row['nombrecompleto'];
                }

                if (!empty($nombre)) {
                    $response['nombre'] = $nombre;
                    // Agregar campos adicionales
                    if (isset($row['dirresidencia'])) $response['direccion'] = $row['dirresidencia'];
                    if (isset($row['correoprincipal'])) $response['correo'] = $row['correoprincipal'];
                    if (isset($row['correosecundario'])) $response['correo_secundario'] = $row['correosecundario'];
                    
                    // Procesar títulos si existen
                    $titulosResponse = [];
                    if (isset($row['titulos']) && isset($row['fechas_grado'])) {
                        $titulos = explode(',', (string)$row['titulos']);
                        $fechas = explode(',', (string)$row['fechas_grado']);
                        $numeros = isset($row['numeros_documento']) ? explode(',', (string)$row['numeros_documento']) : [];

                        for ($i = 0; $i < count($titulos); $i++) {
                            if (!empty($titulos[$i])) {
                                $titulosResponse[] = [
                                    'nombre' => $titulos[$i],
                                    'fecha' => $fechas[$i] ?? null,
                                    'numero' => $numeros[$i] ?? null
                                ];
                            }
                        }
                    }

                    $tituloExtraido = $row['titulo_obtenido'] ?? null;
                    $fechaExtraida = $row['fecha_entrega_certificado'] ?? null;
                    $numeroExtraido = $row['numero_certificado'] ?? null;

                    if ($tituloExtraido || $fechaExtraida || $numeroExtraido) {
                        $primario = [
                            'nombre' => $tituloExtraido ?: ($titulosResponse[0]['nombre'] ?? null),
                            'fecha' => $fechaExtraida ?: ($titulosResponse[0]['fecha'] ?? null),
                            'numero' => $numeroExtraido ?: ($titulosResponse[0]['numero'] ?? null)
                        ];

                        $duplicado = false;
                        if (!empty($titulosResponse)) {
                            $first = $titulosResponse[0];
                            $duplicado = ($first['nombre'] ?? null) === $primario['nombre']
                                && ($first['fecha'] ?? null) === $primario['fecha']
                                && ($first['numero'] ?? null) === $primario['numero'];
                        }

                        if (!$duplicado) {
                            array_unshift($titulosResponse, $primario);
                        } else {
                            $titulosResponse[0] = $primario;
                        }
                    } elseif (empty($titulosResponse) && (!empty($row['titulo_obtenido']) || !empty($row['fecha_entrega_certificado']))) {
                        $titulosResponse[] = [
                            'nombre' => $row['titulo_obtenido'] ?? null,
                            'fecha' => $row['fecha_entrega_certificado'] ?? null,
                            'numero' => $row['numero_certificado'] ?? null
                        ];
                    }

                    if (!empty($titulosResponse)) {
                        $response['titulos'] = $titulosResponse;
                        $response['titulo'] = $titulosResponse[0]['nombre'] ?? ($response['titulo'] ?? '');
                        $response['fechaTitulo'] = $titulosResponse[0]['fecha'] ?? ($response['fechaTitulo'] ?? '');
                        if (!empty($titulosResponse[0]['numero'])) {
                            $response['numeroRegistro'] = $titulosResponse[0]['numero'];
                        }
                    }

                    $response['message'] = 'RUT válido. Nombre encontrado.';
                } else {
                    // Si no se encontró por 'carnet', intentar buscar por 'identificacion' (numérico)
                    $egresado = new Egresado();
                    $result = $egresado->Buscar($numero_int);

                    if (!empty($result)) {
                        // $result es un array de objetos; las propiedades pueden ser minúsculas
                        $first = $result[0];
                        $nombre2 = null;
                        if (isset($first->nombreCompleto)) {
                            $nombre2 = $first->nombreCompleto;
                        } elseif (isset($first->nombrecompleto)) {
                            $nombre2 = $first->nombrecompleto;
                        }

                        if (!empty($nombre2)) {
                            $response['nombre'] = $nombre2;
                            $response['message'] = 'RUT válido. Nombre encontrado (búsqueda por identificacion).';
                        } else {
                            $response['message'] = 'RUT válido. No se encontró registro en la base de datos.';
                        }
                    } else {
                        $response['message'] = 'RUT válido. No se encontró registro en la base de datos.';
                    }
                }
            } else {
                // No se obtuvieron filas con la consulta por carnet; intentar búsqueda por identificacion
                $egresado = new Egresado();
                $result = $egresado->Buscar($numero_int);
                if (!empty($result)) {
                    $first = $result[0];
                    $nombre2 = null;
                    if (isset($first->nombreCompleto)) $nombre2 = $first->nombreCompleto;
                    elseif (isset($first->nombrecompleto)) $nombre2 = $first->nombrecompleto;
                    if (!empty($nombre2)) {
                        $response['nombre'] = $nombre2;
                        $response['message'] = 'RUT válido. Nombre encontrado (búsqueda por identificacion).';
                    } else {
                        $response['message'] = 'RUT válido. No se encontró registro en la base de datos.';
                    }
                } else {
                    $response['message'] = 'RUT válido. No se encontró registro en la base de datos.';
                }
            }
        } catch (Exception $e) {
            $response['success'] = false;
            $response['valid'] = true;
            $response['message'] = 'RUT válido, pero error al consultar base de datos: ' . $e->getMessage();
        }
    }

    return $response;
}

// Si se invoca como endpoint web, responder JSON
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');

    $rut = null;
    if (isset($_POST['rut'])) $rut = $_POST['rut'];
    elseif (isset($_GET['rut'])) $rut = $_GET['rut'];

    if ($rut === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'valid' => false,
            'message' => 'Parámetro "rut" faltante. Enviar via POST o GET.'
        ]);
        exit;
    }

    $resp = validar_rut_endpoint($rut);
    echo json_encode($resp);
    exit;
}

// Si se incluye desde CLI para pruebas, la función validar_rut_endpoint está disponible.
