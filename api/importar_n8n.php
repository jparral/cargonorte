<?php
// api/importar_n8n.php

// Mostrar errores para depuración (Desactivar en producción real)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/LogisticaHelper.php';

try {
    // 1. Obtener conexión y Helper
    $pdo = getDBConnection();
    $helper = new LogisticaHelper($pdo);

    // 2. Leer JSON crudo
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    if (!$data) {
        throw new Exception("No se recibió JSON válido o el cuerpo está vacío.");
    }

    // Normalizar a array si llega un solo objeto
    if (isset($data['parte'])) {
        $data = [$data];
    }

    $pdo->beginTransaction();
    $contador = 0;

    foreach ($data as $item) {
        // --- A. DATOS GENERALES ---
        // Fletero PDF
        $idFleteroPdf = $item['fletero']['id_fletero'] ?? null;
        $nombreFleteroPdf = $item['fletero']['nombre_fletero'] ?? 'DESCONOCIDO';
        
        // Zona PDF
        $idZonaPdf = $item['zona']['id_zona'] ?? null;
        $nombreZonaPdf = $item['zona']['nombre_zona'] ?? 'SIN ZONA';

        // --- B. PROCESAR MAESTROS (Fletero/Zona) ---
        // Insertamos Fletero si no existe (por código externo)
        // Nota: No actualizamos el nombre para no pisar correcciones manuales
        $stmtFletero = $pdo->prepare("INSERT IGNORE INTO fleteros (codigo_externo, nombre) VALUES (?, ?)");
        $stmtFletero->execute([$idFleteroPdf, $nombreFleteroPdf]);

        // Insertamos Zona si no existe
        $stmtZona = $pdo->prepare("INSERT IGNORE INTO zonas (codigo_externo, nombre_zona) VALUES (?, ?)");
        $stmtZona->execute([$idZonaPdf, $nombreZonaPdf]);

        // --- C. PROCESAR PARTE ---
        $idParte = $item['parte']['id_parte'];
        // Convertir fecha dd/mm/yyyy a yyyy-mm-dd
        $fechaRaw = $item['parte']['fecha_envio'];
        $fechaObj = DateTime::createFromFormat('d/m/Y', $fechaRaw);
        $fechaEnvio = $fechaObj ? $fechaObj->format('Y-m-d') : date('Y-m-d');

        $stmtParte = $pdo->prepare("INSERT IGNORE INTO partes (id_parte, fecha_envio) VALUES (?, ?)");
        $stmtParte->execute([$idParte, $fechaEnvio]);

        // --- D. PROCESAR CLIENTE ---
        $idCliente = $item['cliente']['id_cliente'];
        $nombreCliente = $item['cliente']['nombre_cliente'];
        
        $stmtCliente = $pdo->prepare("INSERT INTO clientes (id_cliente, nombre_cliente) VALUES (?, ?) ON DUPLICATE KEY UPDATE nombre_cliente = VALUES(nombre_cliente)");
        $stmtCliente->execute([$idCliente, $nombreCliente]);

        // --- E. PROCESAR SUCURSAL (Lógica Inteligente) ---
        $sucRaw = $item['sucursal'];
        $idSucursal = $helper->procesarSucursal(
            $idCliente,
            $sucRaw['calle'],
            $sucRaw['puerta'],
            $sucRaw['localidad'],
            $sucRaw['dias_horarios']
        );

        // --- F. PROCESAR ENTREGA ---
        $metrics = $item['metricas'];
        $meta = $item['meta'];
        $comprobante = $item['entrega']['comprobante'];

        $sqlEntrega = "INSERT INTO entregas 
            (id_parte, comprobante, id_cliente, id_sucursal, 
             fletero_pdf_nombre, zona_pdf_nombre,
             bultos, vacunas, bolsas, cajas, otros, pdf_origen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
             bultos = VALUES(bultos), vacunas = VALUES(vacunas)"; // Actualizar métricas si se re-importa
        
        $stmtEntrega = $pdo->prepare($sqlEntrega);
        $stmtEntrega->execute([
            $idParte,
            $comprobante,
            $idCliente,
            $idSucursal,
            $nombreFleteroPdf,
            $nombreZonaPdf,
            (int)$metrics['bultos'],
            (int)$metrics['vacunas'],
            (int)$metrics['bolsas'],
            (int)$metrics['cajas'],
            (int)$metrics['otros'],
            $meta['pdf_origen']
        ]);

        $contador++;
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "Importación exitosa. Se procesaron $contador registros.",
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>