<?php
/**
 * Rate Model - Parking The Beasts
 * Handles all rate/pricing-related database operations
 * Updated to match parking_db schema
 */

require_once __DIR__ . '/../../config/database.php';

class RateModel {
    private $db;
    private $table = 'rates';

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Create a new rate
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (facility_id, vehicle_type_id, price_per_hour, min_minutes, rounding_minutes, grace_minutes, is_active) 
                VALUES 
                (:facility_id, :vehicle_type_id, :price_per_hour, :min_minutes, :rounding_minutes, :grace_minutes, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':facility_id'       => $data['facility_id'],
            ':vehicle_type_id'   => $data['vehicle_type_id'],
            ':price_per_hour'    => $data['price_per_hour'],
            ':min_minutes'       => $data['min_minutes'] ?? 60,
            ':rounding_minutes'  => $data['rounding_minutes'] ?? 60,
            ':grace_minutes'     => $data['grace_minutes'] ?? 0,
            ':is_active'         => $data['is_active'] ?? 1
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get rate by ID
     */
    public function getById($id) {
        $sql = "SELECT r.*, 
                       f.name as facility_name,
                       vt.name as vehicle_type_name, vt.code as vehicle_type_code
                FROM {$this->table} r
                JOIN facilities f ON r.facility_id = f.id
                JOIN vehicle_types vt ON r.vehicle_type_id = vt.id
                WHERE r.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get rate by facility and vehicle type
     */
    public function getByFacilityAndVehicleType($facilityId, $vehicleTypeId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE facility_id = :facility_id 
                AND vehicle_type_id = :vehicle_type_id 
                AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':facility_id'     => $facilityId,
            ':vehicle_type_id' => $vehicleTypeId
        ]);
        return $stmt->fetch();
    }

    /**
     * Get all rates for a facility
     */
    public function getByFacilityId($facilityId) {
        $sql = "SELECT r.*, 
                       vt.name as vehicle_type_name, vt.code as vehicle_type_code
                FROM {$this->table} r
                JOIN vehicle_types vt ON r.vehicle_type_id = vt.id
                WHERE r.facility_id = :facility_id
                ORDER BY vt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':facility_id' => $facilityId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all active rates
     */
    public function getActive() {
        $sql = "SELECT r.*, 
                       f.name as facility_name,
                       vt.name as vehicle_type_name, vt.code as vehicle_type_code
                FROM {$this->table} r
                JOIN facilities f ON r.facility_id = f.id
                JOIN vehicle_types vt ON r.vehicle_type_id = vt.id
                WHERE r.is_active = 1
                ORDER BY f.name, vt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all rates
     */
    public function getAll() {
        $sql = "SELECT r.*, 
                       f.name as facility_name,
                       vt.name as vehicle_type_name, vt.code as vehicle_type_code
                FROM {$this->table} r
                JOIN facilities f ON r.facility_id = f.id
                JOIN vehicle_types vt ON r.vehicle_type_id = vt.id
                ORDER BY f.name, vt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update rate
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                    price_per_hour = :price_per_hour,
                    min_minutes = :min_minutes,
                    rounding_minutes = :rounding_minutes,
                    grace_minutes = :grace_minutes,
                    is_active = :is_active
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'               => $id,
            ':price_per_hour'   => $data['price_per_hour'],
            ':min_minutes'      => $data['min_minutes'] ?? 60,
            ':rounding_minutes' => $data['rounding_minutes'] ?? 60,
            ':grace_minutes'    => $data['grace_minutes'] ?? 0,
            ':is_active'        => $data['is_active'] ?? 1
        ]);
    }

    /**
     * Deactivate rate
     */
    public function deactivate($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Calculate price for a duration
     */
    public function calculatePrice($facilityId, $vehicleTypeId, $startAt, $endAt) {
        $rate = $this->getByFacilityAndVehicleType($facilityId, $vehicleTypeId);
        
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
        if ($roundingMinutes > 0) {
            $totalMinutes = ceil($totalMinutes / $roundingMinutes) * $roundingMinutes;
        }
        
        // Calculate price
        $hours = $totalMinutes / 60;
        $price = $hours * $rate['price_per_hour'];
        
        return round($price, 2);
    }
}
?>
