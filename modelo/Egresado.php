<?php
include_once 'Conexion.php';

class Egresado {
    var $objetos;

    public function __construct() {
        $db = new Conexion();
        $this->acceso = $db->pdo;
    }

    //-----------------------------------------------------------
    // Buscar los registros segun criterio de busqueda en consulta
    //-----------------------------------------------------------
    function BuscarTodos($consulta = '') {
        if (!empty($consulta)) {
        $sql = "SELECT e.identificacion, e.nombreCompleto, e.dirResidencia, e.telResidencia, e.telAlternativo, e.correoPrincipal,
                e.correoSecundario, e.carnet, e.sexo, e.fallecido, t.nombre as titulo_catalogo, e.tituloObtenido as titulo_obtenido, e.numeroCertificado as numerocertificado, e.avatar, e.expediente_pdf,
                DATE_FORMAT(te.fechaGrado, '%Y-%m-%d') as fechaGrado, DATE_FORMAT(e.fechaEntregaCertificado, '%Y-%m-%d') as fechaEntregaCertificado
            FROM egresado e
                    LEFT JOIN tituloegresado te ON e.identificacion = te.identificacion
                    LEFT JOIN titulo t ON te.id = t.id
                    WHERE e.nombreCompleto LIKE :consulta 
                    OR e.identificacion LIKE :consulta
                    OR t.nombre LIKE :consulta
                    OR (te.fechaGrado IS NOT NULL AND YEAR(te.fechaGrado) LIKE :consulta)";
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':consulta' => "%$consulta%"));
            $this->objetos = $query->fetchall();
        } else {
        $sql = "SELECT e.identificacion, e.nombreCompleto, e.dirResidencia, e.telResidencia, e.telAlternativo, e.correoPrincipal,
                e.correoSecundario, e.carnet, e.sexo, e.fallecido, t.nombre as titulo_catalogo, e.tituloObtenido as titulo_obtenido, e.numeroCertificado AS numerocertificado, e.avatar, e.expediente_pdf,
                DATE_FORMAT(te.fechaGrado, '%Y-%m-%d') as fechaGrado, DATE_FORMAT(e.fechaEntregaCertificado, '%Y-%m-%d') as fechaEntregaCertificado
            FROM egresado e
                    LEFT JOIN tituloegresado te ON e.identificacion = te.identificacion
                    LEFT JOIN titulo t ON te.id = t.id
                    ORDER BY e.identificacion";
            $query = $this->acceso->prepare($sql);
            $query->execute();
            $this->objetos = $query->fetchall();
        }

        return $this->objetos;
    }

    //-----------------------------------------------------------
    // Buscar un registro por ID
    //-----------------------------------------------------------
    function Buscar($id) {
    $sql = "SELECT e.identificacion, e.nombreCompleto, e.dirResidencia, e.telResidencia, e.telAlternativo, e.correoPrincipal,
            e.correoSecundario, e.carnet, e.sexo, e.fallecido,
            t.nombre as titulo_catalogo, e.tituloObtenido as titulo_obtenido,
            e.avatar, e.expediente_pdf,
            DATE_FORMAT(te.fechaGrado, '%Y-%m-%d') as fechaGrado,
            DATE_FORMAT(e.fechaEntregaCertificado, '%Y-%m-%d') as fechaEntregaCertificado
        FROM egresado e
                LEFT JOIN tituloegresado te ON e.identificacion = te.identificacion
                LEFT JOIN titulo t ON te.id = t.id
                WHERE e.identificacion = :id";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id' => $id));
        $this->objetos = $query->fetchall();
        return $this->objetos;
    }

    //-----------------------------------------------------------
    // Buscar un registro por carnet
    //-----------------------------------------------------------
    function BuscarPorCarnet($carnet) {
        $sql = "SELECT e.identificacion, e.nombreCompleto, e.dirResidencia, e.telResidencia, e.telAlternativo, e.correoPrincipal,
                        e.correoSecundario, e.carnet, e.sexo, e.fallecido, e.avatar
                FROM egresado e
                WHERE e.carnet = :carnet";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':carnet' => $carnet));
        $this->objetos = $query->fetchall();
        return $this->objetos;
    }

    //-------------------------------------------
    // Crear (ID autogenerado como MAX(identificacion)+1)
    //-------------------------------------------
    function Crear($nombreCompleto, $dirResidencia, $telResidencia, $telAlternativo, $correoPrincipal, $correoSecundario, $carnet, $sexo, $fallecido, $avatar) {
        // Calcular siguiente ID disponible
        $sql = "SELECT COALESCE(MAX(identificacion),0)+1 AS next_id FROM egresado";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $next_id = isset($row['next_id']) ? intval($row['next_id']) : 1;

        // Insertar registro con el nuevo ID
        $sql = "INSERT INTO egresado (identificacion, nombreCompleto, dirResidencia, telResidencia, telAlternativo, correoPrincipal, correoSecundario, carnet, sexo, fallecido, avatar)
                VALUES (:identificacion, :nombreCompleto, :dirResidencia, :telResidencia, :telAlternativo, :correoPrincipal, :correoSecundario, :carnet, :sexo, :fallecido, :avatar)";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(
            ':identificacion' => $next_id,
            ':nombreCompleto' => $nombreCompleto,
            ':dirResidencia' => $dirResidencia,
            ':telResidencia' => $telResidencia,
            ':telAlternativo' => $telAlternativo,
            ':correoPrincipal' => $correoPrincipal,
            ':correoSecundario' => $correoSecundario,
            ':carnet' => $carnet,
            ':sexo' => $sexo,
            ':fallecido' => $fallecido,
            ':avatar' => $avatar
        ));
        return 'add';
    }
    

    //-----------------------------------------------------------
    // Editar
    //-----------------------------------------------------------
    function Editar($identificacion, $nombreCompleto, $dirResidencia, $telResidencia, $telAlternativo, $correoPrincipal, $correoSecundario, $carnet, $sexo, $fallecido) {
        $sql = "UPDATE egresado SET nombreCompleto = :nombreCompleto, dirResidencia = :dirResidencia, telResidencia = :telResidencia, telAlternativo = :telAlternativo, correoPrincipal = :correoPrincipal, correoSecundario = :correoSecundario, carnet = :carnet, sexo = :sexo, fallecido = :fallecido
                WHERE identificacion = :identificacion";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':identificacion' => $identificacion, ':nombreCompleto' => $nombreCompleto, ':dirResidencia' => $dirResidencia, ':telResidencia' => $telResidencia, ':telAlternativo' => $telAlternativo, ':correoPrincipal' => $correoPrincipal, ':correoSecundario' => $correoSecundario, ':carnet' => $carnet, ':sexo' => $sexo, ':fallecido' => $fallecido));
    }

    //-----------------------------------------------------------
    // Eliminar
    //-----------------------------------------------------------
    function Eliminar($id) {
        try {
            $this->acceso->beginTransaction();

            $sql = "DELETE FROM tituloegresado WHERE identificacion = :id";
            $query = $this->acceso->prepare($sql);
            $query->execute([':id' => $id]);

            $sql = "DELETE FROM observacion WHERE identificacion = :id";
            $query = $this->acceso->prepare($sql);
            $query->execute([':id' => $id]);

            $sql = "DELETE FROM egresado WHERE identificacion = :id";
            $query = $this->acceso->prepare($sql);
            $query->execute([':id' => $id]);

            $this->acceso->commit();
            echo 'eliminado';
        } catch (Exception $e) {
            $this->acceso->rollBack();
            echo 'noeliminado';
        }
    }

    //-----------------------------------------------------------
    // Funcion para cargar un ComboBox
    //-----------------------------------------------------------
    function Seleccionar() {
        $sql = "SELECT * FROM egresado ORDER BY nombreCompleto asc";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $this->objetos = $query->fetchall();
        return $this->objetos;
    }

    //--------------------------------
    // Cambiar Avatar
    //--------------------------------
    function CambiarLogo($id, $img) {
        // Consulta el nombre de la imagen antes de borrarla
        $sql = 'SELECT avatar from egresado WHERE identificacion = :id';
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id' => $id));
        $this->objetos = $query->fetchall();

        // Actualiza la imagen
        $sql = 'UPDATE egresado SET avatar = :img WHERE identificacion = :id';
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id' => $id, ':img' => $img));

        return $this->objetos;
    }

        // Cambiar Expediente PDF
        function CambiarExpediente($id, $filename) {
            $this->asegurarColumnasCertificado();

            // Obtener expediente actual
            $sql = 'SELECT expediente_pdf from egresado WHERE identificacion = :id';
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id' => $id));
            $this->objetos = $query->fetchall();

            // Actualiza el expediente
            $sql = 'UPDATE egresado SET expediente_pdf = :file WHERE identificacion = :id';
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id' => $id, ':file' => $filename));

            return $this->objetos;
        }

        public function CrearDesdeExpediente(array $datos, ?string $expedienteArchivo = null) {
            $this->asegurarColumnasCertificado();

            $nombre = $datos['nombre'] ?? null;
            $rut = $datos['rut'] ?? null;

            if (empty($nombre) && empty($rut)) {
                return false;
            }

            $identificacion = $this->obtenerSiguienteIdentificacion();

            $sql = "INSERT INTO egresado (identificacion, nombreCompleto, carnet, anioEgreso, tituloObtenido, numeroCertificado, fechaEntregaCertificado, expediente_pdf)
                    VALUES (:identificacion, :nombre, :carnet, :anio, :titulo, :numero, :fecha, :expediente)";
            $query = $this->acceso->prepare($sql);
            $query->execute([
                ':identificacion' => $identificacion,
                ':nombre' => $nombre,
                ':carnet' => $rut,
                ':anio' => $datos['anio_egreso'] ?? null,
                ':titulo' => $datos['titulo'] ?? null,
                ':numero' => $datos['numero_certificado'] ?? null,
                ':fecha' => $datos['fecha_entrega'] ?? null,
                ':expediente' => $expedienteArchivo
            ]);

            return $identificacion;
        }

        public function BuscarPorRutNormalizado($rut) {
            $clean = preg_replace('/[^0-9kK]/', '', strtoupper($rut));
            if (empty($clean)) {
                return null;
            }

            $sql = "SELECT identificacion, nombreCompleto, carnet FROM egresado WHERE REPLACE(REPLACE(UPPER(carnet),'.',''),'-','') = :rut LIMIT 1";
            $query = $this->acceso->prepare($sql);
            $query->execute([':rut' => $clean]);
            return $query->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        public function BuscarPorNumeroCertificado(?string $numero) {
            if (empty($numero)) {
                return null;
            }

            $sql = "SELECT identificacion, nombreCompleto, numeroCertificado FROM egresado WHERE numeroCertificado = :numero LIMIT 1";
            $query = $this->acceso->prepare($sql);
            $query->execute([':numero' => $numero]);
            return $query->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        public function ObtenerDatosCertificadoPorRut(?string $rut)
        {
            $clean = preg_replace('/[^0-9kK]/', '', strtoupper((string) $rut));
            if (empty($clean)) {
                return null;
            }

            $sql = "SELECT 
                        e.identificacion,
                        e.nombreCompleto,
                        e.carnet,
                        e.tituloObtenido,
                        e.fechaEntregaCertificado,
                        e.numeroCertificado,
                        t.nombre AS titulo_catalogo,
                        te.fechaGrado,
                        te.numero_documento,
                        te.titulo_nombre,
                        te.fecha_grado
                    FROM egresado e
                    LEFT JOIN tituloegresado te ON e.identificacion = te.identificacion
                    LEFT JOIN titulo t ON te.id = t.id
                    WHERE REPLACE(REPLACE(UPPER(e.carnet),'.',''),'-','') = :rut
                    LIMIT 1";

            $query = $this->acceso->prepare($sql);
            $query->execute([':rut' => $clean]);
            $row = $query->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        }

        public function ActualizarDatosCertificado($identificacion, array $datos) {
            $this->asegurarColumnasCertificado();

            $columnMap = [
                'nombre' => 'nombreCompleto',
                'rut' => 'carnet',
                'correo' => 'correoPrincipal',
                'sexo' => 'sexo',
                'anio_egreso' => 'anioEgreso',
                'titulo' => 'tituloObtenido',
                'numero_certificado' => 'numeroCertificado',
                'fecha_entrega' => 'fechaEntregaCertificado',
            ];

            $setParts = [];
            $params = [':identificacion' => $identificacion];

            foreach ($columnMap as $key => $column) {
                if (!isset($datos[$key]) || $datos[$key] === '' || $datos[$key] === null) {
                    continue;
                }
                $placeholder = ':' . $column;
                $setParts[] = "$column = $placeholder";
                $params[$placeholder] = $datos[$key];
            }

            if (empty($setParts)) {
                return false;
            }

            $sql = 'UPDATE egresado SET ' . implode(', ', $setParts) . ' WHERE identificacion = :identificacion';
            $query = $this->acceso->prepare($sql);
            return $query->execute($params);
        }

        public function ActualizarTituloEgresadoDatos($identificacion, array $datos) {
            $this->asegurarColumnasTituloEgresado();

            $numeroDocumento = $datos['numero_documento'] ?? null;
            $fechaGrado = $datos['fecha_grado'] ?? null;
            $tituloId = $datos['titulo_id'] ?? null;
            $tituloNombre = $datos['titulo_nombre'] ?? null;

            if (!$tituloId && !empty($tituloNombre)) {
                $tituloId = $this->obtenerTituloIdPorNombre($tituloNombre);
            }

            $sql = 'SELECT id FROM tituloegresado WHERE identificacion = :identificacion LIMIT 1';
            $check = $this->acceso->prepare($sql);
            $check->execute([':identificacion' => $identificacion]);
            $existe = $check->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                $setParts = [];
                $params = [':identificacion' => $identificacion];

                if ($tituloId) {
                    $setParts[] = 'id = :titulo_id';
                    $params[':titulo_id'] = $tituloId;
                }

                if (!empty($fechaGrado)) {
                    $setParts[] = 'fechaGrado = :fechaGrado';
                    $params[':fechaGrado'] = $fechaGrado;
                }

                if (!empty($numeroDocumento)) {
                    $setParts[] = 'numero_documento = :numero_documento';
                    $params[':numero_documento'] = $numeroDocumento;
                }

                if (empty($setParts)) {
                    return false;
                }

                $sqlUpdate = 'UPDATE tituloegresado SET ' . implode(', ', $setParts) . ' WHERE identificacion = :identificacion';
                $query = $this->acceso->prepare($sqlUpdate);
                return $query->execute($params);
            }

            if (!$tituloId) {
                return false;
            }

            $insert = 'INSERT INTO tituloegresado (id, identificacion, fechaGrado, numero_documento)
                       VALUES (:id, :identificacion, :fechaGrado, :numero_documento)';
            $query = $this->acceso->prepare($insert);
            return $query->execute([
                ':id' => $tituloId,
                ':identificacion' => $identificacion,
                ':fechaGrado' => $fechaGrado,
                ':numero_documento' => $numeroDocumento
            ]);
        }

    // Obtener datos de género
    public function obtenerDatosGenero() {
        $sql = "SELECT sexo, COUNT(*) AS cantidad FROM gestion_egresados.egresado GROUP BY sexo";
        $stmt = $this->acceso->query($sql);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['sexo']] = $row['cantidad'];
        }
        return $result;
    }

    public function ActualizarFechaManual($identificacion, $fecha)
    {
        if (empty($identificacion) || empty($fecha)) {
            return false;
        }

        $this->asegurarColumnasCertificado();
        $stmt = $this->acceso->prepare('UPDATE egresado SET fechaEntregaCertificado = :fecha WHERE identificacion = :identificacion');
        $stmt->execute([
            ':fecha' => $fecha,
            ':identificacion' => $identificacion
        ]);

        $this->asegurarColumnasTituloEgresado();
        $stmt2 = $this->acceso->prepare('UPDATE tituloegresado SET fechaGrado = :fecha WHERE identificacion = :identificacion');
        $stmt2->execute([
            ':fecha' => $fecha,
            ':identificacion' => $identificacion
        ]);

        return true;
    }

    private function asegurarColumnasCertificado() {
        $this->ensureColumnExistsInTable('egresado', 'expediente_pdf', 'VARCHAR(255) NULL DEFAULT NULL');
        $this->ensureColumnExistsInTable('egresado', 'anioEgreso', 'INT NULL DEFAULT NULL');
        $this->ensureColumnExistsInTable('egresado', 'numeroCertificado', 'VARCHAR(50) NULL DEFAULT NULL');
        $this->ensureColumnExistsInTable('egresado', 'fechaEntregaCertificado', 'DATE NULL DEFAULT NULL');
        $this->ensureColumnExistsInTable('egresado', 'tituloObtenido', 'VARCHAR(255) NULL DEFAULT NULL');
    }

    private function asegurarColumnasTituloEgresado() {
        $this->ensureColumnExistsInTable('tituloegresado', 'numero_documento', 'VARCHAR(50) NULL DEFAULT NULL');
    }

    private function ensureColumnExistsInTable($table, $column, $definition) {
        $sql = sprintf('SHOW COLUMNS FROM `%s` LIKE :column', $table);
        $query = $this->acceso->prepare($sql);
        $query->execute([':column' => $column]);
        $exists = $query->fetch(PDO::FETCH_ASSOC);

        if (!$exists) {
            $alter = sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition);
            $this->acceso->exec($alter);
        }
    }

    private function obtenerSiguienteIdentificacion() {
        $sql = "SELECT COALESCE(MAX(identificacion),0)+1 AS next_id FROM egresado";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);
        return isset($row['next_id']) ? intval($row['next_id']) : 1;
    }

    private function obtenerTituloIdPorNombre($tituloNombre) {
        if (empty($tituloNombre)) {
            return null;
        }

        $tituloNombre = trim($tituloNombre);
        if ($tituloNombre === '') {
            return null;
        }

        // 1) intento exacto (respetando mayúsculas/minúsculas)
        $sqlExact = "SELECT id FROM titulo WHERE UPPER(nombre) = UPPER(:nombre) LIMIT 1";
        $queryExact = $this->acceso->prepare($sqlExact);
        $queryExact->execute([':nombre' => $tituloNombre]);
        $rowExact = $queryExact->fetch(PDO::FETCH_ASSOC);
        if ($rowExact && isset($rowExact['id'])) {
            return (int) $rowExact['id'];
        }

        // 2) buscar el mejor match por similitud / palabras clave
        $normalizedTarget = $this->normalizeTituloCadena($tituloNombre);
        if ($normalizedTarget === '') {
            return null;
        }

        $sql = "SELECT id, nombre FROM titulo";
        $query = $this->acceso->prepare($sql);
        $query->execute();
        $titulos = $query->fetchAll(PDO::FETCH_ASSOC);

        $bestId = null;
        $bestScore = 0;

        foreach ($titulos as $row) {
            $catalogName = $row['nombre'] ?? '';
            if ($catalogName === '') {
                continue;
            }

            $normalizedCatalog = $this->normalizeTituloCadena($catalogName);
            if ($normalizedCatalog === '') {
                continue;
            }

            if ($normalizedCatalog === $normalizedTarget) {
                return (int) $row['id'];
            }

            $score = $this->calcularSimilitudTitulos($normalizedTarget, $normalizedCatalog);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = (int) $row['id'];
            }
        }

        if ($bestId !== null && $bestScore >= 70) {
            return $bestId;
        }

        // 3) crear registro nuevo para mantener catálogo actualizado
        return $this->crearTituloSiNoExiste($tituloNombre);
    }

    private function crearTituloSiNoExiste(string $tituloNombre): ?int {
        $nombre = trim($tituloNombre);
        if ($nombre === '') {
            return null;
        }

        $sqlCheck = "SELECT id FROM titulo WHERE UPPER(nombre) = UPPER(:nombre) LIMIT 1";
        $queryCheck = $this->acceso->prepare($sqlCheck);
        $queryCheck->execute([':nombre' => $nombre]);
        $row = $queryCheck->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['id'])) {
            return (int) $row['id'];
        }

        $sqlInsert = "INSERT INTO titulo (nombre) VALUES (:nombre)";
        $insert = $this->acceso->prepare($sqlInsert);
        $insert->execute([':nombre' => $nombre]);

        return (int) $this->acceso->lastInsertId();
    }

    private function normalizeTituloCadena(string $titulo): string {
        $upper = mb_strtoupper($titulo, 'UTF-8');
        $unaccent = strtr($upper, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ü' => 'U', 'Ñ' => 'N'
        ]);
        $clean = preg_replace('/[^A-Z0-9 ]+/u', ' ', $unaccent);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        if ($clean === '') {
            return '';
        }

        $stopwords = ['DE', 'DEL', 'LA', 'EL', 'LOS', 'LAS', 'EN', 'A', 'Y', 'NIVEL', 'MEDIO', 'TECNICO', 'TECNICA', 'PROFESIONAL'];
        $parts = array_filter(explode(' ', $clean), function ($word) use ($stopwords) {
            return !in_array($word, $stopwords, true);
        });

        return implode(' ', $parts) ?: $clean;
    }

    private function calcularSimilitudTitulos(string $tituloA, string $tituloB): float {
        if ($tituloA === '' || $tituloB === '') {
            return 0;
        }

        if (strpos($tituloA, $tituloB) !== false || strpos($tituloB, $tituloA) !== false) {
            $shorter = min(strlen($tituloA), strlen($tituloB));
            $longer = max(strlen($tituloA), strlen($tituloB));
            return $longer > 0 ? ($shorter / $longer) * 100 : 0;
        }

        similar_text($tituloA, $tituloB, $percent);
        return (float) $percent;
    }

    // Obtener datos de títulos
    public function obtenerDatosTitulo() {
        $sql = "SELECT titulo.nombre, COUNT(*) AS cantidad 
                FROM gestion_egresados.tituloegresado 
                JOIN gestion_egresados.titulo 
                ON tituloegresado.id = titulo.id 
                GROUP BY titulo.nombre";
        $stmt = $this->acceso->query($sql);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['nombre']] = $row['cantidad'];
        }
        return $result;
    }

    // Obtener datos de fallecidos
    public function obtenerDatosFallecidos() {
        $sql = "SELECT fallecido, COUNT(*) AS cantidad 
                FROM gestion_egresados.egresado 
                GROUP BY fallecido";
        $stmt = $this->acceso->query($sql);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $estado = $row['fallecido'] == 'Si' ? 'Fallecido' : 'No Fallecido';
            $result[$estado] = $row['cantidad'];
        }
        return $result;
    }

    // Obtener datos de año de graduación
    public function obtenerDatosGraduacion() {
        $sql = "SELECT YEAR(fechaGrado) AS anio, COUNT(*) AS cantidad 
                FROM gestion_egresados.tituloegresado 
                GROUP BY anio 
                ORDER BY anio";
        $stmt = $this->acceso->query($sql);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['anio']] = $row['cantidad'];
        }
        return $result;
    }

    // Obtener títulos de un egresado por su identificación
    public function ObtenerTitulosPorIdentificacion($identificacion) {
        $sql = "SELECT t.nombre, te.fechaGrado FROM tituloegresado te JOIN titulo t ON te.id = t.id WHERE te.identificacion = :id";
        $query = $this->acceso->prepare($sql);
        $query->execute(array(':id' => $identificacion));
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
