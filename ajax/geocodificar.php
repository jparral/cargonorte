<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$idSucursal = $_POST['id_sucursal'] ?? null;
$direccionManual = $_POST['direccion'] ?? null; // Si el usuario la editó a mano

if (!$idSucursal) {
    echo json_encode(['status' => 'error', 'message' => 'Falta ID Sucursal']);
    exit;
}

try {
    $pdo = getDBConnection();

    // 1. Obtener datos actuales si no se pasó dirección manual
    if (!$direccionManual) {
        $stmt = $pdo->prepare("SELECT texto_busqueda FROM sucursales WHERE id_sucursal = ?");
        $stmt->execute([$idSucursal]);
        $sucursal = $stmt->fetch();
        $direccionBusqueda = $sucursal['texto_busqueda'] . ", Argentina"; // Forzamos país para mejorar precisión
    } else {
        $direccionBusqueda = $direccionManual . ", Argentina";
    }

    // 2. Consultar Google Geocoding API
    $apiKey = GOOGLE_API_KEY;
    $address = urlencode($direccionBusqueda);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($data['status'] === 'OK') {
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        $formatted_address = $data['results'][0]['formatted_address'];
        $place_id = $data['results'][0]['place_id'];

        // 3. Actualizar BD
        $update = $pdo->prepare("UPDATE sucursales SET 
            latitud = ?, longitud = ?, direccion_formateada = ?, google_place_id = ?, 
            estado_direccion = 'verificada',
            calle_pdf = ? -- Actualizamos la calle si el usuario la editó
            WHERE id_sucursal = ?");
        
        // Si el usuario editó, guardamos ese texto como referencia, sino dejamos el original
        $calleGuardar = $direccionManual ? $direccionManual : $direccionBusqueda;
        
        $update->execute([$lat, $lng, $formatted_address, $place_id, $calleGuardar, $idSucursal]);

        echo json_encode(['status' => 'success', 'direccion' => $formatted_address]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Google no encontró la dirección: ' . $data['status']]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}