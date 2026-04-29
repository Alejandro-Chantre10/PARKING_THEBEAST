<?php
/**
 * Reservation Controller - Parking The Beasts
 * Handles reservation-related requests
 */

require_once __DIR__ . '/../models/reserva.php';

class ReservationController {
    private $reservationModel;

    public function __construct() {
        $this->reservationModel = new ReservationModel();
    }

    /**
     * Create a new reservation
     */
    public function create($data) {
        // Validate required fields
        $required = ['id_users', 'id_vehicle_types', 'vehicle_plate', 'start_at', 'end_at'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => "El campo {$field} es requerido"
                ];
            }
        }

        // Validate dates
        $startAt = new DateTime($data['start_at']);
        $endAt = new DateTime($data['end_at']);
        $now = new DateTime();

        if ($startAt < $now) {
            return [
                'success' => false,
                'message' => 'La fecha de inicio no puede ser en el pasado'
            ];
        }

        if ($endAt <= $startAt) {
            return [
                'success' => false,
                'message' => 'La fecha de fin debe ser posterior a la fecha de inicio'
            ];
        }

        // Check availability
        $facilityId = $data['id_facilities'] ?? 1;
        $isAvailable = $this->reservationModel->checkAvailability(
            $facilityId,
            $data['id_vehicle_types'],
            $data['start_at'],
            $data['end_at']
        );

        if (!$isAvailable) {
            return [
                'success' => false,
                'message' => 'No hay disponibilidad para el horario seleccionado'
            ];
        }

        // Calculate price
        $price = $this->reservationModel->calculatePrice(
            $facilityId,
            $data['id_vehicle_types'],
            $data['start_at'],
            $data['end_at']
        );

        // Create reservation
        $reservationId = $this->reservationModel->create([
            'id_facilities'       => $facilityId,
            'id_users'            => $data['id_users'],
            'id_vehicle_types'    => $data['id_vehicle_types'],
            'vehicle_plate'       => $data['vehicle_plate'],
            'vehicle_description' => $data['vehicle_description'] ?? null,
            'start_at'            => $data['start_at'],
            'end_at'              => $data['end_at'],
            'price'               => $price,
            'notes'               => $data['notes'] ?? null
        ]);

        if ($reservationId) {
            return [
                'success' => true,
                'message' => 'Reserva creada exitosamente',
                'reservation_id' => $reservationId,
                'price' => $price
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al crear la reserva'
        ];
    }

    /**
     * Get reservation by ID
     */
    public function getById($id) {
        $reservation = $this->reservationModel->getById($id);
        
        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Reserva no encontrada'
            ];
        }

        return [
            'success' => true,
            'reservation' => $reservation
        ];
    }

    /**
     * Get reservations for a user
     */
    public function getByUserId($userId, $status = null) {
        $reservations = $this->reservationModel->getByUserId($userId, $status);
        
        return [
            'success' => true,
            'reservations' => $reservations
        ];
    }

    /**
     * Update reservation
     */
    public function update($id, $data, $userId) {
        // Get existing reservation
        $reservation = $this->reservationModel->getById($id);
        
        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Reserva no encontrada'
            ];
        }

        // Check ownership
        if ($reservation['id_users'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para modificar esta reserva'
            ];
        }

        // Check if reservation can be modified
        if (!in_array($reservation['status'], ['PENDING', 'CONFIRMED'])) {
            return [
                'success' => false,
                'message' => 'Esta reserva no puede ser modificada'
            ];
        }

        // Validate dates
        if (!empty($data['start_at']) && !empty($data['end_at'])) {
            $startAt = new DateTime($data['start_at']);
            $endAt = new DateTime($data['end_at']);
            $now = new DateTime();

            if ($startAt < $now) {
                return [
                    'success' => false,
                    'message' => 'La fecha de inicio no puede ser en el pasado'
                ];
            }

            if ($endAt <= $startAt) {
                return [
                    'success' => false,
                    'message' => 'La fecha de fin debe ser posterior a la fecha de inicio'
                ];
            }

            // Check availability excluding current reservation
            $isAvailable = $this->reservationModel->checkAvailability(
                $reservation['id_facilities'],
                $data['id_vehicle_types'] ?? $reservation['id_vehicle_types'],
                $data['start_at'],
                $data['end_at'],
                $id
            );

            if (!$isAvailable) {
                return [
                    'success' => false,
                    'message' => 'No hay disponibilidad para el horario seleccionado'
                ];
            }
        }

        // Update reservation
        $result = $this->reservationModel->update($id, [
            'id_vehicle_types'    => $data['id_vehicle_types'] ?? $reservation['id_vehicle_types'],
            'vehicle_plate'       => $data['vehicle_plate'] ?? $reservation['vehicle_plate'],
            'vehicle_description' => $data['vehicle_description'] ?? $reservation['vehicle_description'],
            'start_at'            => $data['start_at'] ?? $reservation['start_at'],
            'end_at'              => $data['end_at'] ?? $reservation['end_at'],
            'notes'               => $data['notes'] ?? $reservation['notes']
        ]);

        if ($result) {
            // Recalculate price if dates changed
            if (!empty($data['start_at']) && !empty($data['end_at'])) {
                $newPrice = $this->reservationModel->calculatePrice(
                    $reservation['id_facilities'],
                    $data['id_vehicle_types'] ?? $reservation['id_vehicle_types'],
                    $data['start_at'],
                    $data['end_at']
                );
                $this->reservationModel->updatePrice($id, $newPrice);
            }

            return [
                'success' => true,
                'message' => 'Reserva actualizada exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al actualizar la reserva'
        ];
    }

    /**
     * Cancel reservation
     */
    public function cancel($id, $userId) {
        $reservation = $this->reservationModel->getById($id);
        
        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Reserva no encontrada'
            ];
        }

        // Check ownership
        if ($reservation['id_users'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para cancelar esta reserva'
            ];
        }

        // Check if reservation can be cancelled
        if (!in_array($reservation['status'], ['PENDING', 'CONFIRMED'])) {
            return [
                'success' => false,
                'message' => 'Esta reserva no puede ser cancelada'
            ];
        }

        $result = $this->reservationModel->delete($id);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Reserva cancelada exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al cancelar la reserva'
        ];
    }

    /**
     * Get all reservations (Admin)
     */
    public function getAll($limit = 50, $offset = 0, $filters = []) {
        $reservations = $this->reservationModel->getAll($limit, $offset, $filters);
        
        return [
            'success' => true,
            'reservations' => $reservations
        ];
    }

    /**
     * Calculate price for a reservation
     */
    public function calculatePrice($facilityId, $vehicleTypeId, $startAt, $endAt) {
        $price = $this->reservationModel->calculatePrice($facilityId, $vehicleTypeId, $startAt, $endAt);
        
        return [
            'success' => true,
            'price' => $price
        ];
    }

    /**
     * Check availability
     */
    public function checkAvailability($facilityId, $vehicleTypeId, $startAt, $endAt) {
        $isAvailable = $this->reservationModel->checkAvailability($facilityId, $vehicleTypeId, $startAt, $endAt);
        
        return [
            'success' => true,
            'available' => $isAvailable
        ];
    }
}
?>
