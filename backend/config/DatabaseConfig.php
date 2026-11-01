<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Configuración centralizada de la base de datos y variables de entorno.
 */
class DatabaseConfig
{
    /**
     * Host del servidor de base de datos.
     */
    public static function getHost(): string
    {
        return (string) (getenv('DB_HOST') ?: 'localhost');
    }

    /**
     * Nombre de la base de datos.
     */
    public static function getDbName(): string
    {
        return (string) (getenv('DB_NAME') ?: 'rendimiento_componentes');
    }

    /**
     * Usuario de la base de datos.
     */
    public static function getUser(): string
    {
        return (string) (getenv('DB_USER') ?: 'root');
    }

    /**
     * Contraseña de la base de datos.
     */
    public static function getPassword(): string
    {
        return (string) (getenv('DB_PASS') ?: 'sasageyo');
    }

    /**
     * Puerto del servicio de base de datos.
     */
    public static function getPort(): int
    {
        $port = getenv('DB_PORT');
        return $port !== false ? (int) $port : 3306;
    }

    /**
     * Conjunto de caracteres de la conexión.
     */
    public static function getCharset(): string
    {
        return 'utf8mb4';
    }
}
