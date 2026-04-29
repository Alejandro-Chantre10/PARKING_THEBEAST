<?php
/**
 * Payments API - Parking The Beasts
 * Handles payment endpoints
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/pago_controller.php';

setApiHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$controller = new PaymentController();

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                $userId = requireAuth();
                $result = $controller->getByUserId($userId);
                sendResponse($result);
                break;
                
            case 'detail':
                requireAuth();
                if (!$id) {
                    sendError('ID de pago requerido');
                }
                $result = $controller->getById($id);
                sendResponse($result);
                break;
                
            case 'by-reservation':
                requireAuth();
                $reservationId = $_GET['reservation_id'] ?? null;
                if (!$reservationId) {
                    sendError('ID de reserva requerido');
                }
                $result = $controller->getByReservationId($reservationId);
                sendResponse($result);
                break;
                
            case 'all':
                requireAdmin();
                $limit = (int)($_GET['limit'] ?? 50);
                $offset = (int)($_GET['offset'] ?? 0);
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'method' => $_GET['method'] ?? null
                ];
                $result = $controller->getAll($limit, $offset, $filters);
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'POST':
        $userId = requireAuth();
        $data = getJsonInput();
        
        switch ($action) {
            case 'create':
                validateRequired($data, ['reservation_id', 'method']);
                $data['user_id'] = $userId;
                $result = $controller->create(sanitizeInput($data));
                sendResponse($result, $result['success'] ? 201 : 400);
                break;
                
            case 'process':
                if (!$id) {
                    sendError('ID de pago requerido');
                }
                $result = $controller->processPayment($id, $userId);
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
