<?php
/**
 * Vehicle Types API - Parking The Beasts
 * Handles vehicle type endpoints
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/models/tipo_vehiculo.php';

setApiHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$model = new VehicleTypeModel();

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                $vehicleTypes = $model->getAll();
                sendResponse(['success' => true, 'vehicle_types' => $vehicleTypes]);
                break;
                
            case 'detail':
                if (!$id) {
                    sendError('ID de tipo de vehículo requerido');
                }
                $vehicleType = $model->getById($id);
                if ($vehicleType) {
                    sendResponse(['success' => true, 'vehicle_type' => $vehicleType]);
                } else {
                    sendError('Tipo de vehículo no encontrado', 404);
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
