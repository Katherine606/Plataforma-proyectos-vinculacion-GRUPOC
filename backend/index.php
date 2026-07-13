<?php
/**
 * ====================================================================
 * API REST - Plataforma de Proyectos de Vinculación
 * ====================================================================
 */

// ─── CORS ─────────────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─── CONEXIÓN A MYSQL ─────────────────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/config/JwtHelper.php';
require_once __DIR__ . '/controllers/AuthController.php';

$db = Database::getInstance()->getConnection();

$jwtSecret = 'CAMBIAR_ESTA_LLAVE_EN_PRODUCCION_2026';

// ─── HELPERS ──────────────────────────────────────────────────────────────────

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function getBearerToken(): ?string
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($authHeader === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

function autenticarOrFail(JwtHelper $jwtHelper): array
{
    $token = getBearerToken();
    if (!$token) {
        jsonResponse(['error' => 'Token no enviado'], 403);
    }

    $payload = $jwtHelper->validarToken($token);
    if (!$payload) {
        jsonResponse(['error' => 'Token inválido o expirado'], 403);
    }

    return $payload;
}

function autorizarRolOrFail(array $payload, array $rolesPermitidos, string $contexto): void
{
    $rol = $payload['rol'] ?? null;
    if (!$rol || !in_array($rol, $rolesPermitidos, true)) {
        jsonResponse([
            'error'   => 'Acceso denegado',
            'contexto' => $contexto,
            'rol_actual' => $rol
        ], 403);
    }
}

// ─── ENRUTADO ─────────────────────────────────────────────────────────────────
$recurso = $_GET['recurso'] ?? '';
$accion  = $_GET['accion']  ?? '';
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;
$metodo  = $_SERVER['REQUEST_METHOD'];

$authModel     = new Auth();
$jwtHelper     = new JwtHelper($jwtSecret);
$authController = new AuthController($authModel, $jwtHelper);

// ─── AUTH ─────────────────────────────────────────────────────────────────────
if ($recurso === 'auth') {
    if ($metodo === 'POST' && $accion === 'login') {
        $resultado = $authController->login(getJsonBody());
        jsonResponse($resultado['body'], $resultado['status']);
    }

    jsonResponse(['error' => 'Endpoint de auth no encontrado'], 404);
}

// ─── TUTORES ──────────────────────────────────────────────────────────────────
if ($recurso === 'tutores') {
    if ($metodo === 'GET' && $accion === '') {
        autenticarOrFail($jwtHelper);
        $stmt = $db->query("
            SELECT id_usuario as id, nombre, apellido, correo
            FROM usuarios WHERE rol_id = 2 AND estado = 'activo'
            ORDER BY nombre
        ");
        jsonResponse($stmt->fetchAll());
    }
}

// ─── FACULTADES ───────────────────────────────────────────────────────────────
if ($recurso === 'facultades') {
    if ($metodo === 'GET' && $accion === '') {
        autenticarOrFail($jwtHelper);
        $stmt = $db->query("SELECT id_facultad as id, nombre FROM facultades ORDER BY nombre");
        jsonResponse($stmt->fetchAll());
    }
}

// ─── CARRERAS ─────────────────────────────────────────────────────────────────
if ($recurso === 'carreras') {
    if ($metodo === 'GET' && $accion === '') {
        autenticarOrFail($jwtHelper);
        $facultadId = isset($_GET['facultad_id']) ? (int) $_GET['facultad_id'] : null;
        if ($facultadId) {
            $stmt = $db->prepare("SELECT id_carrera as id, nombre FROM carreras WHERE facultad_id = :fid ORDER BY nombre");
            $stmt->execute([':fid' => $facultadId]);
        } else {
            $stmt = $db->query("SELECT id_carrera as id, nombre, facultad_id FROM carreras ORDER BY nombre");
        }
        jsonResponse($stmt->fetchAll());
    }
}

// ─── PROTECCIÓN DE RUTAS ──────────────────────────────────────────────────────
$recursosProtegidos = ['proyectos', 'solicitudes'];
$usuarioAuth = null;

if (in_array($recurso, $recursosProtegidos, true)) {
    $usuarioAuth = autenticarOrFail($jwtHelper);
}

// ─── PROYECTOS ────────────────────────────────────────────────────────────────
if ($recurso === 'proyectos') {

    // GET → lista todos los proyectos con datos del tutor, facultad y carrera
    if ($metodo === 'GET' && $accion === '') {
        $stmt = $db->query("
            SELECT p.id_proyecto as id, p.nombre, p.descripcion,
                   CONCAT(t.nombre, ' ', t.apellido) as tutor,
                   f.nombre as facultad,
                   c.nombre as carrera,
                   p.cupos_max, p.cupos_usados, p.estado,
                   p.fecha_inicio, p.fecha_fin
            FROM proyectos p
            LEFT JOIN usuarios t ON p.tutor_id = t.id_usuario
            LEFT JOIN facultades f ON p.facultad_id = f.id_facultad
            LEFT JOIN carreras c ON p.carrera_id = c.id_carrera
            ORDER BY p.id_proyecto DESC
        ");
        jsonResponse($stmt->fetchAll());
    }

    // POST → crear proyecto (solo Coordinador)
    if ($metodo === 'POST' && $accion === '') {
        autorizarRolOrFail($usuarioAuth, ['Coordinador'], 'Crear proyecto');
        $data = getJsonBody();

        // Validar campos obligatorios
        $camposRequeridos = ['nombre', 'descripcion', 'tutor_id', 'facultad_id', 'carrera_id', 'cupos_max'];
        foreach ($camposRequeridos as $campo) {
            if (empty($data[$campo])) {
                jsonResponse(['error' => "El campo '$campo' es obligatorio"], 400);
            }
        }

        $cuposMax = min((int) $data['cupos_max'], 60);
        if ($cuposMax <= 0) {
            jsonResponse(['error' => 'Los cupos máximos deben ser mayores a 0'], 400);
        }

        // Verificar que el tutor exista y sea rol Tutor
        $stmtTutor = $db->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND rol_id = 2");
        $stmtTutor->execute([':id' => $data['tutor_id']]);
        if (!$stmtTutor->fetch()) {
            jsonResponse(['error' => 'El tutor seleccionado no es válido'], 400);
        }

        // Insertar proyecto
        $stmt = $db->prepare("
            INSERT INTO proyectos (nombre, descripcion, tutor_id, facultad_id, carrera_id, cupos_max, cupos_usados, estado)
            VALUES (:nombre, :descripcion, :tutor_id, :facultad_id, :carrera_id, :cupos_max, 0, 'activo')
        ");
        $stmt->execute([
            ':nombre'       => $data['nombre'],
            ':descripcion'  => $data['descripcion'],
            ':tutor_id'     => $data['tutor_id'],
            ':facultad_id'  => $data['facultad_id'],
            ':carrera_id'   => $data['carrera_id'],
            ':cupos_max'    => $cuposMax,
        ]);

        $nuevoId = $db->lastInsertId();

        // Retornar el proyecto creado con datos completos
        $stmtProyecto = $db->prepare("
            SELECT p.id_proyecto as id, p.nombre, p.descripcion,
                   CONCAT(t.nombre, ' ', t.apellido) as tutor,
                   f.nombre as facultad,
                   c.nombre as carrera,
                   p.cupos_max, p.cupos_usados, p.estado
            FROM proyectos p
            LEFT JOIN usuarios t ON p.tutor_id = t.id_usuario
            LEFT JOIN facultades f ON p.facultad_id = f.id_facultad
            LEFT JOIN carreras c ON p.carrera_id = c.id_carrera
            WHERE p.id_proyecto = :id
        ");
        $stmtProyecto->execute([':id' => $nuevoId]);
        jsonResponse($stmtProyecto->fetch(), 201);
    }

    // PUT → actualizar proyecto (solo Coordinador)
    if ($metodo === 'PUT' && $id !== null) {
        autorizarRolOrFail($usuarioAuth, ['Coordinador'], 'Editar proyecto');
        $data = getJsonBody();

        // Verificar que el proyecto exista
        $stmtCheck = $db->prepare("SELECT id_proyecto FROM proyectos WHERE id_proyecto = :id");
        $stmtCheck->execute([':id' => $id]);
        if (!$stmtCheck->fetch()) {
            jsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        // Construir UPDATE dinámicamente
        $camposPermitidos = ['nombre', 'descripcion', 'tutor_id', 'facultad_id', 'carrera_id', 'cupos_max', 'estado'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $sets[] = "$campo = :$campo";
                $params[":$campo"] = $data[$campo];
            }
        }

        if (empty($sets)) {
            jsonResponse(['error' => 'No se enviaron datos para actualizar'], 400);
        }

        $sql = "UPDATE proyectos SET " . implode(', ', $sets) . " WHERE id_proyecto = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Retornar proyecto actualizado
        $stmtProyecto = $db->prepare("
            SELECT p.id_proyecto as id, p.nombre, p.descripcion,
                   CONCAT(t.nombre, ' ', t.apellido) as tutor,
                   f.nombre as facultad,
                   c.nombre as carrera,
                   p.cupos_max, p.cupos_usados, p.estado
            FROM proyectos p
            LEFT JOIN usuarios t ON p.tutor_id = t.id_usuario
            LEFT JOIN facultades f ON p.facultad_id = f.id_facultad
            LEFT JOIN carreras c ON p.carrera_id = c.id_carrera
            WHERE p.id_proyecto = :id
        ");
        $stmtProyecto->execute([':id' => $id]);
        jsonResponse($stmtProyecto->fetch());
    }

    // DELETE → eliminar proyecto (solo Coordinador)
    if ($metodo === 'DELETE' && $id !== null) {
        autorizarRolOrFail($usuarioAuth, ['Coordinador'], 'Eliminar proyecto');

        $stmtCheck = $db->prepare("SELECT id_proyecto FROM proyectos WHERE id_proyecto = :id");
        $stmtCheck->execute([':id' => $id]);
        if (!$stmtCheck->fetch()) {
            jsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        $stmt = $db->prepare("DELETE FROM proyectos WHERE id_proyecto = :id");
        $stmt->execute([':id' => $id]);
        jsonResponse(['ok' => true]);
    }
}

// ─── SOLICITUDES ──────────────────────────────────────────────────────────────
if ($recurso === 'solicitudes') {

    // GET → listar solicitudes con datos del estudiante y proyecto
    if ($metodo === 'GET' && $accion === '') {
        $stmt = $db->query("
            SELECT s.id_solicitud as id,
                   CONCAT(e.nombre, ' ', e.apellido) as estudiante,
                   e.correo as estudiante_email,
                   s.proyecto_id as id_proyecto,
                   p.nombre as nombre_proyecto,
                   s.estado,
                   s.fecha_solicitud
            FROM solicitudes s
            JOIN usuarios e ON s.estudiante_id = e.id_usuario
            JOIN proyectos p ON s.proyecto_id = p.id_proyecto
            ORDER BY s.fecha_solicitud DESC
        ");
        jsonResponse($stmt->fetchAll());
    }

    // POST → crear solicitud (estudiante)
    if ($metodo === 'POST' && $accion === '') {
        autorizarRolOrFail($usuarioAuth, ['Estudiante'], 'Crear solicitud');
        $data = getJsonBody();
        $idProyecto = isset($data['id_proyecto']) ? (int) $data['id_proyecto'] : 0;

        if ($idProyecto <= 0) {
            jsonResponse(['error' => 'id_proyecto inválido'], 400);
        }

        // Verificar que el proyecto exista y tenga cupos
        $stmtProyecto = $db->prepare("
            SELECT id_proyecto, nombre, cupos_max, cupos_usados, estado
            FROM proyectos WHERE id_proyecto = :id
        ");
        $stmtProyecto->execute([':id' => $idProyecto]);
        $proyecto = $stmtProyecto->fetch();

        if (!$proyecto) {
            jsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        if ($proyecto['cupos_usados'] >= $proyecto['cupos_max']) {
            jsonResponse(['error' => 'No hay cupos disponibles'], 400);
        }

        if ($proyecto['estado'] !== 'activo') {
            jsonResponse(['error' => 'El proyecto no está activo'], 400);
        }

        // Verificar que no tenga solicitud duplicada (pendiente o aceptada)
        $stmtDuplicada = $db->prepare("
            SELECT COUNT(*) FROM solicitudes
            WHERE estudiante_id = :eid AND proyecto_id = :pid
            AND estado IN ('pendiente', 'aceptada')
        ");
        $stmtDuplicada->execute([
            ':eid' => $usuarioAuth['sub'],
            ':pid' => $idProyecto
        ]);

        if ($stmtDuplicada->fetchColumn() > 0) {
            jsonResponse(['error' => 'Ya existe una solicitud abierta para este proyecto'], 400);
        }

        // Crear solicitud con transacción
        $db->beginTransaction();
        try {
            $stmtInsert = $db->prepare("
                INSERT INTO solicitudes (estudiante_id, proyecto_id, estado, fecha_solicitud)
                VALUES (:eid, :pid, 'pendiente', NOW())
            ");
            $stmtInsert->execute([
                ':eid' => $usuarioAuth['sub'],
                ':pid' => $idProyecto
            ]);

            $nuevaSolicitudId = $db->lastInsertId();
            $db->commit();

            jsonResponse([
                'id'              => (int) $nuevaSolicitudId,
                'estudiante'      => $usuarioAuth['correo'],
                'id_proyecto'     => $idProyecto,
                'nombre_proyecto' => $proyecto['nombre'],
                'estado'          => 'pendiente'
            ], 201);

        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Error al crear la solicitud'], 500);
        }
    }

    // PUT → aceptar/denegar solicitud (Tutor o Coordinador)
    if ($metodo === 'PUT' && $id !== null) {
        autorizarRolOrFail($usuarioAuth, ['Coordinador', 'Tutor'], 'Gestionar solicitud');

        $nuevoEstado = null;
        if ($accion === 'aceptar') {
            $nuevoEstado = 'aceptada';
        } elseif ($accion === 'denegar') {
            $nuevoEstado = 'denegada';
        }

        if ($nuevoEstado === null) {
            jsonResponse(['error' => 'Acción inválida. Use accion=aceptar o accion=denegar'], 400);
        }

        // Verificar que la solicitud exista y esté pendiente
        $stmtSolicitud = $db->prepare("
            SELECT s.*, p.nombre as nombre_proyecto
            FROM solicitudes s
            JOIN proyectos p ON s.proyecto_id = p.id_proyecto
            WHERE s.id_solicitud = :id AND s.estado = 'pendiente'
        ");
        $stmtSolicitud->execute([':id' => $id]);
        $solicitud = $stmtSolicitud->fetch();

        if (!$solicitud) {
            jsonResponse(['error' => 'Solicitud no encontrada o ya fue procesada'], 404);
        }

        // Transacción: actualizar solicitud + incrementar cupos
        $db->beginTransaction();
        try {
            // Actualizar estado de la solicitud
            $stmtUpdate = $db->prepare("UPDATE solicitudes SET estado = :estado WHERE id_solicitud = :id");
            $stmtUpdate->execute([':estado' => $nuevoEstado, ':id' => $id]);

            // Si se acepta, incrementar cupos_usados del proyecto
            if ($nuevoEstado === 'aceptada') {
                $stmtCupos = $db->prepare("
                    UPDATE proyectos SET cupos_usados = cupos_usados + 1
                    WHERE id_proyecto = :pid AND cupos_usados < cupos_max
                ");
                $stmtCupos->execute([':pid' => $solicitud['proyecto_id']]);

                if ($stmtCupos->rowCount() === 0) {
                    $db->rollBack();
                    jsonResponse(['error' => 'No hay cupos disponibles'], 400);
                }
            }

            $db->commit();

            jsonResponse([
                'id'              => (int) $id,
                'estudiante'      => $solicitud['estudiante_id'],
                'id_proyecto'     => $solicitud['proyecto_id'],
                'nombre_proyecto' => $solicitud['nombre_proyecto'],
                'estado'          => $nuevoEstado
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    // POST → reset (solo para desarrollo)
    if ($metodo === 'POST' && $accion === 'reset') {
        autorizarRolOrFail($usuarioAuth, ['Coordinador'], 'Resetear solicitudes');
        $db->exec("DELETE FROM solicitudes");
        jsonResponse(['ok' => true, 'mensaje' => 'Solicitudes reseteadas']);
    }
}

jsonResponse(['error' => 'Endpoint no encontrado', 'recurso' => $recurso, 'metodo' => $metodo], 404);
