<?php
/**
 * Rate Controller - Parking The Beasts
 * Handles rate/pricing-related requests
 * Updated to match parking_db schema
 */

require_once __DIR__ . '/../models/tarifa.php';

class RateController {
    private $rateModel;

    public function __construct() {
        $this->rateModel = new RateModel();
    }

    /**
     * Get all active rates
     */
    public function getActive() {
        $rates = $this->rateModel->getActive();
        
        return [
            'success' => true,
            'rates' => $rates
        ];
    }

    /**
     * Get rates by facility
     */
    public function getByFacility($facilityId) {
        $rates = $this->rateModel->getByFacilityId($facilityId);
        
        return [
            'success' => true,
            'rates' => $rates
        ];
    }

    /**
     * Get rate by ID
     */
    public function getById($id) {
        $rate = $this->rateModel->getById($id);
        
        if (!$rate) {
            return [
                'success' => false,
                'message' => 'Tarifa no encontrada'
            ];
        }

        return [
            'success' => true,
            'rate' => $rate
        ];
    }

    /**
     * Create rate (Admin)
     */
    public function create($data) {
        if (empty($data['facility_id']) || empty($data['vehicle_type_id']) || empty($data['price_per_hour'])) {
            return [
                'success' => false,
                'message' => 'Instalación, tipo de vehículo y precio son requeridos'
            ];
        }

        // Check if rate already exists for this facility/vehicle combo
        $existingRate = $this->rateModel->getByFacilityAndVehicleType(
            $data['facility_id'],
            $data['vehicle_type_id']
        );

        if ($existingRate) {
            return [
                'success' => false,
                'message' => 'Ya existe una tarifa para esta combinación'
            ];
        }

        $rateId = $this->rateModel->create($data);

        if ($rateId) {
            return [
                'success' => true,
                'message' => 'Tarifa creada exitosamente',
                'rate_id' => $rateId
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al crear la tarifa'
        ];
    }

    /**
     * Update rate (Admin)
     */
    public function update($id, $data) {
        $rate = $this->rateModel->getById($id);
        
        if (!$rate) {
            return [
                'success' => false,
                'message' => 'Tarifa no encontrada'
            ];
        }

        $result = $this->rateModel->update($id, [
            'price_per_hour'   => $data['price_per_hour'] ?? $rate['price_per_hour'],
            'min_minutes'      => $data['min_minutes'] ?? $rate['min_minutes'],
            'rounding_minutes' => $data['rounding_minutes'] ?? $rate['rounding_minutes'],
            'grace_minutes'    => $data['grace_minutes'] ?? $rate['grace_minutes'],
            'is_active'        => $data['is_active'] ?? $rate['is_active']
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Tarifa actualizada exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al actualizar la tarifa'
        ];
    }

    /**
     * Deactivate rate (Admin)
     */
    public function deactivate($id) {
        $rate = $this->rateModel->getById($id);
        
        if (!$rate) {
            return [
                'success' => false,
                'message' => 'Tarifa no encontrada'
            ];
        }

        $result = $this->rateModel->deactivate($id);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Tarifa desactivada exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al desactivar la tarifa'
        ];
    }

    /**
     * Get all rates (Admin)
     */
    public function getAll() {
        $rates = $this->rateModel->getAll();
        
        return [
            'success' => true,
            'rates' => $rates
        ];
    }

    /**
     * Calculate price for given parameters
     */
    public function calculatePrice($facilityId, $vehicleTypeId, $startAt, $endAt) {
        $price = $this->rateModel->calculatePrice($facilityId, $vehicleTypeId, $startAt, $endAt);
        
        return [
            'success' => true,
            'price' => $price
        ];
    }
}
?>
