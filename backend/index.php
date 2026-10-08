<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $subPath = !empty($parts) ? strtolower(implode('/', $parts)) . '/' : '';

    $filePrimary = $baseDir . $subPath . $className . '.php';
    $fileDirect = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($filePrimary)) {
        require_once $filePrimary;
    } elseif (file_exists($fileDirect)) {
        require_once $fileDirect;
    }
});

use App\Services;
use App\Tools;

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'live':
        Services::obtenerYGuardarDatos();
        break;

    case 'history':
        Services::historialFechas($_GET['inicio'] ?? null, $_GET['fin'] ?? null);
        break;

    case 'login':
        Services::login($_GET['usuario'] ?? '');
        break;

    case 'profile':
        Services::checkSession();
        break;

    case 'logout':
        Services::logout();
        break;

    default:
        Tools::jsonResponse(['error' => 'Endpoint no valido.'], 404);
}