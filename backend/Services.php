<?php

declare(strict_types=1);

namespace App;

use App\Connection;
use App\Tools;
use App\ValueValidation;
use App\Sessions\Session;
use InvalidArgumentException;
use PDO;

class Services
{
    public static function login(string $usuario): void
    {
        try {
            $userClean = ValueValidation::validateRequired($usuario, 'usuario');
            Session::start();
            Session::set('user_id', 1);
            Session::set('usuario', $userClean);

            Tools::jsonResponse([
                'status' => 'success',
                'mensaje' => "Sesion iniciada para $userClean"
            ], 200);
        } catch (InvalidArgumentException $e) {
            Tools::jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public static function checkSession(): void
    {
        if (!Session::isValid()) {
            http_response_code(401);
            echo Errors::UNAUTHORIZED->value;
            exit;
        }

        Tools::jsonResponse([
            'status' => 'success',
            'usuario' => Session::get('usuario')
        ], 200);
    }

    public static function logout(): void
    {
        Session::destroy();
        Tools::jsonResponse([
            'status' => 'success',
            'mensaje' => 'Sesion cerrada exitosamente'
        ], 200);
    }

    public static function obtenerYGuardarDatos(): void
    {
        $command = escapeshellcmd('python3 ' . __DIR__ . '/python/obtencion_datos_rendimiento.py') . ' 2>&1';
        $output = shell_exec($command);
        $metrics = json_decode($output ?: '{}', true);

        if (!isset($metrics['status']) || $metrics['status'] !== 'success') {
            Tools::jsonResponse(['error' => 'Error al ejecutar script de Python'], 500);
        }

        $pdo = Connection::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO monitoreo () VALUES ()");
            $stmt->execute();
            $sesionId = (int) $pdo->lastInsertId();

            $stmtCpu = $pdo->prepare("INSERT INTO cpu (sesion_id, porcentaje_de_uso) VALUES (:id, :uso)");
            $stmtCpu->execute(['id' => $sesionId, 'uso' => $metrics['cpu_uso']]);

            $stmtRam = $pdo->prepare("INSERT INTO ram (sesion_id, porcentaje_de_uso) VALUES (:id, :uso)");
            $stmtRam->execute(['id' => $sesionId, 'uso' => $metrics['ram_uso']]);

            $stmtDisco = $pdo->prepare("INSERT INTO disco (sesion_id, porcentaje_de_uso) VALUES (:id, :uso)");
            $stmtDisco->execute(['id' => $sesionId, 'uso' => $metrics['disco_uso']]);

            $pdo->commit();

            Tools::jsonResponse([
                'status' => 'success',
                'sesion_id' => $sesionId,
                'datos' => [
                    'cpu_uso' => $metrics['cpu_uso'],
                    'ram_uso' => $metrics['ram_uso'],
                    'disco_uso' => $metrics['disco_uso']
                ]
            ], 200);
        } catch (\Exception $e) {
            $pdo->rollBack();
            Tools::jsonResponse(['error' => 'Error al guardar en BD: ' . $e->getMessage()], 500);
        }
    }

    public static function historialFechas(?string $fechaInicio, ?string $fechaFin): void
    {
        try {
            ValueValidation::validateRequired($fechaInicio, 'inicio');
            ValueValidation::validateRequired($fechaFin, 'fin');

            $inicioValido = ValueValidation::validateDate($fechaInicio);
            $finValido = ValueValidation::validateDate($fechaFin);

            if (strtotime($inicioValido) > strtotime($finValido)) {
                Tools::jsonResponse(['error' => 'La fecha de inicio no puede ser mayor que la fecha final.'], 400);
            }

            $pdo = Connection::getInstance();
            $query = "
                SELECT 
                    m.id AS sesion_id,
                    m.fecha_registro,
                    c.porcentaje_de_uso AS cpu_uso,
                    r.porcentaje_de_uso AS ram_uso,
                    d.porcentaje_de_uso AS disco_uso
                FROM monitoreo m
                INNER JOIN cpu c ON m.id = c.sesion_id
                INNER JOIN ram r ON m.id = r.sesion_id
                INNER JOIN disco d ON m.id = d.sesion_id
                WHERE m.fecha_registro BETWEEN :inicio AND :fin
                ORDER BY m.fecha_registro ASC
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute(['inicio' => $inicioValido, 'fin' => $finValido]);
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Tools::jsonResponse([
                'status' => 'success',
                'total_registros' => count($datos),
                'datos' => $datos
            ], 200);
        } catch (InvalidArgumentException $e) {
            Tools::jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}