<?php
/**
 * Conexión PDO a MySQL/MariaDB (patrón singleton: una sola conexión por petición).
 */

class Database
{
    private static ?PDO $pdo = null;

    /** Usuario en sesión; se copia a la variable @id_usuario_actual que leen los triggers. */
    private static ?int $idUsuario = null;

    private function __construct() {}
    private function __clone() {}

    public static function conexion(): PDO
    {
        if (self::$pdo === null) {
            $cfg = require RUTA_APP . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'] ?? 3306,
                $cfg['database'],
                $cfg['charset'] ?? 'utf8mb4'
            );

            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // filas como arreglos asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // sentencias preparadas reales
            ]);

            // Hora de El Salvador (UTC-6, sin horario de verano) para NOW() y CURRENT_TIMESTAMP
            self::$pdo->exec("SET time_zone = '-06:00'");
            self::aplicarUsuario();
        }

        return self::$pdo;
    }

    /** Indica qué usuario está haciendo los cambios (para historial_estados_pedido). */
    public static function fijarUsuario(?int $idUsuario): void
    {
        self::$idUsuario = $idUsuario;
        if (self::$pdo !== null) {
            self::aplicarUsuario();
        }
    }

    private static function aplicarUsuario(): void
    {
        $st = self::$pdo->prepare('SET @id_usuario_actual = ?');
        $st->execute([self::$idUsuario]);
    }
}
