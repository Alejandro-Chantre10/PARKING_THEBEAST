<?php
/**
 * Payment Model - Parking The Beasts
 * Handles all payment-related database operations
 * Updated to match parking_db schema
 */

require_once __DIR__ . '/../../config/database.php';

class PaymentModel {
    private $db;
    private $table = 'payments';

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Create a new payment
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (reservation_id, user_id, amount, currency, method, status, gateway_reference) 
                VALUES 
                (:reservation_id, :user_id, :amount, :currency, :method, :status, :gateway_reference)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':reservation_id'    => $data['reservation_id'],
            ':user_id'           => $data['user_id'],
            ':amount'            => $data['amount'],
            ':currency'          => $data['currency'] ?? 'COP',
            ':method'            => $data['method'],
            ':status'            => $data['status'] ?? 'PENDING',
            ':gateway_reference' => $data['gateway_reference'] ?? null
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get payment by ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                       r.vehicle_plate, r.start_at, r.end_at,
                       u.full_name as user_name, u.email as user_email
                FROM {$this->table} p
                JOIN reservations r ON p.reservation_id = r.id
                JOIN users u ON p.user_id = u.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get payments by reservation ID
     */
    public function getByReservationId($reservationId) {
        $sql = "SELECT * FROM {$this->table} WHERE reservation_id = :reservation_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reservation_id' => $reservationId]);
        return $stmt->fetchAll();
    }

    /**
     * Get payments by user ID
     */
    public function getByUserId($userId) {
        $sql = "SELECT p.*, 
                       r.vehicle_plate, r.start_at as reservation_start
                FROM {$this->table} p
                JOIN reservations r ON p.reservation_id = r.id
                WHERE p.user_id = :user_id
                ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Update payment status
     */
    public function updateStatus($id, $status, $gatewayReference = null) {
        $sql = "UPDATE {$this->table} SET status = :status";
        
        if ($gatewayReference) {
            $sql .= ", gateway_reference = :gateway_reference";
        }
        
        if ($status === 'PAID') {
            $sql .= ", paid_at = NOW()";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $params = [':id' => $id, ':status' => $status];
        if ($gatewayReference) {
            $params[':gateway_reference'] = $gatewayReference;
        }
        return $stmt->execute($params);
    }

    /**
     * Process payment (mark as PAID and update reservation)
     */
    public function processPayment($id, $gatewayReference = null) {
        $this->db->beginTransaction();
        
        try {
            // Update payment status
            $this->updateStatus($id, 'PAID', $gatewayReference);
            
            // Get payment details
            $payment = $this->getById($id);
            
            // Update reservation status to CONFIRMED
            $sql = "UPDATE reservations SET status = 'CONFIRMED', updated_at = NOW() 
                    WHERE id = :reservation_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':reservation_id' => $payment['reservation_id']]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get all payments (Admin)
     */
    public function getAll($limit = 50, $offset = 0, $filters = []) {
        $sql = "SELECT p.*, 
                       r.vehicle_plate,
                       u.full_name as user_name
                FROM {$this->table} p
                JOIN reservations r ON p.reservation_id = r.id
                JOIN users u ON p.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['method'])) {
            $sql .= " AND p.method = :method";
            $params[':method'] = $filters['method'];
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Sum total payments by status
     */
    public function sumByStatus($status = 'PAID') {
        $sql = "SELECT SUM(amount) as total FROM {$this->table} WHERE status = :status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => $status]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    /**
     * Count payments by status
     */
    public function countByStatus($status = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($status) {
            $sql .= " WHERE status = :status";
        }
        $stmt = $this->db->prepare($sql);
        if ($status) {
            $stmt->execute([':status' => $status]);
        } else {
            $stmt->execute();
        }
        $result = $stmt->fetch();
        return $result['total'];
    }
}
?>
