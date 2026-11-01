<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Manejo centralizado de errores y respuestas HTTP uniformes en formato JSON.
 */
class Errors
{
    /**
     * Emite una respuesta de error 400 Bad Request.
     */
    public static function badRequest(string $message = 'Datos inválidos o parámetros faltantes.', ?array $details = null): void
    {
        $response = [
            'status' => 'error',
            'codigo' => 400,
            'error' => $message,
        ];

        if ($details !== null) {
            $response['detalles'] = $details;
        }

        Tools::jsonResponse($response, 400);
    }

    /**
     * Emite una respuesta de error 401 Unauthorized.
     */
    public static function unauthorized(string $message = 'Sesión no iniciada o inválida.'): void
    {
        Tools::jsonResponse([
            'status' => 'error',
            'codigo' => 401,
            'error' => $message,
        ], 401);
    }

    /**
     * Emite una respuesta de error 403 Forbidden.
     */
    public static function forbidden(string $message = 'Acceso denegado.'): void
    {
        Tools::jsonResponse([
            'status' => 'error',
            'codigo' => 403,
            'error' => $message,
        ], 403);
    }

    /**
     * Emite una respuesta de error 404 Not Found.
     */
    public static function notFound(string $message = 'Endpoint o recurso no encontrado.'): void
    {
        Tools::jsonResponse([
            'status' => 'error',
            'codigo' => 404,
            'error' => $message,
            'acciones_disponibles' => ['live', 'history', 'login', 'profile', 'logout'],
        ], 404);
    }

    /**
     * Emite una respuesta de error 500 Database Error.
     */
    public static function databaseError(string $message = 'Error en la base de datos.', ?string $detail = null): void
    {
        $response = [
            'status' => 'error',
            'codigo' => 500,
            'error' => $message,
        ];

        if ($detail !== null) {
            $response['detalle'] = $detail;
        }

        Tools::jsonResponse($response, 500);
    }

    /**
     * Emite una respuesta de error 500 Internal Server Error.
     */
    public static function serverError(string $message = 'Error interno del servidor.', ?string $detail = null): void
    {
        $response = [
            'status' => 'error',
            'codigo' => 500,
            'error' => $message,
        ];

        if ($detail !== null) {
            $response['detalle'] = $detail;
        }

        Tools::jsonResponse($response, 500);
    }
}

