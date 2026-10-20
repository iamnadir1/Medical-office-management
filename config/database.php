<?php
// Database settings (same values as the Java DatabaseConnection class)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'cabinet_medical');
define('DB_USER', 'root');
define('DB_PASS', 'Hknadir11@');

// Clinic details used on printed documents (from PrintUtils.java)
define('CLINIC_NAME', 'CABINET MEDICAL');
define('CLINIC_ADDRESS', 'Filfila, Skikda');
define('CLINIC_PHONE', '0548896435');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die('<h3>Database connection failed</h3><p>Check <code>config/database.php</code> and make sure MySQL is running and the schema is imported.</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
        }
    }
    return $pdo;
}
