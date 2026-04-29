<?php
/**
 * Reservations API - Parking The Beasts
 * Handles reservation endpoints
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/reserva_controller.php';

setApiHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$controller = new ReservationController();

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                $userId = requireAuth();
                $status = $_GET['status'] ?? null;
                $result = $controller->getByUserId($userId, $status);
                sendResponse($result);
                break;
                
            case 'detail':
                requireAuth();
                if (!$id) {
                    sendError('ID de reserva requerido');
                }
                $result = $controller->getById($id);
                sendResponse($result);
                break;
                
            case 'all':
                requireAdmin();
                $limit = (int)($_GET['limit'] ?? 50);
                $offset = (int)($_GET['offset'] ?? 0);
                $filters = [
                    'status'      => $_GET['status'] ?? null,
                    'facility_id' => $_GET['facility_id'] ?? null,
                    'date_from'   => $_GET['date_from'] ?? null,
                    'date_to'     => $_GET['date_to'] ?? null
                ];
                $result = $controller->getAll($limit, $offset, $filters);
                sendResponse($result);
                break;
                
            case 'check-availability':
                $facilityId = $_GET['facility_id'] ?? 1;
                $vehicleTypeId = $_GET['vehicle_type_id'] ?? null;
                $startAt = $_GET['start_at'] ?? null;
                $endAt = $_GET['end_at'] ?? null;
                
                if (!$vehicleTypeId || !$startAt || !$endAt) {
                    sendError('Tipo de vehículo y fechas son requeridos');
                }
                
                // Convert datetime-local format to MySQL datetime
                $startAt = str_replace('T', ' ', $startAt);
                $endAt = str_replace('T', ' ', $endAt);
                if (strlen($startAt) == 16) $startAt .= ':00';
                if (strlen($endAt) == 16) $endAt .= ':00';
                
                $result = $controller->checkAvailability($facilityId, $vehicleTypeId, $startAt, $endAt);
                sendResponse($result);
                break;
                
            case 'calculate-price':
                $facilityId = $_GET['facility_id'] ?? 1;
                $vehicleTypeId = $_GET['vehicle_type_id'] ?? null;
                $startAt = $_GET['start_at'] ?? null;
                $endAt = $_GET['end_at'] ?? null;
                
                if (!$vehicleTypeId || !$startAt || !$endAt) {
                    sendError('Tipo de vehículo y fechas son requeridos');
                }
                
                // Convert datetime-local format to MySQL datetime
                $startAt = str_replace('T', ' ', $startAt);
                $endAt = str_replace('T', ' ', $endAt);
                if (strlen($startAt) == 16) $startAt .= ':00';
                if (strlen($endAt) == 16) $endAt .= ':00';
                
                $result = $controller->calculatePrice($facilityId, $vehicleTypeId, $startAt, $endAt);
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
                validateRequired($data, ['vehicle_type_id', 'vehicle_plate', 'start_at', 'end_at']);
                $data['user_id'] = $userId;
                $result = $controller->create(sanitizeInput($data));
                sendResponse($result, $result['success'] ? 201 : 400);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'PUT':
        $userId = requireAuth();
        $data = getJsonInput();
        
        switch ($action) {
            case 'update':
                if (!$id) {
                    sendError('ID de reserva requerido');
                }
                $result = $controller->update($id, sanitizeInput($data), $userId);
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'DELETE':
        $userId = requireAuth();
        
        switch ($action) {
            case 'cancel':
                if (!$id) {
                    sendError('ID de reserva requerido');
                }
                $result = $controller->cancel($id, $userId);
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
