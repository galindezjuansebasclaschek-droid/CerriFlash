<?php
/**
 * CerriFlash - Configuración General de la Aplicación
 * “Tu pedido, rápido y seguro.”
 */

// Iniciar sesión segura si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Zona horaria
date_default_timezone_set('America/Bogota');

// Identidad de marca
define('APP_NAME', 'CerriFlash');
define('APP_SLOGAN', 'Tu pedido, rápido y seguro.');
define('APP_VERSION', '1.0.0');

// Rutas del Sistema de Archivos
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('LOGOS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'logos');
define('DOCS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'documentos');
define('FOTOS_PATH', UPLOADS_PATH . DIRECTORY_SEPARATOR . 'fotos');

// Detección automática de BASE_URL (funciona tanto en php -S localhost:8000 como en localhost/cerriflash)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Si se corre desde Apache htdocs en subcarpeta /cerriflash
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalizar si se ejecuta desde subcarpetas (admin, restaurante, etc.)
    $baseSubdir = '';
    if (strpos($scriptDir, '/cerriflash') !== false) {
        $parts = explode('/cerriflash', $scriptDir);
        $baseSubdir = '/cerriflash';
    } else {
        // Si el root web apunta directamente a la carpeta del proyecto
        $baseSubdir = '';
    }
    
    define('BASE_URL', rtrim($protocol . $host . $baseSubdir, '/'));
}

// Assets URL
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Cargar dependencias esenciales
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

