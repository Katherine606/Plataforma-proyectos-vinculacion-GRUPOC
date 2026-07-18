<?php
/**
 * ====================================================================
 * API REST - Plataforma de Proyectos de Vinculación
 * --------------------------------------------------------------------
 * Router limpio — MVC con DTO/DAO/Controller
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

// ─── DEPENDENCIAS ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/JwtHelper.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/models/dto/UsuarioDTO.php';
require_once __DIR__ . '/models/dto/ProyectoDTO.php';
require_once __DIR__ . '/models/dto/SolicitudDTO.php';
require_once __DIR__ . '/models/dto/HoraDTO.php';
require_once __DIR__ . '/models/dto/ActividadDTO.php';
require_once __DIR__ . '/models/dao/UsuarioDAO.php';
require_once __DIR__ . '/models/dao/ProyectoDAO.php';
require_once __DIR__ . '/models/dao/SolicitudDAO.php';
require_once __DIR__ . '/models/dao/HoraDAO.php';
require_once __DIR__ . '/models/dao/DashboardDAO.php';
require_once __DIR__ . '/models/dao/ActividadDAO.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProyectoController.php';
require_once __DIR__ . '/controllers/SolicitudController.php';
require_once __DIR__ . '/controllers/HoraController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/CatalogoController.php';
require_once __DIR__ . '/controllers/ActividadController.php';

// ─── HELPERS ──────────────────────────────────────────────────────────────────
$jwtSecret = 'CAMBIAR_ESTA_LLAVE_EN_PRODUCCION_2026';
$jwtHelper = new JwtHelper($jwtSecret);

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

function autenticarOrFail(): array
{
    global $jwtHelper;
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
    if (!$rol) {
        jsonResponse(['error' => 'Acceso denegado', 'contexto' => $contexto, 'rol_actual' => null], 403);
    }

    $rolLower = strtolower($rol);
    $permitido = false;
    foreach ($rolesPermitidos as $rp) {
        if (str_contains($rolLower, strtolower($rp))) {
            $permitido = true;
            break;
        }
    }

    if (!$permitido) {
        jsonResponse([
            'error'      => 'Acceso denegado',
            'contexto'   => $contexto,
            'rol_actual' => $rol
        ], 403);
    }
}

// ─── PARÁMETROS DE RUTA ───────────────────────────────────────────────────────
$recurso = $_GET['recurso'] ?? '';
$accion  = $_GET['accion']  ?? '';
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;
$metodo  = $_SERVER['REQUEST_METHOD'];

// ══════════════════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS
// ══════════════════════════════════════════════════════════════════════════════

// AUTH — Login
if ($recurso === 'auth') {
    if ($metodo === 'POST' && $accion === 'login') {
        $authModel      = new Auth();
        $authController = new AuthController($authModel, $jwtHelper);
        $resultado      = $authController->login(getJsonBody());
        jsonResponse($resultado['body'], $resultado['status']);
    }
    jsonResponse(['error' => 'Endpoint de auth no encontrado'], 404);
}

// ─── CATÁLOGOS PÚBLICOS (solo requieren autenticación) ───────────────────────
if ($recurso === 'tutores') {
    if ($metodo === 'GET' && $accion === '') {
        autenticarOrFail();
        $ctrl = new CatalogoController();
        $r = $ctrl->listarTutores();
        jsonResponse($r['body'], $r['status']);
    }
}

if ($recurso === 'facultades') {
    if ($metodo === 'GET' && $accion === '') {
        autenticarOrFail();
        $ctrl = new CatalogoController();
        $r = $ctrl->listarFacultades();
        jsonResponse($r['body'], $r['status']);
    }
}

if ($recurso === 'carreras') {
    if ($metodo === 'GET' && $accion === '') {
        autenticarOrFail();
        $facultadId = isset($_GET['facultad_id']) ? (int) $_GET['facultad_id'] : null;
        $ctrl = new CatalogoController();
        $r = $ctrl->listarCarreras($facultadId);
        jsonResponse($r['body'], $r['status']);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// RUTAS PROTEGIDAS — requieren JWT válido
// ══════════════════════════════════════════════════════════════════════════════
$payload = autenticarOrFail();

// PROYECTOS — todos los roles pueden listar, solo Coordinador puede crear/editar/eliminar
if ($recurso === 'proyectos') {
    $ctrl = new ProyectoController();

    if ($metodo === 'GET' && $accion === '') {
        $r = $ctrl->listar();
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'POST' && $accion === '') {
        autorizarRolOrFail($payload, ['Coordinador'], 'Crear proyecto');
        $r = $ctrl->crear(getJsonBody());
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'PUT' && $id !== null) {
        autorizarRolOrFail($payload, ['Coordinador'], 'Editar proyecto');
        $r = $ctrl->actualizar($id, getJsonBody());
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'DELETE' && $id !== null) {
        autorizarRolOrFail($payload, ['Coordinador'], 'Eliminar proyecto');
        $r = $ctrl->eliminar($id);
        jsonResponse($r['body'], $r['status']);
    }

    jsonResponse(['error' => 'Endpoint de proyectos no encontrado'], 404);
}

// SOLICITUDES — Estudiante crea, Coordinador/Tutor gestionan
if ($recurso === 'solicitudes') {
    $ctrl = new SolicitudController();

    if ($metodo === 'GET' && $accion === '') {
        $r = $ctrl->listar();
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'POST' && $accion === '') {
        autorizarRolOrFail($payload, ['Estudiante'], 'Crear solicitud');
        $data = getJsonBody();
        $idProyecto = isset($data['id_proyecto']) ? (int) $data['id_proyecto'] : 0;
        $r = $ctrl->crear((int) $payload['sub'], $idProyecto);
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'PUT' && $id !== null) {
        autorizarRolOrFail($payload, ['Coordinador', 'Tutor'], 'Gestionar solicitud');
        $r = $ctrl->gestionar($id, $accion);
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'POST' && $accion === 'reset') {
        autorizarRolOrFail($payload, ['Coordinador'], 'Resetear solicitudes');
        $r = $ctrl->reset();
        jsonResponse($r['body'], $r['status']);
    }

    jsonResponse(['error' => 'Endpoint de solicitudes no encontrado'], 404);
}

// HORAS DE VINCULACIÓN
if ($recurso === 'horas') {
    $ctrl = new HoraController();

    if ($metodo === 'GET' && $accion === '') {
        $r = $ctrl->listar($payload);
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'GET' && $accion === 'resumen') {
        $userId = (int) $payload['sub'];
        $r = $ctrl->resumen($userId);
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'POST' && $accion === '') {
        autorizarRolOrFail($payload, ['Estudiante'], 'Registrar horas');
        $userId = (int) $payload['sub'];
        $r = $ctrl->registrar($userId, getJsonBody());
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'PUT' && $id !== null) {
        autorizarRolOrFail($payload, ['Tutor'], 'Gestionar horas');
        $userId = (int) $payload['sub'];
        $r = $ctrl->gestionar($id, $accion, $userId);
        jsonResponse($r['body'], $r['status']);
    }

    jsonResponse(['error' => 'Endpoint de horas no encontrado'], 404);
}

// ACTIVIDADES — Tutor crea, Estudiante lista
if ($recurso === 'actividades') {
    $ctrl = new ActividadController();

    if ($metodo === 'GET' && $accion === '') {
        $r = $ctrl->listar($payload);
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'POST' && $accion === '') {
        autorizarRolOrFail($payload, ['Tutor'], 'Crear actividad');
        $userId = (int) $payload['sub'];
        $r = $ctrl->crear($userId, getJsonBody());
        jsonResponse($r['body'], $r['status']);
    }

    if ($metodo === 'DELETE' && $id !== null) {
        autorizarRolOrFail($payload, ['Tutor'], 'Eliminar actividad');
        $userId = (int) $payload['sub'];
        $r = $ctrl->eliminar($id, $userId);
        jsonResponse($r['body'], $r['status']);
    }

    jsonResponse(['error' => 'Endpoint de actividades no encontrado'], 404);
}

// DASHBOARD — datos según el rol
if ($recurso === 'dashboard') {
    if ($metodo === 'GET' && $accion === '') {
        $ctrl = new DashboardController();
        $r = $ctrl->obtener($payload);
        jsonResponse($r['body'], $r['status']);
    }
    jsonResponse(['error' => 'Endpoint de dashboard no encontrado'], 404);
}

// ─── RECURSO NO ENCONTRADO ────────────────────────────────────────────────────
jsonResponse(['error' => 'Endpoint no encontrado', 'recurso' => $recurso, 'metodo' => $metodo], 404);
