<?php
/**
 * Rates API - Parking The Beasts
 * Handles rate/pricing endpoints
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/tarifa_controller.php';

setApiHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$controller = new RateController();

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                $result = $controller->getActive();
                sendResponse($result);
                break;
                
            case 'by-facility':
                $facilityId = $_GET['facility_id'] ?? null;
                if (!$facilityId) {
                    sendError('ID de instalación requerido');
                }
                $result = $controller->getByFacility($facilityId);
                sendResponse($result);
                break;
                
            case 'detail':
                if (!$id) {
                    sendError('ID de tarifa requerido');
                }
                $result = $controller->getById($id);
                sendResponse($result);
                break;
                
            case 'all':
                requireAdmin();
                $result = $controller->getAll();
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'POST':
        requireAdmin();
        $data = getJsonInput();
        
        switch ($action) {
            case 'create':
                validateRequired($data, ['id_facilities', 'id_vehicle_types', 'price_per_hour']);
                $result = $controller->create(sanitizeInput($data));
                sendResponse($result, $result['success'] ? 201 : 400);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'PUT':
        requireAdmin();
        $data = getJsonInput();
        
        switch ($action) {
            case 'update':
                if (!$id) {
                    sendError('ID de tarifa requerido');
                }
                $result = $controller->update($id, sanitizeInput($data));
                sendResponse($result);
                break;
                
            default:
                sendError('Acción no válida', 400);
        }
        break;
        
    case 'DELETE':
        requireAdmin();
        
        switch ($action) {
            case 'deactivate':
                if (!$id) {
                    sendError('ID de tarifa requerido');
                }
                $result = $controller->deactivate($id);
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
