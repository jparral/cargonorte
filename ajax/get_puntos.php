<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$fecha = $_GET['fecha'] ?? date('Y-m-d');

try {
    $pdo = getDBConnection();
    // Traemos entregas + datos de sucursal + cliente
    $sql = "SELECT e.id_entrega, e.bultos, e.comprobante, e.estado_entrega,
                   s.latitud, s.longitud, s.direccion_formateada, s.dias_horarios,
                   c.nombre_cliente,
                   f.nombre as nombre_fletero
            FROM entregas e
            JOIN sucursales s ON e.id_sucursal = s.id_sucursal
            JOIN clientes c ON e.id_cliente = c.id_cliente
            LEFT JOIN fleteros f ON e.id_fletero_asignado = f.id_fletero
            JOIN partes p ON e.id_parte = p.id_parte
            WHERE p.fecha_envio = ? 
            AND s.estado_direccion = 'verificada' -- Solo mostramos lo que tiene coordenadas
            AND s.latitud IS NOT NULL";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha]);
    $puntos = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'data' => $puntos]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}