<?php
/**
 * Payment Controller - Parking The Beasts
 * Handles payment-related requests
 */

require_once __DIR__ . '/../models/pago.php';
require_once __DIR__ . '/../models/reserva.php';

class PaymentController {
    private $paymentModel;
    private $reservationModel;

    public function __construct() {
        $this->paymentModel = new PaymentModel();
        $this->reservationModel = new ReservationModel();
    }

    /**
     * Create a payment for a reservation
     */
    public function create($data) {
        // Validate required fields
        if (empty($data['id_reservations']) || empty($data['id_users']) || empty($data['method'])) {
            return [
                'success' => false,
                'message' => 'Reserva, usuario y método de pago son requeridos'
            ];
        }

        // Get reservation
        $reservation = $this->reservationModel->getById($data['id_reservations']);
        
        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Reserva no encontrada'
            ];
        }

        // Check ownership
        if ($reservation['id_users'] != $data['id_users']) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para pagar esta reserva'
            ];
        }

        // Check if reservation can be paid
        if ($reservation['status'] !== 'PENDING') {
            return [
                'success' => false,
                'message' => 'Esta reserva ya fue procesada'
            ];
        }

        // Create payment
        $paymentId = $this->paymentModel->create([
            'id_reservations' => $data['id_reservations'],
            'id_users'        => $data['id_users'],
            'amount'          => $reservation['price'],
            'currency'        => 'COP',
            'method'          => $data['method'],
            'status'          => 'PENDING'
        ]);

        if ($paymentId) {
            return [
                'success' => true,
                'message' => 'Pago creado exitosamente',
                'payment_id' => $paymentId,
                'amount' => $reservation['price']
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al crear el pago'
        ];
    }

    /**
     * Process payment (simulate payment gateway)
     */
    public function processPayment($paymentId, $userId) {
        $payment = $this->paymentModel->getById($paymentId);
        
        if (!$payment) {
            return [
                'success' => false,
                'message' => 'Pago no encontrado'
            ];
        }

        // Check ownership
        if ($payment['id_users'] != $userId) {
            return [
                'success' => false,
                'message' => 'No tienes permiso para procesar este pago'
            ];
        }

        // Check if payment is pending
        if ($payment['status'] !== 'PENDING') {
            return [
                'success' => false,
                'message' => 'Este pago ya fue procesado'
            ];
        }

        // Simulate payment gateway (generate reference)
        $gatewayReference = 'PTB-' . date('Ymd') . '-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT);

        // Process payment
        $result = $this->paymentModel->processPayment($paymentId, $gatewayReference);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Pago procesado exitosamente',
                'reference' => $gatewayReference
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al procesar el pago'
        ];
    }

    /**
     * Get payment by ID
     */
    public function getById($id) {
        $payment = $this->paymentModel->getById($id);
        
        if (!$payment) {
            return [
                'success' => false,
                'message' => 'Pago no encontrado'
            ];
        }

        return [
            'success' => true,
            'payment' => $payment
        ];
    }

    /**
     * Get payments for a user
     */
    public function getByUserId($userId) {
        $payments = $this->paymentModel->getByUserId($userId);
        
        return [
            'success' => true,
            'payments' => $payments
        ];
    }

    /**
     * Get payments for a reservation
     */
    public function getByReservationId($reservationId) {
        $payments = $this->paymentModel->getByReservationId($reservationId);
        
        return [
            'success' => true,
            'payments' => $payments
        ];
    }

    /**
     * Get all payments (Admin)
     */
    public function getAll($limit = 50, $offset = 0, $filters = []) {
        $payments = $this->paymentModel->getAll($limit, $offset, $filters);
        
        return [
            'success' => true,
            'payments' => $payments
        ];
    }
}
?>
