<?php

declare(strict_types=1);

namespace App\Utils;

use DateTime;
use InvalidArgumentException;

/**
 * Validaciones y saneamiento reutilizable de entradas.
 * Aplica el principio DRY y filtrado estricto de seguridad.
 */
class ValueValidation
{
    /**
     * Valida que un valor sea un ID numérico válido.
     */
    public static function validateId(mixed $val, bool $allowMinusOne = false): int
    {
        if (!is_numeric($val)) {
            throw new InvalidArgumentException('El identificador debe ser numérico.');
        }

        $valInt = (int) $val;

        if ($valInt <= 0 && (!$allowMinusOne || $valInt !== -1)) {
            throw new InvalidArgumentException('El identificador debe ser mayor a 0.');
        }

        return $valInt;
    }

    /**
     * Valida enteros positivos para límites, paginaciones o contadores.
     */
    public static function validatePositiveInt(mixed $val, string $paramName): int
    {
        if (!is_numeric($val) || (int) $val <= 0) {
            throw new InvalidArgumentException("El parámetro '$paramName' debe ser un entero positivo.");
        }

        return (int) $val;
    }

    /**
     * Valida que un campo obligatorio contenga texto no vacío.
     */
    public static function validateRequired(mixed $val, string $paramName): string
    {
        if ($val === null || trim((string) $val) === '') {
            throw new InvalidArgumentException("El parámetro '$paramName' es obligatorio y no puede estar vacío.");
        }

        return trim((string) $val);
    }

    /**
     * Valida cadenas de texto acotadas por longitud mínima y máxima.
     */
    public static function validateString(
        mixed $val,
        string $paramName,
        int $minLength = 1,
        int $maxLength = 255
    ): string {
        $clean = self::validateRequired($val, $paramName);
        $len = mb_strlen($clean);

        if ($len < $minLength || $len > $maxLength) {
            throw new InvalidArgumentException(
                "El parámetro '$paramName' debe tener entre $minLength y $maxLength caracteres."
            );
        }

        return $clean;
    }

    /**
     * Valida formato de correo electrónico mediante filter_var.
     */
    public static function validateEmail(mixed $val, string $paramName = 'correo'): string
    {
        $clean = self::validateRequired($val, $paramName);

        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("El parámetro '$paramName' no tiene un formato de correo válido.");
        }

        return $clean;
    }

    /**
     * Valida que un valor pertenezca a una lista blanca de valores permitidos.
     */
    public static function validateAllowed(mixed $val, array $allowedValues, string $paramName): mixed
    {
        if (!in_array($val, $allowedValues, true)) {
            $allowedList = implode(', ', array_map(static fn($v) => (string) $v, $allowedValues));
            throw new InvalidArgumentException(
                "Valor inválido para '$paramName'. Valores permitidos: [$allowedList]."
            );
        }

        return $val;
    }

    /**
     * Valida y normaliza cadenas de fecha (YYYY-MM-DD o YYYY-MM-DD HH:MM:SS).
     */
    public static function validateDate(mixed $val, bool $isEndOfDay = false): string
    {
        if (!is_string($val) || trim($val) === '') {
            throw new InvalidArgumentException('La fecha no puede estar vacía.');
        }

        $val = trim($val);

        // Si solo se proporciona el día (YYYY-MM-DD), normalizar al inicio o fin del día
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            $val .= $isEndOfDay ? ' 23:59:59' : ' 00:00:00';
        }

        $format = 'Y-m-d H:i:s';
        $d = DateTime::createFromFormat($format, $val);

        if (!$d || $d->format($format) !== $val) {
            throw new InvalidArgumentException(
                'Formato de fecha inválido. Se espera YYYY-MM-DD o YYYY-MM-DD HH:MM:SS.'
            );
        }

        return $val;
    }
}

