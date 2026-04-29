<?php
/**
 * Facility Model - Parking The Beasts
 * Handles all facility-related database operations
 * Updated to match parking_db schema
 */

require_once __DIR__ . '/../../config/database.php';

class FacilityModel {
    private $db;
    private $table = 'facilities';

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Create a new facility
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (name, address, is_active) 
                VALUES (:name, :address, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':name'      => $data['name'],
            ':address'   => $data['address'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get facility by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get all active facilities
     */
    public function getActive() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all facilities
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update facility
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                    name = :name,
                    address = :address,
                    is_active = :is_active
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'        => $id,
            ':name'      => $data['name'],
            ':address'   => $data['address'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);
    }

    /**
     * Get facility with capacity info
     */
    public function getWithCapacity($id) {
        $sql = "SELECT f.*, 
                       vt.id, vt.code as vehicle_type_code, vt.name as vehicle_type_name,
                       pc.capacity
                FROM {$this->table} f
                JOIN parking_capacity pc ON f.id = pc.facility_id
                JOIN vehicle_types vt ON pc.vehicle_type_id = vt.id
                WHERE f.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    /**
     * Update capacity for a facility and vehicle type
     */
    public function updateCapacity($facilityId, $vehicleTypeId, $capacity) {
        $sql = "INSERT INTO parking_capacity (facility_id, vehicle_type_id, capacity)
                VALUES (:facility_id, :vehicle_type_id, :capacity)
                ON DUPLICATE KEY UPDATE capacity = :capacity2";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':facility_id'     => $facilityId,
            ':vehicle_type_id' => $vehicleTypeId,
            ':capacity'        => $capacity,
            ':capacity2'       => $capacity
        ]);
    }

    /**
     * Get current occupancy for a facility
     */
    public function getOccupancy($facilityId) {
        $sql = "SELECT 
                    vt.id as vehicle_type_id, vt.name as vehicle_type_name,
                    pc.capacity,
                    COUNT(r.id) as occupied
                FROM vehicle_types vt
                LEFT JOIN parking_capacity pc ON vt.id = pc.vehicle_type_id 
                    AND pc.facility_id = :facility_id
                LEFT JOIN reservations r ON vt.id = r.vehicle_type_id 
                    AND r.facility_id = :facility_id2
                    AND r.status IN ('CONFIRMED')
                    AND NOW() BETWEEN r.start_at AND r.end_at
                GROUP BY vt.id, vt.name, pc.capacity";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':facility_id'  => $facilityId,
            ':facility_id2' => $facilityId
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Get capacity summary
     */
    public function getCapacitySummary($facilityId) {
        $sql = "SELECT 
                    vt.id as vehicle_type_id, 
                    vt.name as vehicle_type_name,
                    vt.code as vehicle_type_code,
                    COALESCE(pc.capacity, 0) as capacity
                FROM vehicle_types vt
                LEFT JOIN parking_capacity pc ON vt.id = pc.vehicle_type_id 
                    AND pc.facility_id = :facility_id
                ORDER BY vt.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':facility_id' => $facilityId]);
        return $stmt->fetchAll();
    }
}
?>
