<?php
/**
 * Rate Model - Parking The Beasts
 * Handles all rate/pricing-related database operations
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
                (id_facilities, id_vehicle_types, price_per_hour, min_minutes, rounding_minutes, grace_minutes, is_active) 
                VALUES 
                (:id_facilities, :id_vehicle_types, :price_per_hour, :min_minutes, :rounding_minutes, :grace_minutes, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':id_facilities'     => $data['id_facilities'],
            ':id_vehicle_types'  => $data['id_vehicle_types'],
            ':price_per_hour'    => $data['price_per_hour'],
            ':min_minutes'       => $data['min_minutes'] ?? 15,
            ':rounding_minutes'  => $data['rounding_minutes'] ?? 15,
            ':grace_minutes'     => $data['grace_minutes'] ?? 10,
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
                JOIN facilities f ON r.id_facilities = f.id_facilities
                JOIN vehicle_types vt ON r.id_vehicle_types = vt.id_vehicle_types
                WHERE r.id_rates = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get rate by facility and vehicle type
     */
    public function getByFacilityAndVehicleType($facilityId, $vehicleTypeId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE id_facilities = :facility_id 
                AND id_vehicle_types = :vehicle_type_id 
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
                JOIN vehicle_types vt ON r.id_vehicle_types = vt.id_vehicle_types
                WHERE r.id_facilities = :facility_id
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
                JOIN facilities f ON r.id_facilities = f.id_facilities
                JOIN vehicle_types vt ON r.id_vehicle_types = vt.id_vehicle_types
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
                JOIN facilities f ON r.id_facilities = f.id_facilities
                JOIN vehicle_types vt ON r.id_vehicle_types = vt.id_vehicle_types
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
                WHERE id_rates = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'               => $id,
            ':price_per_hour'   => $data['price_per_hour'],
            ':min_minutes'      => $data['min_minutes'] ?? 15,
            ':rounding_minutes' => $data['rounding_minutes'] ?? 15,
            ':grace_minutes'    => $data['grace_minutes'] ?? 10,
            ':is_active'        => $data['is_active'] ?? 1
        ]);
    }

    /**
     * Deactivate rate
     */
    public function deactivate($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE id_rates = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>
