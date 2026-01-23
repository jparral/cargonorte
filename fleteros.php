<?php
require_once 'includes/auth.php'; // SEGURIDAD
require_once 'config/db.php';

$pdo = getDBConnection();
$fleteros = $pdo->query("SELECT * FROM fleteros WHERE activo = 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Fleteros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
    <h2>Gestión de Fleteros</h2>
    <button class="btn btn-primary mb-3" onclick="abrirModal()">+ Nuevo Fletero</button>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Nombre</th><th>Patente</th><th>Teléfono</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($fleteros as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['nombre']) ?></td>
                        <td><?= htmlspecialchars($f['patente_vehiculo'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($f['telefono'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick='editar(<?= json_encode($f) ?>)'>Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="eliminar(<?= $f['id_fletero'] ?>)">Borrar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalFletero" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Fletero</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="formFletero">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id_fletero" id="id_fletero">
            <div class="mb-3"><label>Nombre</label><input type="text" name="nombre" id="nombre" class="form-control" required></div>
            <div class="mb-3"><label>Patente</label><input type="text" name="patente" id="patente" class="form-control"></div>
            <div class="mb-3"><label>Teléfono (WhatsApp)</label><input type="text" name="telefono" id="telefono" class="form-control" placeholder="54911..."></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="guardar()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
function abrirModal() {
    $('#formFletero')[0].reset();
    $('#id_fletero').val('');
    new bootstrap.Modal(document.getElementById('modalFletero')).show();
}
function editar(data) {
    $('#id_fletero').val(data.id_fletero);
    $('#nombre').val(data.nombre);
    $('#patente').val(data.patente_vehiculo);
    $('#telefono').val(data.telefono);
    new bootstrap.Modal(document.getElementById('modalFletero')).show();
}
function guardar() {
    $.post('ajax/abm_fleteros.php', $('#formFletero').serialize(), function(res) {
        if(res.status === 'success') location.reload();
        else alert('Error');
    }, 'json');
}
function eliminar(id) {
    if(confirm('¿Seguro?')) {
        $.post('ajax/abm_fleteros.php', {accion: 'eliminar', id: id}, function(res) {
            location.reload();
        }, 'json');
    }
}
</script>
</body>
</html>