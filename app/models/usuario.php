<?php
/**
 * User Model - Parking The Beasts
 * Handles all user-related database operations
 * Updated to match parking_db schema
 */

require_once __DIR__ . '/../../config/database.php';

class UserModel {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = getDBConnection();
    }

    /**
     * Create a new user (Registration)
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (role_id, full_name, email, password_hash, phone) 
                VALUES (:role_id, :full_name, :email, :password_hash, :phone)";
        
        $stmt = $this->db->prepare($sql);
        
        // Hash password before saving
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $result = $stmt->execute([
            ':role_id'       => $data['role_id'] ?? 2, // Default to USER role
            ':full_name'     => $data['full_name'],
            ':email'         => $data['email'],
            ':password_hash' => $hashedPassword,
            ':phone'         => $data['phone'] ?? null
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Find user by email (for Login)
     */
    public function findByEmail($email) {
        $sql = "SELECT u.*, r.code as role_code, r.name as role_name 
                FROM {$this->table} u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = :email AND u.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.role_id, 
                       u.created_at, u.is_active, r.code as role_code, r.name as role_name
                FROM {$this->table} u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Update user profile
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                    full_name = :full_name,
                    phone = :phone,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'        => $id,
            ':full_name' => $data['full_name'],
            ':phone'     => $data['phone'] ?? null
        ]);
    }

    /**
     * Update user email
     */
    public function updateEmail($id, $email) {
        $sql = "UPDATE {$this->table} SET 
                    email = :email,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'    => $id,
            ':email' => $email
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE {$this->table} SET 
                    password_hash = :password_hash,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        return $stmt->execute([
            ':id'            => $id,
            ':password_hash' => $hashedPassword
        ]);
    }

    /**
     * Verify current password
     */
    public function verifyPassword($id, $password) {
        $sql = "SELECT password_hash FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        
        if ($user) {
            return password_verify($password, $user['password_hash']);
        }
        return false;
    }

    /**
     * Deactivate user account
     */
    public function deactivate($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email";
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }
        $stmt = $this->db->prepare($sql);
        $params = [':email' => $email];
        if ($excludeId) {
            $params[':exclude_id'] = $excludeId;
        }
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }

    /**
     * Get all users (Admin)
     */
    public function getAll($limit = 50, $offset = 0) {
        $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.is_active, 
                       u.created_at, r.name as role_name
                FROM {$this->table} u 
                JOIN roles r ON u.role_id = r.id 
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count total users
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
}
?>
