<?php
/**
 * Vehicle Type Model - Parking The Beasts
 * Handles all vehicle type-related database operations
 * Updated to match parking_db schema
 */

require_once __DIR__ . '/../../config/database.php';

class VehicleTypeModel {
    private $db;
    private $table = 'vehicle_types';

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Create a new vehicle type
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (code, name) VALUES (:code, :name)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':code' => strtoupper($data['code']),
            ':name' => $data['name']
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get vehicle type by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get vehicle type by code
     */
    public function getByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE code = :code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => strtoupper($code)]);
        return $stmt->fetch();
    }

    /**
     * Get all vehicle types
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update vehicle type
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                    code = :code,
                    name = :name
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'   => $id,
            ':code' => strtoupper($data['code']),
            ':name' => $data['name']
        ]);
    }

    /**
     * Delete vehicle type
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get vehicle types with rates for a facility
     */
    public function getWithRates($facilityId) {
        $sql = "SELECT vt.*, 
                       r.price_per_hour, r.min_minutes, r.grace_minutes
                FROM {$this->table} vt
                LEFT JOIN rates r ON vt.id = r.vehicle_type_id 
                    AND r.facility_id = :facility_id 
                    AND r.is_active = 1
                ORDER BY vt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':facility_id' => $facilityId]);
        return $stmt->fetchAll();
    }
}
?>
