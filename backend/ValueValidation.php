<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

class ValueValidation
{
    public static function validateId(mixed $val, bool $allowMinusOne = false): int
    {
        if (!is_numeric($val)) {
            throw new InvalidArgumentException("El valor debe ser numérico.");
        }

        $valInt = (int) $val;

        if ($valInt <= 0 && (!$allowMinusOne || $valInt !== -1)) {
            throw new InvalidArgumentException("El valor debe ser mayor a 0.");
        }

        return $valInt;
    }

    public static function validateRequired(mixed $val, string $paramName): string
    {
        if ($val === null || trim((string)$val) === '') {
            throw new InvalidArgumentException("El parámetro '$paramName' es obligatorio y no puede estar vacío.");
        }

        return trim((string)$val);
    }

    public static function validateDate(mixed $val): string
    {
        if (!is_string($val) || trim($val) === '') {
            throw new InvalidArgumentException("La fecha no puede estar vacía.");
        }

        $val = trim($val);
        // Normalizar si solo envían la fecha (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            $val .= ' 00:00:00';
        }

        $format = 'Y-m-d H:i:s';
        $d = \DateTime::createFromFormat($format, $val);
        if (!$d || $d->format($format) !== $val) {
            throw new InvalidArgumentException("Formato de fecha inválido. Se espera YYYY-MM-DD o YYYY-MM-DD HH:MM:SS.");
        }

        return $val;
    }
}