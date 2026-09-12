<?php

declare(strict_types=1);

namespace App;

class ValueValidation
{
    // validacion de enteros
    public static function validateId(mixed $val, bool $allowMinusOne = false): int
    {
        if (!is_numeric($val)) {
            throw new \InvalidArgumentException("El valor debe ser numérico.");
        }

        $valInt = (int) $val;

        if ($valInt <= 0 && (!$allowMinusOne || $valInt !== -1)) {
            throw new \InvalidArgumentException("El valor debe ser mayor a 0.");
        }

        return $valInt;
    }

    //longitud maxima y minima de las cadenas de texto
    public static function validateString(mixed $val, int $min = 1, int $max = 255): string
    {
        if (!is_string($val)) {
            throw new \InvalidArgumentException("El valor debe ser una cadena de texto.");
        }

        $length = strlen(trim($val));
        if ($length < $min || $length > $max) {
            throw new \InvalidArgumentException("La longitud debe estar entre $min y $max caracteres.");
        }

        return trim($val);
    }

    // validacion de correos electronicos
    public static function validateEmail(mixed $val): string
    {
        if (!is_string($val) || !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("El formato del correo es inválido.");
        }

        return $val;
    }

    //formatos de fechas correctos
    public static function validateDate(mixed $val, string $format = 'Y-m-d'): string
    {
        if (!is_string($val)) {
            throw new \InvalidArgumentException("La fecha debe ser una cadena de texto.");
        }

        $date = \DateTime::createFromFormat($format, $val);
        if (!$date || $date->format($format) !== $val) {
            throw new \InvalidArgumentException("El formato de fecha es inválido. Se espera: $format.");
        }

        return $val;
    }
}