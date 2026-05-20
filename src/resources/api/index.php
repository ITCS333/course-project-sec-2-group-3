<?php
/**
 *  Resources API 
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the database connection file (using Task 1's db.php)
require_once '../../api/db.php';

// Get the PDO database connection
$db = getDBConnection();

// Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request body for POST and PUT requests
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Parse query parameters from $_GET
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$resource_id = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;
$comment_id = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'desc';

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

/**
 * Get all resources with search and sort
 */
function getAllResources($db, $search, $sort, $order) {
    // Initialize the base SQL query
    $sql = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];
    
    // Check if search parameter exists
    if ($search && !empty($search)) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Validate the sort parameter
    $allowedSort = ['title', 'created_at'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'created_at';
    }
    
    // Validate the order parameter
    $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
    
    // Add ORDER BY clause
    $sql .= " ORDER BY $sort $order";
    
    // Prepare and execute
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    // Fetch all results
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    sendResponse(['success' => true, 'data' => $resources], 200);
}

/**
 * Get a single resource by ID
 */
function getResourceById($db, $resourceId) {
    // Validate that $resourceId is provided and is numeric
    if (!$resourceId || $resourceId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
    
    // Prepare SQL query
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id = ?");
    
    // Bind and execute
    $stmt->execute([$resourceId]);
    
    // Fetch result
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If found, return success, else 404
    if ($resource) {
        sendResponse(['success' => true, 'data' => $resource], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
}

/**
 * Create a new resource
 */
function createResource($db, $data) {
    // Validate required fields
    if (empty($data['title']) || empty($data['link'])) {
        sendResponse(['success' => false, 'message' => 'Title and link are required'], 400);
    }
    
    // Sanitize input
    $title = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link = trim($data['link']);
    
    // Validate the link
    if (!validateUrl($link)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL format'], 400);
    }
    
    // Default description to empty string if not provided
    if (empty($description)) {
        $description = '';
    }
    
    // Prepare INSERT query
    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    
    // Bind and execute
    $success = $stmt->execute([$title, $description, $link]);
    
    // Return response
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully',
            'id' => $db->lastInsertId()
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create resource'], 500);
    }
}

/**
 * Update an existing resource
 */
function updateResource($db, $data) {
    // Validate that id is provided
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID is required'], 400);
    }
    
    // Check if resource exists
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['id']]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
    
    // Build UPDATE query dynamically
    $updates = [];
    $params = [];
    
    if (isset($data['title'])) {
        $updates[] = "title = ?";
        $params[] = sanitizeInput($data['title']);
    }
    
    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = sanitizeInput($data['description']);
    }
    
    if (isset($data['link'])) {
        // Validate link if provided
        if (!validateUrl($data['link'])) {
            sendResponse(['success' => false, 'message' => 'Invalid URL format'], 400);
        }
        $updates[] = "link = ?";
        $params[] = trim($data['link']);
    }
    
    // If no fields to update
    if (empty($updates)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }
    
    // Add ID to params
    $params[] = $data['id'];
    
    // Build final SQL
    $sql = "UPDATE resources SET " . implode(", ", $updates) . " WHERE id = ?";
    
    // Prepare and execute
    $stmt = $db->prepare($sql);
    $success = $stmt->execute($params);
    
    // Return response
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Resource updated successfully'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update resource'], 500);
    }
}

/**
 * Delete a resource
 */
function deleteResource($db, $resourceId) {
    // Validate that $resourceId is provided and is numeric
    if (!$resourceId || $resourceId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
    
    // Check if resource exists
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$resourceId]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
    
    // Prepare DELETE query
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    
    // Bind and execute
    $success = $stmt->execute([$resourceId]);
    
    // Return response (comments auto-deleted by CASCADE)
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Resource deleted successfully'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete resource'], 500);
    }
}

// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

/**
 * Get all comments for a specific resource
 */
