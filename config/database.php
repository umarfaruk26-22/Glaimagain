<?php
/**
 * GLAIMAGAIN - Database PDO Connection
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // In production, log error and show friendly message
                error_log("Database Connection Error: " . $e->getMessage());
                die("<div style='font-family:sans-serif;padding:30px;text-align:center;background:#013C26;color:#FFFFFF;'>
                    <h2 style='color:#B99036;'>GLAIMAGAIN Database Offline</h2>
                    <p>Unable to connect to the database. Please verify that MySQL is running and the database is imported.</p>
                </div>");
            }
        }

        return self::$instance;
    }
}

// Global helper for PDO
function getDb(): PDO {
    return Database::getConnection();
}
