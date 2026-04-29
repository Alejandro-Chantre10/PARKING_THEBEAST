<?php
/**
 * Database Configuration - Parking The Beasts
 * Uses PDO for secure database connections
 */

class Database {
    private $host = "localhost";
    private $db_name = "parking_db";
    private $username = "root";
    private $password = "";
    private $conn = null;
    private static $instance = null;

    // Private constructor for singleton pattern
    private function __construct() {}

    // Get database instance (Singleton)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get database connection
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                // Return JSON error instead of HTML
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error de conexión a la base de datos. Verifica que MySQL esté corriendo y que la base de datos "parking_db" exista.',
                    'debug' => $e->getMessage()
                ]);
                exit();
            }
        }
        return $this->conn;
    }

    // Close connection
    public function closeConnection() {
        $this->conn = null;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get DB connection
function getDBConnection() {
    return Database::getInstance()->getConnection();
}
?>
