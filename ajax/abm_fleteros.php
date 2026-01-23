<?php
// ACTIVAR REPORTE DE ERRORES (Solo para entorno de desarrollo/XAMPP)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar buffer para atrapar cualquier texto indeseado (warnings, espacios)
ob_start();

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$response = [];

try {
    // Verificar conexión
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        // Validar datos mínimos
        if (empty($_POST['nombre'])) {
            throw new Exception("El campo Nombre es obligatorio.");
        }

        $nombre = trim($_POST['nombre']);
        $patente = trim($_POST['patente'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $id = $_POST['id_fletero'] ?? '';

        if (!empty($id)) {
            // EDITAR
            $sql = "UPDATE fleteros SET nombre=?, patente_vehiculo=?, telefono=? WHERE id_fletero=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $patente, $telefono, $id]);
        } else {
            // CREAR NUEVO
            $sql = "INSERT INTO fleteros (nombre, patente_vehiculo, telefono) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $patente, $telefono]);
        }
        $response = ['status' => 'success', 'message' => 'Guardado correctamente'];

    } elseif ($accion === 'eliminar') {
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception("Falta el ID para eliminar.");

        $stmt = $pdo->prepare("UPDATE fleteros SET activo = 0 WHERE id_fletero = ?");
        $stmt->execute([$id]);
        $response = ['status' => 'success', 'message' => 'Eliminado correctamente'];

    } else {
        throw new Exception("Acción no válida: " . htmlspecialchars($accion));
    }

} catch (PDOException $e) {
    // Error de Base de Datos Específico
    $response = ['status' => 'error', 'message' => 'Error SQL: ' . $e->getMessage()];
} catch (Exception $e) {
    // Error Genérico
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// LIMPIEZA FINAL: Borramos cualquier texto previo (warnings de PHP) y mandamos solo JSON
ob_end_clean(); 
echo json_encode($response);
exit;
?>