function getCommentsByResourceId($db, $resourceId) {
    // Validate that $resourceId is provided and is numeric
    if (!$resourceId || $resourceId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
    
    // Prepare SQL query
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC");
    
    // Bind and execute
    $stmt->execute([$resourceId]);
    
    // Fetch all results
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success (empty array if no comments)
    sendResponse(['success' => true, 'data' => $comments], 200);
}

/**
 * Create a new comment
 */
function createComment($db, $data) {
    // Validate required fields
    if (empty($data['resource_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID, author, and comment text are required'], 400);
    }
    
    // Validate that resource_id is numeric
    if (!is_numeric($data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
    
    // Check that resource exists
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['resource_id']]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
    
    // Sanitize input
    $resource_id = (int)$data['resource_id'];
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // Prepare INSERT query
    $stmt = $db->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
    
    // Bind and execute
    $success = $stmt->execute([$resource_id, $author, $text]);
    
    // Return response
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Comment added successfully',
            'id' => $db->lastInsertId()
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to add comment'], 500);
    }
}

/**
 * Delete a comment
 */
function deleteComment($db, $commentId) {
    // Validate that $commentId is provided and is numeric
    if (!$commentId || $commentId <= 0) {
        sendResponse(['success' => false, 'message' => 'Invalid comment ID'], 400);
    }
    
    // Check if comment exists
    $check = $db->prepare("SELECT id FROM comments_resource WHERE id = ?");
    $check->execute([$commentId]);
    if (!$check->fetch()) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
    }
    
    // Prepare DELETE query
    $stmt = $db->prepare("DELETE FROM comments_resource WHERE id = ?");
    
    // Bind and execute
    $success = $stmt->execute([$commentId]);
    
    // Return response
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete comment'], 500);
    }
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // Route the request based on $method and $action
    if ($method === 'GET') {
        // If action === 'comments', return all comments for a resource
        if ($action === 'comments' && $resource_id) {
            getCommentsByResourceId($db, $resource_id);
        }
        // If 'id' is present, return a single resource
        elseif ($id) {
            getResourceById($db, $id);
        }
        // Otherwise, return all resources
        else {
            getAllResources($db, $search, $sort, $order);
        }
    } 
    elseif ($method === 'POST') {
        // If action === 'comment', create a new comment
        if ($action === 'comment') {
            createComment($db, $data);
        }
        // Otherwise, create a new resource
        else {
            createResource($db, $data);
        }
    } 
    elseif ($method === 'PUT') {
        // Update an existing resource
        updateResource($db, $data);
    } 
    elseif ($method === 'DELETE') {
        // If action === 'delete_comment', delete a comment
        if ($action === 'delete_comment' && $comment_id) {
            deleteComment($db, $comment_id);
        }
        // Otherwise, delete a resource
        elseif ($id) {
            deleteResource($db, $id);
        }
        else {
            sendResponse(['success' => false, 'message' => 'Missing ID parameter'], 400);
        }
    } 
    else {
        // Return HTTP 405 for unsupported methods
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} 
catch (PDOException $e) {
    // Log the error
    error_log("Database error: " . $e->getMessage());
    // Return generic error (don't expose details)
    sendResponse(['success' => false, 'message' => 'Database error occurred'], 500);
} 
catch (Exception $e) {
    // Log the error
    error_log("General error: " . $e->getMessage());
    // Return HTTP 500
    sendResponse(['success' => false, 'message' => 'Server error occurred'], 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send a JSON response and stop execution
 */
function sendResponse($data, $statusCode = 200) {
    // Set HTTP status code
    http_response_code($statusCode);
    // Ensure data is an array
    if (!is_array($data)) {
        $data = ['success' => false, 'message' => $data];
    }
    // Echo JSON and exit
    echo json_encode($data);
    exit();
}

/**
 * Validate a URL string
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Sanitize a single input string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Check that all required fields exist and are non-empty
 */
function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return ['valid' => count($missing) === 0, 'missing' => $missing];
}
?>
