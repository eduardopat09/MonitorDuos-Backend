<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de preflight request de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "db";
$db_name = "rendimiento_componentes";
$username = "root";
$password = "rootpassword";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Validar parámetros requeridos
    if (!isset($_GET['fecha_inicio']) || !isset($_GET['fecha_fin']) || 
        trim($_GET['fecha_inicio']) === '' || trim($_GET['fecha_fin']) === '') {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "mensaje" => "Debe proporcionar los parametros 'fecha_inicio' y 'fecha_fin'."
        ]);
        exit();
    }

    $fecha_inicio_raw = trim($_GET['fecha_inicio']);
    $fecha_fin_raw = trim($_GET['fecha_fin']);

    // Si solo viene fecha (YYYY-MM-DD), agregar hora inicio y fin del día
    $fecha_inicio = strlen($fecha_inicio_raw) === 10 ? "$fecha_inicio_raw 00:00:00" : $fecha_inicio_raw;
    $fecha_fin = strlen($fecha_fin_raw) === 10 ? "$fecha_fin_raw 23:59:59" : $fecha_fin_raw;

    // 2. Validar formato y rango
    if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "mensaje" => "Formato de fecha invalido."
        ]);
        exit();
    }

    if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "mensaje" => "La fecha de inicio no puede ser mayor que la fecha de fin."
        ]);
        exit();
    }

    // 3. Consulta SQL con JOIN a tus tablas reales
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
        WHERE m.fecha_registro BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY m.fecha_registro ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear tipos numéricos para Chart.js
    $datos = array_map(function($fila) {
        return [
            "sesion_id" => (int)$fila['sesion_id'],
            "fecha_registro" => $fila['fecha_registro'],
            "cpu_uso" => (float)$fila['cpu_uso'],
            "ram_uso" => (float)$fila['ram_uso'],
            "disco_uso" => (float)$fila['disco_uso']
        ];
    }, $registros);

    echo json_encode([
        "status" => "success",
        "total_registros" => count($datos),
        "datos" => $datos
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "mensaje" => "Error en la consulta: " . $e->getMessage()
    ]);
}