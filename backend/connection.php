<?php

declare(strict_types=1);

namespace App;

use App\Config\DatabaseConfig;
use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=utf8mb4",
                    DatabaseConfig::getHost(),
                    DatabaseConfig::getDbName()
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
                // enumerrors de errores.php
                http_response_code(500);
                echo Errors::DATABASE_ERROR->value;
                exit;
            }
        }

        return self::$instance;
    }
}