<?php
require_once 'config/db.php';
require_once 'includes/auth.php'; // Seguridad activada

$pdo = getDBConnection();

// Obtener Fleteros para el select (asegurando que traemos el teléfono)
$fleteros = $pdo->query("SELECT * FROM fleteros WHERE activo = 1")->fetchAll();
$fechaHoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Armar Rutas - Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #map { height: 600px; width: 100%; border-radius: 8px; }
        .entregas-list { max-height: 600px; overflow-y: auto; }
        /* Pequeño ajuste visual para la lista */
        .list-group-item { cursor: pointer; } 
        .list-group-item:hover { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>

<div class="container-fluid px-4">
    <div class="row mb-3">
        <div class="col-md-3">
            <label>Fecha de Reparto:</label>
            <input type="date" id="filtro-fecha" class="form-control" value="<?= $fechaHoy ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100" onclick="cargarPuntos()">🔄 Cargar Mapa</button>
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Controles y Lista -->
        <div class="col-md-3">
            <div class="card shadow mb-3">
                <div class="card-header bg-primary text-white">Asignar Fletero</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Seleccionar Fletero:</label>
                        <select id="select-fletero" class="form-select">
                            <option value="" data-tel="">-- Elegir --</option>
                            <?php foreach ($fleteros as $f): ?>
                                <!-- CAMBIO AQUI: Agregamos data-tel -->
                                <option value="<?= $f['id_fletero'] ?>" data-tel="<?= htmlspecialchars($f['telefono'] ?? '') ?>">
                                    <?= htmlspecialchars($f['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="small text-muted">Selecciona puntos en el mapa haciendo clic en ellos.</p>
                    <button class="btn btn-success w-100 mb-2" onclick="guardarAsignacion()">💾 Guardar Asignación</button>
                    <button class="btn btn-outline-success w-100" onclick="compartirWhatsapp()">📱 Enviar por WhatsApp</button>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header">Seleccionados (<span id="contador-sel">0</span>)</div>
                <ul class="list-group list-group-flush entregas-list" id="lista-seleccionados">
                    <!-- Aquí se llenan los seleccionados con JS -->
                </ul>
            </div>
        </div>

        <!-- Columna Derecha: Mapa -->
        <div class="col-md-9">
            <div id="map" class="shadow"></div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- API Key Configurada -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_API_KEY ?>&callback=initMap" async defer></script>

<script>
let map;
let markers = [];
let selectedEntregas = []; 

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: -34.6037, lng: -58.3816 }, // Buenos Aires
        zoom: 12,
    });
    cargarPuntos(); 
}

function cargarPuntos() {
    let fecha = $('#filtro-fecha').val();
    
    // Limpiar mapa
    markers.forEach(m => m.setMap(null));
    markers = [];
    selectedEntregas = [];
    actualizarLista();

    $.get('ajax/get_puntos.php', { fecha: fecha }, function(res) {
        if (res.status === 'success') {
            let bounds = new google.maps.LatLngBounds();

            res.data.forEach(item => {
                let position = { lat: parseFloat(item.latitud), lng: parseFloat(item.longitud) };
                
                // Verde si ya tiene fletero, Rojo si está libre
                let iconUrl = item.nombre_fletero 
                    ? "http://maps.google.com/mapfiles/ms/icons/green-dot.png" 
                    : "http://maps.google.com/mapfiles/ms/icons/red-dot.png";

                let marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: item.nombre_cliente,
                    icon: iconUrl,
                    id_entrega: item.id_entrega,
                    data: item 
                });

                marker.addListener("click", () => {
                    toggleSeleccion(marker);
                });

                markers.push(marker);
                bounds.extend(position);
            });

            if (res.data.length > 0) map.fitBounds(bounds);
        }
    }, 'json');
}

