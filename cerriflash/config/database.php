<?php
/**
 * CerriFlash - Configuración de Base de Datos PDO
 */

class Database {
    private static string $host = '127.0.0.1';
    private static string $db   = 'cerriflash';
    private static string $user = 'root';
    private static string $pass = '';
    private static string $charset = 'utf8mb4';
    private static ?PDO $pdo = null;

    /**
     * Obtiene la instancia única de conexión PDO
     */
    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, self::$user, self::$pass, $options);
            } catch (PDOException $e) {
                // Registrar error y mostrar mensaje genérico en producción
                error_log("Error de conexión a la base de datos CerriFlash: " . $e->getMessage());
                die("Error de conexión con la base de datos. Por favor verifica que el servicio MySQL de XAMPP esté iniciado.");
            }
        }

        return self::$pdo;
    }
}

