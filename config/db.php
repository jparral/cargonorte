<?php
// Credenciales de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'NOMBRE_DE_TU_BD'); // <-- CAMBIAR ESTO
define('DB_USER', 'USUARIO_DE_TU_BD'); // <-- CAMBIAR ESTO
define('DB_PASS', 'PASSWORD_DE_TU_BD'); // <-- CAMBIAR ESTO
define('DB_CHARSET', 'utf8mb4');

// API Key de Google (Déjala vacía si aún no la tienes, el sistema no fallará)
define('GOOGLE_API_KEY', 'TU_API_KEY_AQUI'); 

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