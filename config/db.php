<?php
// Credenciales de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'cargonorte'); // <-- CAMBIAR ESTO
define('DB_USER', 'root'); // <-- CAMBIAR ESTO
define('DB_PASS', ''); // <-- CAMBIAR ESTO
define('DB_CHARSET', 'utf8mb4');

// API Key de Google (Déjala vacía si aún no la tienes, el sistema no fallará)
define('GOOGLE_API_KEY', 'AIzaSyAFXeIQ-MrWRjN3gYEFNWNOXaR8vDnFF2Q'); 

function getDBConnection() {
    try {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        // En producción no mostrar el error detallado al público
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>