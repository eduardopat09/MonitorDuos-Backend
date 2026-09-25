<?php

declare(strict_types=1);

namespace App\Config;

class DatabaseConfig
{
    public static function getHost(): string
    {
        return getenv('DB_HOST') ?: 'IP_REMOTA';
    }

    public static function getDbName(): string
    {
        return getenv('DB_NAME') ?: 'rendimiento_componentes';
    }

    public static function getUser(): string
    {
        return getenv('DB_USER') ?: 'usuario';
    }

    public static function getPassword(): string
    {
        return getenv('DB_PASS') ?: 'password';
    }
}