function toggleSeleccion(marker) {
    let id = marker.id_entrega;
    let index = selectedEntregas.indexOf(id);

    if (index === -1) {
        // Seleccionar (Azul)
        selectedEntregas.push(id);
        marker.setIcon("http://maps.google.com/mapfiles/ms/icons/blue-dot.png");
        
        // Agregar a la lista lateral
        $('#lista-seleccionados').append(
            `<li class="list-group-item small" id="li-${id}" onclick="centrarMapa(${marker.getPosition().lat()}, ${marker.getPosition().lng()})">
                <strong>${marker.data.nombre_cliente}</strong><br>
                <span class="text-muted">${marker.data.direccion_formateada}</span>
            </li>`
        );
    } else {
        // Deseleccionar (Volver a su color original)
        selectedEntregas.splice(index, 1);
        
        let icon = marker.data.nombre_fletero 
            ? "http://maps.google.com/mapfiles/ms/icons/green-dot.png" 
            : "http://maps.google.com/mapfiles/ms/icons/red-dot.png";
        marker.setIcon(icon);
        
        $('#li-' + id).remove();
    }
    $('#contador-sel').text(selectedEntregas.length);
}

function centrarMapa(lat, lng) {
    map.setCenter({lat: lat, lng: lng});
    map.setZoom(15);
}

function actualizarLista() {
    $('#lista-seleccionados').empty();
    $('#contador-sel').text(0);
}

function guardarAsignacion() {
    let idFletero = $('#select-fletero').val();
    
    if (!idFletero) {
        alert('Por favor selecciona un fletero.');
        return;
    }
    if (selectedEntregas.length === 0) {
        alert('Selecciona al menos un punto en el mapa.');
        return;
    }

    $.ajax({
        url: 'ajax/guardar_ruta.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            id_fletero: idFletero,
            ids_entregas: selectedEntregas
        }),
        success: function(res) {
            if (res.status === 'success') {
                alert('¡Ruta asignada correctamente!');
                cargarPuntos();
            } else {
                alert('Error: ' + res.message);
            }
        }
    });
}

function compartirWhatsapp() {
    if (selectedEntregas.length === 0) {
        alert("Selecciona entregas primero.");
        return;
    }

    // 1. Obtener Fletero y Teléfono
    let select = document.getElementById('select-fletero');
    let opcionSeleccionada = select.options[select.selectedIndex];
    let telefono = opcionSeleccionada.getAttribute('data-tel'); // Leemos el atributo data-tel
    
    // 2. Construir la URL Global de Google Maps
    let baseUrl = "https://www.google.com/maps/dir/";
    let waypoints = "";
    
    let mensajeTexto = "*Hoja de Ruta - " + $('#filtro-fecha').val() + "*\n";
    mensajeTexto += "🚛 Fletero: " + opcionSeleccionada.text + "\n\n";

    // Filtramos los markers que están seleccionados
    let puntosOrdenados = markers.filter(m => selectedEntregas.includes(m.id_entrega));

    puntosOrdenados.forEach((m, index) => {
        let lat = m.data.latitud;
        let lng = m.data.longitud;
        let direccion = m.data.direccion_formateada;
        let cliente = m.data.nombre_cliente;
        let remito = m.data.comprobante;
        // Importante: Bultos o info extra
        let bultos = m.data.bultos || 0; 

        // Agregar al link global de ruta
        waypoints += `${lat},${lng}/`;

        // Agregar al texto detallado
        mensajeTexto += `📍 *Parada ${index + 1}: ${cliente}*\n`;
        mensajeTexto += `📄 Remito: ${remito} (${bultos} bultos)\n`;
        mensajeTexto += `🏠 ${direccion}\n`;
        mensajeTexto += `🔗 https://www.google.com/maps/search/?api=1&query=${lat},${lng}\n\n`;
    });

    let linkGlobal = baseUrl + waypoints;
    
    mensajeTexto += "🌍 *Ruta Completa (Google Maps):*\n";
    mensajeTexto += linkGlobal;

    // 3. Generar Link
    let mensajeCodificado = encodeURIComponent(mensajeTexto);
    let urlWhatsapp = "";

    if (telefono && telefono.length > 5) {
        // Limpiamos el teléfono (quitamos espacios, guiones, paréntesis)
        let telLimpio = telefono.replace(/[^0-9]/g, '');
        urlWhatsapp = `https://web.whatsapp.com/send?phone=${telLimpio}&text=${mensajeCodificado}`;
    } else {
        // Si no hay teléfono, abre selector de contactos
        urlWhatsapp = `https://web.whatsapp.com/send?text=${mensajeCodificado}`;
    }
    
    window.open(urlWhatsapp, '_blank');
}
</script>

</body>
</html>