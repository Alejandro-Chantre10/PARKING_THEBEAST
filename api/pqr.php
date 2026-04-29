<?php
/**
 * PQR API Endpoint
 * Handles PQR (Petitions, Complaints, Claims) operations
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($action) {
        case 'create':
            if ($method !== 'POST') {
                sendError('Method not allowed', 405);
            }
            
            $user = requireAuth();
            $data = getJsonInput();
            
            if (empty($data['type']) || empty($data['description'])) {
                sendError('Type and description are required', 400);
            }
            
            $validTypes = ['PETITION', 'COMPLAINT', 'CLAIM', 'SUGGESTION'];
            if (!in_array(strtoupper($data['type']), $validTypes)) {
                sendError('Invalid PQR type. Must be: PETITION, COMPLAINT, CLAIM, or SUGGESTION', 400);
            }
            
            $stmt = $db->prepare("
                INSERT INTO pqr (id_users, type, description, status, created_at) 
                VALUES (?, ?, ?, 'PENDING', NOW())
            ");
            $stmt->execute([
                $user['id_users'],
                strtoupper($data['type']),
                $data['description']
            ]);
            
            $pqrId = $db->lastInsertId();
            
            sendSuccess([
                'id_pqr' => $pqrId,
                'message' => 'PQR created successfully'
            ], 201);
            break;
            
        case 'list':
            if ($method !== 'GET') {
                sendError('Method not allowed', 405);
            }
            
            $user = requireAuth();
            
            $stmt = $db->prepare("
                SELECT id_pqr, type, description, status, response, created_at, updated_at
                FROM pqr 
                WHERE id_users = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user['id_users']]);
            $pqrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccess($pqrs);
            break;
            
        case 'detail':
            if ($method !== 'GET') {
                sendError('Method not allowed', 405);
            }
            
            $user = requireAuth();
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                sendError('PQR ID is required', 400);
            }
            
            $stmt = $db->prepare("
                SELECT id_pqr, type, description, status, response, created_at, updated_at
                FROM pqr 
                WHERE id_pqr = ? AND id_users = ?
            ");
            $stmt->execute([$id, $user['id_users']]);
            $pqr = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pqr) {
                sendError('PQR not found', 404);
            }
            
            sendSuccess($pqr);
            break;
            
        case 'update':
            if ($method !== 'PUT') {
                sendError('Method not allowed', 405);
            }
            
            $user = requireAuth();
            
            // Only admin can update PQR status
            if ($user['id_rol'] != 1) {
                sendError('Only administrators can update PQR status', 403);
            }
            
            $id = $_GET['id'] ?? null;
            $data = getJsonInput();
            
            if (!$id) {
                sendError('PQR ID is required', 400);
            }
            
            $validStatuses = ['PENDING', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];
            if (!empty($data['status']) && !in_array(strtoupper($data['status']), $validStatuses)) {
                sendError('Invalid status', 400);
            }
            
            $updateFields = [];
            $params = [];
            
            if (!empty($data['status'])) {
                $updateFields[] = 'status = ?';
                $params[] = strtoupper($data['status']);
            }
            
            if (!empty($data['response'])) {
                $updateFields[] = 'response = ?';
                $params[] = $data['response'];
            }
            
            if (empty($updateFields)) {
                sendError('No fields to update', 400);
            }
            
            $updateFields[] = 'updated_at = NOW()';
            $params[] = $id;
            
            $sql = "UPDATE pqr SET " . implode(', ', $updateFields) . " WHERE id_pqr = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            sendSuccess(['message' => 'PQR updated successfully']);
            break;
            
        case 'all':
            if ($method !== 'GET') {
                sendError('Method not allowed', 405);
            }
            
            $user = requireAuth();
            
            // Only admin can see all PQRs
            if ($user['id_rol'] != 1) {
                sendError('Only administrators can view all PQRs', 403);
            }
            
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            $stmt = $db->prepare("
                SELECT p.id_pqr, p.type, p.description, p.status, p.response, 
                       p.created_at, p.updated_at, u.full_name, u.email
                FROM pqr p
                JOIN users u ON p.id_users = u.id_users
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $pqrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countStmt = $db->query("SELECT COUNT(*) FROM pqr");
            $total = $countStmt->fetchColumn();
            
            sendSuccess([
                'data' => $pqrs,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        default:
            sendError('Invalid action', 400);
    }
    
} catch (PDOException $e) {
    error_log("PQR API Error: " . $e->getMessage());
    sendError('Database error occurred', 500);
} catch (Exception $e) {
    error_log("PQR API Error: " . $e->getMessage());
    sendError($e->getMessage(), 500);
}
