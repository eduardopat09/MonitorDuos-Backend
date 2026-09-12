<?php

declare(strict_types=1);

namespace App;

enum Errors: string
{
    // Error del servidor
    case UNKNOWN = '{"Error":{"Type":2, "Category":1, "Description":"Unknown error! Look at the error log."}}';
    
    // Datos inválidos
    case INVALID_DATA = '{"Error":{"Type":400, "Category":2, "Description":"Invalid data provided in the request."}}';
    
    // Parámetros faltantes
    case MISSING_PARAMETERS = '{"Error":{"Type":400, "Category":2, "Description":"Required parameters are missing."}}';
    
    // Sesión inválida
    case UNAUTHORIZED = '{"Error":{"Type":401, "Category":3, "Description":"User is not authenticated or session is invalid."}}';
    
    // Registro inexistente
    case NOT_FOUND = '{"Error":{"Type":404, "Category":4, "Description":"The requested record does not exist."}}';
    
    // Error de Base de Datos
    case DATABASE_ERROR = '{"Error":{"Type":500, "Category":5, "Description":"A database error occurred."}}';
}