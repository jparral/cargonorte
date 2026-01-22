<?php
require_once 'config/db.php';
$pdo = getDBConnection();

// Obtener direcciones pendientes
$sql = "SELECT s.*, c.nombre_cliente 
        FROM sucursales s 
        JOIN clientes c ON s.id_cliente = c.id_cliente 
        WHERE s.estado_direccion = 'pendiente' 
        ORDER BY s.id_sucursal DESC LIMIT 50";
$pendientes = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validar Direcciones - Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="#">Panel Logístico</a>
    <div class="navbar-nav">
      <a class="nav-link active" href="index.php">1. Validar Direcciones</a>
      <a class="nav-link" href="mapa_rutas.php">2. Armar Rutas</a>
    </div>
  </div>
</nav>

<div class="container">
    <h2 class="mb-4">Direcciones Pendientes de Validación</h2>
    <div class="alert alert-info">
        Estas direcciones provienen del PDF y Google aún no las ubica. 
        Edítalas para corregir errores de tipeo y pulsa "Geocodificar".
    </div>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Dirección PDF (Sucia)</th>
                        <th>Localidad</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendientes as $p): ?>
                    <tr id="fila-<?= $p['id_sucursal'] ?>">
                        <td><?= htmlspecialchars($p['nombre_cliente']) ?></td>
                        <td>
                            <input type="text" class="form-control" id="input-<?= $p['id_sucursal'] ?>" 
                                   value="<?= htmlspecialchars($p['texto_busqueda']) ?>">
                        </td>
                        <td><?= htmlspecialchars($p['localidad_pdf']) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm btn-geo" data-id="<?= $p['id_sucursal'] ?>">
                                🌍 Geocodificar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(count($pendientes) == 0): ?>
                <p class="text-center text-success fw-bold">¡Todo limpio! No hay direcciones pendientes.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.btn-geo').click(function() {
        var id = $(this).data('id');
        var direccion = $('#input-' + id).val();
        var btn = $(this);

        btn.prop('disabled', true).text('Procesando...');

        $.post('ajax/geocodificar.php', { id_sucursal: id, direccion: direccion }, function(res) {
            if (res.status === 'success') {
                alert('Ubicado: ' + res.direccion);
                $('#fila-' + id).fadeOut();
            } else {
                alert('Error: ' + res.message);
                btn.prop('disabled', false).text('🌍 Reintentar');
            }
        }, 'json');
    });
});
</script>

</body>
</html>