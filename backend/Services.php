<?php

declare(strict_types=1);

/**
 * Enrutador Frontal (Dispatcher) del Backend - MoniDuo.
 * 
 * Cumple con PSR-1, PSR-4 y PSR-12.
 * Despacha peticiones hacia manejadores independientes y delega responsabilidades a:
 * - App\Database\Connection (Persistencia PDO)
 * - App\Sessions\Session (Control de sesiones y tokens)
 * - App\Utils\Errors (Manejo centralizado de respuestas de error)
 * - App\Utils\Tools (CORS, respuestas JSON y puente tecnológico con Python)
 * - App\Utils\ValueValidation (Validación y saneamiento de entradas)
 */

// Registro de Autoloader PSR-4 para el namespace App\
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $subPath = !empty($parts) ? strtolower(implode(DIRECTORY_SEPARATOR, $parts)) . DIRECTORY_SEPARATOR : '';

    $filePrimary = $baseDir . $subPath . $className . '.php';
    $fileDirect = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($filePrimary)) {
        require_once $filePrimary;
    } elseif (file_exists($fileDirect)) {
        require_once $fileDirect;
    }
});

// Alias de compatibilidad hacia atrás
if (!class_exists('App\Connection', false)) {
    class_alias(\App\Database\Connection::class, 'App\Connection');
}
if (!class_exists('App\Tools', false)) {
    class_alias(\App\Utils\Tools::class, 'App\Tools');
}
if (!class_exists('App\Errors', false)) {
    class_alias(\App\Utils\Errors::class, 'App\Errors');
}
if (!class_exists('App\ValueValidation', false)) {
    class_alias(\App\Utils\ValueValidation::class, 'App\ValueValidation');
}

use App\Database\Connection;
use App\Sessions\Session;
use App\Utils\Errors;
use App\Utils\Tools;
use App\Utils\ValueValidation;

// Aplicar cabeceras CORS y atender peticiones OPTIONS preflight de inmediato
Tools::applyCorsHeaders();

// Obtener cuerpo JSON si fue enviado
$rawInput = file_get_contents('php://input');
$body = [];
if ($rawInput !== false && trim($rawInput) !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

// Consolidar parámetros de la petición (Query URL, POST y JSON Body)
$params = array_merge($_GET, $_POST, $body);
$action = (string) ($params['action'] ?? '');

/**
 * Manejador: Inicio de sesión.
 */
function handleLogin(array $params): void
{
    try {
        $usuarioRaw = $params['usuario'] ?? $params['username'] ?? '';
        $userClean = ValueValidation::validateRequired($usuarioRaw, 'usuario');

        Session::start();
        Session::set('user_id', 1);
        Session::set('usuario', $userClean);
        $token = Session::generateToken(16);
        Session::set('token', $token);

        Tools::jsonResponse([
            'status' => 'success',
            'mensaje' => "Sesion iniciada para $userClean",
            'usuario' => $userClean,
            'token' => $token,
        ], 200);
    } catch (InvalidArgumentException $e) {
        Errors::badRequest($e->getMessage());
    }
}

/**
 * Manejador: Verificación de sesión activa del perfil.
 */
function handleProfile(): void
{
    if (!Session::isValid()) {
        Errors::unauthorized('Sesion no iniciada o invalida');
    }

    Tools::jsonResponse([
        'status' => 'success',
        'usuario' => Session::get('usuario'),
    ], 200);
}

/**
 * Manejador: Cierre de sesión.
 */
function handleLogout(): void
{
    Session::destroy();
    Tools::jsonResponse([
        'status' => 'success',
        'mensaje' => 'Sesion cerrada exitosamente',
    ], 200);
}

/**
 * Manejador: Captura y registro de métricas de hardware en tiempo real.
 */
function handleLiveMetrics(): void
{
    // Delegar extracción de hardware al puente tecnológico con Python (con fallback)
    $metrics = Tools::getPerformanceMetrics();

    $pdo = Connection::getInstance();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO monitoreo () VALUES ()');
        $stmt->execute();
        $sesionId = (int) $pdo->lastInsertId();

        $stmtCpu = $pdo->prepare('INSERT INTO cpu (sesion_id, porcentaje_de_uso) VALUES (:id, :uso)');
        $stmtCpu->execute(['id' => $sesionId, 'uso' => $metrics['cpu_uso']]);

        $stmtRam = $pdo->prepare('INSERT INTO ram (sesion_id, porcentaje_de_uso) VALUES (:id, :uso)');
        $stmtRam->execute(['id' => $sesionId, 'uso' => $metrics['ram_uso']]);

        $stmtDisco = $pdo->prepare('INSERT INTO disco (sesion_id, porcentaje_de_uso) VALUES (:id, :uso)');
        $stmtDisco->execute(['id' => $sesionId, 'uso' => $metrics['disco_uso']]);

        $pdo->commit();

        Tools::jsonResponse([
            'status' => 'success',
            'sesion_id' => $sesionId,
            'equipo' => Tools::getHostname(),
            'timestamp' => date('Y-m-d H:i:s'),
            'datos' => [
                'cpu_uso' => (float) $metrics['cpu_uso'],
                'ram_uso' => (float) $metrics['ram_uso'],
                'disco_uso' => (float) $metrics['disco_uso'],
            ],
            'fallback' => $metrics['fallback'] ?? false,
        ], 200);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        Errors::databaseError('Error al guardar lecturas en base de datos.', $e->getMessage());
    }
}

