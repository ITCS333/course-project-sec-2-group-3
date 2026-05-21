<?php
/**
 * Resources API
 */
 
// ============================================================================
// HEADERS
// ============================================================================
 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
 
// ============================================================================
// HELPER FUNCTIONS  (must be defined before any executable code that calls them)
// ============================================================================
 
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if (!is_array($data)) {
        $data = ['success' => false, 'message' => $data];
    }
    echo json_encode($data);
    exit();
}
 
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}
 
function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
 
// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================
 
function getAllResources($db, $search, $sort, $order) {
    $sql    = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];
 
    if ($search && !empty($search)) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
 
    $allowedSort = ['title', 'created_at'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'created_at';
    }
 
    $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
    $sql  .= " ORDER BY $sort $order";
 
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    sendResponse(['success' => true, 'data' => $resources], 200);
}
 
function getResourceById($db, $resourceId) {
    if (!$resourceId || $resourceId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
 
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($resource) {
        sendResponse(['success' => true, 'data' => $resource], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
}
 
function createResource($db, $data) {
    if (empty($data['title']) || empty($data['link'])) {
        sendResponse(['success' => false, 'message' => 'Title and link are required'], 400);
    }
 
    $title       = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link        = trim($data['link']);
 
    if (!validateUrl($link)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL format'], 400);
    }
 
    $stmt    = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    $success = $stmt->execute([$title, $description, $link]);
 
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully',
            'id'      => (int)$db->lastInsertId()
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create resource'], 500);
    }
}
 
function updateResource($db, $data) {
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID is required'], 400);
    }
 
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['id']]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
 
    $updates = [];
    $params  = [];
 
    if (isset($data['title'])) {
        $updates[] = "title = ?";
        $params[]  = sanitizeInput($data['title']);
    }
 
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[]  = sanitizeInput($data['description']);
    }
 
    if (isset($data['link'])) {
        if (!validateUrl($data['link'])) {
            sendResponse(['success' => false, 'message' => 'Invalid URL format'], 400);
        }
        $updates[] = "link = ?";
        $params[]  = trim($data['link']);
    }
 
    if (empty($updates)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }
 
    $params[] = $data['id'];
    $sql      = "UPDATE resources SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt     = $db->prepare($sql);
    $success  = $stmt->execute($params);
 
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Resource updated successfully'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update resource'], 500);
    }
}
 
function deleteResource($db, $resourceId) {
    if (!$resourceId || $resourceId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
 
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$resourceId]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
 
    $stmt    = $db->prepare("DELETE FROM resources WHERE id = ?");
    $success = $stmt->execute([$resourceId]);
 
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Resource deleted successfully'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete resource'], 500);
    }
}
 
// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================
 
function getCommentsByResourceId($db, $resourceId) {
    if (!$resourceId || $resourceId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
 
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    sendResponse(['success' => true, 'data' => $comments], 200);
}
 
function createComment($db, $data) {
    if (empty($data['resource_id']) || empty($data['text'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID and comment text are required'], 400);
    }
 
    if (!is_numeric($data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
 
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['resource_id']]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
 
    $resource_id = (int)$data['resource_id'];
    $author      = isset($data['author']) ? sanitizeInput($data['author']) : 'Anonymous';
    $text        = sanitizeInput($data['text']);
 
    $stmt    = $db->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
    $success = $stmt->execute([$resource_id, $author, $text]);
 
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Comment added successfully',
            'id'      => (int)$db->lastInsertId()
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to add comment'], 500);
    }
}
 
function deleteComment($db, $commentId) {
    if (!$commentId || $commentId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid comment ID'], 400);
    }
 
    $check = $db->prepare("SELECT id FROM comments_resource WHERE id = ?");
    $check->execute([$commentId]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
    }
 
    $stmt    = $db->prepare("DELETE FROM comments_resource WHERE id = ?");
    $success = $stmt->execute([$commentId]);
 
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete comment'], 500);
    }
}
 
// ============================================================================
// INITIALIZATION  (executable code starts here, after all function definitions)
// ============================================================================
 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
 
require_once __DIR__ . '/../../common/db.php';
 
try {
    $db = getDBConnection();
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()], 500);
}
 
$method      = $_SERVER['REQUEST_METHOD'];
$rawData     = file_get_contents('php://input');
$data        = json_decode($rawData, true) ?? [];
$action      = $_GET['action']       ?? null;
$id          = isset($_GET['id'])          ? (int)$_GET['id']          : null;
$resource_id = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;
$comment_id  = isset($_GET['comment_id'])  ? (int)$_GET['comment_id']  : null;
$search      = $_GET['search'] ?? null;
$sort        = $_GET['sort']   ?? 'created_at';
$order       = $_GET['order']  ?? 'desc';
 
// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================
 
try {
    if ($method === 'GET') {
        if ($action === 'comments' && $resource_id) {
            getCommentsByResourceId($db, $resource_id);
        } elseif ($id) {
            getResourceById($db, $id);
        } else {
            getAllResources($db, $search, $sort, $order);
        }
    } elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createResource($db, $data);
        }
    } elseif ($method === 'PUT') {
        updateResource($db, $data);
    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment' && $comment_id) {
            deleteComment($db, $comment_id);
        } elseif ($id) {
            deleteResource($db, $id);
        } else {
            sendResponse(['success' => false, 'message' => 'Missing ID parameter'], 400);
        }
    } else {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Server error occurred'], 500);
}
