<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$idFletero = $input['id_fletero'] ?? null;
$idsEntregas = $input['ids_entregas'] ?? []; // Array de IDs

if (!$idFletero || empty($idsEntregas)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Crear string de placeholders para el IN (?,?,?)
    $placeholders = implode(',', array_fill(0, count($idsEntregas), '?'));
    
    $sql = "UPDATE entregas SET 
            id_fletero_asignado = ?, 
            estado_entrega = 'asignado' 
            WHERE id_entrega IN ($placeholders)";
            
    $params = array_merge([$idFletero], $idsEntregas);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Ruta guardada correctamente']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}