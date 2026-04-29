<?php
/**
 * User Controller - Parking The Beasts
 * Handles user-related requests (registration, login, profile)
 * Updated to match parking_db schema
 */

require_once __DIR__ . '/../models/usuario.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    /**
     * Register a new user
     */
    public function register($data) {
        // Validate required fields
        if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'Nombre, email y contraseña son requeridos'
            ];
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email inválido'
            ];
        }

        // Check if email already exists
        if ($this->userModel->emailExists($data['email'])) {
            return [
                'success' => false,
                'message' => 'El email ya está registrado'
            ];
        }

        // Validate password length
        if (strlen($data['password']) < 6) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }

        // Create user
        $userId = $this->userModel->create([
            'role_id'   => 2, // Default USER role
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'phone'     => $data['phone'] ?? null
        ]);

        if ($userId) {
            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'user_id' => $userId
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al registrar usuario'
        ];
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email y contraseña son requeridos'
            ];
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ];
        }

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role']       = $user['role_code'];

        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => [
                'id'        => $user['id'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'phone'     => $user['phone'],
                'role'      => $user['role_code'],
                'role_name' => $user['role_name']
            ]
        ];
    }

    /**
     * Logout user
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ];
    }

    /**
     * Get current user profile
     */
    public function getProfile($userId) {
        $user = $this->userModel->getById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }

        return [
            'success' => true,
            'user' => $user
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        if (empty($data['full_name'])) {
            return [
                'success' => false,
                'message' => 'El nombre es requerido'
            ];
        }

        $result = $this->userModel->update($userId, [
            'full_name' => $data['full_name'],
            'phone'     => $data['phone'] ?? null
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Perfil actualizado exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al actualizar perfil'
        ];
    }

    /**
     * Update user email
     */
    public function updateEmail($userId, $newEmail) {
        if (empty($newEmail)) {
            return [
                'success' => false,
                'message' => 'El email es requerido'
            ];
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email inválido'
            ];
        }

        // Check if email already exists (excluding current user)
        if ($this->userModel->emailExists($newEmail, $userId)) {
            return [
                'success' => false,
                'message' => 'El email ya está en uso'
            ];
        }

        $result = $this->userModel->updateEmail($userId, $newEmail);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Email actualizado exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al actualizar email'
        ];
    }

    /**
     * Update user password
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        if (empty($currentPassword) || empty($newPassword)) {
            return [
                'success' => false,
                'message' => 'Contraseña actual y nueva son requeridas'
            ];
        }

        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => 'La nueva contraseña debe tener al menos 6 caracteres'
            ];
        }

        // Verify current password
        if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
            return [
                'success' => false,
                'message' => 'Contraseña actual incorrecta'
            ];
        }

        $result = $this->userModel->updatePassword($userId, $newPassword);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al actualizar contraseña'
        ];
    }

    /**
     * Deactivate user account
     */
    public function deactivateAccount($userId) {
        $result = $this->userModel->deactivate($userId);

        if ($result) {
            // Logout user
            $this->logout();
            
            return [
                'success' => true,
                'message' => 'Cuenta desactivada exitosamente'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al desactivar cuenta'
        ];
    }

    /**
     * Get all users (Admin only)
     */
    public function getAllUsers($limit = 50, $offset = 0) {
        $users = $this->userModel->getAll($limit, $offset);
        $total = $this->userModel->countAll();
        
        return [
            'success' => true,
            'users' => $users,
            'total' => $total
        ];
    }
}
?>
