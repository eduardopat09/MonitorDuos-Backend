<?php

declare(strict_types=1);

namespace App;

class Tools
{
    /**
     * Aplica los encabezados CORS necesarios para peticiones cross-origin con credenciales.
     */
    public static function applyCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (!empty($origin)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        } else {
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
        header('Access-Control-Max-Age: 86400');

        // Responder de inmediato a las peticiones preflight (OPTIONS)
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Da una respuesta JSON estandarizada y termina la ejecución.
     */
    public static function jsonResponse(array $data, int $statusCode = 200): void
    {
        self::applyCorsHeaders();
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
