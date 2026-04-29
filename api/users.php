<?php
/**
 * Users API - Parking The Beasts
 * Handles user profile endpoints
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/usuario_controller.php';

setApiHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$controller = new UserController();

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'profile':
                $userId = requireAuth();
                $result = $controller->getProfile($userId);
                sendResponse($result);
                break;
                
            case 'list':
                requireAdmin();
                $limit = (int)($_GET['limit'] ?? 50);
                $offset = (int)($_GET['offset'] ?? 0);
                $result = $controller->getAllUsers($limit, $offset);
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'PUT':
        $userId = requireAuth();
        $data = getJsonInput();
        
        switch ($action) {
            case 'profile':
                $result = $controller->updateProfile($userId, sanitizeInput($data));
                sendResponse($result);
                break;
                
            case 'password':
                validateRequired($data, ['current_password', 'new_password']);
                $result = $controller->updatePassword(
                    $userId,
                    $data['current_password'],
                    $data['new_password']
                );
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'DELETE':
        $userId = requireAuth();
        
        switch ($action) {
            case 'account':
                $result = $controller->deactivateAccount($userId);
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    default:
        sendError('Método no permitido', 405);
}
?>
