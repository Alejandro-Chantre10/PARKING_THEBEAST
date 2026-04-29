<?php
/**
 * Auth API - Parking The Beasts
 * Handles authentication endpoints: login, register, logout
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/usuario_controller.php';

setApiHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$controller = new UserController();

switch ($method) {
    case 'POST':
        $data = getJsonInput();
        
        switch ($action) {
            case 'register':
                validateRequired($data, ['full_name', 'email', 'password']);
                $result = $controller->register(sanitizeInput($data));
                sendResponse($result, $result['success'] ? 201 : 400);
                break;
                
            case 'login':
                validateRequired($data, ['email', 'password']);
                $result = $controller->login(
                    sanitizeInput($data['email']),
                    $data['password'] // Don't sanitize password
                );
                sendResponse($result, $result['success'] ? 200 : 401);
                break;
                
            case 'logout':
                $result = $controller->logout();
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'check':
                $user = getCurrentUser();
                if ($user) {
                    sendResponse(['success' => true, 'authenticated' => true, 'user' => $user]);
                } else {
                    sendResponse(['success' => true, 'authenticated' => false]);
                }
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    default:
        sendError('Método no permitido', 405);
}
?>
