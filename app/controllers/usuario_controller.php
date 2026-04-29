<?php
require_once 'models/UserModel.php';

class UserController {
    private $userModel;

    public function __construct($db) {
        $this->userModel = new UserModel($db);
    }

    // Lógica para registrarse
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'role_id'   => 2,
                'full_name' => $_POST['full_name'],
                'email'     => $_POST['email'],
                'password'  => $_POST['password'],
                'phone'     => $_POST['phone']
            ];

            if ($this->userModel->create($data)) {
                header("Location: login.php?success=registered");
            } else {
                echo "Error al registrar usuario.";
            }
        }
    }

    // Lógica para iniciar sesión
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $user = $this->userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Iniciar sesión global
                session_start();
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role_code'];

                // Redirigir según rol
                if ($user['role_code'] === 'ADMIN') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
            } else {
                echo "Credenciales incorrectas o cuenta inactiva."; 
            }
        }
    }

    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php");
    }
}