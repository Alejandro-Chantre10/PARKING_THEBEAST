<?php
/**
 * Payment Controller - Parking The Beasts
 * Handles payment-related requests
 * Updated to match parking_db schema
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
        if (empty($data['reservation_id']) || empty($data['user_id']) || empty($data['method'])) {
            return [
                'success' => false,
                'message' => 'Reserva, usuario y método de pago son requeridos'
            ];
        }

        // Get reservation
        $reservation = $this->reservationModel->getById($data['reservation_id']);
        
        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Reserva no encontrada'
            ];
        }

        // Check ownership
        if ($reservation['user_id'] != $data['user_id']) {
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
            'reservation_id' => $data['reservation_id'],
            'user_id'        => $data['user_id'],
            'amount'         => $reservation['price'],
            'currency'       => 'COP',
            'method'         => $data['method'],
            'status'         => 'PENDING'
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
        if ($payment['user_id'] != $userId) {
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

    /**
     * Get payment stats
     */
    public function getStats() {
        return [
            'success' => true,
            'stats' => [
                'total_revenue' => $this->paymentModel->sumByStatus('PAID'),
                'total_payments' => $this->paymentModel->countByStatus(),
                'pending' => $this->paymentModel->countByStatus('PENDING'),
                'paid' => $this->paymentModel->countByStatus('PAID'),
                'failed' => $this->paymentModel->countByStatus('FAILED')
            ]
        ];
    }
}
?>
