<?php
require_once 'config/db.php';
$pdo = getDBConnection();

// Obtener Fleteros para el select
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
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="#">Panel Logístico</a>
    <div class="navbar-nav">
      <a class="nav-link" href="index.php">1. Validar Direcciones</a>
      <a class="nav-link active" href="mapa_rutas.php">2. Armar Rutas</a>
    </div>
  </div>
</nav>

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
                            <option value="">-- Elegir --</option>
                            <?php foreach ($fleteros as $f): ?>
                                <option value="<?= $f['id_fletero'] ?>"><?= $f['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="small text-muted">Selecciona puntos en el mapa haciendo clic en ellos.</p>
                    <button class="btn btn-success w-100" onclick="guardarAsignacion()">💾 Guardar Asignación</button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Seleccionados (<span id="contador-sel">0</span>)</div>
                <ul class="list-group list-group-flush entregas-list" id="lista-seleccionados">
                    <!-- Aquí se llenan los seleccionados con JS -->
                </ul>
            </div>
        </div>

        <!-- Columna Derecha: Mapa -->
        <div class="col-md-9">
            <div id="map"></div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- IMPORTANTE: Reemplaza TU_API_KEY_AQUI con tu clave real -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_API_KEY ?>&callback=initMap" async defer></script>

<script>
let map;
let markers = [];
let selectedEntregas = []; // IDs de entregas seleccionadas

function initMap() {
    // Centrar mapa en Buenos Aires por defecto
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: -34.6037, lng: -58.3816 },
        zoom: 12,
    });
    cargarPuntos(); // Cargar al inicio
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
                
                // Icono diferente si ya está asignado
                let iconUrl = item.nombre_fletero 
                    ? "http://maps.google.com/mapfiles/ms/icons/green-dot.png" 
                    : "http://maps.google.com/mapfiles/ms/icons/red-dot.png";

                let marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: item.nombre_cliente,
                    icon: iconUrl,
                    id_entrega: item.id_entrega,
                    data: item // Guardamos toda la data en el marker
                });

                // Evento Clic
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
        // Seleccionar
        selectedEntregas.push(id);
        marker.setIcon("http://maps.google.com/mapfiles/ms/icons/blue-dot.png"); // Cambiar a Azul
        
        // Agregar a la lista visual
        $('#lista-seleccionados').append(
            `<li class="list-group-item small" id="li-${id}">
                <b>${marker.data.nombre_cliente}</b><br>
                ${marker.data.direccion_formateada}
            </li>`
        );
    } else {
        // Deseleccionar
        selectedEntregas.splice(index, 1);
        // Volver al color original (verde si asignado, rojo si no)
        let icon = marker.data.nombre_fletero 
            ? "http://maps.google.com/mapfiles/ms/icons/green-dot.png" 
            : "http://maps.google.com/mapfiles/ms/icons/red-dot.png";
        marker.setIcon(icon);
        
        // Quitar de lista
        $('#li-' + id).remove();
    }
    $('#contador-sel').text(selectedEntregas.length);
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
                cargarPuntos(); // Recargar mapa para ver cambios
            } else {
                alert('Error: ' + res.message);
            }
        }
    });
}
</script>

</body>
</html>