/**
 * Manejador: Consulta del histórico de métricas por rango de fechas.
 */
function handleHistory(array $params): void
{
    try {
        $fechaInicio = $params['inicio'] ?? null;
        $fechaFin = $params['fin'] ?? null;

        ValueValidation::validateRequired($fechaInicio, 'inicio');
        ValueValidation::validateRequired($fechaFin, 'fin');

        $inicioValido = ValueValidation::validateDate((string) $fechaInicio, false);
        $finValido = ValueValidation::validateDate((string) $fechaFin, true);

        if (strtotime($inicioValido) > strtotime($finValido)) {
            Errors::badRequest('La fecha de inicio no puede ser mayor que la fecha final.');
        }

        $pdo = Connection::getInstance();
        $query = '
            SELECT 
                m.id AS sesion_id,
                m.fecha_registro,
                c.porcentaje_de_uso AS cpu_uso,
                r.porcentaje_de_uso AS ram_uso,
                d.porcentaje_de_uso AS disco_uso
            FROM monitoreo m
            INNER JOIN cpu c ON m.id = c.sesion_id
            INNER JOIN ram r ON m.id = r.sesion_id
            INNER JOIN disco d ON m.id = d.sesion_id
            WHERE m.fecha_registro BETWEEN :inicio AND :fin
            ORDER BY m.fecha_registro ASC
        ';

        $stmt = $pdo->prepare($query);
        $stmt->execute(['inicio' => $inicioValido, 'fin' => $finValido]);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Tools::jsonResponse([
            'status' => 'success',
            'equipo' => Tools::getHostname(),
            'total_registros' => count($datos),
            'datos' => $datos,
        ], 200);
    } catch (InvalidArgumentException $e) {
        Errors::badRequest($e->getMessage());
    } catch (Throwable $e) {
        Errors::databaseError('Error al consultar historial de métricas.', $e->getMessage());
    }
}

// Despacho de la acción solicitada (Front Controller)
switch ($action) {
    case 'live':
        handleLiveMetrics();
        break;

    case 'history':
        handleHistory($params);
        break;

    case 'login':
        handleLogin($params);
        break;

    case 'profile':
        handleProfile();
        break;

    case 'logout':
        handleLogout();
        break;

    default:
        Errors::notFound('Endpoint no valido.');
}
