<?php
/**
 * API Helpers - Parking The Beasts
 * Common functions for API endpoints
 */

// Error handler to return JSON instead of HTML
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $errstr,
        'debug' => [
            'file' => basename($errfile),
            'line' => $errline
        ]
    ]);
    exit();
}

// Exception handler to return JSON
function jsonExceptionHandler($exception) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Excepción: ' . $exception->getMessage(),
        'debug' => [
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine()
        ]
    ]);
    exit();
}

// Set error handlers
set_error_handler('jsonErrorHandler');
set_exception_handler('jsonExceptionHandler');

// Disable HTML error output
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);

// Set headers for JSON API
function setApiHeaders() {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Send error response
function sendError($message, $statusCode = 400) {
    sendResponse(['success' => false, 'message' => $message], $statusCode);
}

// Get JSON input
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

// Check if user is logged in
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendError('No autorizado', 401);
    }
    
    return $_SESSION['user_id'];
}

// Check if user is admin
function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendError('No autorizado', 401);
    }
    
    if ($_SESSION['role'] !== 'ADMIN') {
        sendError('Acceso denegado', 403);
    }
    
    return $_SESSION['user_id'];
}

// Get current user session
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['role'] ?? ''
        ];
    }
    
    return null;
}

// Validate required fields
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        sendError('Campos requeridos: ' . implode(', ', $missing));
    }
}

// Sanitize input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>
