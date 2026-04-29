<?php
// Archivo de prueba simple - NO requiere base de datos
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'PHP esta funcionando correctamente',
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'script_filename' => $_SERVER['SCRIPT_FILENAME'],
    'request_uri' => $_SERVER['REQUEST_URI']
]);
?>
