<?php
class UserModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Crear un nuevo usuario (Registro)
    public function create($data) {
        $sql = "INSERT INTO users (role_id, full_name, email, password_hash, phone) 
                VALUES (:role_id, :full_name, :email, :password_hash, :phone)";
        
        $stmt = $this->db->prepare($sql);
        
        // Encriptar contraseña antes de guardar
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        return $stmt->execute([
            ':role_id'      => $data['role_id'], 
            ':full_name'    => $data['full_name'],
            ':email'        => $data['email'],
            ':password_hash' => $hashedPassword,
            ':phone'        => $data['phone'] ?? null
        ]);
    }

    // Buscar usuario por email (para Login)
    public function findByEmail($email) {
        $sql = "SELECT u.*, r.code as role_code 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = :email AND u.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener perfil completo
    public function getById($id) {
        $sql = "SELECT id, full_name, email, phone, role_id, created_at FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}