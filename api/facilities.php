<?php
/**
 * Facilities API - Parking The Beasts
 * Handles facility/parking location endpoints
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/models/instalacion.php';

setApiHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$model = new FacilityModel();

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                $facilities = $model->getActive();
                sendResponse(['success' => true, 'facilities' => $facilities]);
                break;
                
            case 'detail':
                if (!$id) {
                    sendError('ID de instalación requerido');
                }
                $facility = $model->getById($id);
                if ($facility) {
                    sendResponse(['success' => true, 'facility' => $facility]);
                } else {
                    sendError('Instalación no encontrada', 404);
                }
                break;
                
            case 'capacity':
                if (!$id) {
                    sendError('ID de instalación requerido');
                }
                $capacity = $model->getWithCapacity($id);
                sendResponse(['success' => true, 'capacity' => $capacity]);
                break;
                
            case 'occupancy':
                if (!$id) {
                    sendError('ID de instalación requerido');
                }
                $occupancy = $model->getOccupancy($id);
                sendResponse(['success' => true, 'occupancy' => $occupancy]);
                break;
                
            case 'all':
                requireAdmin();
                $facilities = $model->getAll();
                sendResponse(['success' => true, 'facilities' => $facilities]);
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
                validateRequired($data, ['name', 'address']);
                $facilityId = $model->create(sanitizeInput($data));
                if ($facilityId) {
                    sendResponse([
                        'success' => true,
                        'message' => 'Instalación creada exitosamente',
                        'facility_id' => $facilityId
                    ], 201);
                } else {
                    sendError('Error al crear la instalación');
                }
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
                    sendError('ID de instalación requerido');
                }
                $result = $model->update($id, sanitizeInput($data));
                if ($result) {
                    sendResponse(['success' => true, 'message' => 'Instalación actualizada exitosamente']);
                } else {
                    sendError('Error al actualizar la instalación');
                }
                break;
                
            case 'capacity':
                if (!$id) {
                    sendError('ID de instalación requerido');
                }
                validateRequired($data, ['id_vehicle_types', 'capacity']);
                $result = $model->updateCapacity($id, $data['id_vehicle_types'], $data['capacity']);
                if ($result) {
                    sendResponse(['success' => true, 'message' => 'Capacidad actualizada exitosamente']);
                } else {
                    sendError('Error al actualizar la capacidad');
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
