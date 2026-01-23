<?php
// Obtenemos el nombre del archivo actual (ej: index.php, fleteros.php)
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🚛 Panel Logístico</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
        <div class="navbar-nav me-auto">
            <!-- Solapa 1: Validar (Index) -->
            <a class="nav-link <?= ($paginaActual == 'index.php') ? 'active' : '' ?>" href="index.php">
                1. Validar Direcciones
            </a>
            
            <!-- Solapa 2: Mapa -->
            <a class="nav-link <?= ($paginaActual == 'mapa_rutas.php') ? 'active' : '' ?>" href="mapa_rutas.php">
                2. Armar Rutas
            </a>
            
            <!-- Solapa 3: Fleteros -->
            <a class="nav-link <?= ($paginaActual == 'fleteros.php') ? 'active' : '' ?>" href="fleteros.php">
                3. Fleteros
            </a>
        </div>
        
        <!-- Sección Derecha: Usuario y Logout -->
        <div class="d-flex align-items-center text-white">
            <span class="small me-3">
                Hola, <strong><?= htmlspecialchars($_SESSION['nombre'] ?? 'Invitado') ?></strong>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Salir</a>
        </div>
    </div>
  </div>
</nav>