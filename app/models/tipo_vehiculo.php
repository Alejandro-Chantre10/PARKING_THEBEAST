<?php
/**
 * Vehicle Type Model - Parking The Beasts
 * Handles all vehicle type-related database operations
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
        $sql = "SELECT * FROM {$this->table} WHERE id_vehicle_types = :id";
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
                WHERE id_vehicle_types = :id";
        
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
        $sql = "DELETE FROM {$this->table} WHERE id_vehicle_types = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>
