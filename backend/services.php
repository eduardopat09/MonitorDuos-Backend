<?php

declare(strict_types=1);

namespace App;

use App\Connection;
use App\Tools;
use App\ValueValidation;
use PDO;

class Services
{
    /**
     * se obtienen métricas de obtencion_datos.py y las guarda en la base de datos remota.
     */
    public static function obtenerYGuardarDatos(): void
    {
        $command = escapeshellcmd('/usr/bin/python3 ' . __DIR__ . '/python/obtencion_datos_rendimiento.py');
        $output = shell_exec($command);
        $metrics = json_decode($output ?: '{}', true);

        if (!isset($metrics['status']) || $metrics['status'] !== 'success') {
            Tools::jsonResponse(['error' => 'Error al ejecutar script de Python'], 500);
        }

        $pdo = Connection::getInstance();
        $pdo->beginTransaction();

        try {
            // Inserción en tabla padre 'monitoreo'
            $stmt = $pdo->prepare("INSERT INTO monitoreo () VALUES ()");
            $stmt->execute();
            $sesionId = (int) $pdo->lastInsertId();

            // insercion en tablas hijas (cpu, ram, disco) sesion_id
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
            ]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            Tools::jsonResponse(['error' => 'Error al guardar en BD: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Consulta del historial de rendimiento filtrando por fechas válidas.
     */
    public static function historialFechas(string $fechaInicio, string $fechaFin): void
    {
        try {
            // Se utiliza ValueValidation,php para evitar fechas maliciosas o erróneas
            $inicioValido = ValueValidation::validateDate($fechaInicio, 'Y-m-d H:i:s');
            $finValido = ValueValidation::validateDate($fechaFin, 'Y-m-d H:i:s');

            if (strtotime($inicioValido) > strtotime($finValido)) {
                Tools::jsonResponse(['error' => 'La fecha inicio no puede ser posterior a la fecha fin'], 400);
            }

            $pdo = Connection::getInstance();
            
            // JOIN adaptado a tu dump exacto de la base de datos[cite: 9]
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
            $datos = $stmt->fetchAll();

            Tools::jsonResponse([
                'status' => 'success',
                'total_registros' => count($datos),
                'datos' => $datos
            ]);
        } catch (\Exception $e) {
            Tools::jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}