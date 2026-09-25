<?php

declare(strict_types=1);

namespace App;

class Tools
{
    /**
     * da una respuesta JSON estandarizada y termina la ejecución.
     */
    public static function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}