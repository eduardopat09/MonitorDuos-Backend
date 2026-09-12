<?php

declare(strict_types=1);

namespace App\Sessions;

class Session
{
    // cracion de la sesion y la cookie
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            
            session_set_cookie_params([
                'lifetime' => 3600,
                'path' => '/',
                'domain' => '',
                'secure' => true,     
                'httponly' => true,   
                'samesite' => 'Strict'
            ]);
            
            session_start();
        }
    }

   // validacion de una sesion existente
    public static function isValid(): bool
    {
        self::start();
        return isset($_SESSION['user_id']);
    }

    // obtencion de la info de la sesion
    public static function get(string $key): mixed
    {
        self::start();
        return $_SESSION[$key] ?? null;
    }
// se establece info de la sesion
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    // cierre de la sesion
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}