<?php
/**
 * Test API - Parking The Beasts
 * Use this to test database connection and configuration
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

setApiHeaders();

$action = $_GET['action'] ?? 'info';

switch ($action) {
    case 'info':
        sendResponse([
            'success' => true,
            'message' => 'API funcionando correctamente',
            'php_version' => phpversion(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
        
    case 'db':
        try {
            $db = getDBConnection();
            
            // Test query
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            // Check if tables exist
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            sendResponse([
                'success' => true,
                'message' => 'Conexión a base de datos exitosa',
                'database' => 'parking_db',
                'tables' => $tables,
                'tables_count' => count($tables)
            ]);
        } catch (Exception $e) {
            sendError('Error de conexión: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'roles':
        try {
            $db = getDBConnection();
            $stmt = $db->query("SELECT * FROM roles");
            $roles = $stmt->fetchAll();
            
            sendResponse([
                'success' => true,
                'roles' => $roles
            ]);
        } catch (Exception $e) {
            sendError('Error al obtener roles: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        sendError('Acción no válida. Usa: info, db, roles', 400);
}
?>
