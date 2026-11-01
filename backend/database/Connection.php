<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\DatabaseConfig;
use App\Utils\Errors;
use PDO;
use PDOException;

/**
 * Gestor de conexión PDO mediante el patrón de diseño Singleton.
 */
class Connection
{
    private static ?PDO $instance = null;

    /**
     * Constructor privado para prevenir instanciación directa (Singleton).
     */
    private function __construct()
    {
    }

    /**
     * Prevenir clonación del objeto.
     */
    private function __clone()
    {
    }

    /**
     * Prevenir deserialización del objeto.
     */
    public function __wakeup(): void
    {
    }

    /**
     * Obtiene la instancia única de conexión PDO.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    DatabaseConfig::getHost(),
                    DatabaseConfig::getPort(),
                    DatabaseConfig::getDbName(),
                    DatabaseConfig::getCharset()
                );

                self::$instance = new PDO(
                    $dsn,
                    DatabaseConfig::getUser(),
                    DatabaseConfig::getPassword(),
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                Errors::databaseError(
                    'Error de conexión a la base de datos MySQL.',
                    $e->getMessage()
                );
            }
        }

        return self::$instance;
    }
}

