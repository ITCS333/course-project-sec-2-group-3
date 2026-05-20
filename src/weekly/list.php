<?php
header('Content-Type: application/json');
require_once 'helpers.php';

try {
    $weeks = getWeeksList();
    echo json_encode(['success' => true, 'data' => $weeks]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
