<?php
/**
 * Reservation Model - Parking The Beasts
 * Handles all reservation-related database operations
 */

require_once __DIR__ . '/../../config/database.php';

class ReservationModel {
    private $db;
    private $table = 'reservations';

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Create a new reservation
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (id_facilities, id_users, id_vehicle_types, vehicle_plate, vehicle_description, 
                 start_at, end_at, status, price, notes) 
                VALUES 
                (:id_facilities, :id_users, :id_vehicle_types, :vehicle_plate, :vehicle_description,
                 :start_at, :end_at, :status, :price, :notes)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':id_facilities'       => $data['id_facilities'] ?? 1,
            ':id_users'            => $data['id_users'],
            ':id_vehicle_types'    => $data['id_vehicle_types'],
            ':vehicle_plate'       => strtoupper($data['vehicle_plate']),
            ':vehicle_description' => $data['vehicle_description'] ?? null,
            ':start_at'            => $data['start_at'],
            ':end_at'              => $data['end_at'],
            ':status'              => $data['status'] ?? 'PENDING',
            ':price'               => $data['price'] ?? 0,
            ':notes'               => $data['notes'] ?? null
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get reservation by ID
     */
    public function getById($id) {
        $sql = "SELECT r.*, 
                       f.name as facility_name, f.address as facility_address,
                       u.full_name as user_name, u.email as user_email,
                       vt.code as vehicle_type_code, vt.name as vehicle_type_name
                FROM {$this->table} r
                JOIN facilities f ON r.id_facilities = f.id_facilities
                JOIN users u ON r.id_users = u.id_users
                JOIN vehicle_types vt ON r.id_vehicle_types = vt.id_vehicle_types
                WHERE r.id_reservations = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get reservations by user ID
     */
    public function getByUserId($userId, $status = null) {
        $sql = "SELECT r.*, 
                       f.name as facility_name,
                       vt.name as vehicle_type_name, vt.code as vehicle_type_code
                FROM {$this->table} r
                JOIN facilities f ON r.id_facilities = f.id_facilities
                JOIN vehicle_types vt ON r.id_vehicle_types = vt.id_vehicle_types
                WHERE r.id_users = :user_id";
        
        if ($status) {
            $sql .= " AND r.status = :status";
        }
        
        $sql .= " ORDER BY r.start_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $params = [':user_id' => $userId];
        if ($status) {
            $params[':status'] = $status;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update reservation
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                    id_vehicle_types = :id_vehicle_types,
                    vehicle_plate = :vehicle_plate,
                    vehicle_description = :vehicle_description,
                    start_at = :start_at,
                    end_at = :end_at,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id_reservations = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'                  => $id,
            ':id_vehicle_types'    => $data['id_vehicle_types'],
            ':vehicle_plate'       => strtoupper($data['vehicle_plate']),
            ':vehicle_description' => $data['vehicle_description'] ?? null,
            ':start_at'            => $data['start_at'],
            ':end_at'              => $data['end_at'],
            ':notes'               => $data['notes'] ?? null
        ]);
    }

    /**
     * Update reservation status
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id_reservations = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }

    /**
     * Update reservation price
     */
    public function updatePrice($id, $price) {
        $sql = "UPDATE {$this->table} SET price = :price, updated_at = NOW() WHERE id_reservations = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':price' => $price]);
    }

    /**
     * Delete reservation (soft delete - change status to CANCELLED)
     */
    public function delete($id) {
        return $this->updateStatus($id, 'CANCELLED');
    }

    /**
     * Get all reservations (Admin)
     */
    public function getAll($limit = 50, $offset = 0, $filters = []) {
        $sql = "SELECT r.*, 
                       f.name as facility_name,
                       u.full_name as user_name,
                       vt.name as vehicle_type_name
                FROM {$this->table} r
                JOIN facilities f ON r.id_facilities = f.id_facilities
                JOIN users u ON r.id_users = u.id_users
                JOIN vehicle_types vt ON r.id_vehicle_types = vt.id_vehicle_types
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['facility_id'])) {
            $sql .= " AND r.id_facilities = :facility_id";
            $params[':facility_id'] = $filters['facility_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND r.start_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND r.end_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
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
     * Calculate price based on duration and rates
     */
    public function calculatePrice($facilityId, $vehicleTypeId, $startAt, $endAt) {
        // Get rate for this facility and vehicle type
        $sql = "SELECT price_per_hour, min_minutes, rounding_minutes, grace_minutes 
                FROM rates 
                WHERE id_facilities = :facility_id 
                AND id_vehicle_types = :vehicle_type_id 
                AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':facility_id'     => $facilityId,
            ':vehicle_type_id' => $vehicleTypeId
        ]);
        $rate = $stmt->fetch();
        
        if (!$rate) {
            return 0;
        }
        
        // Calculate duration in minutes
        $start = new DateTime($startAt);
        $end = new DateTime($endAt);
        $diff = $start->diff($end);
        $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        // Apply grace period
        if ($totalMinutes <= $rate['grace_minutes']) {
            return 0;
        }
        
        // Apply minimum minutes
        if ($totalMinutes < $rate['min_minutes']) {
            $totalMinutes = $rate['min_minutes'];
        }
        
        // Round up to nearest rounding interval
        $roundingMinutes = $rate['rounding_minutes'];
        $totalMinutes = ceil($totalMinutes / $roundingMinutes) * $roundingMinutes;
        
        // Calculate price
        $hours = $totalMinutes / 60;
        $price = $hours * $rate['price_per_hour'];
        
        return round($price, 2);
    }

    /**
     * Check availability for a time slot
     */
    public function checkAvailability($facilityId, $vehicleTypeId, $startAt, $endAt, $excludeReservationId = null) {
        // Get capacity
        $sql = "SELECT capacity FROM parking_capacity 
                WHERE id_facilities = :facility_id AND id_vehicle_types = :vehicle_type_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':facility_id'     => $facilityId,
            ':vehicle_type_id' => $vehicleTypeId
        ]);
        $capacity = $stmt->fetch();
        
        if (!$capacity) {
            return false;
        }
        
        // Count existing reservations for the time slot
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE id_facilities = :facility_id 
                AND id_vehicle_types = :vehicle_type_id
                AND status IN ('PENDING', 'CONFIRMED')
                AND ((start_at <= :start_at AND end_at > :start_at)
                     OR (start_at < :end_at AND end_at >= :end_at)
                     OR (start_at >= :start_at AND end_at <= :end_at))";
        
        if ($excludeReservationId) {
            $sql .= " AND id_reservations != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [
            ':facility_id'     => $facilityId,
            ':vehicle_type_id' => $vehicleTypeId,
            ':start_at'        => $startAt,
            ':end_at'          => $endAt
        ];
        if ($excludeReservationId) {
            $params[':exclude_id'] = $excludeReservationId;
        }
        $stmt->execute($params);
        $count = $stmt->fetch();
        
        return $count['count'] < $capacity['capacity'];
    }
}
?>
