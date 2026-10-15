<?php
// brightness_ajax.php - Handle brightness updates via AJAX
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Check if action is set
if (!isset($_POST['action']) || $_POST['action'] !== 'update_brightness') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit();
}

// Get brightness value
$value = isset($_POST['value']) ? intval($_POST['value']) : 100;

// Update brightness in database
updateGlobalBrightness($value);

// Return success response
echo json_encode([
    'success' => true,
    'brightness' => $value
]);
exit();
?>