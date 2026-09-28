<?php
/**
 * Conexión PDO a la base de datos de Punto y Aparte.
 *
 * Uso:
 *   require_once __DIR__ . '/../config/Conexion.php';
 *   $pdo = Conexion::obtener();
 *   $stmt = $pdo->prepare('SELECT * FROM libros WHERE id_libro = ?');
 *   $stmt->execute([$id]);
 *   $libro = $stmt->fetch();
 *
 * Las credenciales se leen de config/database.php (ver database.example.php).
 */

class Conexion
{
    /** Una sola conexión por petición. */
    private static ?PDO $pdo = null;

    public static function obtener(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $archivo = __DIR__ . '/database.php';
        if (!is_file($archivo)) {
            throw new RuntimeException(
                'Falta config/database.php. Copie config/database.example.php y llene sus datos.'
            );
        }
        $cfg = require $archivo;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'] ?? 3306,
            $cfg['database'],
            $cfg['charset'] ?? 'utf8mb4'
        );

        try {
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // filas como arreglos asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // consultas preparadas reales
            ]);
        } catch (PDOException $e) {
            // No se muestra el detalle al usuario: puede incluir datos del servidor.
            error_log('Error de conexión a la BD: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }

        // Montos y fechas en hora de El Salvador (UTC-6, sin horario de verano).
        self::$pdo->exec("SET time_zone = '-06:00'");

        return self::$pdo;
    }
}
