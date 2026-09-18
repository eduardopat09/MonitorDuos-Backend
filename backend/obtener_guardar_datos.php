<?php
// Permitir solicitudes desde el frontend 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8"); // esto le indica al navegador que recibirá datos y no un HTML

// Configuración para el contenedor Docker
$host = "db";
$db_name = "rendimiento_componentes"; 
$username = "root";
$password = "rootpassword";
try {
    // la libreria PDO permite el inicio de sesion en la base de datos, similar a mysqli
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password); 
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
    exit();
}

$python_path = "/usr/bin/python3"; 
$script_path = __DIR__ . "/obtencion_datos.py";

// Ejecutar el script de python y capturar la salida
// con escapeshellcmd() borra cualquier tipo de comando que se pueda ejecutar en el sistema operativo 
$comando = escapeshellcmd("$python_path $script_path");
$salida_json = shell_exec($comando); // ejecuta en la terminal todo print() del script de python como un cadena de texto

if (!$salida_json) {
    echo json_encode(["error" => "No se pudo ejecutar el script de Python"]);
    exit();
}

// decodificar el JSON generado
$datos = json_decode(trim($salida_json), true);

if (!$datos || !isset($datos['cpu'], $datos['ram'], $datos['disco'])) {
    echo json_encode(["error" => "Formato de datos no válido", "raw" => $salida_json]);
    exit();
}

try {
    // aqui se nicia transacción SQL para garantizar consistencia entre la tabla padre e hijas
    $conn->beginTransaction();

    // insertar registro en la tabla padre
    $stmtPadre = $conn->prepare("INSERT INTO monitoreo (fecha_registro) VALUES (NOW())");
    $stmtPadre->execute();
    
    // ID generado para la sesión actual
    $sesion_id = $conn->lastInsertId();

    // Insertar en tabla hija cpu
    $stmtCpu = $conn->prepare("INSERT INTO cpu (sesion_id, porcentaje_de_uso) VALUES (:sesion_id, :uso)");
    $stmtCpu->execute([':sesion_id' => $sesion_id, ':uso' => $datos['cpu']]);

    // insertar en la tabla hija ram
    $stmtRam = $conn->prepare("INSERT INTO ram (sesion_id, porcentaje_de_uso) VALUES (:sesion_id, :uso)");
    $stmtRam->execute([':sesion_id' => $sesion_id, ':uso' => $datos['ram']]);

    // insertar en la tabla hija disco
    $stmtDisco = $conn->prepare("INSERT INTO disco (sesion_id, porcentaje_de_uso) VALUES (:sesion_id, :uso)");
    $stmtDisco->execute([':sesion_id' => $sesion_id, ':uso' => $datos['disco']]);

    // confirmas los cambios
    $conn->commit();

    // responder al frontend con los datos insertados y la confirmación
    echo json_encode([
        "status" => "success",
        "sesion_id" => $sesion_id,
        "datos" => $datos
    ]);

} catch (Exception $e) {
    // por si algo falla, se revierten los cambios en la base con rollback(), se revierte lo que hizo beginTransaction()
    $conn->rollBack();
    echo json_encode(["error" => "Error al guardar en BD: " . $e->getMessage()]);
}
?>